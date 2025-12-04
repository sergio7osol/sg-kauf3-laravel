<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller
    }

    public function rules(): array
    {
        return [
            // Header fields - all optional for partial updates
            'shop_id' => ['sometimes', 'required', 'integer', 'exists:shops,id'],
            'shop_address_id' => ['sometimes', 'required', 'integer', 'exists:shop_addresses,id'],
            'user_payment_method_id' => ['nullable', 'integer', 'exists:user_payment_methods,id'],
            'purchase_date' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'receipt_number' => ['nullable', 'string', 'max:255'],

            // Lines - optional; if provided, replaces all existing lines
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.line_number' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['required_with:lines', 'string', 'max:255'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0.001', 'max:9999.999'],
            'lines.*.unit_price' => ['required_with:lines', 'integer', 'min:0'],
            'lines.*.tax_rate' => ['required_with:lines', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Additional validation after basic rules pass.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $purchaseId = $this->route('purchase')?->id ?? $this->route('purchase');

            // Determine effective shop_id (from request or existing purchase)
            $shopId = $this->shop_id;
            if (!$shopId && $purchaseId) {
                $purchase = \App\Models\Purchase::find($purchaseId);
                $shopId = $purchase?->shop_id;
            }

            // Ensure shop_address belongs to the selected shop
            if ($shopId && $this->shop_address_id) {
                $addressBelongsToShop = \App\Models\ShopAddress::where('id', $this->shop_address_id)
                    ->where('shop_id', $shopId)
                    ->exists();

                if (!$addressBelongsToShop) {
                    $validator->errors()->add(
                        'shop_address_id',
                        'The selected address does not belong to the specified shop.'
                    );
                }
            }

            // Ensure line numbers are unique within the purchase
            if ($this->has('lines') && is_array($this->lines)) {
                $lineNumbers = collect($this->lines)->pluck('line_number')->filter()->all();
                if (count($lineNumbers) !== count(array_unique($lineNumbers))) {
                    $validator->errors()->add(
                        'lines',
                        'Line numbers must be unique within the purchase.'
                    );
                }
            }

            // Check receipt_number uniqueness per shop (ignore current purchase)
            if ($shopId && $this->receipt_number) {
                $exists = \App\Models\Purchase::where('shop_id', $shopId)
                    ->where('receipt_number', $this->receipt_number)
                    ->where('id', '!=', $purchaseId)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'receipt_number',
                        'This receipt number already exists for this shop.'
                    );
                }
            }

            // Ensure user_payment_method belongs to the authenticated user
            if ($this->user_payment_method_id) {
                $methodBelongsToUser = \App\Models\UserPaymentMethod::where('id', $this->user_payment_method_id)
                    ->where('user_id', $this->user()->id)
                    ->exists();

                if (!$methodBelongsToUser) {
                    $validator->errors()->add(
                        'user_payment_method_id',
                        'The selected payment method does not belong to you.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'shop_id.exists' => 'The selected shop does not exist.',
            'shop_address_id.exists' => 'The selected address does not exist.',
            'purchase_date.before_or_equal' => 'Purchase date cannot be in the future.',
            'lines.min' => 'At least one line item is required when updating lines.',
            'lines.*.description.required_with' => 'Line item description is required.',
            'lines.*.quantity.required_with' => 'Line item quantity is required.',
            'lines.*.unit_price.required_with' => 'Unit price is required.',
            'lines.*.tax_rate.required_with' => 'Tax rate is required.',
        ];
    }
}
