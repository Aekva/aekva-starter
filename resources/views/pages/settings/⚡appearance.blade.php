<?php

use Livewire\Component;
use Livewire\Attributes\Title;

new #[Title('Paramètres d’apparence')] class extends Component {
    //
}; ?>

<section class="w-full">

    @include('partials.settings-heading')

    <flux:heading class="sr-only">
        Paramètres d’apparence
    </flux:heading>

    <x-pages::settings.layout
        heading="Apparence"
        subheading="Personnalisez l’apparence de votre espace"
    >

        <flux:radio.group
            x-data
            variant="segmented"
            x-model="$flux.appearance"
        >

            <flux:radio
                value="light"
                icon="sun"
            >
                Clair
            </flux:radio>

            <flux:radio
                value="dark"
                icon="moon"
            >
                Sombre
            </flux:radio>

            <flux:radio
                value="system"
                icon="computer-desktop"
            >
                Système
            </flux:radio>

        </flux:radio.group>

    </x-pages::settings.layout>

</section>