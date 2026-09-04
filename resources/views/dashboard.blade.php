@php

    $todayStart = now()->startOfDay();
    $todayEnd = now()->endOfDay();


    /*
    |--------------------------------------------------------------------------
    | Rendez-vous aujourd'hui
    |--------------------------------------------------------------------------
    */

    $todayReservations = \App\Models\Reservation::query()
        ->with(['service', 'resource'])
        ->whereBetween('starts_at', [
            $todayStart,
            $todayEnd,
        ])
        ->whereNotIn('status', [
            'cancelled',
            'declined',
        ])
        ->orderBy('starts_at')
        ->get();


    /*
    |--------------------------------------------------------------------------
    | Prochains rendez-vous
    |--------------------------------------------------------------------------
    */

    $upcomingReservations = \App\Models\Reservation::query()
        ->with(['service', 'resource'])
        ->where('starts_at', '>=', now())
        ->whereNotIn('status', [
            'cancelled',
            'declined',
        ])
        ->orderBy('starts_at')
        ->limit(5)
        ->get();


    $nextReservation = $upcomingReservations->first();


    /*
    |--------------------------------------------------------------------------
    | Compteurs
    |--------------------------------------------------------------------------
    */

    $upcomingReservationsCount = \App\Models\Reservation::query()
        ->where('starts_at', '>=', now())
        ->whereNotIn('status', [
            'cancelled',
            'declined',
        ])
        ->count();


    $activeServicesCount = \App\Models\Service::query()
        ->where('is_active', true)
        ->count();

@endphp


