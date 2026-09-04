<?php

use App\Models\Availability;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);


function createDashboardReservationSetup(): array
{
    $service = Service::create([
        'name' => 'Prestation test',
        'description' => null,
        'duration_minutes' => 30,
        'price' => 25,
        'capacity' => 1,
        'is_active' => true,
    ]);

    $resource = Resource::create([
        'name' => 'Moi Meme',
        'type' => 'Personne',
        'description' => null,
        'capacity' => 1,
        'is_active' => true,
    ]);

    $resource->services()->attach($service->id, [
        'is_active' => true,
    ]);

    return [$service, $resource];
}


function createDashboardAvailability(
    string $date,
    string $start = '09:00',
    string $end = '18:00'
): void {
    Availability::create([
        'resource_id' => null,
        'service_id' => null,
        'day_of_week' => Carbon::parse($date)->dayOfWeek,
        'specific_date' => null,
        'start_time' => $start,
        'end_time' => $end,
        'is_available' => true,
        'valid_from' => null,
        'valid_until' => null,
        'capacity' => null,
    ]);
}


test('annuler une réservation change son statut et libère son créneau', function () {

    [$service, $resource] = createDashboardReservationSetup();

    $date = now()->addDays(5)->format('Y-m-d');

    createDashboardAvailability($date);


    $reservation = Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,

        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',

        'quantity' => 1,

        'customer_name' => 'Client test',
        'customer_email' => 'client@test.fr',

        'total_price' => 25,
        'status' => 'confirmed',
    ]);


    /*
     * Le dashboard annule réellement la réservation.
     */
    Livewire::test('pages::dashboard.reservations.index')
        ->call(
            'setStatus',
            $reservation->id,
            'cancelled'
        );


    $reservation->refresh();

    expect($reservation->status)
        ->toBe('cancelled');


    /*
     * Une réservation annulée ne doit plus
     * bloquer son ancien créneau côté public.
     */
    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertSee('14:30');
});

test('reprogrammer une réservation annulée libère lancien créneau et bloque le nouveau', function () {

    [$service, $resource] = createDashboardReservationSetup();

    $date = now()->addDays(6)->format('Y-m-d');

    createDashboardAvailability($date);


    /*
     * Réservation annulée initialement à 14:30.
     */
    $reservation = Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,

        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',

        'quantity' => 1,

        'customer_name' => 'Client test',
        'customer_email' => 'client@test.fr',

        'total_price' => 25,
        'status' => 'cancelled',
    ]);


    /*
     * On ouvre la réservation annulée
     * pour la reprogrammer.
     */
    Livewire::test('pages::dashboard.reservations.index')

        ->call(
            'openEditReservation',
            $reservation->id
        )

        ->assertSet(
            'editingReservationId',
            $reservation->id
        )

        /*
         * Nouveau créneau : 16:00.
         */
        ->set('editSlot', '16:00')

        ->call('saveEditReservation')

        /*
         * Le formulaire doit se refermer
         * après l'enregistrement.
         */
        ->assertSet(
            'editingReservationId',
            null
        );


    $reservation->refresh();


    /*
     * La réservation est redevenue confirmée.
     */
    expect($reservation->status)
        ->toBe('confirmed');


    /*
     * Elle est maintenant programmée à 16:00.
     */
    expect(
        Carbon::parse($reservation->starts_at)
            ->format('Y-m-d H:i')
    )->toBe(
        $date . ' 16:00'
    );


    expect(
        Carbon::parse($reservation->ends_at)
            ->format('Y-m-d H:i')
    )->toBe(
        $date . ' 16:30'
    );


    /*
     * Côté réservation publique :
     *
     * - l'ancien créneau 14:30 doit être libre ;
     * - le nouveau créneau 16:00 doit être occupé.
     */
    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')

        ->assertSee('14:30')
        ->assertDontSee('16:00');
});

