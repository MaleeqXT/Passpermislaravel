<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Repository\V2\Monitor\Schedule\Reservation\Job\StoreReservationRepo;
use App\Repository\V2\Monitor\Schedule\Reservation\Job\StoreUnrestrictiveReservationRepo;

$c = StoreReservationRepo::run(['monitor_id'=>1,'date'=>date('Y-m-d'),'start_at'=>'12:00','end_at'=>'13:00','hour'=>1,'is_active'=>1,'lieu_id'=>1,'color'=>'#123456']);
$copy = StoreUnrestrictiveReservationRepo::run([
    'id' => $c->id,
    'monitor_id' => $c->monitor_id,
    'date' => optional($c->date)->format('Y-m-d'),
    'start_at' => optional($c->start_at)->format('H:i'),
    'end_at' => optional($c->end_at)->format('H:i'),
    'hour' => $c->hour,
    'is_active' => $c->is_active,
    'lieu_id' => $c->lieu_id,
    'color' => $c->color,
    'created_at' => $c->created_at,
    'updated_at' => $c->updated_at,
]);

echo json_encode(['canonical' => $c->toArray(), 'copy' => $copy->toArray()]) . PHP_EOL;
