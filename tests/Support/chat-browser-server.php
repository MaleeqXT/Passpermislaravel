<?php

// Isolated, opt-in browser fixture. Never load this file from production routes.
// Seed: php tests/Support/chat-browser-server.php seed
// Serve: set CHAT_TEST_DATABASE to the returned path, then run PHP's loopback server with this router.
if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
foreach (['REVERB_APP_ID' => 'chat-test', 'REVERB_APP_KEY' => 'chat-test-key', 'REVERB_APP_SECRET' => 'chat-test-secret',
    'REVERB_HOST' => '127.0.0.1', 'REVERB_PORT' => '8081', 'REVERB_SCHEME' => 'http', 'BROADCAST_CONNECTION' => 'reverb'] as $key => $value) {
    putenv($key.'='.$value); $_ENV[$key] = $value; $_SERVER[$key] = $value;
}
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();
$seed = PHP_SAPI === 'cli' && ($argv[1] ?? '') === 'seed';
$database = $seed ? tempnam(sys_get_temp_dir(), 'ppf') : getenv('CHAT_TEST_DATABASE');
if (!$database || !is_file($database) || !str_starts_with(basename($database), 'ppf')) {
    throw new RuntimeException('A dedicated ppf-chat-* test database is required.');
}
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database,
    'cache.default' => 'array', 'session.driver' => 'array', 'mail.default' => 'array', 'app.debug' => false,
    'cors.allowed_origins' => ['http://localhost:5174', 'http://127.0.0.1:5174', 'http://localhost:5175'],
    'app.env' => 'testing']);
\Illuminate\Support\Facades\DB::purge('sqlite');

if ($seed) {
    (require database_path('migrations/2024_12_24_000006_create_users_table.php'))->up();
    (require database_path('migrations/2025_01_01_220640_create_permission_tables.php'))->up();
    $schema = \Illuminate\Support\Facades\Schema::getFacadeRoot();
    $schema->create('chat_fixture_marker', fn ($t) => $t->id());
    foreach (['monitors', 'secretaries'] as $table) {
        $schema->create($table, function ($t) { $t->uuid('id')->primary(); $t->foreignId('user_id'); $t->softDeletes(); });
    }
    $schema->create('students', function ($t) { $t->uuid('id')->primary(); $t->foreignId('user_id'); $t->uuid('preferred_monitor_id')->nullable(); $t->softDeletes(); });
    $schema->create('reservations', function ($t) { $t->uuid('id')->primary(); $t->uuid('monitor_id'); $t->softDeletes(); });
    $schema->create('trainings', function ($t) { $t->uuid('id')->primary(); $t->uuid('reservation_id'); $t->uuid('student_id'); $t->softDeletes(); });
    $schema->create('personal_access_tokens', function ($t) {
        $t->id(); $t->morphs('tokenable'); $t->string('name'); $t->string('token', 64)->unique();
        $t->text('abilities')->nullable(); $t->timestamp('last_used_at')->nullable(); $t->timestamp('expires_at')->nullable(); $t->timestamps();
    });
    (require database_path('migrations/2026_09_21_120000_create_chat_tables.php'))->up();
    $users = [];
    foreach (['student', 'monitor', 'admin', 'outsider'] as $role) {
        $user = \App\Models\User::create(['first_name' => 'Chat Test', 'last_name' => ucfirst($role),
            'email' => $role.'@chat.example.test', 'password' => bin2hex(random_bytes(16)), 'status' => 1]);
        $user->assignRole(\Spatie\Permission\Models\Role::findOrCreate($role === 'outsider' ? 'student' : $role, 'web'));
        $user->tokens()->create(['name' => 'isolated-chat-test', 'token' => hash('sha256', 'test-'.$role), 'abilities' => ['*']]);
        $users[$role] = $user;
    }
    $monitorId = (string) \Illuminate\Support\Str::uuid();
    \Illuminate\Support\Facades\DB::table('monitors')->insert(['id' => $monitorId, 'user_id' => $users['monitor']->id]);
    \Illuminate\Support\Facades\DB::table('students')->insert(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $users['student']->id, 'preferred_monitor_id' => $monitorId]);
    echo json_encode(['database' => $database]);
    exit;
}

if (!\Illuminate\Support\Facades\Schema::hasTable('chat_fixture_marker')) throw new RuntimeException('Not a chat fixture database.');
foreach (\Illuminate\Support\Facades\Route::getRoutes() as $route) {
    $route->withoutMiddleware([\App\Http\Middleware\HandleInertiaRequests::class]);
}
\Illuminate\Support\Facades\Route::get('/api/chat-test/session/{role}', function (string $role) {
    abort_unless(in_array($role, ['student', 'monitor', 'admin', 'outsider'], true), 404);
    $user = \App\Models\User::where('email', $role.'@chat.example.test')->firstOrFail();
    return response()->json(['user' => ['id' => $user->id, 'name' => $user->name, 'first_name' => $user->first_name,
        'last_name' => $user->last_name, 'roles' => $user->roles, 'student' => $user->student, 'monitor' => $user->monitor], 'token' => 'test-'.$role]);
});
$request = \Illuminate\Http\Request::capture();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
