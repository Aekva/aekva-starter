<?php

use App\Models\Service;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public bool $showCreateForm = false;
    public ?int $editingServiceId = null;

    public string $name = '';
    public string $description = '';
    public int $durationMinutes = 30;
    public string $price = '';

    #[Computed]
    public function services()
    {
        return Service::query()
            ->orderBy('name')
            ->get();
    }

    public function openCreateForm(): void
    {
        $this->resetValidation();

        $this->editingServiceId = null;

        $this->name = '';
        $this->description = '';
        $this->durationMinutes = 30;
        $this->price = '';
        $this->showCreateForm = true;
        
    }

    public function closeCreateForm(): void
    {
        $this->showCreateForm = false;

        $this->resetValidation();
    }

    public function createService(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'durationMinutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'price' => ['nullable', 'numeric', 'min:0'],
        ]);

        Service::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?: null,
            'duration_minutes' => (int) $validated['durationMinutes'],
            'price' => $validated['price'] !== ''
                ? (float) $validated['price']
                : null,
            'capacity' => 1,
            'is_active' => true,
        ]);

        $this->showCreateForm = false;

        $this->name = '';
        $this->description = '';
        $this->durationMinutes = 30;
        $this->price = '';
    }

    public function toggleActive(int $serviceId): void
{
    $service = Service::findOrFail($serviceId);

    $service->is_active = ! $service->is_active;
    $service->save();
}

public function editService(int $serviceId): void
{
    $service = Service::findOrFail($serviceId);

    $this->resetValidation();

    $this->editingServiceId = $service->id;
    $this->name = $service->name;
    $this->description = $service->description ?? '';
    $this->durationMinutes = $service->duration_minutes;
    $this->price = $service->price !== null
        ? (string) $service->price
        : '';

    $this->showCreateForm = true;
}

public function updateService(): void
{
    if (! $this->editingServiceId) {
        return;
    }

    $validated = $this->validate([
        'name' => ['required', 'string', 'min:2', 'max:150'],
        'description' => ['nullable', 'string', 'max:1000'],
        'durationMinutes' => ['required', 'integer', 'min:5', 'max:1440'],
        'price' => ['nullable', 'numeric', 'min:0'],
    ]);

    $service = Service::findOrFail($this->editingServiceId);

    $service->update([
        'name' => $validated['name'],
        'description' => $validated['description'] ?: null,
        'duration_minutes' => (int) $validated['durationMinutes'],
        'price' => $validated['price'] !== ''
            ? (float) $validated['price']
            : null,
    ]);

    $this->editingServiceId = null;
    $this->showCreateForm = false;

    $this->name = '';
    $this->description = '';
    $this->durationMinutes = 30;
    $this->price = '';
}

};
?>

<div class="p-6 lg:p-10">

    <div class="mx-auto max-w-6xl">

        <div class="mb-8 flex items-start justify-between gap-6">

            <div>
                <p class="text-sm font-medium uppercase tracking-widest text-zinc-500">
                    Dashboard
                </p>

                <h1 class="mt-2 text-3xl font-semibold">
                    Prestations
                </h1>

                <p class="mt-2 text-zinc-500">
                    Gérez les prestations disponibles à la réservation.
                </p>
            </div>

            <button
    type="button"
    wire:click="openCreateForm"
    class="rounded-xl bg-white px-4 py-3 text-sm font-medium text-zinc-900"
>
    + Nouvelle prestation
</button>

</div>

@if ($showCreateForm)

    <div class="mb-6 rounded-2xl border border-zinc-700 p-6">

        <div class="mb-6 flex items-center justify-between">
            <div>
            <h2 class="text-xl font-semibold">
    {{ $editingServiceId ? 'Modifier la prestation' : 'Nouvelle prestation' }}
</h2>

<p class="mt-1 text-sm text-zinc-400">
    {{ $editingServiceId
        ? 'Modifiez les informations de cette prestation.'
        : 'Ajoutez une prestation disponible à la réservation.'
    }}
