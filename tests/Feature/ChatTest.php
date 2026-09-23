<?php

namespace Tests\Feature;

use App\Events\Chat\MessageSent;
use App\Events\Chat\MessageDeleted;
use App\Events\Chat\MessagesRead;
use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Never migrate or clear the developer's MySQL database for tests.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'cache.default' => 'array', 'session.driver' => 'array', 'app.debug' => false,
            'broadcasting.default' => 'reverb', 'broadcasting.connections.reverb.key' => 'chat-test-key',
            'broadcasting.connections.reverb.secret' => 'chat-test-secret', 'broadcasting.connections.reverb.app_id' => 'chat-test']);
        DB::purge('sqlite');
        $this->withoutMiddleware(HandleInertiaRequests::class);
        Event::fake([MessageSent::class, MessagesRead::class, MessageDeleted::class]);
        (require database_path('migrations/2024_12_24_000006_create_users_table.php'))->up();
        (require database_path('migrations/2025_01_01_220640_create_permission_tables.php'))->up();
        Schema::create('monitors', function (Blueprint $t) { $t->uuid('id')->primary(); $t->foreignId('user_id'); $t->softDeletes(); });
        Schema::create('students', function (Blueprint $t) { $t->uuid('id')->primary(); $t->foreignId('user_id'); $t->uuid('preferred_monitor_id')->nullable(); $t->softDeletes(); });
        Schema::create('reservations', function (Blueprint $t) { $t->uuid('id')->primary(); $t->uuid('monitor_id'); $t->date('date'); $t->time('start_at'); $t->time('end_at'); $t->boolean('is_active')->default(true); $t->softDeletes(); });
        Schema::create('trainings', function (Blueprint $t) { $t->uuid('id')->primary(); $t->uuid('reservation_id'); $t->uuid('student_id'); $t->softDeletes(); });
        (require database_path('migrations/2026_09_21_120000_create_chat_tables.php'))->up();
        (require database_path('migrations/2026_09_22_120000_add_personal_chat_visibility_fields.php'))->up();
        (require database_path('migrations/2026_09_22_130000_add_attachments_to_chat_messages.php'))->up();
        (require database_path('migrations/2026_09_22_140000_create_chat_message_attachments_table.php'))->up();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $role): User
    {
        $user = User::create(['first_name' => ucfirst($role), 'last_name' => Str::random(6),
            'email' => Str::uuid().'@example.test', 'password' => 'test-only-password', 'status' => 1]);
        $user->assignRole(Role::findOrCreate($role, 'web'));
        $user->setRelation('student', null)->setRelation('monitor', null)->setRelation('secretary', null);
        return $user;
    }

    private function conversation(User $a, User $b): Conversation
    {
        $conversation = Conversation::create(['type' => 'private']);
        foreach ([$a, $b] as $user) $conversation->participants()->create(['user_id' => $user->id, 'joined_at' => now()]);
        return $conversation;
    }

    private function upcomingAssignment(User $student, User $monitor): void
    {
        $monitorId = (string) Str::uuid();
        $studentId = (string) Str::uuid();
        $reservationId = (string) Str::uuid();
        DB::table('monitors')->insert(['id' => $monitorId, 'user_id' => $monitor->id]);
        DB::table('students')->insert(['id' => $studentId, 'user_id' => $student->id]);
        DB::table('reservations')->insert([
            'id' => $reservationId, 'monitor_id' => $monitorId, 'date' => now()->addDay()->toDateString(),
            'start_at' => '10:00:00', 'end_at' => '11:00:00', 'is_active' => true,
        ]);
        DB::table('trainings')->insert(['id' => (string) Str::uuid(), 'student_id' => $studentId, 'reservation_id' => $reservationId]);
        $student->unsetRelation('student');
        $monitor->unsetRelation('monitor');
    }

    public function test_guest_cannot_access_chat(): void
    {
        $this->getJson('/api/conversations')->assertUnauthorized();
        $this->getJson('/api/chat/contacts')->assertUnauthorized();
        $this->getJson('/api/chat/unread')->assertUnauthorized();
        $this->postJson('/api/broadcasting/auth', ['socket_id' => '1.2', 'channel_name' => 'private-chat.user.1'])->assertUnauthorized();
    }

    public function test_only_participants_can_list_read_send_and_mark_read_even_admins(): void
    {
        $student = $this->user('student'); $monitor = $this->user('monitor'); $admin = $this->user('admin');
        $this->upcomingAssignment($student, $monitor);
        $conversation = $this->conversation($student, $monitor);
        $other = $this->conversation($monitor, $admin);
        Sanctum::actingAs($student);
        $this->getJson('/api/conversations')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $conversation->id);
        $this->getJson("/api/conversations/{$other->id}/messages")->assertForbidden();
        $this->postJson("/api/conversations/{$other->id}/messages", ['message' => 'intrusion'])->assertForbidden();
        $this->postJson("/api/conversations/{$other->id}/read", ['through_id' => 1])->assertForbidden();
        Sanctum::actingAs($admin);
        $this->getJson("/api/conversations/{$conversation->id}/messages")->assertForbidden();
    }

    public function test_server_sets_sender_validates_text_and_makes_retries_idempotent(): void
    {
        $student = $this->user('student'); $monitor = $this->user('monitor');
        $this->upcomingAssignment($student, $monitor);
        $conversation = $this->conversation($student, $monitor);
        Sanctum::actingAs($student);
        $url = "/api/conversations/{$conversation->id}/messages";
        foreach (['', '   ', "\n\t", str_repeat('x', 4001)] as $text) $this->postJson($url, ['message' => $text])->assertUnprocessable();
        $this->postJson($url, ['message' => ['invalid']])->assertUnprocessable();
        $this->postJson($url, ['message' => 'hello', 'message_type' => 'file'])->assertUnprocessable();
        $payload = ['message' => '  Bonjour 😊  ', 'sender_id' => $monitor->id, 'client_message_id' => (string) Str::uuid()];
        $this->postJson($url, $payload)->assertCreated()->assertJsonPath('data.sender_id', $student->id)->assertJsonPath('data.message', 'Bonjour 😊')->assertJsonPath('data.read_at', null);
        $this->postJson($url, $payload)->assertOk();
        $payload['message'] = 'different';
        $this->postJson($url, $payload)->assertStatus(409);
        $this->assertSame(1, Message::count());
        Event::assertDispatched(MessageSent::class, function ($event) use ($student, $monitor, $conversation) {
            $names = array_map(fn ($channel) => $channel->name, $event->broadcastOn());
            return in_array('private-conversation.'.$conversation->id, $names)
                && in_array('private-chat.user.'.$student->id, $names) && in_array('private-chat.user.'.$monitor->id, $names)
                && $event->broadcastWith()['message']['message'] === 'Bonjour 😊';
        });
    }

    public function test_attachment_only_message_does_not_require_text(): void
    {
        $student = $this->user('student'); $monitor = $this->user('monitor');
        $this->upcomingAssignment($student, $monitor);
        $conversation = $this->conversation($student, $monitor);
        Sanctum::actingAs($student);

        $this->post("/api/conversations/{$conversation->id}/messages", [
            'message' => '',
            'client_message_id' => (string) Str::uuid(),
            'attachments' => [UploadedFile::fake()->create('document.pdf', 10, 'application/pdf')],
        ])->assertCreated()->assertJsonPath('data.message', '');
    }

    public function test_cursor_pagination_and_read_watermark_do_not_mark_unseen_messages(): void
    {
        $student = $this->user('student'); $monitor = $this->user('monitor');
        $this->upcomingAssignment($student, $monitor);
        $conversation = $this->conversation($student, $monitor);
        for ($i = 1; $i <= 55; $i++) $conversation->messages()->create(['sender_id' => $monitor->id, 'message' => 'message '.$i]);
        Sanctum::actingAs($student);
        $url = "/api/conversations/{$conversation->id}";
        $this->getJson($url.'/messages')->assertOk()->assertJsonCount(50, 'data')->assertJsonPath('data.0.id', 6)->assertJsonPath('has_more', true);
        $this->getJson($url.'/messages?before_id=6')->assertOk()->assertJsonCount(5, 'data')->assertJsonPath('has_more', false);
        $this->getJson($url.'/messages?after_id=50')->assertOk()->assertJsonCount(5, 'data');
        $this->getJson('/api/conversations')->assertJsonPath('data.0.unread_count', 55);
        $this->postJson($url.'/read', ['through_id' => 50])->assertOk()->assertJsonPath('unread_count', 5);
        $this->postJson($url.'/read', ['through_id' => 10])->assertOk()->assertJsonPath('through_id', 50);
        $this->assertSame(5, Message::whereNull('read_at')->count());
        Event::assertDispatched(MessagesRead::class);
    }

    public function test_only_allowed_contacts_can_start_threads_and_pair_is_unique(): void
    {
        $admin = $this->user('super-admin'); $student = $this->user('student'); $monitor = $this->user('monitor'); $otherMonitor = $this->user('monitor');
        $this->upcomingAssignment($student, $monitor);
        DB::table('monitors')->insert(['id' => (string) Str::uuid(), 'user_id' => $otherMonitor->id]);
        Sanctum::actingAs($student);
        $this->getJson('/api/chat/contacts')->assertOk()->assertJsonCount(2, 'data');
        $this->postJson('/api/conversations', ['user_id' => $otherMonitor->id])->assertForbidden();
        $this->postJson('/api/conversations', ['user_id' => $student->id])->assertForbidden();
        $id = $this->postJson('/api/conversations', ['user_id' => $monitor->id])->assertCreated()->json('data.id');
        $this->postJson('/api/conversations', ['user_id' => $monitor->id])->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson('/api/conversations', ['user_id' => $admin->id])->assertCreated();
        $monitor->unsetRelation('monitor');
        Sanctum::actingAs($monitor);
        $this->postJson('/api/conversations', ['user_id' => $student->id])->assertOk()->assertJsonPath('data.id', $id);
        $this->postJson('/api/conversations', ['user_id' => $admin->id])->assertCreated();
        $this->assertSame(3, Conversation::count());
    }

    public function test_admin_message_reaches_student_and_badge_clears_only_after_reading(): void
    {
        $admin = $this->user('super-admin');
        $student = $this->user('student');
        $monitor = $this->user('monitor');
        $this->upcomingAssignment($student, $monitor);
        $this->conversation($student, $monitor);
        Sanctum::actingAs($admin);
        $id = $this->postJson('/api/conversations', ['user_id' => $student->id])->assertCreated()->json('data.id');
        $messageId = $this->postJson("/api/conversations/{$id}/messages", ['message' => 'Bonjour de l’administration'])
            ->assertCreated()->json('data.id');
        $this->getJson('/api/chat/unread')->assertJsonPath('unread_count', 0);

        Sanctum::actingAs($student);
        $this->getJson('/api/conversations')->assertOk()
            ->assertJsonCount(2, 'data')->assertJsonFragment(['id' => $id]);
        $this->getJson('/api/chat/unread')->assertOk()->assertJsonPath('user_id', $student->id)->assertJsonPath('unread_count', 1);
        $this->getJson("/api/conversations/{$id}/messages")->assertOk()->assertJsonPath('data.0.sender_id', $admin->id);
        $this->getJson('/api/chat/unread')->assertJsonPath('unread_count', 1);
        $this->postJson("/api/conversations/{$id}/read", ['through_id' => $messageId])->assertOk();
        $this->getJson('/api/chat/unread')->assertJsonPath('unread_count', 0);
        $this->postJson("/api/conversations/{$id}/messages", ['message' => 'Merci'])->assertCreated();
        $this->getJson('/api/chat/unread')->assertJsonPath('unread_count', 0);
        Sanctum::actingAs($admin);
        $this->getJson('/api/chat/unread')->assertJsonPath('unread_count', 1);
        Sanctum::actingAs($monitor);
        $this->getJson('/api/chat/unread')->assertJsonPath('unread_count', 0);
    }

    public function test_student_can_always_contact_only_the_super_admin_support_account_without_an_upcoming_reservation(): void
    {
        $superAdmin = $this->user('super-admin');
        $student = $this->user('student');
        $firstMonitor = $this->user('monitor');
        $secondMonitor = $this->user('monitor');
        $admin = $this->user('admin');

        DB::table('students')->insert(['id' => (string) Str::uuid(), 'user_id' => $student->id]);
        DB::table('monitors')->insert(['id' => (string) Str::uuid(), 'user_id' => $firstMonitor->id]);
        DB::table('monitors')->insert(['id' => (string) Str::uuid(), 'user_id' => $secondMonitor->id]);

        $student->unsetRelation('student');
        Sanctum::actingAs($student);

        $this->getJson('/api/chat/contacts')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $superAdmin->id, 'role' => 'Super Admin']);
        $this->getJson('/api/chat/contacts?search=admin')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonFragment(['id' => $superAdmin->id]);
        $this->getJson('/api/chat/contacts?search=superadmin')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonFragment(['id' => $superAdmin->id]);
        $this->postJson('/api/conversations', ['user_id' => $firstMonitor->id])->assertForbidden();
        $this->postJson('/api/conversations', ['user_id' => $admin->id])->assertForbidden();
    }

    public function test_monitor_can_always_contact_the_one_super_admin_and_see_their_messages(): void
    {
        $superAdmin = $this->user('super-admin');
        $monitor = $this->user('monitor');
        $student = $this->user('student');
        $this->upcomingAssignment($student, $monitor);

        Sanctum::actingAs($monitor);
        $this->getJson('/api/chat/contacts?search=superadmin')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $superAdmin->id);
        $conversationId = $this->postJson('/api/conversations', ['user_id' => $superAdmin->id])
            ->assertCreated()->json('data.id');

        Sanctum::actingAs($superAdmin);
        $this->postJson("/api/conversations/{$conversationId}/messages", ['message' => 'Bonjour moniteur'])->assertCreated();

        Sanctum::actingAs($monitor);
        $this->getJson('/api/conversations')->assertOk()->assertJsonPath('data.0.id', $conversationId);
        $this->getJson("/api/conversations/{$conversationId}/messages")->assertOk()
            ->assertJsonPath('data.0.message', 'Bonjour moniteur');
    }

    public function test_removing_a_conversation_only_hides_it_until_the_same_contact_is_selected_again(): void
    {
        $support = $this->user('super-admin');
        $student = $this->user('student');
        DB::table('students')->insert(['id' => (string) Str::uuid(), 'user_id' => $student->id]);
        $student->unsetRelation('student');
        $conversation = $this->conversation($student, $support);
        $ids = [$student->id, $support->id]; sort($ids, SORT_NUMERIC);
        $conversation->update(['private_key' => hash('sha256', implode(':', $ids))]);
        Sanctum::actingAs($student);

        $this->deleteJson("/api/conversations/{$conversation->id}")->assertNoContent();
        $this->getJson('/api/conversations')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/chat/contacts')->assertOk()->assertJsonFragment(['id' => $support->id]);

        $this->postJson('/api/conversations', ['user_id' => $support->id])->assertOk()
            ->assertJsonPath('data.id', $conversation->id);
        $this->getJson('/api/conversations')->assertOk()->assertJsonPath('data.0.id', $conversation->id);

        Sanctum::actingAs($support);
        $this->getJson('/api/conversations')->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conversation->id);
    }

    public function test_private_channel_authorization_checks_membership_and_personal_inbox(): void
    {
        $student = $this->user('student'); $monitor = $this->user('monitor'); $outsider = $this->user('admin');
        $conversation = $this->conversation($student, $monitor);
        $this->upcomingAssignment($student, $monitor);
        $payload = ['socket_id' => '123.456', 'channel_name' => 'private-conversation.'.$conversation->id];
        Sanctum::actingAs($student);
        $this->postJson('/api/broadcasting/auth', $payload)->assertOk()->assertJsonStructure(['auth']);
        $this->postJson('/api/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.user.'.$monitor->id])->assertForbidden();
        Sanctum::actingAs($outsider);
        $this->postJson('/api/broadcasting/auth', $payload)->assertForbidden();
    }

    public function test_connecter_uses_selected_monitors_inbox_sender_and_socket_identity(): void
    {
        $staff = $this->user('admin'); $monitor = $this->user('monitor');
        $student = $this->user('student'); $otherMonitor = $this->user('monitor');
        $monitorId = (string) Str::uuid();
        $studentId = (string) Str::uuid();
        $reservationId = (string) Str::uuid();
        DB::table('monitors')->insert(['id' => $monitorId, 'user_id' => $monitor->id]);
        DB::table('students')->insert(['id' => $studentId, 'user_id' => $student->id]);
        DB::table('reservations')->insert(['id' => $reservationId, 'monitor_id' => $monitorId, 'date' => now()->addDay()->toDateString(), 'start_at' => '10:00:00', 'end_at' => '11:00:00', 'is_active' => true]);
        DB::table('trainings')->insert(['id' => (string) Str::uuid(), 'student_id' => $studentId, 'reservation_id' => $reservationId]);
        $student->unsetRelation('student');
        $monitor->unsetRelation('monitor');
        $conversation = $this->conversation($student, $monitor);
        $unrelated = $this->conversation($student, $otherMonitor);
        Sanctum::actingAs($student);
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['message' => 'Bonjour moniteur'])->assertCreated();

        Sanctum::actingAs($staff);
        $headers = ['X-Chat-Monitor' => $monitorId];
        $this->getJson('/api/chat/session', $headers)->assertOk()->assertJsonPath('user.id', $monitor->id);
        $this->getJson('/api/chat/unread', $headers)->assertOk()->assertJsonPath('user_id', $monitor->id)->assertJsonPath('unread_count', 1);
        $this->getJson('/api/conversations', $headers)->assertOk()->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conversation->id)->assertJsonPath('data.0.unread_count', 1);
        $this->getJson("/api/conversations/{$conversation->id}/messages", $headers)->assertOk()
            ->assertJsonPath('data.0.message', 'Bonjour moniteur');
        $this->postJson("/api/conversations/{$conversation->id}/messages", ['message' => 'Bonjour élève'], $headers)
            ->assertCreated()->assertJsonPath('data.sender_id', $monitor->id);
        $this->postJson('/api/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.user.'.$monitor->id], $headers)->assertOk();
        $this->postJson('/api/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-conversation.'.$conversation->id], $headers)->assertOk();
        $this->getJson("/api/conversations/{$unrelated->id}/messages", $headers)->assertForbidden();
        $this->postJson('/api/broadcasting/auth', ['socket_id' => '123.456', 'channel_name' => 'private-chat.user.'.$otherMonitor->id], $headers)->assertForbidden();
        $this->getJson('/api/chat/session')->assertJsonPath('user.id', $staff->id);

        Sanctum::actingAs($monitor);
        $this->getJson('/api/conversations')->assertJsonPath('data.0.id', $conversation->id);
        Sanctum::actingAs($student);
        $this->getJson('/api/chat/session', $headers)->assertForbidden();
        Sanctum::actingAs($otherMonitor);
        $this->getJson('/api/chat/session', $headers)->assertForbidden();
    }
}
