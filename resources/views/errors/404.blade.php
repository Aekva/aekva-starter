<!DOCTYPE html>
<html lang="fr" class="dark">
<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-zinc-950 text-white">

    <main class="flex min-h-screen items-center justify-center px-6">

        <div class="max-w-lg text-center">

            <p class="mb-4 text-sm font-medium uppercase tracking-[0.25em] text-zinc-500">
                Erreur 404
            </p>

            <h1 class="text-4xl font-semibold tracking-tight sm:text-5xl">
                Page introuvable
            </h1>

            <p class="mt-5 text-base leading-7 text-zinc-400">
                La page que vous recherchez n’existe pas ou n’est plus disponible.
            </p>

            <a
                href="{{ route('home') }}"
                class="mt-8 inline-flex items-center justify-center rounded-lg bg-white px-5 py-3 text-sm font-medium transition hover:bg-zinc-200"
                style="color: #18181b !important;"
            >
                Retour à l’accueil
            </a>

        </div>

    </main>

</body>
</html>