</p>
            </div>

            <button
                type="button"
                wire:click="closeCreateForm"
                class="text-sm text-zinc-400 hover:text-white"
            >
                Fermer
            </button>
        </div>

        <div class="space-y-5">

            <div>
                <label class="mb-2 block text-sm font-medium">
                    Nom *
                </label>

                <input
                    type="text"
                    wire:model="name"
                    placeholder="Ex. Coupe homme"
                    class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                >

                @error('name')
                    <p class="mt-2 text-sm text-red-500">
                        {{ $message }}
                    </p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">
                    Description
                </label>

                <textarea
                    wire:model="description"
                    rows="3"
                    placeholder="Description de la prestation..."
                    class="w-full resize-none rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                ></textarea>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">

                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Durée (minutes) *
                    </label>

                    <input
                        type="number"
                        wire:model.number="durationMinutes"                        min="5"
                        step="5"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                    >

                    @error('durationMinutes')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">
                        Prix (€)
                    </label>

                    <input
                        type="number"
                        wire:model="price"
                        min="0"
                        step="0.01"
                        placeholder="25.00"
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                    >

                    @error('price')
                        <p class="mt-2 text-sm text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>

            </div>

            <div class="flex justify-end gap-3">

                <button
                    type="button"
                    wire:click="closeCreateForm"
                    class="rounded-xl border border-zinc-700 px-5 py-3 text-sm font-medium"
                >
                    Annuler
                </button>

                @if ($editingServiceId)

<button
    type="button"
    wire:click="updateService"
    class="rounded-xl bg-white px-5 py-3 text-sm font-medium text-zinc-900"
>
    Enregistrer les modifications
</button>

@else

<button
    type="button"
    wire:click="createService"
    class="rounded-xl bg-white px-5 py-3 text-sm font-medium text-zinc-900"
>
    Créer la prestation
</button>

@endif

            </div>

        </div>

    </div>

@endif

        <div class="overflow-hidden rounded-2xl border border-zinc-700">

            @forelse ($this->services as $service)

                <div
                    wire:key="service-{{ $service->id }}"
                    class="border-b border-zinc-700 p-5 last:border-b-0"
                >

                    <div class="flex items-start justify-between gap-6">

                        <div>

                            <div class="flex items-center gap-3">

                                <h2 class="font-semibold">
                                    {{ $service->name }}
                                </h2>

                                @if ($service->is_active)

                                    <span class="rounded-full bg-zinc-700 px-2 py-1 text-xs">
                                        Active
                                    </span>

                                @else

                                    <span class="rounded-full bg-zinc-800 px-2 py-1 text-xs text-zinc-400">
                                        Inactive
                                    </span>

                                @endif

                            </div>

                            @if ($service->description)

                                <p class="mt-2 text-sm text-zinc-400">
                                    {{ $service->description }}
                                </p>

                            @endif

                            <p class="mt-3 text-sm text-zinc-300">
                                {{ $service->duration_minutes }} min
                            </p>

                        </div>


                        <div class="text-right">

@if ($service->price !== null)

    <p class="font-medium">
        {{ number_format(
            (float) $service->price,
            2,
            ',',
            ' '
        ) }} €
    </p>

@endif

<button
    type="button"
    wire:click="editService({{ $service->id }})"
    class="mt-4 rounded-lg border border-zinc-600 px-3 py-2 text-xs font-medium hover:bg-zinc-700"
>
    Modifier
</button>

<button
    type="button"
    wire:click="toggleActive({{ $service->id }})"
    class="mt-4 rounded-lg border border-zinc-600 px-3 py-2 text-xs font-medium hover:bg-zinc-700"
>
    {{ $service->is_active ? 'Désactiver' : 'Réactiver' }}
</button>

</div>

                    </div>

                </div>

            @empty

                <div class="p-8 text-center text-zinc-400">
                    Aucune prestation pour le moment.
                </div>

            @endforelse

        </div>

    </div>

</div>