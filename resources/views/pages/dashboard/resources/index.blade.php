<?php

use App\Models\Resource;
use App\Models\Service;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public bool $showForm = false;
    public ?int $editingResourceId = null;

    public string $name = '';
    public string $type = '';
    public string $description = '';
    public int $capacity = 1;
    public bool $isActive = true;

    public array $selectedServiceIds = [];


    /*
    |--------------------------------------------------------------------------
    | Ressources
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function resources()
    {
        return Resource::query()
            ->with('services')
            ->orderBy('name')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Prestations
    |--------------------------------------------------------------------------
    */

    #[Computed]
    public function services()
    {
        return Service::query()
            ->orderBy('name')
            ->get();
    }


    /*
    |--------------------------------------------------------------------------
    | Nouvelle ressource
    |--------------------------------------------------------------------------
    */

    public function openCreateResource(): void
    {
        $this->resetForm();

        $this->showForm = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Modifier
    |--------------------------------------------------------------------------
    */

    public function openEditResource(int $resourceId): void
    {
        $resource = Resource::query()
            ->with('services')
            ->findOrFail($resourceId);

        $this->editingResourceId = $resource->id;

        $this->name = $resource->name;
        $this->type = $resource->type ?? '';
        $this->description = $resource->description ?? '';
        $this->capacity = $resource->capacity ?? 1;
        $this->isActive = (bool) $resource->is_active;

        $this->selectedServiceIds = $resource
            ->services
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->showForm = true;
    }


    /*
    |--------------------------------------------------------------------------
    | Enregistrer
    |--------------------------------------------------------------------------
    */

    public function saveResource(): void
    {
        $validated = $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'type' => [
                'nullable',
                'string',
                'max:100',
            ],

            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'capacity' => [
                'required',
                'integer',
                'min:1',
            ],

            'isActive' => [
                'boolean',
            ],

            'selectedServiceIds' => [
                'array',
            ],

            'selectedServiceIds.*' => [
                'integer',
                'exists:services,id',
            ],
        ]);


        if ($this->editingResourceId) {

            $resource = Resource::findOrFail(
                $this->editingResourceId
            );

            $resource->update([
                'name' => $validated['name'],
                'type' => $validated['type'] ?: null,
                'description' => $validated['description'] ?: null,
                'capacity' => $validated['capacity'],
                'is_active' => $validated['isActive'],
            ]);

        } else {

            $resource = Resource::create([
                'name' => $validated['name'],
                'type' => $validated['type'] ?: null,
                'description' => $validated['description'] ?: null,
                'capacity' => $validated['capacity'],
                'is_active' => $validated['isActive'],
            ]);

        }


        /*
         * Association avec les prestations.
         */

        $serviceIds = collect(
            $validated['selectedServiceIds']
        )
            ->map(fn ($id) => (int) $id)
            ->all();


        $resource
            ->services()
            ->syncWithPivotValues(
                $serviceIds,
                [
                    'is_active' => true,
                ]
            );


        $this->resetForm();
    }


    /*
    |--------------------------------------------------------------------------
    | Activer / désactiver
    |--------------------------------------------------------------------------
    */

    public function toggleResource(int $resourceId): void
    {
        $resource = Resource::findOrFail(
            $resourceId
        );

        $resource->update([
            'is_active' => ! $resource->is_active,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Annuler formulaire
    |--------------------------------------------------------------------------
    */

    public function closeForm(): void
    {
        $this->resetForm();
    }


    /*
    |--------------------------------------------------------------------------
    | Reset
    |--------------------------------------------------------------------------
    */

    private function resetForm(): void
    {
        $this->resetValidation();

        $this->showForm = false;
        $this->editingResourceId = null;

        $this->name = '';
        $this->type = '';
        $this->description = '';
        $this->capacity = 1;
        $this->isActive = true;

        $this->selectedServiceIds = [];
    }
};

?>


<div class="p-6 lg:p-10">

    <div class="mx-auto max-w-6xl">


        {{-- En-tête --}}

        <div class="flex flex-col gap-5 sm:flex-row sm:items-end sm:justify-between">

            <div>

                <p class="text-sm font-medium uppercase tracking-widest text-zinc-500">
                    Dashboard
                </p>

                <h1 class="mt-2 text-3xl font-semibold">
                    Équipe & ressources
                </h1>

                <p class="mt-2 max-w-2xl text-zinc-500">
                    Gérez les personnes, salles, équipements ou autres ressources disponibles à la réservation.
                </p>

            </div>


            <button
                type="button"
                wire:click="openCreateResource"
                class="inline-flex items-center justify-center rounded-xl bg-white px-5 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200"
            >
                + Ajouter
            </button>

        </div>



        {{-- Formulaire --}}

        @if ($showForm)

            <form
                wire:submit="saveResource"
                class="mt-8 rounded-2xl border border-zinc-700 p-6"
            >

                <div class="flex items-start justify-between gap-6">

                    <div>

                        <h2 class="text-xl font-semibold">

                            {{ $editingResourceId
                                ? 'Modifier la ressource'
                                : 'Ajouter une ressource'
                            }}

                        </h2>

                        <p class="mt-1 text-sm text-zinc-500">
                            Configurez cette ressource et les prestations associées.
                        </p>

                    </div>

                </div>


                <div class="mt-6 grid gap-5 md:grid-cols-2">


                {{-- Nom --}}

<div>

    <label class="text-sm font-medium">
        Nom
    </label>

    <input
        type="text"
        wire:model="name"
        placeholder="Ex. Julie, Salle 1, Véhicule A..."
        class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none transition focus:border-zinc-500"
    >

    @error('name')
        <p class="mt-2 text-sm text-red-400">
            {{ $message }}
        </p>
    @enderror

</div>



                    {{-- Type --}}

                    <div>

                        <label class="text-sm font-medium">
                            Type
                        </label>

                        <input
                            type="text"
                            wire:model="type"
                            placeholder="Ex. Personne, salle, équipement..."
                            class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none transition focus:border-zinc-500"
                        >

                        @error('type')
                            <p class="mt-2 text-sm text-red-400">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- Capacité --}}

