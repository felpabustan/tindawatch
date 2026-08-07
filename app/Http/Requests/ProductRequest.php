<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer'],
            'unit' => ['required', 'in:piece,sachet,case,kilo'],
            'pieces_per_case' => ['nullable', 'integer', 'min:1'],
            'cost_price' => ['required', 'numeric', 'min:0'],
            'sell_price' => ['required', 'numeric', 'min:0'],
            'reorder_threshold' => ['required', 'integer', 'min:0'],
            'stock_qty' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
