<x-layouts::auth :title="'Vérification de l’adresse e-mail'">

    <div class="mt-4 flex flex-col gap-6">

        <flux:text class="text-center">
            Vérifiez votre adresse e-mail en cliquant sur le lien que nous venons de vous envoyer.
        </flux:text>

        @if (session('status') == 'verification-link-sent')

            <flux:text class="text-center font-medium !text-green-600 !dark:text-green-400">
                Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
            </flux:text>

        @endif

        <div class="flex flex-col items-center justify-between space-y-3">

            <form
                method="POST"
                action="{{ route('verification.send') }}"
            >
                @csrf

                <flux:button
                    type="submit"
                    variant="primary"
                    class="w-full"
                >
                    Renvoyer l’e-mail de vérification
                </flux:button>

            </form>

            <form
                method="POST"
                action="{{ route('logout') }}"
            >
                @csrf

                <flux:button
                    variant="ghost"
                    type="submit"
                    class="cursor-pointer text-sm"
                    data-test="logout-button"
                >
                    Se déconnecter
                </flux:button>

            </form>

        </div>

    </div>

</x-layouts::auth>