<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPurchaseLabelsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'labelIds' => ['present', 'array'],
            'labelIds.*' => ['integer', 'exists:labels,id'],
        ];
    }

    /**
     * Additional validation after basic rules pass.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->has('labelIds') || !is_array($this->labelIds)) {
                return;
            }

            // Ensure all label IDs belong to the authenticated user
            $userLabelIds = \App\Models\Label::where('user_id', $this->user()->id)
                ->whereIn('id', $this->labelIds)
                ->pluck('id')
                ->all();

            $invalidIds = array_diff($this->labelIds, $userLabelIds);

            if (!empty($invalidIds)) {
                $validator->errors()->add(
                    'labelIds',
                    'One or more labels do not belong to you.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'labelIds.present' => 'Label IDs array is required.',
            'labelIds.array' => 'Label IDs must be an array.',
            'labelIds.*.integer' => 'Each label ID must be an integer.',
            'labelIds.*.exists' => 'One or more labels do not exist.',
        ];
    }
}
