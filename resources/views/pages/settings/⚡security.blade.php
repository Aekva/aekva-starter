<?php

use App\Concerns\PasswordValidationRules;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Features;
use Laravel\Fortify\Fortify;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Paramètres de sécurité')] class extends Component {
    use PasswordValidationRules;

    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(DisableTwoFactorAuthentication $disableTwoFactorAuthentication): void
    {
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        Auth::user()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(
            variant: 'success',
            text: 'Mot de passe mis à jour.'
        );
    }
}; ?>

<section class="w-full">

    @include('partials.settings-heading')

    <flux:heading class="sr-only">
        Paramètres de sécurité
    </flux:heading>

    <x-pages::settings.layout
        heading="Modifier le mot de passe"
        subheading="Utilisez un mot de passe long et sécurisé pour protéger votre compte"
    >

        <form
            method="POST"
            wire:submit="updatePassword"
            class="mt-6 space-y-6"
        >

            <flux:input
                wire:model="current_password"
                label="Mot de passe actuel"
                type="password"
                required
                autocomplete="current-password"
                viewable
            />

            <flux:input
                wire:model="password"
                label="Nouveau mot de passe"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <flux:input
                wire:model="password_confirmation"
                label="Confirmer le nouveau mot de passe"
                type="password"
                required
                autocomplete="new-password"
                passwordrules="{{ \Illuminate\Validation\Rules\Password::defaults()->toPasswordRulesString() }}"
                viewable
            />

            <div class="flex items-center gap-4">

                <flux:button
                    variant="primary"
                    type="submit"
                    data-test="update-password-button"
                >
                    Enregistrer
                </flux:button>

            </div>

        </form>

    </x-pages::settings.layout>

</section>