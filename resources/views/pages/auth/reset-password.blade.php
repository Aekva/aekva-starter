<x-layouts::auth :title="'Réinitialiser le mot de passe'">

    <div class="flex flex-col gap-6">

        <x-auth-header
            title="Réinitialiser le mot de passe"
            description="Saisissez votre nouveau mot de passe ci-dessous"
        />

        <!-- Session Status -->
        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form
            method="POST"
            action="{{ route('password.update') }}"
            class="flex flex-col gap-6"
        >
            @csrf

            <!-- Token -->
            <input
                type="hidden"
                name="token"
                value="{{ request()->route('token') }}"
            >

            <!-- Email Address -->
            <flux:input
                name="email"
                value="{{ request('email') }}"
                label="Adresse e-mail"
                type="email"
                required
                autocomplete="email"
            />

            <!-- Password -->
            <flux:input
                name="password"
                label="Nouveau mot de passe"
                type="password"
                required
                autocomplete="new-password"
                placeholder="Nouveau mot de passe"
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
                    data-test="reset-password-button"
                >
                    Réinitialiser le mot de passe
                </flux:button>

            </div>

        </form>

    </div>

</x-layouts::auth>