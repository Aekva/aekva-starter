<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create';

    protected $description = 'Créer un compte administrateur';

    public function handle(): int
    {
        $this->info('Création du compte administrateur');

        $name = $this->ask('Nom');

        $email = $this->ask('Adresse email');

        if (User::where('email', $email)->exists()) {
            $this->error('Un utilisateur avec cette adresse email existe déjà.');

            return self::FAILURE;
        }

        $password = $this->secret('Mot de passe');

        $passwordConfirmation = $this->secret('Confirmez le mot de passe');

        $validator = Validator::make([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
            ],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User();

        $user->name = $name;
        $user->email = $email;
        $user->email_verified_at = now();
        $user->password = Hash::make($password);

        $user->save();

        $this->newLine();
        $this->info('Compte administrateur créé avec succès.');

        return self::SUCCESS;
    }
}