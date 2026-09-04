<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Paramètres du profil')] class extends Component {
    use ProfileValidationRules;

    public string $name = '';
    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        Flux::toast(
            variant: 'success',
            text: 'Profil mis à jour.'
        );
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(
                default: route('dashboard', absolute: false)
            );

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail
            && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (
                Auth::user() instanceof MustVerifyEmail
                && Auth::user()->hasVerifiedEmail()
            );
    }
}; ?>

<section class="w-full">

    @include('partials.settings-heading')

    <flux:heading class="sr-only">
        Paramètres du profil
    </flux:heading>

    <x-pages::settings.layout
        heading="Profil"
        subheading="Modifiez votre nom et votre adresse e-mail"
    >

        <form
            wire:submit="updateProfileInformation"
            class="my-6 w-full space-y-6"
        >

            <flux:input
                wire:model="name"
                label="Nom"
                type="text"
                required
                autofocus
                autocomplete="name"
            />

            <div>

                <flux:input
                    wire:model="email"
                    label="Adresse e-mail"
                    type="email"
                    required
                    autocomplete="email"
                />

                @if ($this->hasUnverifiedEmail)

                    <div>

                        <flux:text class="mt-4">

                            Votre adresse e-mail n'est pas vérifiée.

                            <flux:link
                                class="cursor-pointer text-sm"
                                wire:click.prevent="resendVerificationNotification"
                            >
                                Renvoyer l'e-mail de vérification
                            </flux:link>

                        </flux:text>

                        @if (session('status') === 'verification-link-sent')

                            <flux:text class="mt-2 font-medium !text-green-600 !dark:text-green-400">
                                Un nouveau lien de vérification vous a été envoyé par e-mail.
                            </flux:text>

                        @endif

                    </div>

                @endif

            </div>

            <div class="flex items-center gap-4">

                <div class="flex items-center justify-end">

                    <flux:button
                        variant="primary"
                        type="submit"
                        class="w-full"
                        data-test="update-profile-button"
                    >
                        Enregistrer
                    </flux:button>

                </div>

            </div>

        </form>


        @if ($this->showDeleteUser)

            <livewire:pages::settings.delete-user-form />

        @endif

    </x-pages::settings.layout>

</section>