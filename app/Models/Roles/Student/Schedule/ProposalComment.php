<?php

namespace App\Models\Roles\Student\Schedule;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProposalComment extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = [];

      protected $fillable = ['proposal_id', 'comment'];

    public $incrementing = false;
    protected $keyType = 'string';

    public function proposal()
    {
        return $this->belongsTo(TrainingProposal::class, 'proposal_id');
    }
}
