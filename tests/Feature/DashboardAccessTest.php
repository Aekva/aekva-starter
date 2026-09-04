<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashboardRoutes(): array
{
    return [
        'dashboard',
        'dashboard.availabilities',
        'dashboard.customization',
        'dashboard.reservations',
        'dashboard.resources',
        'dashboard.services',
    ];
}

test('un visiteur non connecté ne peut accéder à aucune page du dashboard', function () {

    foreach (dashboardRoutes() as $routeName) {

        $this->get(route($routeName))
            ->assertRedirect(route('login'));
    }
});

test('un utilisateur connecté et vérifié peut accéder aux pages du dashboard', function () {

    $user = User::factory()->create();

    $this->actingAs($user);

    foreach (dashboardRoutes() as $routeName) {

        $this->get(route($routeName))
            ->assertOk();
    }
});

test('un utilisateur connecté mais non vérifié ne peut accéder à aucune page du dashboard', function () {

    $user = User::factory()
        ->unverified()
        ->create();

    $this->actingAs($user);

    foreach (dashboardRoutes() as $routeName) {

        $this->get(route($routeName))
            ->assertRedirect(route('verification.notice'));
    }
});