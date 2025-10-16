<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $rules = [
            'content_id' => ['required', 'integer', 'exists:contents,id'],
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', Rule::in(['lesson', 'quiz', 'challenge'])],
            'order_data' => ['required', 'array'],
            'is_premium' => ['nullable', 'boolean'],
        ];

        $type = $this->input('type');

        switch ($type) {
            case 'lesson':
                $rules = array_merge($rules, [
                    'order_data.body' => [
                        Rule::requiredIf(fn () =>
                            empty($this->input('order_data.video_url')) &&
                            empty($this->input('order_data.attachment_url'))
                        ),
                        'string',
                    ],
                    'order_data.video_url' => ['nullable', 'max:255'],
                    'order_data.attachment_url' => ['nullable', 'max:255'],
                ]);
                break;

            case 'quiz':
                $rules = array_merge($rules, [
                    'order_data.name' => ['required', 'string', 'max:255'],
                    'order_data.passing_score' => ['required', 'integer', 'min:1', 'max:100'],
                    'order_data.time_limit' => ['nullable', 'integer', 'min:1'],
                ]);
                break;

            case 'challenge':
                $rules = array_merge($rules, [
                    'order_data.instruction_body' => ['required', 'string'],
                    'order_data.language' => ['required', Rule::in(['python', 'javascript', 'php', 'java'])],
                    'order_data.initial_code' => ['nullable', 'string'],
                    'order_data.passing_score' => ['required', 'integer', 'min:1'],
                    'order_data.test_cases' => ['required', 'array', 'min:1'],
                    'order_data.test_cases.*.input' => ['required', 'array'],
                    'order_data.test_cases.*.expected' => ['required'],
                ]);
                break;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Content unit title must be filled.',
            'type.in' => 'Content unit type must be one of these: lesson, quiz, or challenge.',
            'content_id.exists' => 'Parent content not found.',
            'order_data.required' => 'Specific content data (order_data) must be filled.',
            'order_data.content_body.required_if' => 'Lesson required one of the content like Text, Video URL, or File URL.',
            'is_premium.boolean' => 'Premium status must be boolean (true or false).'
        ];
    }
}
