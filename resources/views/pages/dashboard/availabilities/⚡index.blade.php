<?php

use App\Models\Availability;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

new class extends Component
{
    /*
    |--------------------------------------------------------------------------
    | Planning hebdomadaire
    |--------------------------------------------------------------------------
    */

    public array $schedule = [];


    /*
    |--------------------------------------------------------------------------
    | Exceptions ponctuelles
    |--------------------------------------------------------------------------
    */

    public array $exceptions = [];

    public bool $showExceptionForm = false;

    public string $exceptionDate = '';

    public bool $exceptionClosed = true;

    public array $exceptionWindows = [];


    /*
    |--------------------------------------------------------------------------
    | Périodes de fermeture
    |--------------------------------------------------------------------------
    */

    public array $closurePeriods = [];

    public bool $showClosurePeriodForm = false;

    public string $closureStartDate = '';

    public string $closureEndDate = '';


    /*
    |--------------------------------------------------------------------------
    | Initialisation
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->schedule = [
            1 => [
                'label' => 'Lundi',
                'enabled' => false,
                'windows' => [],
            ],

            2 => [
                'label' => 'Mardi',
                'enabled' => false,
                'windows' => [],
            ],

            3 => [
                'label' => 'Mercredi',
                'enabled' => false,
                'windows' => [],
            ],

            4 => [
                'label' => 'Jeudi',
                'enabled' => false,
                'windows' => [],
            ],

            5 => [
                'label' => 'Vendredi',
                'enabled' => false,
                'windows' => [],
            ],

            6 => [
                'label' => 'Samedi',
                'enabled' => false,
                'windows' => [],
            ],

            0 => [
                'label' => 'Dimanche',
                'enabled' => false,
                'windows' => [],
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | Chargement des horaires hebdomadaires
        |--------------------------------------------------------------------------
        */

        $availabilities = Availability::query()
            ->whereNull('resource_id')
            ->whereNull('service_id')
            ->whereNull('specific_date')
            ->where('is_available', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');


        foreach ($availabilities as $day => $rules) {

            $day = (int) $day;

            if (! isset($this->schedule[$day])) {
                continue;
            }

            $this->schedule[$day]['enabled'] = true;

            $this->schedule[$day]['windows'] = $rules
                ->map(fn ($availability) => [
                    'start' => substr(
                        (string) $availability->start_time,
                        0,
                        5
                    ),

                    'end' => substr(
                        (string) $availability->end_time,
                        0,
                        5
                    ),
                ])
                ->values()
                ->all();
        }


        /*
         * Chargement des exceptions et périodes.
         */
        $this->loadExceptions();
    }


    /*
    |--------------------------------------------------------------------------
    | Chargement centralisé des exceptions
    |--------------------------------------------------------------------------
    */

    protected function loadExceptions(): void
    {
        $rules = Availability::query()
            ->whereNull('resource_id')
            ->whereNull('service_id')
            ->whereNotNull('specific_date')
            ->orderBy('specific_date')
            ->orderBy('start_time')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | Périodes de fermeture
        |--------------------------------------------------------------------------
        */

        $periodRules = $rules->filter(
            fn ($availability) =>
                ($availability->metadata['type'] ?? null)
                === 'closure_period'
        );


        $this->closurePeriods = $periodRules
            ->groupBy(function ($availability) {

                $start =
                    $availability->metadata['period_start']
                    ?? $availability->specific_date->format('Y-m-d');

                $end =
                    $availability->metadata['period_end']
                    ?? $availability->specific_date->format('Y-m-d');

                return $start . '|' . $end;
            })
            ->map(function ($period) {

                $first = $period->first();

                return [
                    'start' =>
                        $first->metadata['period_start']
                        ?? $first->specific_date->format('Y-m-d'),
                
                    'end' =>
                        $first->metadata['period_end']
                        ?? $first->specific_date->format('Y-m-d'),
                
                    'ids' => $period
                        ->pluck('id')
                        ->map(fn ($id) => (int) $id)
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('start')
            ->values()
            ->all();


        /*
        |--------------------------------------------------------------------------
        | Exceptions ponctuelles
        |--------------------------------------------------------------------------
        */

        $individualRules = $rules->reject(
            fn ($availability) =>
                ($availability->metadata['type'] ?? null)
                === 'closure_period'
        );


        $this->exceptions = $individualRules
            ->groupBy(
                fn ($availability) =>
                    $availability->specific_date->format('Y-m-d')
            )
            ->map(function ($rules, $date) {

                $availableRules = $rules
                    ->where('is_available', true);

                return [
                    'date' => $date,

                    'closed' => $availableRules->isEmpty(),

                    'windows' => $availableRules
                        ->map(fn ($availability) => [
                            'start' => substr(
                                (string) $availability->start_time,
                                0,
                                5
                            ),

                            'end' => substr(
                                (string) $availability->end_time,
                                0,
                                5
                            ),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('date')
            ->values()
            ->all();
    }


    /*
    |--------------------------------------------------------------------------
    | Planning hebdomadaire
    |--------------------------------------------------------------------------
    */

    public function addWindow(int $day): void
    {
        $this->schedule[$day]['enabled'] = true;

        $this->schedule[$day]['windows'][] = [
            'start' => '09:00',
            'end' => '18:00',
        ];
    }


    public function removeWindow(int $day, int $index): void
    {
        unset(
            $this->schedule[$day]['windows'][$index]
        );

        $this->schedule[$day]['windows'] = array_values(
            $this->schedule[$day]['windows']
        );

        if (
            count($this->schedule[$day]['windows']) === 0
        ) {
            $this->schedule[$day]['enabled'] = false;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Formulaire d'exception
    |--------------------------------------------------------------------------
    */

    public function openExceptionForm(): void
    {
        $this->exceptionDate = '';

        $this->exceptionClosed = true;

        $this->exceptionWindows = [];

        $this->showExceptionForm = true;

        $this->showClosurePeriodForm = false;

        $this->resetValidation();
    }


    public function closeExceptionForm(): void
    {
        $this->showExceptionForm = false;

        $this->resetValidation();
    }


    public function addExceptionWindow(): void
    {
        $this->exceptionClosed = false;

        $this->exceptionWindows[] = [
            'start' => '09:00',
            'end' => '18:00',
        ];
    }


    public function removeExceptionWindow(int $index): void
    {
        unset(
            $this->exceptionWindows[$index]
        );

        $this->exceptionWindows = array_values(
            $this->exceptionWindows
        );

        if (
            count($this->exceptionWindows) === 0
        ) {
            $this->exceptionClosed = true;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Sauvegarde d'une exception ponctuelle
    |--------------------------------------------------------------------------
    */

    public function saveException(): void
    {
        $this->resetErrorBag();

        $this->validate([
            'exceptionDate' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ]);


        /*
         * Empêche de créer une exception ponctuelle au milieu
         * d'une période de fermeture existante.
         */
        $periodRule = Availability::query()
            ->whereNull('resource_id')
            ->whereNull('service_id')
            ->whereDate(
                'specific_date',
                $this->exceptionDate
            )
            ->get()
            ->first(
                fn ($availability) =>
                    ($availability->metadata['type'] ?? null)
                    === 'closure_period'
            );


        if ($periodRule) {

            $this->addError(
                'exceptionDate',
                'Cette date appartient déjà à une période de fermeture. Supprimez d’abord cette période.'
            );

            return;
        }


        /*
         * Validation des horaires exceptionnels.
         */
        if (! $this->exceptionClosed) {

            if (
                count($this->exceptionWindows) === 0
            ) {

                $this->addError(
                    'exceptionWindows',
                    'Ajoutez au moins une plage horaire.'
                );

                return;
            }


            $windows = collect(
                $this->exceptionWindows
            )
                ->sortBy('start')
                ->values();


            foreach (
                $windows as $index => $window
            ) {

                if (
                    empty($window['start']) ||
                    empty($window['end'])
                ) {

                    $this->addError(
                        "exceptionWindows.$index",
                        'Les heures de début et de fin sont obligatoires.'
                    );

                    continue;
                }


                if (
                    $window['start'] >=
                    $window['end']
                ) {

                    $this->addError(
                        "exceptionWindows.$index",
                        'L’heure de fin doit être après l’heure de début.'
                    );
                }


                if ($index > 0) {

                    $previous =
                        $windows[$index - 1];

                    if (
                        $window['start'] <
                        $previous['end']
                    ) {

                        $this->addError(
                            "exceptionWindows.$index",
                            'Les plages horaires ne peuvent pas se chevaucher.'
                        );
                    }
                }
            }
        }


        if (
            $this->getErrorBag()->isNotEmpty()
        ) {
            return;
        }


        DB::transaction(function () {

            /*
             * Remplace une éventuelle exception existante
             * sur cette date.
             */
            Availability::query()
                ->whereNull('resource_id')
                ->whereNull('service_id')
                ->whereDate(
                    'specific_date',
                    $this->exceptionDate
                )
                ->delete();


            /*
            |--------------------------------------------------------------------------
            | Fermeture toute la journée
            |--------------------------------------------------------------------------
            */

            if ($this->exceptionClosed) {

                Availability::create([
                    'resource_id' => null,

                    'service_id' => null,

                    'day_of_week' =>
                        Carbon::parse(
                            $this->exceptionDate
                        )->dayOfWeek,

                    'specific_date' =>
                        $this->exceptionDate,

                    'start_time' => '00:00',

                    'end_time' => '23:59',

                    'is_available' => false,

                    'valid_from' => null,

                    'valid_until' => null,

                    'capacity' => null,

                    'metadata' => [
                        'type' =>
                            'single_exception',
                    ],
                ]);

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Horaires exceptionnels
            |--------------------------------------------------------------------------
            */

            foreach (
                $this->exceptionWindows
                as $window
            ) {

                Availability::create([
                    'resource_id' => null,

                    'service_id' => null,

                    'day_of_week' =>
                        Carbon::parse(
                            $this->exceptionDate
                        )->dayOfWeek,

                    'specific_date' =>
                        $this->exceptionDate,

                    'start_time' =>
                        $window['start'],

                    'end_time' =>
                        $window['end'],

                    'is_available' => true,

                    'valid_from' => null,

                    'valid_until' => null,

                    'capacity' => null,

                    'metadata' => [
                        'type' =>
                            'single_exception',
                    ],
                ]);
            }
        });


        $this->loadExceptions();


        $this->exceptionDate = '';

        $this->exceptionClosed = true;

        $this->exceptionWindows = [];

        $this->showExceptionForm = false;


        session()->flash(
            'exception_saved',
            'L’exception a été enregistrée.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Suppression d'une exception ponctuelle
    |--------------------------------------------------------------------------
    */

    public function deleteException(
        string $date
    ): void {

        Availability::query()
            ->whereNull('resource_id')
            ->whereNull('service_id')
            ->whereDate(
                'specific_date',
                $date
            )
            ->get()
            ->filter(
                fn ($availability) =>
                    ($availability->metadata['type'] ?? null)
                    !== 'closure_period'
            )
            ->each(
                fn ($availability) =>
                    $availability->delete()
            );


        $this->loadExceptions();


        session()->flash(
            'exception_saved',
            'L’exception a été supprimée.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Formulaire de période de fermeture
    |--------------------------------------------------------------------------
    */

    public function openClosurePeriodForm(): void
    {
        $this->closureStartDate = '';

        $this->closureEndDate = '';

        $this->showClosurePeriodForm = true;

        $this->showExceptionForm = false;

        $this->resetValidation();
    }


    public function closeClosurePeriodForm(): void
    {
        $this->showClosurePeriodForm = false;

        $this->resetValidation();
    }


    /*
    |--------------------------------------------------------------------------
    | Enregistrement d'une période de fermeture
    |--------------------------------------------------------------------------
    */

    public function saveClosurePeriod(): void
    {
        $this->resetErrorBag();


        $this->validate([
            'closureStartDate' => [
                'required',
                'date',
                'after_or_equal:today',
            ],

            'closureEndDate' => [
                'required',
                'date',
                'after_or_equal:closureStartDate',
            ],
        ]);


        $start = Carbon::parse(
            $this->closureStartDate
        )->startOfDay();


        $end = Carbon::parse(
            $this->closureEndDate
        )->startOfDay();


        /*
         * Sécurité :
         * évite une période absurde de plusieurs années.
         */
        if (
            $start->diffInDays($end) > 365
        ) {

            $this->addError(
                'closureEndDate',
                'La période ne peut pas dépasser 365 jours.'
            );

            return;
        }


        DB::transaction(
            function () use ($start, $end) {

                $date = $start->copy();


                while ($date->lte($end)) {

                    $dateString =
                        $date->format('Y-m-d');


                    /*
                     * La fermeture de période devient prioritaire.
                     *
                     * Elle remplace donc toute exception générale
                     * déjà présente sur la date.
                     */
                    Availability::query()
                        ->whereNull('resource_id')
                        ->whereNull('service_id')
                        ->whereDate(
                            'specific_date',
                            $dateString
                        )
                        ->delete();


                    Availability::create([
                        'resource_id' => null,

                        'service_id' => null,

                        'day_of_week' =>
                            $date->dayOfWeek,

                        'specific_date' =>
                            $dateString,

                        'start_time' =>
                            '00:00',

                        'end_time' =>
                            '23:59',

                        'is_available' =>
                            false,

                        'valid_from' => null,

                        'valid_until' => null,

                        'capacity' => null,

                        'metadata' => [
                            'type' =>
                                'closure_period',

                            'period_start' =>
                                $start->format(
                                    'Y-m-d'
                                ),

                            'period_end' =>
                                $end->format(
                                    'Y-m-d'
                                ),
                        ],
                    ]);


                    $date->addDay();
                }
            }
        );


        $this->loadExceptions();


        $this->closureStartDate = '';

        $this->closureEndDate = '';

        $this->showClosurePeriodForm =
            false;


        session()->flash(
            'exception_saved',
            'La période de fermeture a été enregistrée.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Suppression d'une période
    |--------------------------------------------------------------------------
    */

    public function deleteClosurePeriod(int $index): void
    {
        if (! isset($this->closurePeriods[$index])) {
            return;
        }
    
        $ids = $this->closurePeriods[$index]['ids'] ?? [];
    
        if (count($ids) === 0) {
            return;
        }
    
        Availability::query()
            ->whereIn('id', $ids)
            ->delete();
    
        $this->loadExceptions();
    
        session()->flash(
            'exception_saved',
            'La période de fermeture a été supprimée.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Sauvegarde du planning hebdomadaire
    |--------------------------------------------------------------------------
    */

    public function saveSchedule(): void
    {
        $this->resetErrorBag();


        foreach (
            $this->schedule
            as $day => $config
        ) {

            if (! $config['enabled']) {
                continue;
            }


            if (
                count($config['windows']) === 0
            ) {

                $this->addError(
                    "schedule.$day.windows",
                    'Ajoutez au moins une plage horaire.'
                );

                continue;
            }


            $windows = collect(
                $config['windows']
            )
                ->sortBy('start')
                ->values();


            foreach (
                $windows as $index => $window
            ) {

                if (
                    empty($window['start']) ||
                    empty($window['end'])
                ) {

                    $this->addError(
                        "schedule.$day.windows.$index",
                        'Les heures de début et de fin sont obligatoires.'
                    );

                    continue;
                }


                if (
                    $window['start'] >=
                    $window['end']
                ) {

                    $this->addError(
                        "schedule.$day.windows.$index",
                        'L’heure de fin doit être après l’heure de début.'
                    );
                }


                if ($index > 0) {

                    $previous =
                        $windows[$index - 1];


                    if (
                        $window['start'] <
                        $previous['end']
                    ) {

                        $this->addError(
                            "schedule.$day.windows.$index",
                            'Les plages horaires ne peuvent pas se chevaucher.'
                        );
                    }
                }
            }
        }


        if (
            $this->getErrorBag()->isNotEmpty()
        ) {
            return;
        }


        DB::transaction(function () {

            /*
             * On ne supprime que les horaires
             * hebdomadaires généraux.
             *
             * Les exceptions restent intactes.
             */
            Availability::query()
                ->whereNull('resource_id')
                ->whereNull('service_id')
                ->whereNull('specific_date')
                ->delete();


            foreach (
                $this->schedule
                as $day => $config
            ) {

                if (! $config['enabled']) {
                    continue;
                }


                foreach (
                    $config['windows']
                    as $window
                ) {

                    Availability::create([
                        'resource_id' =>
                            null,

                        'service_id' =>
                            null,

                        'day_of_week' =>
                            (int) $day,

                        'specific_date' =>
                            null,

                        'start_time' =>
                            $window['start'],

                        'end_time' =>
                            $window['end'],

                        'is_available' =>
                            true,

                        'valid_from' =>
                            null,

                        'valid_until' =>
                            null,

                        'capacity' =>
                            null,
                    ]);
                }
            }
        });


        session()->flash(
            'availability_saved',
            'Les disponibilités ont été enregistrées.'
        );
    }
};

?>


<div class="p-6 lg:p-10">

    <div class="mx-auto max-w-5xl">


        {{-- En-tête --}}

        <div class="mb-8">

            <p class="text-sm font-medium uppercase tracking-widest text-zinc-500">
                Dashboard
            </p>

            <h1 class="mt-2 text-3xl font-semibold">
                Disponibilités
            </h1>

            <p class="mt-2 text-zinc-500">
                Définissez vos horaires habituels et vos périodes d'indisponibilité.
            </p>

        </div>


        {{-- Confirmation horaires --}}

        @if (session('availability_saved'))

            <div class="mb-6 rounded-xl border border-green-700 bg-green-950/30 p-4 text-sm text-green-400">

                {{ session('availability_saved') }}

            </div>

        @endif


        {{-- Planning hebdomadaire --}}

        <div class="space-y-4">

            @foreach ($schedule as $day => $config)

                <div
                    wire:key="availability-day-{{ $day }}"
                    class="rounded-2xl border border-zinc-700 p-5"
                >

                    <div class="flex items-center justify-between gap-6">

                        <div>

                            <h2 class="text-lg font-semibold">
                                {{ $config['label'] }}
                            </h2>

                            <p class="mt-1 text-sm text-zinc-400">

                                {{
                                    $config['enabled']
                                        ? 'Ouvert'
                                        : 'Fermé'
                                }}

                            </p>

                        </div>


                        <label class="flex cursor-pointer items-center gap-3">

                            <span class="text-sm text-zinc-400">
                                Ouvert
                            </span>

                            <input
                                type="checkbox"
                                wire:model.live="schedule.{{ $day }}.enabled"
                                class="h-5 w-5 rounded border-zinc-600 bg-zinc-800"
                            >

                        </label>

                    </div>


                    @if ($config['enabled'])

                        <div class="mt-5 space-y-3">

                            @foreach (
                                $config['windows']
                                as $index => $window
                            )

                                <div
                                    wire:key="window-{{ $day }}-{{ $index }}"
                                    class="flex flex-wrap items-center gap-3"
                                >

                                    <input
                                        type="time"
                                        wire:model="schedule.{{ $day }}.windows.{{ $index }}.start"
                                        class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                                    >

                                    <span class="text-zinc-500">
                                        →
                                    </span>

                                    <input
                                        type="time"
                                        wire:model="schedule.{{ $day }}.windows.{{ $index }}.end"
                                        class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                                    >

                                    <button
                                        type="button"
                                        wire:click="removeWindow({{ $day }}, {{ $index }})"
                                        class="rounded-xl border border-zinc-700 px-4 py-3 text-sm text-zinc-400 hover:bg-zinc-800 hover:text-white"
                                    >
                                        Supprimer
                                    </button>

                                </div>


                                @error("schedule.$day.windows.$index")

                                    <p class="text-sm text-red-500">
                                        {{ $message }}
                                    </p>

                                @enderror

                            @endforeach


                            @error("schedule.$day.windows")

                                <p class="text-sm text-red-500">
                                    {{ $message }}
                                </p>

                            @enderror


                            <button
                                type="button"
                                wire:click="addWindow({{ $day }})"
                                class="mt-2 rounded-xl border border-zinc-700 px-4 py-2 text-sm font-medium hover:bg-zinc-800"
                            >
                                + Ajouter une plage
                            </button>

                        </div>

                    @endif

                </div>

            @endforeach

        </div>


        <div class="mt-8 flex justify-end">

            <button
                type="button"
                wire:click="saveSchedule"
                wire:loading.attr="disabled"
                class="rounded-xl bg-white px-6 py-3 font-medium text-zinc-900 disabled:opacity-50"
            >
                Enregistrer les horaires
            </button>

        </div>


        {{-- Exceptions / fermetures --}}

        <div class="mt-12 border-t border-zinc-700 pt-10">

            <div class="flex flex-wrap items-start justify-between gap-6">

                <div>

                    <p class="text-sm font-medium uppercase tracking-widest text-zinc-500">
                        Planning
                    </p>

                    <h2 class="mt-2 text-2xl font-semibold">
                        Exceptions & absences
                    </h2>

                    <p class="mt-2 text-zinc-500">
                        Gérez les fermetures, horaires exceptionnels et périodes d'absence.
                    </p>

                </div>


                <div class="flex flex-wrap gap-3">

                    <button
                        type="button"
                        wire:click="openExceptionForm"
                        class="rounded-xl border border-zinc-700 px-4 py-3 text-sm font-medium hover:bg-zinc-800"
                    >
                        + Exception
                    </button>

                    <button
                        type="button"
                        wire:click="openClosurePeriodForm"
                        class="rounded-xl bg-white px-4 py-3 text-sm font-medium text-zinc-900"
                    >
                        + Période de fermeture
                    </button>

                </div>

            </div>


            @if (session('exception_saved'))

                <div class="mt-6 rounded-xl border border-green-700 bg-green-950/30 p-4 text-sm text-green-400">

                    {{ session('exception_saved') }}

                </div>

            @endif


            {{-- Formulaire exception ponctuelle --}}

            @if ($showExceptionForm)

                <div class="mt-6 rounded-2xl border border-zinc-700 p-6">

                    <div class="mb-6 flex items-center justify-between">

                        <h3 class="text-lg font-semibold">
                            Nouvelle exception
                        </h3>

                        <button
                            type="button"
                            wire:click="closeExceptionForm"
                            class="text-sm text-zinc-400 hover:text-white"
                        >
                            Fermer
                        </button>

                    </div>


                    <div class="space-y-6">

                        <div>

                            <label class="mb-2 block text-sm font-medium">
                                Date
                            </label>

                            <input
                                type="date"
                                wire:model="exceptionDate"
                                min="{{ now()->format('Y-m-d') }}"
                                class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                            >

                            @error('exceptionDate')

                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        <label class="flex cursor-pointer items-center gap-3">

                            <input
                                type="checkbox"
                                wire:model.live="exceptionClosed"
                                class="h-5 w-5 rounded border-zinc-600 bg-zinc-800"
                            >

                            <span>
                                Fermé toute la journée
                            </span>

                        </label>


                        @if (! $exceptionClosed)

                            <div class="space-y-3">

                                @foreach (
                                    $exceptionWindows
                                    as $index => $window
                                )

                                    <div
                                        wire:key="exception-window-{{ $index }}"
                                        class="flex flex-wrap items-center gap-3"
                                    >

                                        <input
                                            type="time"
                                            wire:model="exceptionWindows.{{ $index }}.start"
                                            class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                                        >

                                        <span class="text-zinc-500">
                                            →
                                        </span>

                                        <input
                                            type="time"
                                            wire:model="exceptionWindows.{{ $index }}.end"
                                            class="rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                                        >

                                        <button
                                            type="button"
                                            wire:click="removeExceptionWindow({{ $index }})"
                                            class="rounded-xl border border-zinc-700 px-4 py-3 text-sm text-zinc-400 hover:bg-zinc-800"
                                        >
                                            Supprimer
                                        </button>

                                    </div>


                                    @error("exceptionWindows.$index")

                                        <p class="text-sm text-red-500">
                                            {{ $message }}
                                        </p>

                                    @enderror

                                @endforeach


                                @error('exceptionWindows')

                                    <p class="text-sm text-red-500">
                                        {{ $message }}
                                    </p>

                                @enderror


                                <button
                                    type="button"
                                    wire:click="addExceptionWindow"
                                    class="rounded-xl border border-zinc-700 px-4 py-2 text-sm font-medium hover:bg-zinc-800"
                                >
                                    + Ajouter une plage
                                </button>

                            </div>

                        @endif


                        <div class="flex justify-end">

                            <button
                                type="button"
                                wire:click="saveException"
                                wire:loading.attr="disabled"
                                class="rounded-xl bg-white px-5 py-3 text-sm font-medium text-zinc-900 disabled:opacity-50"
                            >
                                Enregistrer l’exception
                            </button>

                        </div>

                    </div>

                </div>

            @endif


            {{-- Formulaire période de fermeture --}}

            @if ($showClosurePeriodForm)

                <div class="mt-6 rounded-2xl border border-zinc-700 p-6">

                    <div class="mb-6 flex items-center justify-between">

                        <div>

                            <h3 class="text-lg font-semibold">
                                Nouvelle période de fermeture
                            </h3>

                            <p class="mt-1 text-sm text-zinc-400">
                                Exemple : congés, vacances ou fermeture temporaire.
                            </p>

                        </div>


                        <button
                            type="button"
                            wire:click="closeClosurePeriodForm"
                            class="text-sm text-zinc-400 hover:text-white"
                        >
                            Fermer
                        </button>

                    </div>


                    <div class="grid gap-5 sm:grid-cols-2">

                        <div>

                            <label class="mb-2 block text-sm font-medium">
                                Du
                            </label>

                            <input
                                type="date"
                                wire:model="closureStartDate"
                                min="{{ now()->format('Y-m-d') }}"
                                class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                            >

                            @error('closureStartDate')

                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>


                        <div>

                            <label class="mb-2 block text-sm font-medium">
                                Au
                            </label>

                            <input
                                type="date"
                                wire:model="closureEndDate"
                                min="{{ now()->format('Y-m-d') }}"
                                class="w-full rounded-xl border border-zinc-700 bg-zinc-800 px-4 py-3"
                            >

                            @error('closureEndDate')

                                <p class="mt-2 text-sm text-red-500">
                                    {{ $message }}
                                </p>

                            @enderror

                        </div>

                    </div>


                    <div class="mt-6 flex justify-end">

                        <button
                            type="button"
                            wire:click="saveClosurePeriod"
                            wire:loading.attr="disabled"
                            class="rounded-xl bg-white px-5 py-3 text-sm font-medium text-zinc-900 disabled:opacity-50"
                        >
                            Enregistrer la fermeture
                        </button>

                    </div>

                </div>

            @endif


            {{-- Périodes de fermeture existantes --}}

            @if (count($closurePeriods) > 0)

                <div class="mt-8">

                    <h3 class="mb-4 text-lg font-semibold">
                        Périodes de fermeture
                    </h3>


                    <div class="space-y-3">

                    @foreach ($closurePeriods as $periodIndex => $period)

                            <div
                                wire:key="closure-period-{{ $period['start'] }}-{{ $period['end'] }}"
                                class="flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-zinc-700 p-5"
                            >

                                <div>

                                    <p class="font-semibold">
                                        Du
                                        {{ Carbon::parse($period['start'])->format('d/m/Y') }}
                                        au
                                        {{ Carbon::parse($period['end'])->format('d/m/Y') }}
                                    </p>

                                    <p class="mt-1 text-sm text-zinc-400">
                                        Fermé sur toute la période
                                    </p>

                                </div>


                                <button
                                    type="button"
                                    wire:click="deleteClosurePeriod({{ $periodIndex }})"
                                    wire:confirm="Supprimer cette période de fermeture ?"
                                    class="rounded-xl border border-red-900 px-4 py-2 text-sm text-red-400 hover:bg-red-950/30"
                                >
                                    Supprimer
                                </button>

                            </div>

                        @endforeach

                    </div>

                </div>

            @endif


            {{-- Exceptions ponctuelles existantes --}}

            <div class="mt-8">

                <h3 class="mb-4 text-lg font-semibold">
                    Exceptions ponctuelles
                </h3>


                <div class="space-y-3">

                    @forelse (
                        $exceptions
                        as $exception
                    )

                        <div
                            wire:key="exception-{{ $exception['date'] }}"
                            class="flex flex-wrap items-center justify-between gap-5 rounded-2xl border border-zinc-700 p-5"
                        >

                            <div>

                                <p class="font-semibold">
                                    {{ Carbon::parse($exception['date'])->format('d/m/Y') }}
                                </p>


                                @if ($exception['closed'])

                                    <p class="mt-1 text-sm text-zinc-400">
                                        Fermé toute la journée
                                    </p>

                                @else

                                    <div class="mt-2 flex flex-wrap gap-2">

                                        @foreach (
                                            $exception['windows']
                                            as $window
                                        )

                                            <span class="rounded-lg bg-zinc-800 px-3 py-1 text-sm">

                                                {{ $window['start'] }}

                                                →

                                                {{ $window['end'] }}

                                            </span>

                                        @endforeach

                                    </div>

                                @endif

                            </div>


                            <button
                                type="button"
                                wire:click="deleteException('{{ $exception['date'] }}')"
                                wire:confirm="Supprimer cette exception ?"
                                class="rounded-xl border border-red-900 px-4 py-2 text-sm text-red-400 hover:bg-red-950/30"
                            >
                                Supprimer
                            </button>

                        </div>

                    @empty

                        <div class="rounded-2xl border border-zinc-700 p-6 text-sm text-zinc-400">
                            Aucune exception ponctuelle programmée.
                        </div>

                    @endforelse

                </div>

            </div>

        </div>

    </div>

</div>