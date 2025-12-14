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
        if ($this->has('purchase_time') && $this->purchase_time === '') {
            $this->merge(['purchase_time' => null]);
        }
    }

    public function rules(): array
    {
        return [
            'shop_id' => ['required', 'integer', 'exists:shops,id'],
            'shop_address_id' => ['required', 'integer', 'exists:shop_addresses,id'],
            'user_payment_method_id' => ['nullable', 'integer', 'exists:user_payment_methods,id'],
            'purchase_date' => ['required', 'date', 'before_or_equal:today'],
            'purchase_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['nullable', Rule::in(['draft', 'confirmed', 'cancelled'])],
            'notes' => ['nullable', 'string', 'max:5000'],
            'receipt_number' => ['nullable', 'string', 'max:255'],
            
            // Line items array
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.line_number' => ['required', 'integer', 'min:1'],
            'lines.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'lines.*.description' => ['required', 'string', 'max:255'],
            'lines.*.quantity' => ['required', 'numeric', 'min:0.001', 'max:9999.999'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0'],
            'lines.*.tax_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lines.*.discount_amount' => ['nullable', 'integer', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Additional validation after basic rules pass.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Ensure shop_address belongs to the selected shop
            if ($this->shop_id && $this->shop_address_id) {
                $addressBelongsToShop = \App\Models\ShopAddress::where('id', $this->shop_address_id)
                    ->where('shop_id', $this->shop_id)
                    ->exists();

                if (!$addressBelongsToShop) {
                    $validator->errors()->add(
                        'shop_address_id',
                        'The selected address does not belong to the specified shop.'
                    );
                }
            }

            // Ensure line numbers are unique within the purchase
            if ($this->has('lines')) {
                $lineNumbers = collect($this->lines)->pluck('line_number')->all();
                if (count($lineNumbers) !== count(array_unique($lineNumbers))) {
                    $validator->errors()->add(
                        'lines',
                        'Line numbers must be unique within the purchase.'
                    );
                }
            }

            // Check receipt_number uniqueness per shop (if provided)
            if ($this->shop_id && $this->receipt_number) {
                $exists = \App\Models\Purchase::where('shop_id', $this->shop_id)
                    ->where('receipt_number', $this->receipt_number)
                    ->exists();

                if ($exists) {
                    $validator->errors()->add(
                        'receipt_number',
                        'This receipt number already exists for this shop.'
                    );
                }
            }

            // Validate discount fields are mutually exclusive
            if ($this->has('lines')) {
                foreach ($this->lines as $index => $line) {
                    $hasPercent = isset($line['discount_percent']) && $line['discount_percent'] > 0;
                    $hasAmount = isset($line['discount_amount']) && $line['discount_amount'] > 0;
                    
                    if ($hasPercent && $hasAmount) {
                        $validator->errors()->add(
                            "lines.{$index}.discount_amount",
                            'Cannot specify both discount_percent and discount_amount. Use one or the other.'
                        );
                    }
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
            'shop_id.required' => 'Please select a shop.',
            'shop_address_id.required' => 'Please select a shop address.',
            'purchase_date.required' => 'Purchase date is required.',
            'purchase_date.before_or_equal' => 'Purchase date cannot be in the future.',
            'purchase_time.regex' => 'Purchase time must be in HH:MM or HH:MM:SS format.',
            'lines.required' => 'At least one line item is required.',
            'lines.min' => 'At least one line item is required.',
            'lines.*.description.required' => 'Line item description is required.',
            'lines.*.quantity.required' => 'Line item quantity is required.',
            'lines.*.quantity.min' => 'Quantity must be greater than zero.',
            'lines.*.unit_price.required' => 'Unit price is required.',
            'lines.*.unit_price.min' => 'Unit price must be at least 0.',
            'lines.*.tax_rate.required' => 'Tax rate is required.',
            'lines.*.discount_amount.min' => 'Discount amount must be at least 0.',
        ];
    }
}
