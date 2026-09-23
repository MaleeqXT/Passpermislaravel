<?php

namespace Tests\Feature;

use App\Models\Roles\Monitor\Schedule\Reservation;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\StudentTrainingCancellation;
use App\Models\Roles\Student\User\Student;
use App\Models\Roles\Student\User\Wallet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function setUp(): void
    {
        parent::setUp();

        // ensure necessary roles exist
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'secretary']);
        Role::firstOrCreate(['name' => 'student']);

        // you may need additional seeding depending on app requirements
    }

    public function test_approving_request_with_reservation_cancels_reservation_and_wallet()
    {
        // create a student and related wallet/offer
        $student = Student::factory()->create();
        $wallet = Wallet::factory()->create(['student_id' => $student->id]);

        // create a monitor reservation
        $reservation = Reservation::factory()->create();

        // create a training associating student, reservation and wallet offer
        $training = Training::factory()->create([
            'student_id' => $student->id,
            'reservation_id' => $reservation->id,
            'offer_id' => $wallet->offer_id,
        ]);

        // create the cancellation record from the student side
        $cancellation = StudentTrainingCancellation::create([
            'student_id' => $student->id,
            'hours_requested' => 1,
            'status' => 'pending',
            'comment' => 'need to cancel',
            'reservation_id' => $reservation->id,
        ]);

        // act as admin user
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
                         ->postJson("/admin/approvels/{$cancellation->id}/approve");

        $response->assertStatus(200);
        $response->assertJsonPath('cancellation.status', 'approved');

        // reservation record should still exist but its training removed
        $this->assertDatabaseHas('reservations', ['id' => $reservation->id]);
        $this->assertDatabaseMissing('trainings', ['reservation_id' => $reservation->id]);

        // cancellation status updated
        $this->assertDatabaseHas('student_training_cancellations', [
            'id' => $cancellation->id,
            'status' => 'approved',
        ]);

        // wallet balance should have increased by the reservation hour (1)
        $student->refresh();
        $this->assertEquals(1, $student->balance);
    }

    public function test_approving_two_hour_request_restores_full_reservation_hours()
    {
        $student = Student::factory()->create(['balance' => 0]);
        $wallet = Wallet::factory()->create([
            'student_id' => $student->id,
            'balance' => 0,
        ]);

        $reservation = Reservation::factory()->create([
            'hour' => 2,
            'end_at' => now()->setTime(10, 0, 0)->format('H:i:s'),
        ]);

        Training::factory()->create([
            'student_id' => $student->id,
            'reservation_id' => $reservation->id,
            'offer_id' => $wallet->offer_id,
        ]);

        $cancellation = StudentTrainingCancellation::create([
            'student_id' => $student->id,
            'hours_requested' => 1,
            'status' => 'pending',
            'comment' => 'cancel full 2h session',
            'reservation_id' => $reservation->id,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)
            ->postJson("/admin/approvels/{$cancellation->id}/approve");

        $response->assertStatus(200);
        $response->assertJsonPath('cancellation.status', 'approved');

        $student->refresh();
        $wallet->refresh();

        $this->assertSame(2, (int) $student->balance);
        $this->assertSame(2, (int) $wallet->balance);
    }
}
