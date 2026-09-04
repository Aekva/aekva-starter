<?php

use App\Models\Availability;
use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewReservationNotification;
use App\Mail\ReservationConfirmed;
use App\Models\SiteSetting;

uses(RefreshDatabase::class);

function createBookingSetup(int $duration = 30): array
{
    $service = Service::create([
        'name' => 'Prestation test',
        'description' => null,
        'duration_minutes' => $duration,
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

function createAvailability(
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
function createClosedSpecificDate(
    string $date,
    array $metadata = ['type' => 'single_exception']
): void {
    Availability::create([
        'resource_id' => null,
        'service_id' => null,
        'day_of_week' => Carbon::parse($date)->dayOfWeek,
        'specific_date' => $date,
        'start_time' => '00:00',
        'end_time' => '23:59',
        'is_available' => false,
        'valid_from' => null,
        'valid_until' => null,
        'capacity' => null,
        'metadata' => $metadata,
    ]);
}

test('un créneau déjà réservé disparaît pour la même ressource et le même jour', function () {

    [$service, $resource] = createBookingSetup();

    $date = now()->addDays(5)->format('Y-m-d');

    createAvailability($date);

    Reservation::create([
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

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertDontSee('14:30');
});


test('le même horaire reste disponible un autre jour', function () {

    [$service, $resource] = createBookingSetup();

    $reservedDate = now()->addDays(5)->format('Y-m-d');
    $otherDate = now()->addDays(12)->format('Y-m-d');

    createAvailability($reservedDate);


    Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,
        'starts_at' => $reservedDate . ' 14:30:00',
        'ends_at' => $reservedDate . ' 15:00:00',
        'quantity' => 1,
        'customer_name' => 'Client test',
        'customer_email' => 'client@test.fr',
        'total_price' => 25,
        'status' => 'confirmed',
    ]);

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $otherDate)
        ->call('goToSlotsStep')
        ->assertSee('14:30');
});


test('une réservation annulée ne bloque plus le créneau', function () {

    [$service, $resource] = createBookingSetup();

    $date = now()->addDays(5)->format('Y-m-d');

    createAvailability($date);

    Reservation::create([
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

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertSee('14:30');
});


test('une prestation ne peut pas dépasser lheure de fermeture', function () {

    [$service, $resource] = createBookingSetup(120);

    $date = now()->addDays(5)->format('Y-m-d');

    createAvailability(
        date: $date,
        start: '09:00',
        end: '18:00'
    );

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')

        // 16h + 2h = 18h
        ->assertSee('16:00')

        // 16h30 + 2h = 18h30
        ->assertDontSee('16:30')

        ->assertDontSee('17:00')
        ->assertDontSee('17:30');
});

test('une réservation sur une ressource ne bloque pas une autre ressource', function () {

    [$service, $resource] = createBookingSetup();

    $otherResource = Resource::create([
        'name' => 'Sam',
        'type' => 'Personne',
        'description' => null,
        'capacity' => 1,
        'is_active' => true,
    ]);

    $otherResource->services()->attach($service->id, [
        'is_active' => true,
    ]);

    $date = now()->addDays(6)->format('Y-m-d');

    createAvailability($date);

    Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,
        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',
        'quantity' => 1,
        'customer_name' => 'Client Moi Meme',
        'customer_email' => 'client1@test.fr',
        'total_price' => 25,
        'status' => 'confirmed',
    ]);

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $otherResource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertSee('14:30');
});


test('une fermeture exceptionnelle rend toute la journée indisponible', function () {

    [$service, $resource] = createBookingSetup();

    $date = now()->addDays(8)->format('Y-m-d');

    createAvailability($date);

    createClosedSpecificDate(
        $date,
        ['type' => 'single_exception']
    );

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertSet('availableSlots', []);
});


test('une date comprise dans une période de fermeture est indisponible', function () {

    [$service, $resource] = createBookingSetup();

    $day1 = now()->addDays(10)->format('Y-m-d');
    $day2 = now()->addDays(11)->format('Y-m-d');
    $day3 = now()->addDays(12)->format('Y-m-d');

    /*
     * Horaire normal du jour que l'on va tester.
     */
    createAvailability($day2);

    /*
     * Les trois jours appartiennent à la même
     * période de fermeture.
     */
    $metadata = [
        'type' => 'closure_period',
        'period_start' => $day1,
        'period_end' => $day3,
    ];

    createClosedSpecificDate($day1, $metadata);
    createClosedSpecificDate($day2, $metadata);
    createClosedSpecificDate($day3, $metadata);

    /*
     * Le jour du milieu doit être complètement fermé.
     */
    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $day2)
        ->call('goToSlotsStep')
        ->assertSet('availableSlots', []);
});


test('une ressource de capacité deux accepte deux réservations puis bloque le créneau', function () {

    [$service, $resource] = createBookingSetup();

    $resource->update([
        'capacity' => 2,
    ]);

    $date = now()->addDays(15)->format('Y-m-d');

    createAvailability($date);


    /*
     * Première réservation
     */

    Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,
        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',
        'quantity' => 1,
        'customer_name' => 'Client 1',
        'customer_email' => 'client1@test.fr',
        'total_price' => 25,
        'status' => 'confirmed',
    ]);


    /*
     * Capacité = 2 :
     * après une réservation, le créneau doit
     * encore être disponible.
     */

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertSee('14:30');


    /*
     * Deuxième réservation
     */

    Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,
        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',
        'quantity' => 1,
        'customer_name' => 'Client 2',
        'customer_email' => 'client2@test.fr',
        'total_price' => 25,
        'status' => 'confirmed',
    ]);


    /*
     * Capacité atteinte :
     * le créneau doit maintenant disparaître.
     */

    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertDontSee('14:30');
});

