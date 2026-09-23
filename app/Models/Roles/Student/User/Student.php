<?php

namespace App\Models\Roles\Student\User;

use App\Models\Roles\Admin\Area\Zone;
use App\Models\Roles\Admin\Offer\Cart\Cart;
use App\Models\Roles\Admin\Offer\Order\Sale;
use App\Models\Roles\Monitor\Schedule\ReviewMonitor;
use App\Models\Roles\Student\Schedule\Rating;
use App\Models\Roles\Student\Schedule\Training;
use App\Models\Roles\Student\Schedule\TrainingProposal;
use App\Models\Roles\Student\Cpf\Cpf;
use App\Models\Roles\Student\User\Information\Call;
use App\Models\Roles\Student\User\Information\StudentNote;
use App\Models\User;
use Database\Factories\Roles\Student\User\StudentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Roles\Admin\Offer\Cart\CartDetail;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\ReservationComment;
use App\Models\Roles\Student\Exam\StudentExam;




class Student extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected static $unguarded = true;

    protected static function newFactory()
    {
        return StudentFactory::new();
    }
    protected $casts = [
        'balance' => 'integer',
        
        'is_cpf' => 'integer',
        'boite_type' => 'string',
        'aval_monitor' => 'boolean',
        'contract_available_notified_at' => 'datetime',
        'registration_confirmation_sent_at' => 'datetime',
        'required_documents' => 'array',
        'communication_preferences' => 'array',
    ];

    //  protected $with = ['training.reservation.reviewMonitor'];

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(\App\Models\Media\Media::class, 'mediable');
    }

    public function preferredMonitor(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Roles\Monitor\User\Monitor::class, 'preferred_monitor_id');
    }

    public function reservationComments()
{
    return $this->hasMany(ReservationComment::class);
}

    /**
     * @return HasMany
     */


public function carts()
{
    return $this->hasMany(
        \App\Models\Roles\Admin\Offer\Cart\Cart::class,
        'student_id',
        'id'
    );
}

public function offers()
{
    return $this->belongsToMany(
        \App\Models\Roles\Admin\Offer\Offer::class,
        'cart_details',   // pivot table
        'cart_id',        // foreign key on pivot → carts.id
        'offer_id'        // foreign key on pivot → offers.id
    )
    ->join('carts', 'carts.id', '=', 'cart_details.cart_id')
    ->where('carts.student_id', $this->id)
    ->withPivot(['quantity', 'tranches', 'selected_price_type']);
}

    /**
     * @return HasMany
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * @return HasMany
     */
    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    /**
     * @return BelongsToMany
     */
    public function zones(): BelongsToMany
    {
        return $this->belongsToMany(Zone::class);
    }

    /**
     * @return HasMany
     */
    public function studentNotes(): HasMany
    {
        return $this->hasMany(StudentNote::class);
    }

    /**
     * @return HasMany
     */
    public function calls(): HasMany
    {
        return $this->hasMany(Call::class);
    }

    /**
     * @return HasMany
     */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class);
    }


    public function exams(): HasMany
    {
        return $this->hasMany(StudentExam::class);
    }

    /** CPF record and generated-document statuses for the CPF administration list. */
    public function cpf(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Cpf::class);
    }

    /**
     * @param $query
     * @return void
     */
    public function scopeRealiseHours($query)
    {
        $query->withCount(['trainings as realise_hour' => function ($query) {
            $query->whereHas('reservation', function ($query) {
                $query->whereDate('date', '<', now())
                    ->orWhere(function ($query) {
                        $query->whereDate('date', '=', now())
                            ->whereTime('end_at', '<', now()->format('H:S'));
                    });
            });
        }]);
    }

    /**
     * @return HasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }


    public function training()
    {
        return $this->hasOne(Training::class);
    }

    /**
     * @return HasMany
     */
    public function trainingProposals(): HasMany
    {
        return $this->hasMany(TrainingProposal::class);
    }

    public function reviewMonitor(): \Illuminate\Database\Eloquent\Relations\HasOneThrough
    {
        return $this->hasOneThrough(
            ReviewMonitor::class, // Related model (ReviewMonitor)
            Training::class,         // Intermediate model (Session)
            'student_id',             // Foreign key on the Session table
            'reservation_id',          // Foreign key on the ReviewMonitor table
            'id',                   // Local key on Eleve table
            'reservation_id'           // Local key on the Session table
        )->whereNotNull('estimation');
    }

    public function getBookedTrainingHours(): float
    {
        return (float) $this->trainings()
            ->with('reservation:id,hour')
            ->get()
            ->sum(fn (Training $training) => (float) ($training->reservation?->hour ?? 0));
    }

    public function getBookedTrainingCount(): int
    {
        return (int) $this->trainings()->count();
    }

    public function canViewContract(): bool
    {
        return $this->getBookedTrainingCount() >= 2;
    }
}
