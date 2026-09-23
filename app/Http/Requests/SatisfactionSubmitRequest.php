<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class SatisfactionSubmitRequest extends FormRequest { public function authorize(): bool { return (bool)$this->user(); } public function rules(): array { return ['answers'=>'required|array','answers.*'=>'array','answers.*.question_id'=>'required|uuid','answers.*.rating'=>'nullable|integer|min:1|max:5','answers.*.selected_option'=>'nullable|string|max:255','answers.*.answer_text'=>'nullable|string|max:5000','google_choice'=>'nullable|in:yes,later,no']; } }
