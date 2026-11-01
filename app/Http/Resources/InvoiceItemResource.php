<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin InvoiceItem
 */
class InvoiceItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'description' => $this->description,
            'quantity' => (float) $this->quantity,
            'unit_price' => (int) $this->unit_price,
            'discount' => (int) $this->discount,
            'tax_rate' => (float) $this->tax_rate,
            'line_subtotal' => (int) $this->line_subtotal,
            'line_tax' => (int) $this->line_tax,
            'line_total' => (int) $this->line_total,
            'position' => (int) $this->position,
        ];
    }
}
