<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ParseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Auth handled by middleware
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:pdf,png,jpg,jpeg,webp',
                'max:10240', // 10MB max
            ],
            'debug' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please upload a receipt file.',
            'file.mimes' => 'Receipt must be a PDF or image (PNG, JPG, JPEG, WebP).',
            'file.max' => 'Receipt file must be less than 10MB.',
        ];
    }
}