test('la confirmation crée une seule réservation avec les bonnes informations', function () {

    /*
     * On empêche les vrais emails de partir pendant le test.
     */
    Mail::fake();

    [$service, $resource] = createBookingSetup();

    $date = now()->addDays(20)->format('Y-m-d');

    createAvailability(
        date: $date,
        start: '09:00',
        end: '18:00'
    );


    /*
     * On simule une vraie confirmation
     * depuis le tunnel de réservation.
     */
    $component = Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->set('selectedSlot', '14:30')

        ->set('customerName', 'Jean Dupont')
        ->set('customerEmail', 'jean@test.fr')
        ->set('customerPhone', '0600000000')
        ->set('customerNotes', 'Première visite')

        ->call('confirmReservation')

        ->assertHasNoErrors()
        ->assertSet('step', 7);


    /*
     * Une seule réservation doit avoir été créée.
     */
    expect(Reservation::count())
        ->toBe(1);


    $reservation = Reservation::firstOrFail();


    /*
     * Vérification de toutes les informations enregistrées.
     */
    expect($reservation->service_id)
        ->toBe($service->id)

        ->and($reservation->resource_id)
        ->toBe($resource->id)

        ->and($reservation->customer_name)
        ->toBe('Jean Dupont')

        ->and($reservation->customer_email)
        ->toBe('jean@test.fr')

        ->and($reservation->customer_phone)
        ->toBe('0600000000')

        ->and($reservation->notes)
        ->toBe('Première visite')

        ->and($reservation->quantity)
        ->toBe(1)

        ->and($reservation->status)
        ->toBe('confirmed')

        ->and((float) $reservation->total_price)
        ->toBe(25.0)

        ->and(
            Carbon::parse($reservation->starts_at)
                ->format('Y-m-d H:i')
        )
        ->toBe($date . ' 14:30')

        ->and(
            Carbon::parse($reservation->ends_at)
                ->format('Y-m-d H:i')
        )
        ->toBe($date . ' 15:00');


    /*
     * Le composant doit conserver l'identifiant
     * de la réservation qui vient d'être créée.
     */
    $component->assertSet(
        'createdReservationId',
        $reservation->id
    );
});