<div>

    <div class="flex items-center gap-2">

        <label class="text-sm font-medium">
            Capacité
        </label>

        <span
            title="Nombre maximal de réservations ou de personnes que cette ressource peut gérer simultanément."
            class="inline-flex size-5 cursor-help items-center justify-center rounded-full border border-zinc-600 text-xs font-semibold text-zinc-400"
        >
            ?
        </span>

    </div>

    <input
        type="number"
        min="1"
        wire:model="capacity"
        class="mt-2 w-full rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none transition focus:border-zinc-500"
    >

    <p class="mt-2 text-xs text-zinc-500">
        Exemple : 1 pour une personne, plusieurs pour une salle ou un espace partagé.
    </p>

    @error('capacity')
        <p class="mt-2 text-sm text-red-400">
            {{ $message }}
        </p>
    @enderror

</div>


                    {{-- État --}}

                    <div>

                        <label class="text-sm font-medium">
                            État
                        </label>

                        <label class="mt-2 flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-700 px-4 py-3">

                            <input
                                type="checkbox"
                                wire:model="isActive"
                                class="size-4 rounded"
                            >

                            <span class="text-sm">
                                Ressource active
                            </span>

                        </label>

                    </div>

                </div>



                {{-- Description --}}

                <div class="mt-5">

                    <label class="text-sm font-medium">
                        Description
                    </label>

                    <textarea
                        wire:model="description"
                        rows="3"
                        placeholder="Description facultative..."
                        class="mt-2 w-full resize-none rounded-xl border border-zinc-700 bg-transparent px-4 py-3 outline-none transition focus:border-zinc-500"
                    ></textarea>

                    @error('description')
                        <p class="mt-2 text-sm text-red-400">
                            {{ $message }}
                        </p>
                    @enderror

                </div>



                {{-- Prestations --}}

                <div class="mt-7 border-t border-zinc-700 pt-6">

                    <h3 class="font-semibold">
                        Prestations proposées
                    </h3>

                    <p class="mt-1 text-sm text-zinc-500">
                        Sélectionnez les prestations pouvant être réalisées avec cette ressource.
                    </p>


                    <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">

                        @forelse ($this->services as $service)

                            <label
                                class="flex cursor-pointer items-center gap-3 rounded-xl border border-zinc-700 p-4 transition hover:border-zinc-600"
                            >

                                <input
                                    type="checkbox"
                                    wire:model="selectedServiceIds"
                                    value="{{ $service->id }}"
                                    class="size-4 rounded"
                                >

                                <div class="min-w-0">

                                    <p class="truncate text-sm font-medium">
                                        {{ $service->name }}
                                    </p>

                                    @if (! $service->is_active)

                                        <p class="mt-1 text-xs text-zinc-500">
                                            Prestation inactive
                                        </p>

                                    @endif

                                </div>

                            </label>

                        @empty

                            <p class="text-sm text-zinc-500">
                                Aucune prestation disponible.
                            </p>

                        @endforelse

                    </div>

                </div>



                {{-- Actions --}}

                <div class="mt-7 flex justify-end gap-3">

                    <button
                        type="button"
                        wire:click="closeForm"
                        class="rounded-xl border border-zinc-700 px-5 py-3 text-sm font-medium transition hover:bg-zinc-800"
                    >
                        Annuler
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-zinc-900 transition hover:bg-zinc-200"
                    >
                        {{ $editingResourceId
                            ? 'Enregistrer'
                            : 'Ajouter'
                        }}
                    </button>

                </div>

            </form>

        @endif



        {{-- Liste --}}

        <div class="mt-8 space-y-4">

            @forelse ($this->resources as $resource)

                <div class="rounded-2xl border border-zinc-700 p-6">

                    <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">


                        {{-- Infos --}}

                        <div class="min-w-0">

                            <div class="flex flex-wrap items-center gap-3">

                                <h2 class="text-lg font-semibold">
                                    {{ $resource->name }}
                                </h2>


                                @if ($resource->is_active)

                                    <span class="rounded-full border border-green-500/20 bg-green-500/10 px-3 py-1 text-xs font-medium text-green-400">
                                        Active
                                    </span>

                                @else

                                    <span class="rounded-full border border-zinc-600 px-3 py-1 text-xs font-medium text-zinc-400">
                                        Inactive
                                    </span>

                                @endif

                            </div>


                            <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-zinc-500">

