<?php
namespace App\Models\Theory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class TheoryQuestionPartOption extends Model { use HasUuids; protected $guarded = []; protected $casts = ['is_correct' => 'boolean']; }
