<?php

namespace Tests\Feature;

use App\Jobs\SendChatMessageEmail;
use App\Models\ChatMessageEmailDelivery;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatEmailDeliveryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'cache.default' => 'array']);
        DB::purge('sqlite');
        (require database_path('migrations/2024_12_24_000006_create_users_table.php'))->up();
        (require database_path('migrations/2025_01_01_220640_create_permission_tables.php'))->up();
        Schema::create('monitors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id');
            $table->softDeletes();
        });
        Schema::create('students', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id');
            $table->softDeletes();
        });
        Schema::create('secretaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id');
            $table->softDeletes();
        });
        (require database_path('migrations/2026_09_21_120000_create_chat_tables.php'))->up();
        Schema::create('chat_message_email_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recipient_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->string('error_code', 80)->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['message_id', 'recipient_user_id']);
        });
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_every_supported_sender_recipient_role_pair_notifies_only_the_other_participant(): void
    {
        $pairs = [
            ['admin', 'student'], ['student', 'admin'], ['student', 'monitor'],
            ['monitor', 'student'], ['admin', 'monitor'], ['monitor', 'admin'],
        ];
        Mail::fake();

        foreach ($pairs as [$senderRole, $recipientRole]) {
            $sender = $this->user($senderRole);
            $recipient = $this->user($recipientRole);
            $message = $this->message($sender, [$sender, $recipient]);

            (new SendChatMessageEmail($message->id))->handle();

            $this->assertDatabaseHas('chat_message_email_deliveries', [
                'message_id' => $message->id, 'recipient_user_id' => $recipient->id, 'status' => 'sent',
            ]);
            $this->assertDatabaseMissing('chat_message_email_deliveries', [
                'message_id' => $message->id, 'recipient_user_id' => $sender->id,
            ]);
        }

        Mail::assertSent(NewChatMessageMail::class, count($pairs));
    }

    public function test_multi_participant_conversation_notifies_each_recipient_once_and_never_duplicates_sent_deliveries(): void
    {
        $sender = $this->user('admin');
        $student = $this->user('student');
        $monitor = $this->user('monitor');
        $message = $this->message($sender, [$sender, $student, $monitor, $student]);
        Mail::fake();
        $job = new SendChatMessageEmail($message->id);

        $job->handle();
        $job->handle();

        $this->assertSame(2, ChatMessageEmailDelivery::where('message_id', $message->id)->where('status', 'sent')->count());
        $this->assertDatabaseMissing('chat_message_email_deliveries', ['message_id' => $message->id, 'recipient_user_id' => $sender->id]);
        Mail::assertSent(NewChatMessageMail::class, 2);
    }

    public function test_failure_keeps_the_message_and_delivery_pending_until_laravel_exhausts_retries(): void
    {
        $sender = $this->user('monitor');
        $recipient = $this->user('student');
        $message = $this->message($sender, [$sender, $recipient]);
        Mail::shouldReceive('to')->once()->andThrow(new \RuntimeException('SMTP transport unavailable'));
        $job = new SendChatMessageEmail($message->id);

        try {
            $job->handle();
            $this->fail('The queued job must retry a failed provider request.');
        } catch (\RuntimeException) {
            $this->assertDatabaseHas('messages', ['id' => $message->id]);
            $this->assertDatabaseHas('chat_message_email_deliveries', [
                'message_id' => $message->id, 'recipient_user_id' => $recipient->id,
                'status' => 'pending', 'error_code' => 'EMAIL_DELIVERY_RETRYING',
            ]);
        }

        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 180, 600], $job->backoff);
        $job->failed(new \RuntimeException('SMTP transport unavailable'));

        $this->assertDatabaseHas('chat_message_email_deliveries', [
            'message_id' => $message->id, 'recipient_user_id' => $recipient->id,
            'status' => 'EMAIL_DELIVERY_FAILED', 'error_code' => 'EMAIL_DELIVERY_FAILED',
        ]);
    }

    private function user(string $role): User
    {
        $user = User::create([
            'first_name' => ucfirst($role), 'last_name' => Str::random(6),
            'email' => Str::uuid().'@example.test', 'password' => 'test-password', 'status' => 1,
        ]);
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    /** @param array<int, User> $participants */
    private function message(User $sender, array $participants): Message
    {
        $conversation = Conversation::create(['type' => 'group']);
        foreach ($participants as $participant) {
            $conversation->participants()->firstOrCreate(['user_id' => $participant->id], ['joined_at' => now()]);
        }

        return $conversation->messages()->create(['sender_id' => $sender->id, 'message' => 'Safe notification test']);
    }
}
