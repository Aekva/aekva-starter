<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">

    <flux:sidebar
        sticky
        collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900"
    >

        {{-- Header sidebar --}}

        <flux:sidebar.header>

            <x-app-logo
                :sidebar="true"
                href="{{ route('dashboard') }}"
                wire:navigate
            />

            <flux:sidebar.collapse class="lg:hidden" />

        </flux:sidebar.header>


        {{-- Navigation principale --}}

        <flux:sidebar.nav>

            <flux:sidebar.group
                heading="Gestion"
                class="grid"
            >

                {{-- Dashboard --}}

                <flux:sidebar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                >
                    Dashboard
                </flux:sidebar.item>


                {{-- Réservations --}}

                <flux:sidebar.item
                    icon="calendar-days"
                    :href="route('dashboard.reservations')"
                    :current="request()->routeIs('dashboard.reservations')"
                    wire:navigate
                >
                    Réservations
                </flux:sidebar.item>


                {{-- Prestations --}}

                <flux:sidebar.item
                    icon="briefcase"
                    :href="route('dashboard.services')"
                    :current="request()->routeIs('dashboard.services')"
                    wire:navigate
                >
                    Prestations
                </flux:sidebar.item>


                {{-- Équipe & ressources --}}

                <flux:sidebar.item
                    icon="users"
                    :href="route('dashboard.resources')"
                    :current="request()->routeIs('dashboard.resources')"
                    wire:navigate
                >
                    Équipe & ressources
                </flux:sidebar.item>


                {{-- Disponibilités --}}

                <flux:sidebar.item
                    icon="clock"
                    :href="route('dashboard.availabilities')"
                    :current="request()->routeIs('dashboard.availabilities')"
                    wire:navigate
                >
                    Disponibilités
                </flux:sidebar.item>


                {{-- Personnalisation --}}

                <flux:sidebar.item
                    icon="paint-brush"
                    :href="route('dashboard.customization')"
                    :current="request()->routeIs('dashboard.customization')"
                    wire:navigate
                >
                    Personnalisation
                </flux:sidebar.item>

            </flux:sidebar.group>

        </flux:sidebar.nav>


        {{-- Pousse le profil utilisateur en bas --}}

        <flux:spacer />


        {{-- Profil utilisateur desktop --}}

        <x-desktop-user-menu
            class="hidden lg:block"
            :name="auth()->user()->name"
        />

    </flux:sidebar>


    {{-- Menu utilisateur mobile --}}

    <flux:header
        class="sticky top-0 z-50 border-b border-zinc-700 lg:hidden"
        style="background-color: #18181b !important;"
    >

        <flux:sidebar.toggle
            class="lg:hidden"
            icon="bars-2"
            inset="left"
        />

        <flux:spacer />


        <flux:dropdown
            position="top"
            align="end"
        >

            <flux:profile
                :initials="auth()->user()->initials()"
                icon-trailing="chevron-down"
            />


            <flux:menu>

                <flux:menu.radio.group>

                    <div class="p-0 text-sm font-normal">

                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">

                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                            />

                            <div class="grid flex-1 text-start text-sm leading-tight">

                                <flux:heading class="truncate">
                                    {{ auth()->user()->name }}
                                </flux:heading>

                                <flux:text class="truncate">
                                    {{ auth()->user()->email }}
                                </flux:text>

                            </div>

                        </div>

                    </div>

                </flux:menu.radio.group>


                <flux:menu.separator />


                <flux:menu.radio.group>

                    <flux:menu.item
                        :href="route('profile.edit')"
                        icon="cog"
                        wire:navigate
                    >
                        Paramètres
                    </flux:menu.item>

                </flux:menu.radio.group>


                <flux:menu.separator />


                <form
                    method="POST"
                    action="{{ route('logout') }}"
                    class="w-full"
                >

                    @csrf

                    <flux:menu.item
                        as="button"
                        type="submit"
                        icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer"
                        data-test="logout-button"
                    >
                        Se déconnecter
                    </flux:menu.item>

                </form>

            </flux:menu>

        </flux:dropdown>

    </flux:header>


    {{ $slot }}


    @persist('toast')

        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>

    @endpersist


    @fluxScripts

</body>

</html>