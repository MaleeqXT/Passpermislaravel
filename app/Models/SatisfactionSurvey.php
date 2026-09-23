<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
class SatisfactionSurvey extends Model { use HasUuids; protected $guarded=[]; protected $casts=['is_active'=>'boolean']; public function questions(){ return $this->hasMany(SatisfactionQuestion::class,'survey_id')->orderBy('sort_order'); } public function responses(){ return $this->hasMany(SatisfactionResponse::class,'survey_id'); } }
