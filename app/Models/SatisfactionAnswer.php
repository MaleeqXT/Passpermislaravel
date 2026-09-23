<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids; use Illuminate\Database\Eloquent\Model;
class SatisfactionAnswer extends Model { use HasUuids; protected $guarded=[]; public function response(){ return $this->belongsTo(SatisfactionResponse::class,'response_id'); } public function question(){ return $this->belongsTo(SatisfactionQuestion::class,'question_id'); } }
