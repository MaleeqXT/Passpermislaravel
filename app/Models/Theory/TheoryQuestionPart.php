<?php
namespace App\Models\Theory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class TheoryQuestionPart extends Model { use HasUuids; protected $guarded = []; protected function casts(): array { return ['correct_option_positions' => 'array']; } public function options(): HasMany { return $this->hasMany(TheoryQuestionPartOption::class, 'question_part_id')->orderBy('sort_order'); } }
