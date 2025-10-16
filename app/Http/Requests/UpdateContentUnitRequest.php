<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ContentUnitOrder;

class UpdateContentUnitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $unitOrder = $this->route('contentUnitOrder');

        $fullType = $unitOrder->ordered_unit_type;
        $shortType = strtolower(class_basename($fullType));

        $rules = [
            'order_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('content_unit_order', 'order_number')
                    ->ignore($unitOrder->id)
                    ->where(fn ($query) => $query->where('content_id', $unitOrder->content_id)),
            ],

            'is_premium' => ['sometimes', 'nullable', 'boolean'],

            'order_data' => ['sometimes', 'required', 'array'],
        ];

        if ($this->has('order_data')) {
            $contentableType = match($shortType) {
                'lesson' => 'lesson',
                'quizinformation' => 'quiz',
                'codechallenge' => 'challenge',
                default => null,
            };

            if ($contentableType === 'lesson') {
                $rules = array_merge($rules, [
                    'order_data.body' => ['sometimes', 'nullable', 'string'],
                    'order_data.video_url' => ['sometimes', 'nullable', 'max:255'],
                    'order_data.attachment_url' => ['sometimes', 'nullable', 'max:255'],
                ]);
            }

            if ($contentableType === 'quiz') {
                $rules = array_merge($rules, [
                    'order_data.name' => ['sometimes', 'required', 'string', 'max:255'],
                    'order_data.passing_score' => ['sometimes', 'required', 'integer', 'min:1', 'max:100'],
                    'order_data.time_limit' => ['sometimes', 'nullable', 'integer', 'min:1'],
                ]);
            }

            if ($contentableType === 'challenge') {
                $rules = array_merge($rules, [
                    'order_data.instruction_body' => ['sometimes', 'required', 'string'],
                    'order_data.language' => ['sometimes', 'required', Rule::in(['python', 'javascript', 'php', 'java'])],
                    'order_data.initial_code' => ['sometimes', 'nullable', 'string'],
                    'order_data.passing_score' => ['sometimes', 'required', 'integer', 'min:1'],
                    'order_data.test_cases' => ['sometimes', 'required', 'array', 'min:1'],
                    'order_data.test_cases.*.input' => ['sometimes', 'required', 'array'],
                    'order_data.test_cases.*.expected' => ['sometimes', 'required'],
                ]);
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'order_number.unique' => 'This order number has been used by other content in the same parent.',
            'order_number.integer' => 'Order number must be integer.',
            'is_premium.boolean' => 'Premium status must be boolean(true or false).',
            'order_data.required' => 'If you send the order_data it must not empty.',
        ];
    }
}
