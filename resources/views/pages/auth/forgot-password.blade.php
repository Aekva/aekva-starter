<x-layouts::auth :title="'Mot de passe oublié'">

    <div class="flex flex-col gap-6">

        <x-auth-header
            title="Mot de passe oublié"
            description="Saisissez votre adresse e-mail pour recevoir un lien de réinitialisation"
        />

        <!-- Session Status -->
        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form
            method="POST"
            action="{{ route('password.email') }}"
            class="flex flex-col gap-6"
        >
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Adresse e-mail"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button
                variant="primary"
                type="submit"
                class="w-full"
                data-test="email-password-reset-link-button"
            >
                Envoyer le lien de réinitialisation
            </flux:button>

        </form>

        <div class="space-x-1 text-center text-sm text-zinc-400 rtl:space-x-reverse">

            <span>
                Ou revenir à
            </span>

            <flux:link
                :href="route('login')"
                wire:navigate
            >
                la connexion
            </flux:link>

        </div>

    </div>

</x-layouts::auth>