<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
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
            'shopId' => ['required', 'integer', 'exists:shops,id'],
            'shopAddressId' => ['required', 'integer', 'exists:shop_addresses,id'],
            'userPaymentMethodId' => ['nullable', 'integer', 'exists:user_payment_methods,id'],
            'purchaseDate' => ['required', 'date', 'before_or_equal:today'],
            'purchaseTime' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'receiptNumber' => ['nullable', 'string', 'max:255'],
            
            // Line items array
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.lineNumber' => ['required', 'integer', 'min:1'],
            'lines.*.productId' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:9999.999'],
            'lines.*.unitPrice' => ['required', 'integer', 'min:0'],
            'lines.*.taxRate' => ['required', 'numeric', 'min:0', 'max:100'],
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
            // Ensure shopAddress belongs to the selected shop
            if ($this->shopId && $this->shopAddressId) {
                $addressBelongsToShop = \App\Models\ShopAddress::where('id', $this->shopAddressId)
                    ->where('shop_id', $this->shopId)
                    ->exists();

                if (!$addressBelongsToShop) {
                    $validator->errors()->add(
                        'shopAddressId',
                        'The selected address does not belong to the specified shop.'
                    );
                }
            }

            // Ensure line numbers are unique within the purchase
            if ($this->has('lines')) {
                $lineNumbers = collect($this->lines)->pluck('lineNumber')->all();
                if (count($lineNumbers) !== count(array_unique($lineNumbers))) {
                    $validator->errors()->add(
                        'lines',
                        'Line numbers must be unique within the purchase.'
                    );
                }
            }

            // Check receiptNumber uniqueness per shop (if provided)
            if ($this->shopId && $this->receiptNumber) {
                $exists = \App\Models\Purchase::where('shop_id', $this->shopId)
                    ->where('receipt_number', $this->receiptNumber)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'receiptNumber',
                        'This receipt number already exists for this shop.'
                    );
                }
            }

            // Validate discount fields are mutually exclusive
            if ($this->has('lines')) {
                foreach ($this->lines as $index => $line) {
                    $hasPercent = isset($line['discountPercent']) && $line['discountPercent'] > 0;
                    $hasAmount = isset($line['discountAmount']) && $line['discountAmount'] > 0;
                    
                    if ($hasPercent && $hasAmount) {
                        $validator->errors()->add(
                            "lines.{$index}.discountAmount",
                            'Cannot specify both discountPercent and discountAmount. Use one or the other.'
                        );
                    }
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
            'shopId.required' => 'Please select a shop.',
            'shopAddressId.required' => 'Please select a shop address.',
            'purchaseDate.required' => 'Purchase date is required.',
            'purchaseDate.before_or_equal' => 'Purchase date cannot be in the future.',
            'purchaseTime.regex' => 'Purchase time must be in HH:MM or HH:MM:SS format.',
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
            'lines.*.description.required' => 'Line item description is required.',
            'lines.*.quantity.required' => 'Line item quantity is required.',
            'lines.*.quantity.min' => 'Quantity must be greater than zero.',
            'lines.*.unitPrice.required' => 'Unit price is required.',
            'lines.*.unitPrice.min' => 'Unit price must be at least 0.',
            'lines.*.taxRate.required' => 'Tax rate is required.',
            'lines.*.discountAmount.min' => 'Discount amount must be at least 0.',
        ];
    }
}
