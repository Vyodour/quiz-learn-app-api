<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Content;

class UpdateContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        $contentId = $this->route('content') instanceof Content
            ? $this->route('content')->id
            : null;

        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],

            'order_number' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
                Rule::unique('contents', 'order_number')
                    ->ignore($contentId)
                    ->where(function ($query) {
                        $moduleId = $this->route('content')->module_id;
                        return $query->where('module_id', $moduleId);
                    }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul unit konten wajib diisi jika Anda ingin mengubahnya.',
            'title.max' => 'Judul tidak boleh melebihi 255 karakter.',
            'order_number.unique' => 'Nomor urut ini sudah digunakan oleh konten lain dalam modul yang sama.',
        ];
    }
}
