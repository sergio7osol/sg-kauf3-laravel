<?php

namespace App\Http\Requests;

use App\Enums\CountryCode;
use App\Enums\PurchaseChannel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:shops,name'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:shops,slug'],
            'type' => ['required', Rule::enum(PurchaseChannel::class)],
            'country' => ['required', Rule::enum(CountryCode::class)],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
