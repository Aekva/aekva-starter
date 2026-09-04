@php
    $settings = \App\Models\SiteSetting::first();

    $services = \App\Models\Service::query()
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    $businessName = $settings?->business_name ?? 'Votre établissement';

    $heroEyebrow = $settings?->hero_eyebrow ?? 'Bienvenue';
    $heroTitle = $settings?->hero_title ?? 'Prenez rendez-vous';
    $heroHighlight = $settings?->hero_highlight ?? 'simplement.';

    $heroDescription = $settings?->hero_description
        ?? 'Découvrez nos prestations, choisissez ce qui vous convient et réservez votre créneau en quelques instants.';

    $bookingButtonLabel = $settings?->booking_button_label
        ?? 'Prendre rendez-vous';

    $servicesTitle = $settings?->services_title
        ?? 'Choisissez votre prestation';

    $servicesDescription = $settings?->services_description
        ?? 'Retrouvez les prestations actuellement disponibles à la réservation.';
        $phone = $settings?->phone;
        $email = $settings?->email;
        $address = $settings?->address;

        $logoPath = $settings?->logo_path;
    $primaryColor = $settings?->primary_color ?? '#FFFFFF';

    /*
    |--------------------------------------------------------------------------
    | Couleur du texte sur les boutons
    |--------------------------------------------------------------------------
    | On choisit automatiquement noir ou blanc selon la couleur sélectionnée.
    */

    $hex = ltrim($primaryColor, '#');

    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));

    $luminance = (0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255;

    $primaryTextColor = $luminance > 0.6
    ? '#18181b'
    : '#ffffff';

    $logoZoom = $settings?->logo_zoom ?? 100;
    $logoOffsetX = $settings?->logo_offset_x ?? 0;
    $logoOffsetY = $settings?->logo_offset_y ?? 0;

    $heroImagePath = $settings?->hero_image_path;

    $heroImageZoom =
    (int) ($settings?->hero_image_zoom ?? 100);

    $heroImagePositionX =
    (int) ($settings?->hero_image_position_x ?? 50);

    $heroImagePositionY =
    (int) ($settings?->hero_image_position_y ?? 50);

@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')

    <title>Accueil</title>

    <style>
    :root {
        --primary-color: {{ $primaryColor }};
        --primary-text-color: {{ $primaryTextColor }};
    }

    .primary-button {
        background-color: var(--primary-color);
        color: var(--primary-text-color);
    }

    .primary-button:hover {
        opacity: .88;
    }

    .primary-text {
        color: var(--primary-color);
    }
</style>
</head>

<body class="min-h-screen bg-zinc-950 text-white">


    {{-- Header --}}

    <header class="border-b border-white/10">

        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5 lg:px-10">

        <a
    href="{{ url('/') }}"
    class="flex items-center"
>
@if ($logoPath)

    <div
        class="relative overflow-hidden"
        style="
            width: 180px;
            height: 48px;
        "
    >

        <img
            src="{{ asset('storage/' . $logoPath) }}"
            alt="{{ $businessName }}"
            class="absolute inset-0 h-full w-full object-contain"
            style="
                transform:
                    translate(
                        {{ $logoOffsetX }}px,
                        {{ $logoOffsetY }}px
                    )
                    scale({{ $logoZoom / 100 }});

                transform-origin: center;
            "
        >

    </div>

@else

        <span class="text-xl font-semibold tracking-tight">
            {{ $businessName }}
        </span>

    @endif
</a>


            <nav class="hidden items-center gap-8 md:flex">

                <a
                    href="#prestations"
                    class="text-sm text-zinc-400 transition hover:text-white"
                >
                    Prestations
                </a>

                <a
                    href="#fonctionnement"
                    class="text-sm text-zinc-400 transition hover:text-white"
                >
                    Comment ça marche
                </a>

                <a
                    href="{{ url('/booking') }}"
                    class="primary-button rounded-xl px-5 py-2.5 text-sm font-semibold transition"                >
                {{ $bookingButtonLabel }}                </a>

            </nav>


            <a
    href="{{ url('/booking') }}"
    class="primary-button rounded-xl px-4 py-2 text-sm font-semibold transition md:hidden"
>
    {{ $bookingButtonLabel }}
</a>

        </div>

    </header>



    <main>


    {{-- Hero --}}

<section class="relative overflow-hidden">

    {{-- Image du Hero --}}

    @if ($heroImagePath)

        <div class="absolute inset-0 overflow-hidden">

            <img
                src="{{ asset('storage/' . $heroImagePath) }}"
                alt=""
                class="h-full w-full object-cover"
                style="
                    object-position:
                        {{ $heroImagePositionX }}%
                        {{ $heroImagePositionY }}%;

                    transform:
                        scale({{ $heroImageZoom / 100 }});

                    transform-origin:
                        {{ $heroImagePositionX }}%
                        {{ $heroImagePositionY }}%;
                "
            >

        </div>


        {{-- Assombrissement pour garder le texte lisible --}}

        <div
            class="absolute inset-0"
            style="
                background:
                    linear-gradient(
                        90deg,
                        rgba(9, 9, 11, 0.92) 0%,
                        rgba(9, 9, 11, 0.70) 48%,
                        rgba(9, 9, 11, 0.30) 100%
                    );
            "
        ></div>

    @endif



    {{-- Contenu --}}

    <div class="relative z-10 mx-auto max-w-7xl px-6 py-24 lg:px-10 lg:py-36">

        <div class="max-w-3xl">

            <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-400">
                {{ $heroEyebrow }}
            </p>


            <h1 class="mt-6 text-4xl font-semibold tracking-tight sm:text-5xl lg:text-7xl">

                <span class="block">
                    {{ $heroTitle }}
                </span>

                @if ($heroHighlight)

                    <span class="primary-text block">
                        {{ $heroHighlight }}
                    </span>

                @endif

            </h1>


            <p class="mt-6 max-w-2xl text-lg leading-relaxed text-zinc-300">
                {{ $heroDescription }}
            </p>


            <div class="mt-10 flex flex-wrap gap-4">

                <a
                    href="{{ url('/booking') }}"
                    class="primary-button rounded-xl px-6 py-3.5 font-semibold transition"
                >
                    {{ $bookingButtonLabel }}
                </a>


                <a
                    href="#prestations"
                    class="rounded-xl border border-white/20 bg-black/20 px-6 py-3.5 font-medium text-zinc-200 backdrop-blur-sm transition hover:border-white/40 hover:text-white"
                >
                    Voir les prestations
                </a>

            </div>

        </div>

    </div>

</section>



        {{-- Prestations --}}

        <section
            id="prestations"
            class="border-t border-white/10"
        >

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">

                <div class="max-w-2xl">

                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500">
                        Prestations
                    </p>

                    <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                    {{ $servicesTitle }}                    </h2>

                    <p class="mt-4 text-zinc-400">
                    {{ $servicesDescription }}                    </p>

                </div>


                <div class="mt-10 grid gap-4 md:grid-cols-2 lg:grid-cols-3">

                    @forelse ($services as $service)

                        <a
                            href="{{ url('/booking') }}"
                            class="group rounded-2xl border border-zinc-800 p-6 transition hover:border-zinc-600 hover:bg-zinc-900"
                        >

                            <div class="flex items-start justify-between gap-6">

                                <div>

                                    <h3 class="text-lg font-semibold">
                                        {{ $service->name }}
                                    </h3>


                                    @if ($service->description)

                                        <p class="mt-3 line-clamp-3 text-sm leading-relaxed text-zinc-400">
                                            {{ $service->description }}
                                        </p>

                                    @endif

                                </div>


                                <span class="text-zinc-600 transition group-hover:translate-x-1 group-hover:text-white">
                                    →
                                </span>

                            </div>


                            <div class="mt-6 flex flex-wrap items-center gap-4 text-sm text-zinc-500">

                                @if ($service->duration_minutes)

                                    <span>
                                        {{ $service->duration_minutes }} min
                                    </span>

                                @endif


                                @if ($service->price !== null)

                                    <span>
                                        {{ number_format((float) $service->price, 2, ',', ' ') }} €
                                    </span>

                                @endif

                            </div>

                        </a>


                    @empty

                        <div class="col-span-full rounded-2xl border border-dashed border-zinc-800 p-10 text-center">

                            <p class="font-medium">
                                Aucune prestation disponible
                            </p>

                            <p class="mt-2 text-sm text-zinc-500">
                                Les prestations apparaîtront ici lorsqu'elles seront activées.
                            </p>

                        </div>

                    @endforelse

                </div>

            </div>

        </section>



        {{-- Fonctionnement --}}

        <section
            id="fonctionnement"
            class="border-t border-white/10 bg-zinc-900/30"
        >

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">

                <div class="max-w-2xl">

                    <p class="text-sm font-medium uppercase tracking-[0.2em] text-zinc-500">
                        Réservation
                    </p>

                    <h2 class="mt-4 text-3xl font-semibold tracking-tight sm:text-4xl">
                        Votre rendez-vous en quelques étapes
                    </h2>

                </div>


                <div class="mt-12 grid gap-5 md:grid-cols-3">


                    <div class="rounded-2xl border border-zinc-800 p-6">

                        <span class="text-sm font-semibold text-zinc-500">
                            01
                        </span>

                        <h3 class="mt-6 text-lg font-semibold">
                            Choisissez
                        </h3>

                        <p class="mt-3 text-sm leading-relaxed text-zinc-400">
                            Sélectionnez la prestation qui correspond à vos besoins.
                        </p>

                    </div>


                    <div class="rounded-2xl border border-zinc-800 p-6">

                        <span class="text-sm font-semibold text-zinc-500">
                            02
                        </span>

                        <h3 class="mt-6 text-lg font-semibold">
                            Sélectionnez votre créneau
                        </h3>

                        <p class="mt-3 text-sm leading-relaxed text-zinc-400">
                            Consultez les disponibilités et choisissez la date et l'heure qui vous conviennent.
                        </p>

                    </div>


                    <div class="rounded-2xl border border-zinc-800 p-6">

                        <span class="text-sm font-semibold text-zinc-500">
                            03
                        </span>

                        <h3 class="mt-6 text-lg font-semibold">
                            C'est réservé
                        </h3>

                        <p class="mt-3 text-sm leading-relaxed text-zinc-400">
                            Confirmez vos coordonnées et votre réservation est immédiatement enregistrée.
                        </p>

                    </div>

                </div>

            </div>

        </section>



        {{-- CTA --}}

        <section class="border-t border-white/10">

            <div class="mx-auto max-w-7xl px-6 py-20 lg:px-10 lg:py-28">

                <div class="rounded-3xl border border-zinc-800 bg-zinc-900 p-8 text-center sm:p-14">

                    <h2 class="text-3xl font-semibold tracking-tight sm:text-4xl">
                        Prêt à réserver ?
                    </h2>

                    <p class="mx-auto mt-4 max-w-xl text-zinc-400">
                        Consultez les disponibilités et choisissez le créneau qui vous convient.
                    </p>

                    <a
    href="{{ url('/booking') }}"
    class="primary-button mt-8 inline-flex rounded-xl px-6 py-3.5 font-semibold transition"
>
    {{ $bookingButtonLabel }}
</a>

                </div>

            </div>

        </section>

    </main>



    {{-- Footer --}}

    <footer class="border-t border-white/10">

<div class="mx-auto max-w-7xl px-6 py-10 lg:px-10">

    <div class="flex flex-col gap-8 sm:flex-row sm:items-start sm:justify-between">

        {{-- Établissement --}}

        <div>

            <p class="font-semibold text-white">
                {{ $businessName }}
            </p>

            @if ($address)

                <p class="mt-2 text-sm text-zinc-500">
                    {{ $address }}
                </p>

            @endif

        </div>


        {{-- Coordonnées --}}

        @if ($phone || $email)

            <div class="flex flex-col gap-2 text-sm">

                @if ($phone)

                    <a
                        href="tel:{{ preg_replace('/\s+/', '', $phone) }}"
                        class="text-zinc-400 transition hover:text-white"
                    >
                        {{ $phone }}
                    </a>

                @endif


                @if ($email)

                    <a
                        href="mailto:{{ $email }}"
                        class="text-zinc-400 transition hover:text-white"
                    >
                        {{ $email }}
                    </a>

                @endif

            </div>

        @endif


        {{-- Liens --}}

<div class="flex flex-col gap-2 text-sm">

    <a
        href="{{ url('/booking') }}"
        class="font-medium text-zinc-400 transition hover:text-white"
    >
        {{ $bookingButtonLabel }}
    </a>

    @auth
        <a
            href="{{ route('dashboard') }}"
            class="text-zinc-500 transition hover:text-white"
        >
            Espace professionnel
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="text-zinc-500 transition hover:text-white"
        >
            Espace professionnel
        </a>
    @endauth

</div>

    </div>


    <div class="mt-8 border-t border-white/10 pt-6">

        <p class="text-xs text-zinc-600">
            © {{ now()->year }} {{ $businessName }}
        </p>

    </div>

</div>

</footer>


</body>
</html>