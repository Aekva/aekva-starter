<?php

use App\Models\Availability;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $filter = 'all';

    /*
    |--------------------------------------------------------------------------
    | Modification d'une réservation
    |--------------------------------------------------------------------------
    */

    public ?int $editingReservationId = null;

    public ?int $editServiceId = null;
    public ?int $editResourceId = null;

    public string $editDate = '';

    public ?string $editSlot = null;

    public array $editAvailableSlots = [];


    /*
    |--------------------------------------------------------------------------
    | Réservations
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function reservations()
    {
        $query = Reservation::query()
            ->with(['service', 'resource'])
            ->orderBy('starts_at');

            return match ($this->filter) {

                'upcoming' => $query
                    ->where('ends_at', '>=', now())
                    ->whereNotIn('status', ['cancelled', 'declined'])
                    ->get(),
            
                'past' => $query
                    ->where('ends_at', '<', now())
                    ->whereNotIn('status', ['cancelled', 'declined'])
                    ->get(),
            
                'cancelled' => $query
                    ->where('status', 'cancelled')
                    ->get(),
            
                default => $query->get(),
            };
    }


    /*
    |--------------------------------------------------------------------------
    | Prestations disponibles
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function services()
    {
        return Service::query()
            ->where(function ($query) {

                $query->where('is_active', true);

                /*
                 * On garde la prestation actuelle visible
                 * même si elle a été désactivée entre-temps.
                 */
                if ($this->editServiceId) {
                    $query->orWhere(
                        'id',
                        $this->editServiceId
                    );
                }
            })
            ->orderBy('name')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Ressources disponibles pour la prestation choisie
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function editResources()
    {
        if (! $this->editServiceId) {
            return collect();
        }

        return Resource::query()
            ->where(function ($query) {

                $query->where('is_active', true);

                if ($this->editResourceId) {
                    $query->orWhere(
                        'id',
                        $this->editResourceId
                    );
                }
            })
            ->whereHas(
                'services',
                function ($query) {

                    $query
                        ->where(
                            'services.id',
                            $this->editServiceId
                        )
                        ->where(
                            'resource_service.is_active',
                            true
                        );
                }
            )
            ->orderBy('name')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Changement de statut
    |--------------------------------------------------------------------------
    */

    public function setStatus(
        int $reservationId,
        string $status
    ): void {

        $allowedStatuses = [
            'pending',
            'confirmed',
            'declined',
            'cancelled',
            'completed',
        ];

        if (
            ! in_array(
                $status,
                $allowedStatuses,
                true
            )
        ) {
            return;
        }

        $reservation =
            Reservation::findOrFail(
                $reservationId
            );

        $reservation->status = $status;

        $reservation->save();
    }


    /*
    |--------------------------------------------------------------------------
    | Ouvrir la modification
    |--------------------------------------------------------------------------
    */

    public function openEditReservation(
        int $reservationId
    ): void {

        $reservation =
            Reservation::query()
                ->with([
                    'service',
                    'resource',
                ])
                ->findOrFail(
                    $reservationId
                );


        /*
         * On ne modifie pas une réservation déjà
         * terminée, annulée ou refusée.
         */
        if (
            Carbon::parse($reservation->ends_at)->isPast()
            ||
            ! in_array(
                $reservation->status,
                [
                    'pending',
                    'confirmed',
                    'cancelled',
                ],
                true
            )
        ) {
            return;
        }


        $this->resetValidation();


        $this->editingReservationId =
            $reservation->id;

        $this->editServiceId =
            $reservation->service_id;

        $this->editResourceId =
            $reservation->resource_id;


        $startsAt =
            Carbon::parse(
                $reservation->starts_at
            );


        $this->editDate =
            $startsAt->format('Y-m-d');

        $this->editSlot =
            $startsAt->format('H:i');


        /*
         * Le créneau actuel doit rester disponible
         * car buildEditSlots() ignore cette réservation.
         */
        $this->editAvailableSlots =
            $this->buildEditSlots();
    }


    /*
    |--------------------------------------------------------------------------
    | Fermer la modification
    |--------------------------------------------------------------------------
    */

    public function closeEditReservation(): void
    {
        $this->editingReservationId = null;

        $this->editServiceId = null;

        $this->editResourceId = null;

        $this->editDate = '';

        $this->editSlot = null;

        $this->editAvailableSlots = [];

        $this->resetValidation();
    }


    /*
    |--------------------------------------------------------------------------
    | Réactions aux changements
    |--------------------------------------------------------------------------
    */

    public function updatedEditServiceId(): void
    {
        $this->editResourceId = null;

        $this->editSlot = null;

        $this->editAvailableSlots = [];
    }


    public function updatedEditResourceId(): void
    {
        $this->editSlot = null;

        $this->refreshEditSlots();
    }


    public function updatedEditDate(): void
    {
        $this->editSlot = null;

        $this->refreshEditSlots();
    }


    public function refreshEditSlots(): void
    {
        if (
            ! $this->editServiceId ||
            ! $this->editResourceId ||
            ! $this->editDate
        ) {
            $this->editAvailableSlots = [];

            return;
        }

        $this->editAvailableSlots =
            $this->buildEditSlots();
    }


    /*
    |--------------------------------------------------------------------------
    | Calcul des créneaux disponibles
    |--------------------------------------------------------------------------
    */

    protected function buildEditSlots(): array
    {
        if (
            ! $this->editingReservationId ||
            ! $this->editServiceId ||
            ! $this->editResourceId ||
            ! $this->editDate
        ) {
            return [];
        }


        $service =
            Service::find(
                $this->editServiceId
            );


        $resource =
            Resource::find(
                $this->editResourceId
            );


        $reservation =
            Reservation::find(
                $this->editingReservationId
            );


        if (
            ! $service ||
            ! $resource ||
            ! $reservation
        ) {
            return [];
        }


        /*
         * Vérifie que la ressource peut bien
         * réaliser cette prestation.
         */
        $resourceCanProvideService =
            $resource
                ->services()
                ->where(
                    'services.id',
                    $service->id
                )
                ->where(
                    'resource_service.is_active',
                    true
                )
                ->exists();


        if (! $resourceCanProvideService) {
            return [];
        }


        $date =
            Carbon::parse(
                $this->editDate
            );

        $dateString =
            $date->format('Y-m-d');


        /*
        |--------------------------------------------------------------------------
        | Disponibilités applicables
        |--------------------------------------------------------------------------
        */

        $baseQuery =
            Availability::query()

                ->where(function ($query) {

                    $query
                        ->whereNull('resource_id')
                        ->orWhere(
                            'resource_id',
                            $this->editResourceId
                        );
                })

                ->where(function ($query) {

                    $query
                        ->whereNull('service_id')
                        ->orWhere(
                            'service_id',
                            $this->editServiceId
                        );
                });


        /*
         * Les règles portant sur une date précise
         * ont priorité.
         */
        $specificRules =
            (clone $baseQuery)
                ->whereDate(
                    'specific_date',
                    $dateString
                )
                ->get();


        if ($specificRules->isNotEmpty()) {

            $availabilities =
                $specificRules
                    ->where(
                        'is_available',
                        true
                    );

        } else {

            /*
             * Sinon planning hebdomadaire normal.
             */
            $availabilities =
                (clone $baseQuery)

                    ->whereNull(
                        'specific_date'
                    )

                    ->where(
                        'day_of_week',
                        $date->dayOfWeek
                    )

                    ->where(
                        'is_available',
                        true
                    )

                    ->where(
                        function ($query)
                        use ($dateString) {

                            $query
                                ->whereNull(
                                    'valid_from'
                                )
                                ->orWhereDate(
                                    'valid_from',
                                    '<=',
                                    $dateString
                                );
                        }
                    )

                    ->where(
                        function ($query)
                        use ($dateString) {

                            $query
                                ->whereNull(
                                    'valid_until'
                                )
                                ->orWhereDate(
                                    'valid_until',
                                    '>=',
                                    $dateString
                                );
                        }
                    )

                    ->get();
        }


        if ($availabilities->isEmpty()) {
            return [];
        }


        /*
        |--------------------------------------------------------------------------
        | Génération des créneaux
        |--------------------------------------------------------------------------
        */

        $slots = collect();

        $slotInterval = 30;

        $quantity =
            max(
                1,
                (int) $reservation->quantity
            );


        foreach (
            $availabilities
            as $availability
        ) {

            $windowStart =
                Carbon::parse(
                    $dateString
                    . ' '
                    . $availability->start_time
                );


            $windowEnd =
                Carbon::parse(
                    $dateString
                    . ' '
                    . $availability->end_time
                );


            $cursor =
                $windowStart->copy();


            $capacity =
                max(
                    1,
                    (int) (
                        $availability->capacity
                        ?? $resource->capacity
                        ?? $service->capacity
                        ?? 1
                    )
                );


            while (true) {

                $slotStart =
                    $cursor->copy();


                $slotEnd =
                    $slotStart
                        ->copy()
                        ->addMinutes(
                            $service->duration_minutes
                        );


                /*
                 * La prestation doit se terminer
                 * avant la fin de la disponibilité.
                 */
                if (
                    $slotEnd->gt(
                        $windowEnd
                    )
                ) {
                    break;
                }


                /*
                 * Pas de créneaux passés.
                 */
                if ($slotStart->isFuture()) {

                    /*
                     * IMPORTANT :
                     *
                     * on exclut la réservation actuellement
                     * modifiée pour éviter qu'elle se bloque
                     * elle-même.
                     */
                    $reservedQuantity =
                        Reservation::query()

                            ->where(
                                'resource_id',
                                $resource->id
                            )

                            ->where(
                                'id',
                                '!=',
                                $reservation->id
                            )

                            ->whereNotIn(
                                'status',
                                [
                                    'cancelled',
                                    'declined',
                                ]
                            )

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


                    if (
                        $reservedQuantity
                        + $quantity
                        <= $capacity
                    ) {

                        $slots->push(
                            $slotStart
                                ->format('H:i')
                        );
                    }
                }


                $cursor->addMinutes(
                    $slotInterval
                );
            }
        }


        return $slots
            ->unique()
            ->sort()
            ->values()
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Enregistrer les modifications
    |--------------------------------------------------------------------------
    */

    public function saveEditReservation(): void
    {
        if (! $this->editingReservationId) {
            return;
        }


        $this->resetErrorBag();


        $this->validate([
            'editServiceId' => [
                'required',
                'integer',
            ],

            'editResourceId' => [
                'required',
                'integer',
            ],

            'editDate' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'editSlot' => [
                'required',
                'string',
            ],
        ]);


        $updated = DB::transaction(function (): bool {

            /*
             * Prestation sélectionnée.
             */
            $service = Service::query()
                ->whereKey($this->editServiceId)
                ->where('is_active', true)
                ->firstOrFail();
        
        
            /*
             * On verrouille la ressource pendant toute
             * la vérification du nouveau créneau.
             *
             * Une création ou une autre reprogrammation
             * sur cette même ressource devra attendre.
             */
            $resource = Resource::query()
                ->whereKey($this->editResourceId)
                ->where('is_active', true)
                ->whereHas(
                    'services',
                    function ($query) use ($service) {
                        $query
                            ->where(
                                'services.id',
                                $service->id
                            )
                            ->where(
                                'resource_service.is_active',
                                true
                            );
                    }
                )
                ->lockForUpdate()
                ->firstOrFail();
        
        
            /*
             * On verrouille aussi la réservation
             * actuellement modifiée.
             */
            $reservation = Reservation::query()
                ->whereKey($this->editingReservationId)
                ->lockForUpdate()
                ->firstOrFail();
        
        
                if (
                    Carbon::parse($reservation->ends_at)->isPast()
                    ||
                    ! in_array(
                        $reservation->status,
                        [
                            'pending',
                            'confirmed',
                            'cancelled',
                        ],
                        true
                    )
                ) {
                    return false;
                }
        
        
            /*
             * IMPORTANT :
             * on recalcule les créneaux APRÈS avoir
             * obtenu le verrou.
             */
            $this->editAvailableSlots =
                $this->buildEditSlots();
        
        
            if (
                ! in_array(
                    $this->editSlot,
                    $this->editAvailableSlots,
                    true
                )
            ) {
        
                $this->editSlot = null;
        
                $this->addError(
                    'editSlot',
                    'Ce créneau n’est plus disponible. Choisissez-en un autre.'
                );
        
                return false;
            }
        
        
            $startsAt = Carbon::parse(
                $this->editDate
                . ' '
                . $this->editSlot
            );
        
        
            $endsAt = $startsAt
                ->copy()
                ->addMinutes(
                    $service->duration_minutes
                );
        
        
            $reservation->update([
                'service_id' =>
                    $service->id,
        
                'resource_id' =>
                    $resource->id,
        
                'starts_at' =>
                    $startsAt,
        
                'ends_at' =>
                    $endsAt,
        
                'total_price' =>
                    $service->price,
        
                'status' =>
                    'confirmed',
            ]);
        
        
            return true;
        });
        
        
        /*
         * Si la modification a été refusée,
         * on garde le formulaire ouvert.
         */
        if (! $updated) {
            return;
        }


        $this->closeEditReservation();


        session()->flash(
            'reservation_updated',
            'La réservation a été modifiée.'
        );
    }
};

?>


<div class="p-6 lg:p-10">

    <div class="mx-auto max-w-6xl">


        {{-- En-tête --}}

        <div class="mb-8">

            <p class="text-sm font-medium uppercase tracking-widest text-zinc-500">
                Dashboard
            </p>

            <h1 class="mt-2 text-3xl font-semibold">
                Réservations
            </h1>

            <p class="mt-2 text-zinc-500">
                Retrouvez toutes les réservations enregistrées.
            </p>

        </div>


        {{-- Confirmation modification --}}

        @if (session('reservation_updated'))

            <div class="mb-6 rounded-xl border border-green-700 bg-green-950/30 p-4 text-sm text-green-400">

                {{ session('reservation_updated') }}

            </div>

        @endif


        {{-- Formulaire de modification --}}

        @if ($editingReservationId)

            <div class="mb-8 rounded-2xl border border-zinc-700 p-6">

                <div class="mb-6 flex items-center justify-between gap-6">

                    <div>

                        <h2 class="text-xl font-semibold">
                            Modifier la réservation
                        </h2>

                        <p class="mt-1 text-sm text-zinc-400">
                            Modifiez la prestation, la ressource ou le créneau.
                        </p>

                    </div>


                    <button
                        type="button"
                        wire:click="closeEditReservation"
                        class="text-sm text-zinc-400 hover:text-white"
                    >
                        Fermer
                    </button>

                </div>


                <div class="grid gap-5 md:grid-cols-2">

                    {{-- Prestation --}}

                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            Prestation
                        </label>

                        <select
                            wire:model.live="editServiceId"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                        >

                            <option value="">
                                Choisir une prestation
                            </option>

                            @foreach ($this->services as $service)

                                <option value="{{ $service->id }}">
                                    {{ $service->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('editServiceId')

                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    {{-- Ressource --}}

                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            Ressource
                        </label>

                        <select
                            wire:model.live="editResourceId"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                        >

                            <option value="">
                                Choisir une ressource
                            </option>

                            @foreach ($this->editResources as $resource)

                                <option value="{{ $resource->id }}">
                                    {{ $resource->name }}
                                </option>

                            @endforeach

                        </select>

                        @error('editResourceId')

                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>


                    {{-- Date --}}

                    <div>

                        <label class="mb-2 block text-sm font-medium">
                            Date
                        </label>

                        <input
                            type="date"
                            wire:model.live="editDate"
                            min="{{ now()->format('Y-m-d') }}"
                            class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                        >

                        @error('editDate')

                            <p class="mt-2 text-sm text-red-500">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>

                </div>


                {{-- Créneaux --}}

                @if (
                    $editServiceId
                    && $editResourceId
                    && $editDate
                )

                    <div class="mt-6">

                        <p class="mb-3 text-sm font-medium">
                            Créneau
                        </p>


                        @if (count($editAvailableSlots) > 0)

                            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 md:grid-cols-6">

                                @foreach ($editAvailableSlots as $slot)

                                    <button
                                        type="button"
                                        wire:key="edit-slot-{{ $slot }}"
                                        wire:click="$set('editSlot', '{{ $slot }}')"
                                        class="rounded-xl border px-4 py-3 text-sm font-medium transition
                                            {{
                                                $editSlot === $slot
                                                    ? 'border-white bg-white text-zinc-900'
                                                    : 'border-zinc-700 hover:bg-zinc-800'
                                            }}"
                                    >
                                        {{ $slot }}
                                    </button>

                                @endforeach

                            </div>

                        @else

                            <div class="rounded-xl border border-zinc-700 p-4 text-sm text-zinc-400">
                                Aucun créneau disponible pour cette date.
                            </div>

                        @endif


                        @error('editSlot')

                            <p class="mt-3 text-sm text-red-500">
                                {{ $message }}
                            </p>

                        @enderror

                    </div>

                @endif


                <div class="mt-8 flex justify-end gap-3">

                    <button
                        type="button"
                        wire:click="closeEditReservation"
                        class="rounded-xl border border-zinc-700 px-5 py-3 text-sm font-medium hover:bg-zinc-800"
                    >
                        Annuler
                    </button>


                    <button
                        type="button"
                        wire:click="saveEditReservation"
                        wire:loading.attr="disabled"
                        class="rounded-xl bg-white px-5 py-3 text-sm font-medium text-zinc-900 disabled:opacity-50"
                    >
                        Enregistrer les modifications
                    </button>

                </div>

            </div>

        @endif


        {{-- Filtres --}}

        <div class="mb-6 flex flex-wrap gap-2">

            <button
                type="button"
                wire:click="$set('filter', 'all')"
                class="rounded-lg px-4 py-2 text-sm font-medium transition
                    {{
                        $filter === 'all'
                            ? 'bg-white text-zinc-900'
                            : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                    }}"
            >
                Toutes
            </button>


            <button
                type="button"
                wire:click="$set('filter', 'upcoming')"
                class="rounded-lg px-4 py-2 text-sm font-medium transition
                    {{
                        $filter === 'upcoming'
                            ? 'bg-white text-zinc-900'
                            : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                    }}"
            >
                À venir
            </button>


            <button
                type="button"
                wire:click="$set('filter', 'past')"
                class="rounded-lg px-4 py-2 text-sm font-medium transition
                        {{
                    $filter === 'past'
                    ? 'bg-white text-zinc-900'
                    : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                    }}"
                    >
            Passées
            </button>


            <button
                type="button"
                wire:click="$set('filter', 'cancelled')"
                class="rounded-lg px-4 py-2 text-sm font-medium transition
                    {{
                        $filter === 'cancelled'
                            ? 'bg-white text-zinc-900'
                            : 'bg-zinc-800 text-zinc-300 hover:bg-zinc-700'
                    }}"
            >
                Annulées
            </button>

        </div>


        {{-- Liste --}}

        <div class="overflow-hidden rounded-2xl border border-zinc-700">

            @forelse ($this->reservations as $reservation)

                <div
                    wire:key="reservation-{{ $reservation->id }}"
                    class="border-b border-zinc-700 p-5 last:border-b-0"
                >

                    <div class="flex items-start justify-between gap-6">

                        <div>

                            <p class="font-semibold">
                                {{ $reservation->customer_name }}
                            </p>

                            <p class="mt-1 text-sm text-zinc-400">

                                {{ $reservation->service?->name }}

                                ·

                                {{ $reservation->resource?->name }}

                            </p>

                            <p class="mt-3 text-sm">

                                {{
                                    Carbon::parse(
                                        $reservation->starts_at
                                    )->format(
                                        'd/m/Y à H:i'
                                    )
                                }}

                            </p>

                            <details class="mt-4 max-w-xl">

                            <summary
    class="flex cursor-pointer list-none items-center gap-2 text-sm font-medium text-zinc-400 underline decoration-zinc-600 underline-offset-4 transition hover:text-white hover:decoration-zinc-400"
>
    Informations client

    <span class="text-xs">
        ↓
    </span>
</summary>

    <div class="mt-3 space-y-3 rounded-xl border border-zinc-700 bg-zinc-800/40 p-4">

        {{-- Email --}}

        @if ($reservation->customer_email)

            <div>
                <p class="text-xs uppercase tracking-wider text-zinc-500">
                    Email
                </p>

                <p class="mt-1 text-sm text-zinc-300">
                    {{ $reservation->customer_email }}
                </p>
            </div>

        @endif


        {{-- Téléphone --}}

        @if ($reservation->customer_phone)

            <div>
                <p class="text-xs uppercase tracking-wider text-zinc-500">
                    Téléphone
                </p>

                <p class="mt-1 text-sm text-zinc-300">
                    {{ $reservation->customer_phone }}
                </p>
            </div>

        @endif


        {{-- Commentaire --}}

        @if ($reservation->notes)

            <div>
                <p class="text-xs uppercase tracking-wider text-zinc-500">
                    Commentaire du client
                </p>

                <p class="mt-1 whitespace-pre-line text-sm text-zinc-300">
                    {{ $reservation->notes }}
                </p>
            </div>

        @endif

    </div>

</details>

                            @if ($reservation->notes)

    <div class="mt-4 max-w-xl rounded-xl border border-zinc-700 bg-zinc-800/50 p-4">

        <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">
            Note du client
        </p>

        <p class="mt-2 whitespace-pre-line text-sm text-zinc-300">
            {{ $reservation->notes }}
        </p>

    </div>

@endif

                        </div>


                        <div class="text-right">

{{-- Statut automatique --}}

@php

    $isPast = Carbon::parse($reservation->ends_at)->isPast();

    if ($reservation->status === 'cancelled') {

        $statusLabel = 'Annulée';
        $statusClass = 'bg-zinc-700 text-zinc-400 border-zinc-600';

    } elseif ($reservation->status === 'declined') {

        $statusLabel = 'Refusée';
        $statusClass = 'bg-red-500/10 text-red-400 border-red-500/20';

    } elseif ($isPast) {

        $statusLabel = 'Terminée';
        $statusClass = 'bg-blue-500/10 text-blue-400 border-blue-500/20';

    } else {

        $statusLabel = 'Confirmée';
        $statusClass = 'bg-green-500/10 text-green-400 border-green-500/20';

    }

@endphp


<div class="flex justify-end">

    <span
        class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium {{ $statusClass }}"
    >
        {{ $statusLabel }}
    </span>

</div>


@if ($reservation->total_price !== null)

    <p class="mt-2 text-sm text-zinc-400">

        {{
            number_format(
                (float) $reservation->total_price,
                2,
                ',',
                ' '
            )
        }} €

    </p>

@endif


{{-- Actions --}}

@if (
    ! $isPast
    && in_array(
        $reservation->status,
        ['confirmed', 'pending'],
        true
    )
)

    <div class="mt-4 flex justify-end">

        <details class="relative">

            <summary
                class="flex cursor-pointer list-none items-center justify-center rounded-lg border border-zinc-600 px-3 py-2 text-sm hover:bg-zinc-700"
            >
                •••
            </summary>

            <div
                class="absolute right-full bottom-0 z-20 mr-2 w-40 overflow-hidden rounded-xl border border-zinc-700 bg-zinc-900 shadow-xl"
            >

                <button
                    type="button"
                    wire:click="openEditReservation({{ $reservation->id }})"
                    class="block w-full px-4 py-3 text-left text-sm hover:bg-zinc-800"
                >
                    Modifier
                </button>

                <button
                    type="button"
                    wire:click="setStatus({{ $reservation->id }}, 'cancelled')"
                    wire:confirm="Annuler cette réservation ?"
                    class="block w-full px-4 py-3 text-left text-sm text-red-400 hover:bg-zinc-800"
                >
                    Annuler
                </button>

            </div>

        </details>

    </div>


@elseif ($reservation->status === 'cancelled')

    <div class="mt-4 flex justify-end">

        <details class="relative">

            <summary
                class="flex cursor-pointer list-none items-center justify-center rounded-lg border border-zinc-600 px-3 py-2 text-sm hover:bg-zinc-700"
            >
                •••
            </summary>

            <div
                class="absolute right-full bottom-0 z-20 mr-2 w-40 overflow-hidden rounded-xl border border-zinc-700 bg-zinc-900 shadow-xl"
            >

                <button
                    type="button"
                    wire:click="openEditReservation({{ $reservation->id }})"
                    class="block w-full px-4 py-3 text-left text-sm hover:bg-zinc-800"
                >
                    Reprogrammer
                </button>

            </div>

        </details>

    </div>

@endif

</div>



                    </div>

                </div>


            @empty

                <div class="p-8 text-center text-zinc-400">
                    Aucune réservation pour le moment.
                </div>

            @endforelse

        </div>

    </div>

</div>
