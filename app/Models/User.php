<?php

namespace App\Models;


use App\Models\Media\Media;
use App\Models\Secretary;
use App\Models\Roles\Monitor\User\Monitor;
use App\Models\Roles\Student\User\Information\Call;
use App\Models\Roles\Student\User\Information\StudentNote;
use App\Models\Roles\Student\User\Student;
use App\Scopes\Global\User\StatusInactiveUserScoop;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Octopy\Impersonate\Concerns\HasImpersonation;
use App\Notifications\V1\System\User\CustomResetPasswordNotification;
use Illuminate\Database\Eloquent\Model;
use App\Models\Roles\Admin\Area\Zone;
use App\Models\RdvPermisToken;


class User extends Authenticatable
{
    use HasApiTokens;

    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    use HasRoles;
    use TwoFactorAuthenticatable;
    use HasImpersonation;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guard = 'web';

    protected $with = ['monitor', 'student','secretary'];
    protected $casts = [
        'status' => 'integer',
    ];
    protected $fillable = [
        'name',
        'email',
        'password',
        'first_name',
        'last_name',
        'adresse',
        'status',
        'phone',
        'sexe',
        'media',
        'date_naissance',
        'postal',
        'postal_code_1',
        'ville',
        'zone_id'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];



    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
        'is_secretary',
    ];

    protected static function newFactory()
    {
        return UserFactory::new();
    }


    /**
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($user) {
            $user->name = $user->first_name . ' ' . $user->last_name;
            $user->email = strtolower($user->email);
        });

        static::updating(function ($user) {
            $user->name = $user->first_name . ' ' . $user->last_name;
            $user->email = strtolower($user->email);
        });
    }


    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPasswordNotification($token, $this->email));
    }

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(new StatusInactiveUserScoop());
    }

    /**
     * @param $query
     * @return void
     */
    public function scopeIsAdmin($query)
    {
        $query->whereRelation('roles', 'name', 'admin');
    }

    // Scropes

    /**
     * @param $query
     * @return void
     */
    public function scopeIsStudent($query)
    {
        $query->whereRelation('roles', 'name', 'student');
    }

    /**
     * @param $query
     * @return void
     */
    public function scopeIsMonitor($query)
    {
        $query->whereRelation('roles', 'name', 'monitor');
    }

        public function scopeIsSecretary($query) //  new scope
    {
        $query->whereRelation('roles', 'name', 'secretary');
    }

    public function secretary()
    {
        return $this->hasOne(Secretary::class);
    }
    /**
     * @return HasMany
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    /**
     * @return HasOne
     */
    public function monitor(): HasOne
    {
        return $this->hasOne(Monitor::class);
    }

    /**
     * @return HasOne
     */
    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

 /**
     * @return HasOne
     */

    /**
     * Get the user's first name.
     */
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => $this->first_name . ' ' . $this->last_name,
        );
    }

    /**
     * Check if user is a secretary
     */
    protected function isSecretary(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->hasRole('secretary'),
        );
    }


        /**
     * @return HasOne
     */




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
     * @return string
     */
    public function getImpersonateDisplayText(): string
    {
        return $this->name;
    }

    /**
     * This following is useful for performing user searches through the interface,
     * You can use fields in relations freely using dot notation,
     *
     * example: posts.title, department.name.
     */
    public function getImpersonateSearchField(): array
    {
        return [
            'name',
            'posts.title',
        ];
    }

    /**
     * Ensure passwords are stored using a secure hash.
     * If a raw password (not already a bcrypt/argon hash) is assigned,
     * hash it automatically. This prevents plaintext or legacy hashes
     * from being persisted.
     *
     * @param string|null $value
     * @return void
     */
    protected function setPasswordAttribute(?string $value)
    {
        if (empty($value)) {
            // don't modify when null/empty (useful for updates where password is omitted)
            return;
        }

        // If the value already looks like a modern hash (bcrypt/argon), keep it as-is
        if (Str::startsWith($value, ['$2y$', '$2a$', '$argon2i$', '$argon2id$'])) {
            $this->attributes['password'] = $value;
            return;
        }

        // Otherwise hash it using the framework Hash facade
        $this->attributes['password'] = Hash::make($value);
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'zone_id');
    }

    public function rdvPermisToken(): HasOne
    {
        return $this->hasOne(RdvPermisToken::class);
    }

    public function conversations(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Conversation::class, 'conversation_participants')
            ->withPivot(['joined_at', 'last_read_message_id'])->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }
}
