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

    protected function prepareForValidation(): void
    {
        if ($this->has('purchaseTime') && $this->purchaseTime === '') {
            $this->merge(['purchaseTime' => null]);
        }
    }

    public function rules(): array
    {
        return [
            // Header fields - all optional for partial updates
            'shopId' => ['sometimes', 'required', 'integer', 'exists:shops,id'],
            'shopAddressId' => ['sometimes', 'required', 'integer', 'exists:shop_addresses,id'],
            'userPaymentMethodId' => ['nullable', 'integer', 'exists:user_payment_methods,id'],
            'purchaseDate' => ['sometimes', 'required', 'date', 'before_or_equal:today'],
            'purchaseTime' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'status' => ['sometimes', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'receiptNumber' => ['nullable', 'string', 'max:255'],

            // Lines - optional; if provided, replaces all existing lines
            'lines' => ['sometimes', 'array', 'min:1'],
            'lines.*.lineNumber' => ['required_with:lines', 'integer', 'min:1'],
            'lines.*.productId' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['required_with:lines', 'string', 'max:255'],
            'lines.*.quantity' => ['required_with:lines', 'numeric', 'min:0.001', 'max:9999.999'],
            'lines.*.unitPrice' => ['required_with:lines', 'integer', 'min:0'],
            'lines.*.taxRate' => ['required_with:lines', 'numeric', 'min:0', 'max:100'],
            'lines.*.discountPercent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discountAmount' => ['nullable', 'integer', 'min:0'],
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

            // Determine effective shopId (from request or existing purchase)
            $shopId = $this->shopId;
            if (!$shopId && $purchaseId) {
                $purchase = \App\Models\Purchase::find($purchaseId);
                $shopId = $purchase?->shop_id;
            }

            // Ensure shopAddress belongs to the selected shop
            if ($shopId && $this->shopAddressId) {
                $addressBelongsToShop = \App\Models\ShopAddress::where('id', $this->shopAddressId)
                    ->where('shop_id', $shopId)
                    ->exists();

                if (!$addressBelongsToShop) {
                    $validator->errors()->add(
                        'shopAddressId',
                        'The selected address does not belong to the specified shop.'
                    );
                }
            }

            // Ensure line numbers are unique within the purchase
            if ($this->has('lines') && is_array($this->lines)) {
                $lineNumbers = collect($this->lines)->pluck('lineNumber')->filter()->all();
                if (count($lineNumbers) !== count(array_unique($lineNumbers))) {
                    $validator->errors()->add(
                        'lines',
                        'Line numbers must be unique within the purchase.'
                    );
                }
            }

            // Check receiptNumber uniqueness per shop (ignore current purchase)
            if ($shopId && $this->receiptNumber) {
                $exists = \App\Models\Purchase::where('shop_id', $shopId)
                    ->where('receipt_number', $this->receiptNumber)
                    ->where('id', '!=', $purchaseId)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'receiptNumber',
                        'This receipt number already exists for this shop.'
                    );
                }
            }

            // Ensure userPaymentMethod belongs to the authenticated user
            if ($this->userPaymentMethodId) {
                $methodBelongsToUser = \App\Models\UserPaymentMethod::where('id', $this->userPaymentMethodId)
                    ->where('user_id', $this->user()->id)
                    ->exists();

                if (!$methodBelongsToUser) {
                    $validator->errors()->add(
                        'userPaymentMethodId',
                        'The selected payment method does not belong to you.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'shopId.exists' => 'The selected shop does not exist.',
            'shopAddressId.exists' => 'The selected address does not exist.',
            'purchaseDate.before_or_equal' => 'Purchase date cannot be in the future.',
            'purchaseTime.regex' => 'Purchase time must be in HH:MM or HH:MM:SS format.',
            'lines.min' => 'At least one line item is required when updating lines.',
            'lines.*.description.required_with' => 'Line item description is required.',
            'lines.*.quantity.required_with' => 'Line item quantity is required.',
            'lines.*.unitPrice.required_with' => 'Unit price is required.',
            'lines.*.taxRate.required_with' => 'Tax rate is required.',
        ];
    }
}
