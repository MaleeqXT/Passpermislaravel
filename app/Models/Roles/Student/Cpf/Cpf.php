<?php

namespace App\Models\Roles\Student\Cpf;

use App\Enums\V2\Student\Cpf\DocumentCpfEtatEnum;
use App\Models\Roles\Admin\Offer\Offer;
use App\Models\Roles\Student\User\Student;
use App\Models\User;
use Database\Factories\Roles\Student\Cpf\CpfFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;


class Cpf extends Model
{
    use HasFactory, HasUuids;


    protected static $unguarded = true;
    protected $with = ['student', 'offer'];

    protected static function newFactory()
    {
        return CpfFactory::new();
    }

    /**
     * @return BelongsTo
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }


    /**
     * @return HasMany
     */
    public function documents(): HasMany
    {
        return $this->hasMany(CpfVerificationDocument::class);
    }


    public function documentQuestionnaireEntreFormation(): HasOne
    {
        return $this->hasOne(CpfVerificationDocument::class)->where('document', DocumentCpfEtatEnum::questionnaire_entre_formation->value);
    }

    public function documentAttestationHonneur(): HasOne
    {
        return $this->hasOne(CpfVerificationDocument::class)->where('document', DocumentCpfEtatEnum::attestation_honneur->value);
    }

    public function documentAttestationFinFormation(): HasOne
    {
        return $this->hasOne(CpfVerificationDocument::class)->where('document', DocumentCpfEtatEnum::attestation_fin_formation->value);
    }

    public function documentQuestionnaireSatisfaction(): HasOne
    {
        return $this->hasOne(CpfVerificationDocument::class)->where('document', DocumentCpfEtatEnum::questionnaire_satisfaction->value);
    }

    public function documentSuiviPro(): HasOne
    {
        return $this->hasOne(CpfVerificationDocument::class)->where('document', DocumentCpfEtatEnum::suivi_pro->value);
    }

    public function documentSuiviPro2(): HasOne
    {
        return $this->hasOne(CpfVerificationDocument::class)->where('document', DocumentCpfEtatEnum::suivi_pro2->value);
    }
}
