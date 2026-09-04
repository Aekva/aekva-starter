<?php

use App\Models\Reservation;
use App\Models\Resource;
use App\Models\Service;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Tests\TestCase;

uses(TestCase::class);

test('mysql verrouille la ressource pendant une réservation concurrente', function () {

    /*
     * Ce test est volontairement réservé à notre base MySQL temporaire.
     * On empêche toute exécution accidentelle sur une vraie base.
     */
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Ce test doit être exécuté avec MySQL.');
    }

    expect(DB::connection()->getDatabaseName())
        ->toBe('starter_test');


    /*
     * Nettoyage de notre base MySQL dédiée.
     */
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    DB::table('reservations')->truncate();
    DB::table('resource_service')->truncate();
    DB::table('resources')->truncate();
    DB::table('services')->truncate();

    DB::statement('SET FOREIGN_KEY_CHECKS=1');


    /*
     * Données nécessaires au test.
     */
    $service = Service::create([
        'name' => 'Test concurrence',
        'description' => null,
        'duration_minutes' => 30,
        'price' => 25,
        'capacity' => 1,
        'is_active' => true,
    ]);

    $resource = Resource::create([
        'name' => 'Ressource concurrence',
        'type' => 'Personne',
        'description' => null,
        'capacity' => 1,
        'is_active' => true,
    ]);

    $resource->services()->attach($service->id, [
        'is_active' => true,
    ]);

    /*
     * Le fichier permet au second processus de nous dire
     * quand il a réussi à obtenir le verrou.
     */
    $resultFile = storage_path(
        'framework/testing/mysql-lock-result.txt'
    );

    @unlink($resultFile);


    /*
     * PROCESSUS A
     *
     * La transaction principale verrouille la ressource.
     */
    DB::beginTransaction();

    try {

        Resource::query()
            ->whereKey($resource->id)
            ->lockForUpdate()
            ->firstOrFail();


        /*
         * PROCESSUS B
         *
         * Un deuxième processus Laravel essaie
         * d'obtenir exactement le même verrou.
         */
        $script = <<<'PHP'
$resourceId = (int) getenv('LOCK_RESOURCE_ID');
$resultFile = getenv('LOCK_RESULT_FILE');

Illuminate\Support\Facades\DB::transaction(function () use ($resourceId, $resultFile) {

    App\Models\Resource::query()
        ->whereKey($resourceId)
        ->lockForUpdate()
        ->firstOrFail();

    file_put_contents(
        $resultFile,
        'LOCK_ACQUIRED'
    );
});
PHP;

        $process = new Process(
            [
                PHP_BINARY,
                base_path('artisan'),
                'tinker',
                '--execute=' . $script,
            ],
            base_path(),
            [
                'LOCK_RESOURCE_ID' => (string) $resource->id,
                'LOCK_RESULT_FILE' => $resultFile,

                /*
                 * On garantit que le sous-processus
                 * utilise lui aussi starter_test.
                 */
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => '127.0.0.1',
                'DB_PORT' => '3306',
                'DB_DATABASE' => 'starter_test',
                'DB_USERNAME' => 'root',
                'DB_PASSWORD' => '',
            ]
        );

        $process->setTimeout(10);

        $process->start();


        /*
         * On laisse le temps au processus B
         * d'arriver sur lockForUpdate().
         */
        usleep(700000);


        /*
         * Tant que A possède le verrou,
         * B NE DOIT PAS avoir réussi.
         */
        expect(file_exists($resultFile))
            ->toBeFalse();


        /*
         * Pendant que la ressource est verrouillée,
         * A crée la réservation.
         */
        Reservation::create([
            'service_id' => $service->id,
            'resource_id' => $resource->id,

            'starts_at' => now()
                ->addDays(30)
                ->setTime(14, 30),

            'ends_at' => now()
                ->addDays(30)
                ->setTime(15, 0),

            'quantity' => 1,

            'customer_name' => 'Client A',
            'customer_email' => 'client-a@test.fr',

            'total_price' => 25,
            'status' => 'confirmed',
        ]);


        /*
         * A termine sa transaction.
         * Le verrou est maintenant libéré.
         */
        DB::commit();

    } catch (Throwable $exception) {

        if (DB::transactionLevel() > 0) {
            DB::rollBack();
        }

        if (isset($process) && $process->isRunning()) {
            $process->stop();
        }

        throw $exception;
    }


    /*
     * B doit maintenant pouvoir continuer.
     */
    $process->wait();


    expect($process->isSuccessful())
        ->toBeTrue(
            $process->getErrorOutput()
            . PHP_EOL
            . $process->getOutput()
        );


    /*
     * Maintenant seulement,
     * B doit avoir obtenu le verrou.
     */
    expect(file_exists($resultFile))
        ->toBeTrue();

    expect(trim(file_get_contents($resultFile)))
        ->toBe('LOCK_ACQUIRED');


    /*
     * La réservation créée par A
     * est bien enregistrée.
     */
    expect(Reservation::count())
        ->toBe(1);

    expect(Reservation::firstOrFail()->customer_name)
        ->toBe('Client A');


    @unlink($resultFile);
});