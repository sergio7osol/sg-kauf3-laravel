<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLabelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('labels', 'name')
                    ->where('user_id', $this->user()->id)
                    ->ignore($this->route('label')),
            ],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Label name is required.',
            'name.max' => 'Label name cannot exceed 30 characters.',
            'name.unique' => 'You already have a label with this name.',
        ];
    }
}