<x-layouts::app :title="__('Dashboard')">

    <div class="p-6 lg:p-10">

        <div class="mx-auto max-w-7xl">


            {{-- En-tête --}}

            <div class="mb-8">

                <p class="text-sm font-medium uppercase tracking-widest text-zinc-500">
                    Dashboard
                </p>

                <h1 class="mt-2 text-3xl font-semibold">
                    Vue d’ensemble
                </h1>

                <p class="mt-2 text-zinc-500">
                    Retrouvez rapidement votre activité et vos prochains rendez-vous.
                </p>

            </div>


            {{-- Statistiques --}}

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">


                {{-- Aujourd'hui --}}

                <a
                    href="{{ route('dashboard.reservations') }}"
                    class="rounded-2xl border border-zinc-700 p-5 transition hover:bg-zinc-800/50"
                >

                    <p class="text-sm text-zinc-500">
                        Aujourd’hui
                    </p>

                    <div class="mt-3 flex items-end justify-between gap-4">

                        <div>

                            <p class="text-3xl font-semibold">
                                {{ $todayReservations->count() }}
                            </p>

                            <p class="mt-1 text-sm text-zinc-400">
                                rendez-vous
                            </p>

                        </div>

                        <span class="text-zinc-500">
                            →
                        </span>

                    </div>

                </a>


                {{-- À venir --}}

                <a
                    href="{{ route('dashboard.reservations') }}"
                    class="rounded-2xl border border-zinc-700 p-5 transition hover:bg-zinc-800/50"
                >

                    <p class="text-sm text-zinc-500">
                        À venir
                    </p>

                    <div class="mt-3 flex items-end justify-between gap-4">

                        <div>

                            <p class="text-3xl font-semibold">
                                {{ $upcomingReservationsCount }}
                            </p>

                            <p class="mt-1 text-sm text-zinc-400">
                                réservations programmées
                            </p>

                        </div>

                        <span class="text-zinc-500">
                            →
                        </span>

                    </div>

                </a>


                {{-- Prestations --}}

                <a
                    href="{{ route('dashboard.services') }}"
                    class="rounded-2xl border border-zinc-700 p-5 transition hover:bg-zinc-800/50"
                >

                    <p class="text-sm text-zinc-500">
                        Prestations
                    </p>

                    <div class="mt-3 flex items-end justify-between gap-4">

                        <div>

                            <p class="text-3xl font-semibold">
                                {{ $activeServicesCount }}
                            </p>

                            <p class="mt-1 text-sm text-zinc-400">
                                prestations actives
                            </p>

                        </div>

                        <span class="text-zinc-500">
                            →
                        </span>

                    </div>

                </a>


                {{-- Prochain rendez-vous --}}

                <a
                    href="{{ route('dashboard.reservations') }}"
                    class="rounded-2xl border border-zinc-700 p-5 transition hover:bg-zinc-800/50"
                >

                    <p class="text-sm text-zinc-500">
                        Prochain rendez-vous
                    </p>

                    @if ($nextReservation)

                        <p class="mt-3 text-xl font-semibold">

                            {{
                                \Carbon\Carbon::parse(
                                    $nextReservation->starts_at
                                )->format('d/m · H:i')
                            }}

                        </p>

                        <p class="mt-2 font-medium">
                            {{ $nextReservation->customer_name }}
                        </p>

                        <p class="mt-1 truncate text-sm text-zinc-400">

                            {{ $nextReservation->service?->name }}

                            @if ($nextReservation->resource)
                                · {{ $nextReservation->resource->name }}
                            @endif

                        </p>

                    @else

                        <p class="mt-3 text-sm text-zinc-400">
                            Aucun rendez-vous à venir.
                        </p>

                    @endif

                </a>

            </div>


            {{-- Prochains rendez-vous --}}

            <div class="mt-8 overflow-hidden rounded-2xl border border-zinc-700">


                <div class="flex items-center justify-between gap-6 border-b border-zinc-700 p-6">

                    <div>

                        <h2 class="text-xl font-semibold">
                            Prochains rendez-vous
                        </h2>

                        <p class="mt-1 text-sm text-zinc-500">
                            Les prochaines réservations programmées.
                        </p>

                    </div>


                    <a
                        href="{{ route('dashboard.reservations') }}"
                        class="shrink-0 text-sm font-medium text-zinc-400 underline underline-offset-4 hover:text-white"
                    >
                        Voir tout
                    </a>

                </div>


                @forelse ($upcomingReservations as $reservation)

                    @php

                        $startsAt =
                            \Carbon\Carbon::parse(
                                $reservation->starts_at
                            );

                        $endsAt =
                            \Carbon\Carbon::parse(
                                $reservation->ends_at
                            );

                    @endphp


                    <div
                        class="flex flex-col gap-4 border-b border-zinc-700 p-5 last:border-b-0 sm:flex-row sm:items-center sm:justify-between"
                    >

                        <div class="flex min-w-0 items-start gap-5">


                            {{-- Date --}}

                            <div class="w-20 shrink-0">

                                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">
                                    {{ $startsAt->translatedFormat('d M') }}
                                </p>

                                <p class="mt-1 text-xl font-semibold">
                                    {{ $startsAt->format('H:i') }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-500">
                                    → {{ $endsAt->format('H:i') }}
                                </p>

                            </div>


                            {{-- Client --}}

                            <div class="min-w-0">

                                <p class="font-semibold">
                                    {{ $reservation->customer_name }}
                                </p>

                                <p class="mt-1 text-sm text-zinc-400">

                                    {{ $reservation->service?->name }}

                                    @if ($reservation->resource)
                                        · {{ $reservation->resource->name }}
                                    @endif

                                </p>


                                @if ($reservation->customer_phone)

                                    <p class="mt-2 text-xs text-zinc-500">
                                        {{ $reservation->customer_phone }}
                                    </p>

                                @endif

                            </div>

                        </div>


                        <div class="flex items-center gap-4 sm:text-right">

                            @if ($reservation->total_price !== null)

                                <p class="text-sm text-zinc-400">

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


                            <span
                                class="rounded-full border border-green-500/20 bg-green-500/10 px-3 py-1 text-xs font-medium text-green-400"
                            >
                                Confirmée
                            </span>

                        </div>

                    </div>


                @empty

                    <div class="p-8 text-center">

                        <p class="font-medium text-zinc-300">
                            Aucun rendez-vous à venir
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            Les prochaines réservations apparaîtront ici.
                        </p>

                    </div>

                @endforelse

            </div>


            {{-- Rendez-vous du jour --}}

            <div class="mt-6 overflow-hidden rounded-2xl border border-zinc-700">


                <div class="flex items-center justify-between gap-6 border-b border-zinc-700 p-6">

                    <div>

                        <h2 class="text-xl font-semibold">
                            Rendez-vous du jour
                        </h2>

                        <p class="mt-1 text-sm text-zinc-500">
                            Votre planning pour aujourd’hui.
                        </p>

                    </div>

                </div>


                @forelse ($todayReservations as $reservation)

                    @php

                        $reservationStart =
                            \Carbon\Carbon::parse(
                                $reservation->starts_at
                            );

                        $reservationEnd =
                            \Carbon\Carbon::parse(
                                $reservation->ends_at
                            );

                        $isPast =
                            $reservationEnd->isPast();

                    @endphp


                    <div class="flex items-center justify-between gap-6 border-b border-zinc-700 p-5 last:border-b-0">

                        <div class="flex min-w-0 items-center gap-5">

                            <div class="w-16 shrink-0">

                                <p class="text-lg font-semibold">
                                    {{ $reservationStart->format('H:i') }}
                                </p>

                                <p class="mt-1 text-xs text-zinc-500">
                                    {{ $reservationEnd->format('H:i') }}
                                </p>

                            </div>


                            <div class="min-w-0">

                                <p class="truncate font-semibold">
                                    {{ $reservation->customer_name }}
                                </p>

                                <p class="mt-1 truncate text-sm text-zinc-400">

                                    {{ $reservation->service?->name }}

                                    @if ($reservation->resource)
                                        · {{ $reservation->resource->name }}
                                    @endif

                                </p>

                            </div>

                        </div>


                        @if ($isPast)

                            <span class="shrink-0 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1 text-xs font-medium text-blue-400">
                                Terminée
                            </span>

                        @else

                            <span class="shrink-0 rounded-full border border-green-500/20 bg-green-500/10 px-3 py-1 text-xs font-medium text-green-400">
                                Confirmée
                            </span>

                        @endif

                    </div>


                @empty

                    <div class="p-8 text-center">

                        <p class="font-medium text-zinc-300">
                            Aucun rendez-vous aujourd’hui
                        </p>

                        <p class="mt-2 text-sm text-zinc-500">
                            Votre journée est libre pour le moment.
                        </p>

                    </div>

                @endforelse

            </div>


            {{-- Actions rapides compactes --}}

            <div class="mt-6">

                <p class="mb-3 text-sm font-medium text-zinc-500">
                    Accès rapides
                </p>

                <div class="grid gap-3 sm:grid-cols-3">

                    <a
                        href="{{ route('dashboard.reservations') }}"
                        class="flex items-center justify-between rounded-xl border border-zinc-700 px-4 py-3 text-sm font-medium transition hover:bg-zinc-800"
                    >
                        Réservations
                        <span class="text-zinc-500">→</span>
                    </a>

                    <a
                        href="{{ route('dashboard.availabilities') }}"
                        class="flex items-center justify-between rounded-xl border border-zinc-700 px-4 py-3 text-sm font-medium transition hover:bg-zinc-800"
                    >
                        Disponibilités
                        <span class="text-zinc-500">→</span>
                    </a>

                    <a
                        href="{{ route('dashboard.services') }}"
                        class="flex items-center justify-between rounded-xl border border-zinc-700 px-4 py-3 text-sm font-medium transition hover:bg-zinc-800"
                    >
                        Prestations
                        <span class="text-zinc-500">→</span>
                    </a>

                </div>

            </div>


        </div>

    </div>

</x-layouts::app>