@if ($resource->type)

    <span>
        {{ ucfirst($resource->type) }}
    </span>

    <span class="text-zinc-700">
        ·
    </span>

@endif

<span class="inline-flex items-center gap-1.5">

    Capacité : {{ $resource->capacity ?? 1 }}

    <span
        x-data="{ open: false }"
        @mouseenter="open = true"
        @mouseleave="open = false"
        class="relative inline-flex"
    >

        <span
            class="inline-flex size-4 cursor-help items-center justify-center rounded-full border border-zinc-600 text-[10px] font-semibold text-zinc-400"
        >
            ?
        </span>

        <span
    x-show="open"
    x-transition.opacity
    style="display: none; width: 280px; background-color: #000000 !important; opacity: 1 !important;"    class="absolute bottom-full left-1/2 z-[9999] mb-3 -translate-x-1/2 whitespace-normal rounded-xl border border-zinc-700 bg-black px-4 py-3 text-left text-xs font-normal leading-relaxed text-zinc-200 shadow-xl"
>
    Nombre maximal de réservations ou de personnes que cette ressource peut gérer simultanément.
</span>

    </span>

</span>

</div>


                            @if ($resource->description)

                                <p class="mt-4 max-w-2xl text-sm text-zinc-400">
                                    {{ $resource->description }}
                                </p>

                            @endif



                            {{-- Prestations associées --}}

                            <div class="mt-5">

                                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">
                                    Prestations
                                </p>


                                <div class="mt-2 flex flex-wrap gap-2">

                                    @forelse ($resource->services as $service)

                                        <span class="rounded-lg border border-zinc-700 px-3 py-1.5 text-xs text-zinc-300">
                                            {{ $service->name }}
                                        </span>

                                    @empty

                                        <span class="text-sm text-zinc-500">
                                            Aucune prestation associée
                                        </span>

                                    @endforelse

                                </div>

                            </div>

                        </div>



                        {{-- Actions --}}

                        <div class="flex shrink-0 items-center gap-2">

                            <button
                                type="button"
                                wire:click="openEditResource({{ $resource->id }})"
                                class="rounded-xl border border-zinc-700 px-4 py-2 text-sm font-medium transition hover:bg-zinc-800"
                            >
                                Modifier
                            </button>


                            <button
                                type="button"
                                wire:click="toggleResource({{ $resource->id }})"
                                class="rounded-xl border border-zinc-700 px-4 py-2 text-sm font-medium text-zinc-400 transition hover:bg-zinc-800 hover:text-white"
                            >

                                {{ $resource->is_active
                                    ? 'Désactiver'
                                    : 'Activer'
                                }}

                            </button>

                        </div>

                    </div>

                </div>


            @empty

                <div class="rounded-2xl border border-dashed border-zinc-700 p-10 text-center">

                    <p class="font-medium">
                        Aucune ressource
                    </p>

                    <p class="mt-2 text-sm text-zinc-500">
                        Ajoutez une personne, une salle, un équipement ou toute autre ressource disponible à la réservation.
                    </p>

                    <button
                        type="button"
                        wire:click="openCreateResource"
                        class="mt-5 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-zinc-900"
                    >
                        + Ajouter une ressource
                    </button>

                </div>

            @endforelse

        </div>

    </div>

</div>