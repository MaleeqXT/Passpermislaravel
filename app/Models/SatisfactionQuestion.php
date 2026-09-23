<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class SatisfactionQuestion extends Model { use HasUuids; protected $guarded=[]; protected $casts=['options'=>'array','is_required'=>'boolean','is_active'=>'boolean']; public function survey(){ return $this->belongsTo(SatisfactionSurvey::class,'survey_id'); } public function answers(){ return $this->hasMany(SatisfactionAnswer::class,'question_id'); } }
