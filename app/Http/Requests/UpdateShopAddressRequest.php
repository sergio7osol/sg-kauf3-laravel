<?php

namespace App\Http\Requests;

use App\Enums\CountryCode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShopAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'country' => ['nullable', Rule::enum(CountryCode::class)],
            'postalCode' => ['sometimes', 'required', 'string', 'max:20'],
            'city' => ['sometimes', 'required', 'string', 'max:120'],
            'street' => ['sometimes', 'required', 'string', 'max:150'],
            'houseNumber' => ['sometimes', 'required', 'string', 'max:25'],
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
            $address = $this->route('address');

            // Only validate uniqueness if address fields are being updated
            $postalCode = $this->postalCode ?? $address->postal_code;
            $street = $this->street ?? $address->street;
            $houseNumber = $this->houseNumber ?? $address->house_number;

            // Check uniqueness constraint, excluding current address
            $exists = \App\Models\ShopAddress::where('shop_id', $shop->id)
                ->where('postal_code', $postalCode)
                ->where('street', $street)
                ->where('house_number', $houseNumber)
                ->where('id', '!=', $address->id)
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
        $data = [];

        if (isset($validated['country'])) {
            $data['country'] = $validated['country'];
        }
        if (isset($validated['postalCode'])) {
            $data['postal_code'] = $validated['postalCode'];
        }
        if (isset($validated['city'])) {
            $data['city'] = $validated['city'];
        }
        if (isset($validated['street'])) {
            $data['street'] = $validated['street'];
        }
        if (isset($validated['houseNumber'])) {
            $data['house_number'] = $validated['houseNumber'];
        }
        if (isset($validated['isPrimary'])) {
            $data['is_primary'] = $validated['isPrimary'];
        }
        if (isset($validated['displayOrder'])) {
            $data['display_order'] = $validated['displayOrder'];
        }
        if (isset($validated['isActive'])) {
            $data['is_active'] = $validated['isActive'];
        }

        return $data;
    }
}