test('un créneau pris avant lenregistrement dune modification est refusé et la réservation originale reste intacte', function () {

    [$service, $resource] = createDashboardReservationSetup();

    $date = now()->addDays(7)->format('Y-m-d');

    createDashboardAvailability($date);


    /*
     * Réservation originale à 14:30.
     */
    $reservation = Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,

        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',

        'quantity' => 1,

        'customer_name' => 'Client original',
        'customer_email' => 'original@test.fr',

        'total_price' => 25,
        'status' => 'confirmed',
    ]);


    /*
     * Le professionnel ouvre la modification
     * et choisit 16:00.
     */
    $component = Livewire::test(
        'pages::dashboard.reservations.index'
    )
        ->call(
            'openEditReservation',
            $reservation->id
        )
        ->set(
            'editSlot',
            '16:00'
        );


    /*
     * Entre-temps, quelqu'un d'autre
     * réserve réellement 16:00.
     */
    Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,

        'starts_at' => $date . ' 16:00:00',
        'ends_at' => $date . ' 16:30:00',

        'quantity' => 1,

        'customer_name' => 'Autre client',
        'customer_email' => 'autre@test.fr',

        'total_price' => 25,
        'status' => 'confirmed',
    ]);


    /*
     * Le professionnel tente maintenant
     * d'enregistrer sa modification.
     */
    $component
        ->call('saveEditReservation')

        ->assertHasErrors([
            'editSlot',
        ])

        /*
         * Le formulaire reste ouvert.
         */
        ->assertSet(
            'editingReservationId',
            $reservation->id
        )

        /*
         * Le créneau devenu indisponible
         * est désélectionné.
         */
        ->assertSet(
            'editSlot',
            null
        );


    /*
     * La réservation originale ne doit
     * surtout pas avoir été déplacée.
     */
    $reservation->refresh();

    expect($reservation->status)
        ->toBe('confirmed');

    expect(
        Carbon::parse($reservation->starts_at)
            ->format('Y-m-d H:i')
    )->toBe(
        $date . ' 14:30'
    );

    expect(
        Carbon::parse($reservation->ends_at)
            ->format('Y-m-d H:i')
    )->toBe(
        $date . ' 15:00'
    );


    /*
     * On doit bien avoir uniquement :
     *
     * - la réservation originale à 14:30
     * - l'autre réservation à 16:00
     */
    expect(
        Reservation::count()
    )->toBe(2);
});

test('une réservation passée ne peut pas être ouverte en modification', function () {

    [$service, $resource] = createDashboardReservationSetup();

    $date = now()->subDays(2)->format('Y-m-d');


    $reservation = Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,

        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',

        'quantity' => 1,

        'customer_name' => 'Ancien client',
        'customer_email' => 'ancien@test.fr',

        'total_price' => 25,
        'status' => 'confirmed',
    ]);


    Livewire::test('pages::dashboard.reservations.index')

        ->call(
            'openEditReservation',
            $reservation->id
        )

        ->assertSet(
            'editingReservationId',
            null
        );
});

test('une réservation passée ne peut pas être modifiée en appelant directement la sauvegarde', function () {

    [$service, $resource] = createDashboardReservationSetup();

    $oldDate = now()->subDays(2)->format('Y-m-d');
    $newDate = now()->addDays(5)->format('Y-m-d');

    createDashboardAvailability($newDate);


    $reservation = Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,

        'starts_at' => $oldDate . ' 14:30:00',
        'ends_at' => $oldDate . ' 15:00:00',

        'quantity' => 1,

        'customer_name' => 'Ancien client',
        'customer_email' => 'ancien@test.fr',

        'total_price' => 25,
        'status' => 'confirmed',
    ]);


    /*
     * On contourne volontairement
     * openEditReservation().
     */
    Livewire::test('pages::dashboard.reservations.index')

        ->set(
            'editingReservationId',
            $reservation->id
        )

        ->set(
            'editServiceId',
            $service->id
        )

        ->set(
            'editResourceId',
            $resource->id
        )

        ->set(
            'editDate',
            $newDate
        )

        ->set(
            'editSlot',
            '16:00'
        )

        ->call('saveEditReservation')

        /*
         * La modification étant refusée,
         * l'édition n'est pas fermée.
         */
        ->assertSet(
            'editingReservationId',
            $reservation->id
        );


    $reservation->refresh();


    /*
     * La réservation passée doit rester
     * strictement intacte.
     */
    expect($reservation->status)
        ->toBe('confirmed');

    expect(
        Carbon::parse($reservation->starts_at)
            ->format('Y-m-d H:i')
    )->toBe(
        $oldDate . ' 14:30'
    );

    expect(
        Carbon::parse($reservation->ends_at)
            ->format('Y-m-d H:i')
    )->toBe(
        $oldDate . ' 15:00'
    );
});