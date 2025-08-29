<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_name' => ['required', 'string', 'max:255'],
            'quantity_in_stock' => ['required', 'integer', 'min:0'],
            'price_per_item' => ['required', 'numeric', 'min:0'],
        ];
    }
}


