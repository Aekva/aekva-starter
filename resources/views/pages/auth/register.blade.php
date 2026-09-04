<x-layouts::auth :title="'Créer un compte'">

    <div class="flex flex-col gap-6">

        <x-auth-header
            title="Créer un compte"
            description="Renseignez vos informations pour créer votre compte"
        />

        <!-- Session Status -->
        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form
            method="POST"
            action="{{ route('register.store') }}"
            class="flex flex-col gap-6"
        >
            @csrf

            <!-- Name -->
            <flux:input
                name="name"
                label="Nom"
                :value="old('name')"
                type="text"
                required
                autofocus
                autocomplete="name"
                placeholder="Nom complet"
            />

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Adresse e-mail"
                :value="old('email')"
                type="email"
                required
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <flux:input
                name="password"
                label="Mot de passe"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Mot de passe"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <!-- Confirm Password -->
            <flux:input
                name="password_confirmation"
                label="Confirmer le mot de passe"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Confirmer le mot de passe"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center justify-end">

                <flux:button
                    type="submit"
                    variant="primary"
                    class="w-full"
                    data-test="register-user-button"
                >
                    Créer le compte
                </flux:button>

            </div>

        </form>

        <div class="space-x-1 text-center text-sm text-zinc-600 dark:text-zinc-400 rtl:space-x-reverse">

            <span>
                Vous avez déjà un compte ?
            </span>

            <flux:link
                :href="route('login')"
                wire:navigate
            >
                Se connecter
            </flux:link>

        </div>

    </div>

</x-layouts::auth>