<x-layouts::auth :title="'Confirmer le mot de passe'">

    <div class="flex flex-col gap-6">

        <x-auth-header
            title="Confirmer le mot de passe"
            description="Cette zone est sécurisée. Confirmez votre mot de passe avant de continuer."
        />

        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form
            method="POST"
            action="{{ route('password.confirm.store') }}"
            class="flex flex-col gap-6"
        >
            @csrf

            <flux:input
                name="password"
                label="Mot de passe"
                type="password"
                required
                autocomplete="current-password"
                placeholder="Mot de passe"
                viewable
            />

            <flux:button
                variant="primary"
                type="submit"
                class="w-full"
                data-test="confirm-password-button"
            >
                Confirmer
            </flux:button>

        </form>

    </div>

</x-layouts::auth>