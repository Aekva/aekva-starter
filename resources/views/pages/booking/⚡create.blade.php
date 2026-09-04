<?php

use App\Models\Availability;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use App\Mail\ReservationConfirmed;
use App\Mail\NewReservationNotification;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Mail;

new #[Layout('layouts::booking')] class extends Component
{
    public int $step = 1;

    public ?int $selectedServiceId = null;
    public ?int $selectedResourceId = null;
    public ?string $selectedDate = null;
    public ?string $selectedSlot = null;

    public array $availableSlots = [];

    public string $customerName = '';
    public string $customerEmail = '';
    public string $customerPhone = '';
    public string $customerNotes = '';
    public ?int $createdReservationId = null;


    public function mount(): void
    {
        if (auth()->check()) {
            $this->customerName = auth()->user()->name ?? '';
            $this->customerEmail = auth()->user()->email ?? '';
        }
    }

    #[Computed]
    public function services()
    {
        return Service::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function resources()
    {
        if (! $this->selectedServiceId) {
            return collect();
        }

        return Resource::query()
            ->where('is_active', true)
            ->whereHas('services', function ($query) {
                $query->where('services.id', $this->selectedServiceId)
                    ->where('resource_service.is_active', true);
            })
            ->orderBy('name')
            ->get();
    }

    #[Computed]
public function selectedService()
{
    if (! $this->selectedServiceId) {
        return null;
    }

    return Service::find($this->selectedServiceId);
}

#[Computed]
public function selectedResource()
{
    if (! $this->selectedResourceId) {
        return null;
    }

    return Resource::find($this->selectedResourceId);
}


    protected function buildSlots(): array
    {
        if (
            ! $this->selectedServiceId ||
            ! $this->selectedResourceId ||
            ! $this->selectedDate
        ) {
            return [];
        }

        $service = Service::find($this->selectedServiceId);
        $resource = Resource::find($this->selectedResourceId);

        if (! $service || ! $resource) {
            return [];
        }

        $date = Carbon::parse($this->selectedDate);
        $dateString = $date->format('Y-m-d');

        /*
         * Règles concernant cette ressource / prestation.
         *
         * Une règle avec resource_id/service_id à NULL
         * peut servir de règle générale.
         */
        $baseQuery = Availability::query()
            ->where(function ($query) {
                $query
                    ->whereNull('resource_id')
                    ->orWhere('resource_id', $this->selectedResourceId);
            })
            ->where(function ($query) {
                $query
                    ->whereNull('service_id')
                    ->orWhere('service_id', $this->selectedServiceId);
            });

        /*
         * Une règle sur une date précise est prioritaire.
         * Exemple : jour férié, fermeture exceptionnelle,
         * horaires exceptionnels...
         */
        $specificRules = (clone $baseQuery)
            ->whereDate('specific_date', $dateString)
            ->get();

        if ($specificRules->isNotEmpty()) {
            $availabilities = $specificRules
                ->where('is_available', true);
        } else {
            /*
             * Sinon on utilise les horaires hebdomadaires.
             *
             * Carbon : 0 = dimanche, 1 = lundi ... 6 = samedi.
             */
            $availabilities = (clone $baseQuery)
                ->whereNull('specific_date')
                ->where('day_of_week', $date->dayOfWeek)
                ->where('is_available', true)
                ->where(function ($query) use ($dateString) {
                    $query
                        ->whereNull('valid_from')
                        ->orWhereDate('valid_from', '<=', $dateString);
                })
                ->where(function ($query) use ($dateString) {
                    $query
                        ->whereNull('valid_until')
                        ->orWhereDate('valid_until', '>=', $dateString);
                })
                ->get();
        }

        if ($availabilities->isEmpty()) {
            return [];
        }

        $slots = collect();

        /*
         * Pour le starter, les départs de créneaux sont espacés
         * de 30 minutes.
         *
         * On rendra cette valeur configurable ensuite.
         */
        $slotInterval = 30;

        foreach ($availabilities as $availability) {

            $windowStart = Carbon::parse(
                $dateString . ' ' . $availability->start_time
            );

            $windowEnd = Carbon::parse(
                $dateString . ' ' . $availability->end_time
            );

            $cursor = $windowStart->copy();

            /*
             * Capacité applicable à ce créneau.
             */
            $capacity =
                $availability->capacity
                ?? $resource->capacity
                ?? $service->capacity
                ?? 1;

            while (true) {

                $slotStart = $cursor->copy();

                $slotEnd = $slotStart
                    ->copy()
                    ->addMinutes($service->duration_minutes);

                /*
                 * La prestation doit finir avant la fermeture.
                 */
                if ($slotEnd->gt($windowEnd)) {
                    break;
                }

                /*
                 * On n'affiche pas les horaires déjà passés.
                 */
                if ($slotStart->isFuture()) {

$reservedQuantity = Reservation::query()
    ->where('resource_id', $resource->id)

    ->whereDate(
        'starts_at',
        $dateString
    )

    ->whereNotIn('status', [
        'cancelled',
        'declined',
    ])

    ->where(
        'starts_at',
        '<',
        $slotEnd
    )

    ->where(
        'ends_at',
        '>',
        $slotStart
    )

    ->sum('quantity');

                    /*
                     * Il reste au moins une place disponible.
                     */
                    if ($reservedQuantity < $capacity) {
                        $slots->push(
                            $slotStart->format('H:i')
                        );
                    }
                }

                $cursor->addMinutes($slotInterval);
            }
        }

        return $slots
        ->unique()
        ->sort()
        ->values()
        ->all();
    }

    public function selectService(int $serviceId): void
    {
        $service = Service::query()
            ->whereKey($serviceId)
            ->where('is_active', true)
            ->firstOrFail();

        $this->selectedServiceId = $service->id;

        $this->selectedResourceId = null;
        $this->selectedDate = null;
        $this->selectedSlot = null;
    }

    public function nextStep(): void
    {
        if (! $this->selectedServiceId) {
            return;
        }

        $this->step = 2;
    }

    public function selectResource(int $resourceId): void
    {
        $resource = $this->resources
            ->firstWhere('id', $resourceId);

        if (! $resource) {
            return;
        }

        $this->selectedResourceId = $resource->id;

        $this->selectedDate = null;
        $this->selectedSlot = null;
    }

    public function goToDateStep(): void
    {
        if (! $this->selectedResourceId) {
            return;
        }

        $this->step = 3;
    }

    public function goToSlotsStep(): void
    {
        if (! $this->selectedDate) {
            return;
        }
    
        $this->selectedSlot = null;
    
        $this->availableSlots = $this->buildSlots();
    
        $this->step = 4;
    }

    public function selectSlot(string $slot): void
    {
        if (! in_array($slot, $this->availableSlots, true)) {            return;
        }

        $this->selectedSlot = $slot;
    }
    public function goToDetailsStep(): void
{
    if (! $this->selectedSlot) {
        return;
    }

    $this->step = 5;
}

public function goToReviewStep(): void
{
    $this->validate([
        'customerName' => ['required', 'string', 'min:2', 'max:100'],
        'customerEmail' => ['required', 'email', 'max:255'],
        'customerPhone' => ['nullable', 'string', 'max:30'],
        'customerNotes' => ['nullable', 'string', 'max:1000'],
    ]);

    $this->step = 6;
}

public function confirmReservation(): void
{
    $this->validate([
        'customerName' => ['required', 'string', 'min:2', 'max:100'],
        'customerEmail' => ['required', 'email', 'max:255'],
        'customerPhone' => ['nullable', 'string', 'max:30'],
        'customerNotes' => ['nullable', 'string', 'max:1000'],
    ]);

    if (
        ! $this->selectedServiceId ||
        ! $this->selectedResourceId ||
        ! $this->selectedDate ||
        ! $this->selectedSlot
    ) {
        return;
    }

    $this->createdReservationId = null;


    /*
    |--------------------------------------------------------------------------
    | Création de la réservation
    |--------------------------------------------------------------------------
    */

    DB::transaction(function () {

        $service = Service::query()
            ->whereKey($this->selectedServiceId)
            ->where('is_active', true)
            ->firstOrFail();

        $resource = Resource::query()
            ->whereKey($this->selectedResourceId)
            ->where('is_active', true)
            ->whereHas('services', function ($query) use ($service) {
        $query
            ->where('services.id', $service->id)
            ->where('resource_service.is_active', true);
            })
            ->lockForUpdate()
            ->firstOrFail();


        /*
         * On recalcule les créneaux au moment exact
         * où le client confirme.
         */

        $availableSlots = $this->buildSlots();

        if (! in_array($this->selectedSlot, $availableSlots, true)) {

            $this->availableSlots = $availableSlots;
            $this->step = 4;

            $this->addError(
                'selectedSlot',
                'Ce créneau n’est plus disponible. Veuillez en choisir un autre.'
            );

            return;
        }


        $startsAt = Carbon::parse(
            $this->selectedDate . ' ' . $this->selectedSlot
        );

        $endsAt = $startsAt
            ->copy()
            ->addMinutes($service->duration_minutes);


        $reservation = Reservation::create([
            'user_id' => auth()->id(),

            'service_id' => $service->id,
            'resource_id' => $resource->id,

            'starts_at' => $startsAt,
            'ends_at' => $endsAt,

            'quantity' => 1,

            'customer_name' => $this->customerName,
            'customer_email' => $this->customerEmail,
            'customer_phone' => $this->customerPhone ?: null,

            'total_price' => $service->price,

            'status' => 'confirmed',

            'notes' => $this->customerNotes ?: null,
        ]);


        $this->createdReservationId = $reservation->id;
    });


    /*
    |--------------------------------------------------------------------------
    | Si aucune réservation n'a été créée
    |--------------------------------------------------------------------------
    */

    if (! $this->createdReservationId) {
        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Email de confirmation
    |--------------------------------------------------------------------------
    |
    | L'email est envoyé APRÈS la transaction.
    | Une erreur d'envoi ne doit jamais annuler la réservation.
    |
    */

    try {

        $reservation = Reservation::query()
            ->with([
                'service',
                'resource',
            ])
            ->findOrFail($this->createdReservationId);


        $settings = SiteSetting::first();


        Mail::to($reservation->customer_email)->send(
            new ReservationConfirmed(
                reservation: $reservation,

                businessName:
                    $settings?->business_name
                    ?? 'Votre établissement',

                businessPhone:
                    $settings?->phone,

                businessEmail:
                    $settings?->email,

                businessAddress:
                    $settings?->address,

                primaryColor:
                    $settings?->primary_color ?? '#18181b',
                    
                logoPath:
                    $settings?->logo_path,
            
            )
        );

    } catch (\Throwable $exception) {

        /*
         * On garde une trace de l'erreur,
         * mais on ne casse pas le parcours client.
         */

        report($exception);
    }

    /*
|--------------------------------------------------------------------------
| Notification envoyée au professionnel
|--------------------------------------------------------------------------
|
| Cette adresse est distincte de l'adresse publique
| affichée sur le site.
|
| Une erreur d'envoi ne doit jamais empêcher
| la confirmation de la réservation.
|
*/

    try {

        $reservation = Reservation::query()
            ->with([
            'service',
            'resource',
        ])
            ->findOrFail($this->createdReservationId);


        $settings = SiteSetting::first();


    if ($settings?->notification_email) {

        Mail::to($settings->notification_email)->send(
            new NewReservationNotification(
                reservation: $reservation,

                businessName:
                    $settings->business_name
                    ?? 'Votre établissement',

                primaryColor:
                    $settings->primary_color
                    ?? '#18181b',

                logoPath:
                    $settings->logo_path,
            )
        );

    }

}   catch (\Throwable $exception) {

    /*
     * Même principe que pour l'email client :
     * on journalise l'erreur sans casser
     * la réservation.
     */

    report($exception);
}


    /*
    |--------------------------------------------------------------------------
    | Confirmation affichée au client
    |--------------------------------------------------------------------------
    */

    $this->step = 7;
}
};
?>

<div>

    @if ($step === 1)

        <div class="min-h-screen bg-zinc-50 px-6 py-12">
            <div class="mx-auto max-w-4xl">

                <div class="mb-10">
                    <p class="mb-2 text-sm font-medium uppercase tracking-widest text-zinc-500">
                        Réservation
                    </p>

                    <h1 class="text-3xl font-semibold text-zinc-900">
                        Choisissez une prestation
                    </h1>

                    <p class="mt-2 text-zinc-600">
                        Sélectionnez la prestation que vous souhaitez réserver.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">

                    @forelse ($this->services as $service)

                        <button
                            type="button"
                            wire:key="service-{{ $service->id }}"
                            wire:click="selectService({{ $service->id }})"
                            class="rounded-2xl border p-6 text-left transition
                                {{ $selectedServiceId === $service->id
                                    ? 'border-zinc-900 bg-zinc-900 text-white'
                                    : 'border-zinc-200 bg-white text-zinc-900 hover:border-zinc-400'
                                }}"
                        >
                            <div class="flex items-start justify-between gap-6">

                                <div>
                                    <h2 class="text-lg font-semibold">
                                        {{ $service->name }}
                                    </h2>

                                    @if ($service->description)
                                        <p class="mt-2 text-sm opacity-70">
                                            {{ $service->description }}
                                        </p>
                                    @endif
                                </div>

                                @if ($service->price !== null)
                                    <span class="whitespace-nowrap font-medium">
                                        {{ number_format((float) $service->price, 2, ',', ' ') }} €
                                    </span>
                                @endif

                            </div>

                            <div class="mt-5 text-sm opacity-70">
                                {{ $service->duration_minutes }} min
                            </div>
                        </button>

                    @empty

                        <div class="rounded-2xl border border-zinc-200 bg-white p-8 md:col-span-2">
                            <p class="text-zinc-600">
                                Aucune prestation n'est disponible pour le moment.
                            </p>
                        </div>

                    @endforelse

                </div>

                @if ($selectedServiceId)
                    <div class="mt-8 flex justify-end">
                        <button
                            type="button"
                            wire:click="nextStep"
                            class="rounded-xl bg-zinc-900 px-6 py-3 font-medium text-white"
                        >
                            Continuer
                        </button>
                    </div>
                @endif

            </div>
        </div>

    @endif


    @if ($step === 2)

        <div class="min-h-screen bg-zinc-50 px-6 py-12">
            <div class="mx-auto max-w-4xl">

                <button
                    type="button"
                    wire:click="$set('step', 1)"
                    class="mb-8 text-sm text-zinc-500 hover:text-zinc-900"
                >
                    ← Retour
                </button>

                <div class="mb-10">
                    <p class="mb-2 text-sm font-medium uppercase tracking-widest text-zinc-500">
                        Réservation
                    </p>

                    <h1 class="text-3xl font-semibold text-zinc-900">
                        Choisissez une ressource
                    </h1>

                    <p class="mt-2 text-zinc-600">
                        Sélectionnez la personne ou la ressource souhaitée.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2">

                    @forelse ($this->resources as $resource)

                        <button
                            type="button"
                            wire:key="resource-{{ $resource->id }}"
                            wire:click="selectResource({{ $resource->id }})"
                            class="rounded-2xl border p-6 text-left transition
                                {{ $selectedResourceId === $resource->id
                                    ? 'border-zinc-900 bg-zinc-900 text-white'
                                    : 'border-zinc-200 bg-white text-zinc-900 hover:border-zinc-400'
                                }}"
                        >
                            <h2 class="text-lg font-semibold">
                                {{ $resource->name }}
                            </h2>

                            @if ($resource->description)
                                <p class="mt-2 text-sm opacity-70">
                                    {{ $resource->description }}
                                </p>
                            @endif

                            @if ($resource->type)
                                <p class="mt-4 text-sm opacity-60">
                                    {{ $resource->type }}
                                </p>
                            @endif

                        </button>

                    @empty

                        <div class="rounded-2xl border border-zinc-200 bg-white p-8 md:col-span-2">
                            <p class="text-zinc-600">
                                Aucune ressource disponible.
                            </p>
                        </div>

                    @endforelse

                </div>

                @if ($selectedResourceId)
                    <div class="mt-8 flex justify-end">
                    <button
    type="button"
    wire:click="goToDateStep"
    class="rounded-xl bg-zinc-900 px-6 py-3 font-medium text-white"
>
    Continuer
</button>
                    </div>
                @endif

            </div>
        </div>

    @endif
    @if ($step === 3)

<div class="min-h-screen bg-zinc-50 px-6 py-12">
    <div class="mx-auto max-w-4xl">

        <button
            type="button"
            wire:click="$set('step', 2)"
            class="mb-8 text-sm text-zinc-500 hover:text-zinc-900"
        >
            ← Retour
        </button>

        <div class="mb-10">
            <p class="mb-2 text-sm font-medium uppercase tracking-widest text-zinc-500">
                Réservation
            </p>

            <h1 class="text-3xl font-semibold text-zinc-900">
                Choisissez une date
            </h1>

            <p class="mt-2 text-zinc-600">
                Sélectionnez le jour de votre réservation.
            </p>
        </div>

        <div class="rounded-2xl border border-zinc-200 bg-white p-6">

            <label
                for="booking-date"
                class="mb-3 block text-sm font-medium text-zinc-700"
            >
                Date
            </label>

            <input
                id="booking-date"
                type="date"
                wire:model.live="selectedDate"
                min="{{ now()->format('Y-m-d') }}"
                class="w-full rounded-xl border border-zinc-300 bg-white px-4 py-3 text-zinc-900"
            >

        </div>

        @if ($selectedDate)
            <div class="mt-8 flex justify-end">
            <button
                type="button"
                wire:click="goToSlotsStep"
                class="rounded-xl bg-zinc-900 px-6 py-3 font-medium text-white">
                
                Voir les créneaux
            </button>
            </div>
        @endif

    </div>
</div>

@endif

@if ($step === 4)

<div class="min-h-screen bg-zinc-50 px-6 py-12">
    <div class="mx-auto max-w-4xl">

        <button
            type="button"
            wire:click="$set('step', 3)"
            class="mb-8 text-sm text-zinc-500 hover:text-zinc-900"
        >
            ← Retour
        </button>

        <div class="mb-10">

            <p class="mb-2 text-sm font-medium uppercase tracking-widest text-zinc-500">
                Réservation
            </p>

            <h1 class="text-3xl font-semibold text-zinc-900">
                Choisissez un créneau
            </h1>

            <p class="mt-2 text-zinc-600">
                Créneaux disponibles pour le
                {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
            </p>

        </div>


            @if (count($availableSlots) === 0)

            <div class="rounded-2xl border border-zinc-200 bg-white p-8">

                <p class="font-medium text-zinc-900">
                    Aucun créneau disponible.
                </p>

                <p class="mt-2 text-sm text-zinc-500">
                    Essayez une autre date.
                </p>

            </div>

        @else

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">

            @foreach ($availableSlots as $slot)

                    <button
                        type="button"
                        wire:key="slot-{{ $slot }}"
                        wire:click="selectSlot('{{ $slot }}')"
                        class="rounded-xl border px-4 py-4 text-center font-medium transition
                            {{ $selectedSlot === $slot
                                ? 'border-zinc-900 bg-zinc-900 text-white'
                                : 'border-zinc-200 bg-white text-zinc-900 hover:border-zinc-400'
                            }}"
                    >
                        {{ $slot }}
                    </button>

                @endforeach

            </div>

            @error('selectedSlot')
    <p class="mt-4 text-sm text-red-600">
        {{ $message }}
    </p>
@enderror

            @if ($selectedSlot)

                <div class="mt-8 flex justify-end">

                    <button
                        type="button"
                        wire:click="goToDetailsStep"
                        class="rounded-xl bg-zinc-900 px-6 py-3 font-medium text-white"
                    >
                        Continuer
                    </button>

                </div>

            @endif

        @endif

    </div>
</div>

@endif

@if ($step === 5)

    <div class="min-h-screen bg-zinc-50 px-6 py-12">
        <div class="mx-auto max-w-4xl">

            <button
                type="button"
                wire:click="$set('step', 4)"
                class="mb-8 text-sm text-zinc-500 hover:text-zinc-900"
            >
                ← Retour
            </button>

            <div class="mb-10">
                <p class="mb-2 text-sm font-medium uppercase tracking-widest text-zinc-500">
                    Réservation
                </p>

                <h1 class="text-3xl font-semibold text-zinc-900">
                    Vos coordonnées
                </h1>

                <p class="mt-2 text-zinc-600">
                    Renseignez vos informations pour finaliser la réservation.
                </p>
            </div>

            <div class="space-y-5 rounded-2xl border border-zinc-200 bg-white p-6">

                <div>
                    <label
                        for="customer-name"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Nom *
                    </label>

                    <input
                        id="customer-name"
                        type="text"
                        wire:model="customerName"
                        placeholder="Votre nom"
                        class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-zinc-900"
                    >

                    @error('customerName')
                    <p class="mt-2 text-sm text-red-600">
                    {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>
                    <label
                        for="customer-email"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Email *
                    </label>

                    <input
                        id="customer-email"
                        type="email"
                        wire:model="customerEmail"
                        placeholder="email@exemple.com"
                        class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-zinc-900"
                    >

                    @error('customerEmail')
                    <p class="mt-2 text-sm text-red-600">
                    {{ $message }}
                    </p>
                    @enderror

                </div>

                <div>
                    <label
                        for="customer-phone"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Téléphone
                    </label>

                    <input
                        id="customer-phone"
                        type="tel"
                        wire:model="customerPhone"
                        placeholder="06 00 00 00 00"
                        class="w-full rounded-xl border border-zinc-300 px-4 py-3 text-zinc-900"
                    >
                </div>

                <div>
                    <label
                        for="customer-notes"
                        class="mb-2 block text-sm font-medium text-zinc-700"
                    >
                        Note
                    </label>

                    <textarea
                        id="customer-notes"
                        wire:model="customerNotes"
                        rows="4"
                        placeholder="Informations complémentaires..."
                        class="w-full resize-none rounded-xl border border-zinc-300 px-4 py-3 text-zinc-900"
                    ></textarea>
                </div>

            </div>

            <div class="mt-8 flex justify-end">

                <button
                    type="button"
                    wire:click="goToReviewStep"
                    class="rounded-xl bg-zinc-900 px-6 py-3 font-medium text-white"
                >
                    Continuer
                </button>

            </div>

        </div>
    </div>

@endif

@if ($step === 6)

    <div class="min-h-screen bg-zinc-50 px-6 py-12">
        <div class="mx-auto max-w-4xl">

            <button
                type="button"
                wire:click="$set('step', 5)"
                class="mb-8 text-sm text-zinc-500 hover:text-zinc-900"
            >
                ← Retour
            </button>

            <div class="mb-10">
                <p class="mb-2 text-sm font-medium uppercase tracking-widest text-zinc-500">
                    Réservation
                </p>

                <h1 class="text-3xl font-semibold text-zinc-900">
                    Vérifiez votre réservation
                </h1>

                <p class="mt-2 text-zinc-600">
                    Vérifiez les informations avant de confirmer.
                </p>
            </div>

            <div class="grid gap-6 md:grid-cols-2">

                <div class="rounded-2xl border border-zinc-200 bg-white p-6">

                <div class="mb-5 flex items-center justify-between gap-4">

<h2 class="text-lg font-semibold text-zinc-900">
    Votre réservation
</h2>

<button
    type="button"
    wire:click="$set('step', 1)"
    class="text-sm font-medium text-zinc-500 underline underline-offset-4 hover:text-zinc-900"
>
    Modifier
</button>

</div>

                    <div class="space-y-4">

                        <div>
                            <p class="text-sm text-zinc-500">
                                Prestation
                            </p>

                            <p class="font-medium text-zinc-900">
                                {{ $this->selectedService?->name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-zinc-500">
                                Ressource
                            </p>

                            <p class="font-medium text-zinc-900">
                                {{ $this->selectedResource?->name }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-zinc-500">
                                Date
                            </p>

                            <p class="font-medium text-zinc-900">
                                {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-zinc-500">
                                Heure
                            </p>

                            <p class="font-medium text-zinc-900">
                                {{ $selectedSlot }}
                            </p>
                        </div>

                        @if ($this->selectedService?->price !== null)

                            <div>
                                <p class="text-sm text-zinc-500">
                                    Prix
                                </p>

                                <p class="font-medium text-zinc-900">
                                    {{ number_format(
                                        (float) $this->selectedService->price,
                                        2,
                                        ',',
                                        ' '
                                    ) }} €
                                </p>
                            </div>

                        @endif

                    </div>

                </div>


                <div class="rounded-2xl border border-zinc-200 bg-white p-6">

                <div class="mb-5 flex items-center justify-between gap-4">

<h2 class="text-lg font-semibold text-zinc-900">
    Vos coordonnées
</h2>

<button
    type="button"
    wire:click="$set('step', 5)"
    class="text-sm font-medium text-zinc-500 underline underline-offset-4 hover:text-zinc-900"
>
    Modifier
</button>

</div>

                    <div class="space-y-4">

                        <div>
                            <p class="text-sm text-zinc-500">
                                Nom
                            </p>

                            <p class="font-medium text-zinc-900">
                                {{ $customerName }}
                            </p>
                        </div>

                        <div>
                            <p class="text-sm text-zinc-500">
                                Email
                            </p>

                            <p class="font-medium text-zinc-900">
                                {{ $customerEmail }}
                            </p>
                        </div>

                        @if ($customerPhone)

                            <div>
                                <p class="text-sm text-zinc-500">
                                    Téléphone
                                </p>

                                <p class="font-medium text-zinc-900">
                                    {{ $customerPhone }}
                                </p>
                            </div>

                        @endif

                        @if ($customerNotes)

                            <div>
                                <p class="text-sm text-zinc-500">
                                    Note
                                </p>

                                <p class="font-medium text-zinc-900">
                                    {{ $customerNotes }}
                                </p>
                            </div>

                        @endif

                    </div>

                </div>

            </div>


            <div class="mt-8 flex justify-end">

            <button
    type="button"
    wire:click="confirmReservation"
    wire:loading.attr="disabled"
    wire:target="confirmReservation"
    class="rounded-xl bg-zinc-900 px-6 py-3 font-medium text-white transition
           disabled:cursor-not-allowed disabled:opacity-60"
>
    <span wire:loading.remove wire:target="confirmReservation">
        Confirmer la réservation
    </span>

    <span wire:loading wire:target="confirmReservation">
        Confirmation en cours...
    </span>
</button>

            </div>

        </div>
    </div>

@endif

@if ($step === 7)

    <div class="min-h-screen bg-zinc-50 px-6 py-12">
        <div class="mx-auto max-w-2xl">

            <div class="rounded-2xl border border-zinc-200 bg-white p-8">

                <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-zinc-900 text-xl text-white">
                    ✓
                </div>

                <p class="mb-2 text-sm font-medium uppercase tracking-widest text-zinc-500">
                    Réservation
                </p>

                <h1 class="text-3xl font-semibold text-zinc-900">
                    Réservation confirmée
                </h1>

                <p class="mt-3 text-zinc-600">
                    Votre réservation a bien été enregistrée.
                </p>

                <div class="mt-8 space-y-4 border-t border-zinc-200 pt-6">

                    <div>
                        <p class="text-sm text-zinc-500">
                            Date
                        </p>

                        <p class="font-medium text-zinc-900">
                            {{ \Carbon\Carbon::parse($selectedDate)->format('d/m/Y') }}
                            à {{ $selectedSlot }}
                        </p>
                    </div>

                    <div>
                        <p class="text-sm text-zinc-500">
                            Numéro de réservation
                        </p>

                        <p class="font-medium text-zinc-900">
                            #{{ $createdReservationId }}
                        </p>
                    </div>

                </div>
                <div class="mt-8 border-t border-zinc-200 pt-6">

<a
    href="{{ url('/') }}"
    class="block w-full rounded-xl bg-zinc-900 px-6 py-3 text-center font-medium text-white transition hover:bg-zinc-800"
>
    Retour à l’accueil
</a>

</div>
            </div>

        </div>
    </div>

@endif

</div>