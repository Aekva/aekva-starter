<x-layouts::auth :title="'Connexion'">

    <div class="flex flex-col gap-6">

        <x-auth-header
            title="Connexion à votre espace"
            description="Saisissez votre adresse e-mail et votre mot de passe"
        />

        <!-- Session Status -->
        <x-auth-session-status
            class="text-center"
            :status="session('status')"
        />

        <form
            method="POST"
            action="{{ route('login.store') }}"
            class="flex flex-col gap-6"
        >
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                label="Adresse e-mail"
                :value="old('email')"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="email@example.com"
            />

            <!-- Password -->
            <div class="relative">

                <flux:input
                    name="password"
                    label="Mot de passe"
                    type="password"
                    required
                    autocomplete="current-password"
                    placeholder="Mot de passe"
                    viewable
                />

                @if (Route::has('password.request'))

                    <flux:link
                        class="absolute top-0 text-sm end-0"
                        :href="route('password.request')"
                        wire:navigate
                    >
                        Mot de passe oublié ?
                    </flux:link>

                @endif

            </div>

            <!-- Remember Me -->
            <flux:checkbox
                name="remember"
                label="Se souvenir de moi"
                :checked="old('remember')"
            />

            <div class="flex items-center justify-end">

                <flux:button
                    variant="primary"
                    type="submit"
                    class="w-full"
                    data-test="login-button"
                >
                    Se connecter
                </flux:button>

            </div>

        </form>

        @if (Route::has('register'))

            <div class="space-x-1 text-sm text-center rtl:space-x-reverse text-zinc-600 dark:text-zinc-400">

                <span>
                    Vous n’avez pas encore de compte ?
                </span>

                <flux:link
                    :href="route('register')"
                    wire:navigate
                >
                    Créer un compte
                </flux:link>

            </div>

        @endif

    </div>

</x-layouts::auth>