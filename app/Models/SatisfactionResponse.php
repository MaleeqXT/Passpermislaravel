<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class SatisfactionResponse extends Model { use HasUuids; protected $guarded=[]; protected $casts=['google_clicked'=>'boolean','google_clicked_at'=>'datetime','submitted_at'=>'datetime']; public function survey(){ return $this->belongsTo(SatisfactionSurvey::class,'survey_id'); } public function candidate(){ return $this->belongsTo(\App\Models\Roles\Student\User\Student::class,'candidate_id'); } public function offer(){ return $this->belongsTo(\App\Models\Roles\Admin\Offer\Offer::class,'offer_id'); } public function answers(){ return $this->hasMany(SatisfactionAnswer::class,'response_id'); } }
