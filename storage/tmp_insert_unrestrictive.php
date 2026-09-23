<?php
use Illuminate\Support\Facades\DB;
require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';

// Bootstrap the application to use DB facade
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ok = DB::table('unrestrictive_reservations')->insert([
    'monitor_id' => 1,
    'date' => date('Y-m-d'),
    'start_at' => '09:00',
    'end_at' => '10:00',
    'hour' => 1,
    'is_active' => 1,
    'lieu_id' => 1,
    'color' => '#ff0000',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
]);

$rCount = DB::table('reservations')->count();
$uCount = DB::table('unrestrictive_reservations')->count();


echo json_encode(['inserted' => $ok ? 1 : 0, 'reservations' => $rCount, 'unrestrictive' => $uCount]) . PHP_EOL;
