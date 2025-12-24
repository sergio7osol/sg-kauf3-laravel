<?php

namespace App\Http\Requests;

use App\Enums\CountryCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShopAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $shop = $this->route('shop');

        return [
            'country' => ['nullable', Rule::enum(CountryCode::class)],
            'postalCode' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:120'],
            'street' => ['required', 'string', 'max:150'],
            'houseNumber' => ['required', 'string', 'max:25'],
            'isPrimary' => ['nullable', 'boolean'],
            'displayOrder' => ['nullable', 'integer', 'min:0', 'max:255'],
            'isActive' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Additional validation after basic rules pass.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $shop = $this->route('shop');
            
            // Check uniqueness constraint: shop_id + postal_code + street + house_number
            $exists = \App\Models\ShopAddress::where('shop_id', $shop->id)
                ->where('postal_code', $this->postalCode)
                ->where('street', $this->street)
                ->where('house_number', $this->houseNumber)
                ->exists();

            if ($exists) {
                $validator->errors()->add(
                    'address',
                    'An address with this postal code, street, and house number already exists for this shop.'
                );
            }
        });
    }

    /**
     * Get validated data converted to snake_case for model.
     */
    public function validatedSnakeCase(): array
    {
        $validated = $this->validated();

        return [
            'country' => $validated['country'] ?? CountryCode::GERMANY->value,
            'postal_code' => $validated['postalCode'],
            'city' => $validated['city'],
            'street' => $validated['street'],
            'house_number' => $validated['houseNumber'],
            'is_primary' => $validated['isPrimary'] ?? false,
            'display_order' => $validated['displayOrder'] ?? null,
            'is_active' => $validated['isActive'] ?? true,
        ];
    }
}
