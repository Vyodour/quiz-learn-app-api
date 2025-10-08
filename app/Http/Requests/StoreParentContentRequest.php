<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreParentContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Judul unit konten waj  ib diisi.',
            'title.max' => 'Judul tidak boleh melebihi 255 karakter.',
        ];
    }
}