test('un créneau devenu indisponible avant la confirmation ne crée pas de seconde réservation', function () {

    Mail::fake();

    [$service, $resource] = createBookingSetup();

    $date = now()->addDays(21)->format('Y-m-d');

    createAvailability(
        date: $date,
        start: '09:00',
        end: '18:00'
    );


    /*
     * Le client arrive sur les créneaux
     * et sélectionne 14:30 alors qu'il est encore libre.
     */
    $component = Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->call('goToSlotsStep')
        ->assertSee('14:30')
        ->call('selectSlot', '14:30')
        ->assertSet('selectedSlot', '14:30');


    /*
     * Entre-temps, quelqu'un d'autre réserve
     * exactement ce créneau.
     */
    Reservation::create([
        'service_id' => $service->id,
        'resource_id' => $resource->id,

        'starts_at' => $date . ' 14:30:00',
        'ends_at' => $date . ' 15:00:00',

        'quantity' => 1,

        'customer_name' => 'Client concurrent',
        'customer_email' => 'concurrent@test.fr',

        'total_price' => 25,

        'status' => 'confirmed',
    ]);


    /*
     * Notre premier client essaie maintenant
     * de confirmer son ancienne sélection.
     */
    $component
        ->set('customerName', 'Jean Dupont')
        ->set('customerEmail', 'jean@test.fr')
        ->set('customerPhone', '0600000000')
        ->set('customerNotes', '')

        ->call('confirmReservation')

        /*
         * Il doit être renvoyé au choix du créneau.
         */
        ->assertSet('step', 4)

        /*
         * Et recevoir une erreur indiquant
         * que le créneau n'est plus disponible.
         */
        ->assertHasErrors(['selectedSlot'])

        /*
         * Aucune réservation n'a été créée
         * pour ce client.
         */
        ->assertSet('createdReservationId', null);


    /*
     * Il ne doit toujours exister que la réservation
     * du client concurrent.
     */
    expect(Reservation::count())
        ->toBe(1);

    expect(
        Reservation::firstOrFail()->customer_name
    )->toBe('Client concurrent');


    /*
     * Comme aucune réservation n'a été créée
     * pour Jean, aucun email ne doit être envoyé.
     */
    Mail::assertNothingSent();
});

test('une réservation confirmée envoie un email au client et une notification au professionnel', function () {

    Mail::fake();

    [$service, $resource] = createBookingSetup();

    $date = now()->addDays(22)->format('Y-m-d');

    createAvailability(
        date: $date,
        start: '09:00',
        end: '18:00'
    );


    /*
     * Configuration de l'établissement.
     */
    SiteSetting::create([
        'business_name' => 'Établissement test',
        'email' => 'contact@test.fr',
        'notification_email' => 'pro@test.fr',
        'primary_color' => '#18181b',
    ]);


    /*
     * Confirmation d'une réservation.
     */
    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->set('selectedSlot', '14:30')

        ->set('customerName', 'Jean Dupont')
        ->set('customerEmail', 'client@test.fr')
        ->set('customerPhone', '0600000000')
        ->set('customerNotes', '')

        ->call('confirmReservation')

        ->assertHasNoErrors()
        ->assertSet('step', 7);


    $reservation = Reservation::firstOrFail();


    /*
     * Email de confirmation envoyé au client.
     */
    Mail::assertSent(
        ReservationConfirmed::class,
        function (ReservationConfirmed $mail) use ($reservation) {

            return $mail->hasTo('client@test.fr')
                && $mail->reservation->id === $reservation->id;
        }
    );


    /*
     * Notification envoyée au professionnel.
     */
    Mail::assertSent(
        NewReservationNotification::class,
        function (NewReservationNotification $mail) use ($reservation) {

            return $mail->hasTo('pro@test.fr')
                && $mail->reservation->id === $reservation->id;
        }
    );


    /*
     * Exactement deux emails :
     * un pour le client et un pour le professionnel.
     */
    Mail::assertSentCount(2);
});

test('sans email de notification pro la réservation et le mail client fonctionnent quand même', function () {

    Mail::fake();

    [$service, $resource] = createBookingSetup();

    $date = now()->addDays(23)->format('Y-m-d');

    createAvailability(
        date: $date,
        start: '09:00',
        end: '18:00'
    );


    SiteSetting::create([
        'business_name' => 'Établissement test',
        'email' => 'contact@test.fr',
        'notification_email' => null,
        'primary_color' => '#18181b',
    ]);


    Livewire::test('pages::booking.create')
        ->set('selectedServiceId', $service->id)
        ->set('selectedResourceId', $resource->id)
        ->set('selectedDate', $date)
        ->set('selectedSlot', '14:30')

        ->set('customerName', 'Jean Dupont')
        ->set('customerEmail', 'client@test.fr')
        ->set('customerPhone', '0600000000')
        ->set('customerNotes', '')

        ->call('confirmReservation')

        ->assertHasNoErrors()
        ->assertSet('step', 7);


    expect(Reservation::count())
        ->toBe(1);


    /*
     * Le client reçoit bien sa confirmation.
     */
    Mail::assertSent(
        ReservationConfirmed::class,
        fn (ReservationConfirmed $mail) =>
            $mail->hasTo('client@test.fr')
    );


    /*
     * Aucun mail pro ne doit partir.
     */
    Mail::assertNotSent(
        NewReservationNotification::class
    );


    /*
     * Un seul email au total.
     */
    Mail::assertSentCount(1);
});