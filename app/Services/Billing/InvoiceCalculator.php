<?php

declare(strict_types=1);

namespace App\Services\Billing;

class InvoiceCalculator
{
    /**
     * @param array<int, array{
     *     quantity: float|int|numeric-string,
     *     unit_price: int|numeric-string,
     *     discount?: int|numeric-string|null,
     *     tax_rate?: float|int|numeric-string|null,
     *     description?: string|null,
     *     product_id?: int|numeric-string|null,
     *     position?: int|null
     * }> $items
     * @return array{
     *     subtotal: int,
     *     discount_total: int,
     *     tax_total: int,
     *     total: int,
     *     items: array<int, array{
     *         description: string,
     *         product_id: int|null,
     *         quantity: float,
     *         unit_price: int,
     *         discount: int,
     *         tax_rate: float,
     *         line_subtotal: int,
     *         line_tax: int,
     *         line_total: int,
     *         position: int
     *     }>
     * }
     */
    public function calculate(array $items): array
    {
        $calculatedItems = [];
        $subtotal = 0;
        $discountTotal = 0;
        $taxTotal = 0;

        foreach ($items as $index => $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            $unitPrice = (int) ($item['unit_price'] ?? 0);
            $discountInput = (int) ($item['discount'] ?? 0);
            $taxRate = (float) ($item['tax_rate'] ?? 0);

            $lineGross = (int) round($quantity * $unitPrice);
            $lineDiscount = min($lineGross, max(0, $discountInput));
            $lineTaxable = $lineGross - $lineDiscount;

            // Round half up, once per line, on the tax amount
            $lineTax = (int) round($lineTaxable * ($taxRate / 100), 0, PHP_ROUND_HALF_UP);
            $lineTotal = $lineTaxable + $lineTax;

            $subtotal += $lineGross;
            $discountTotal += $lineDiscount;
            $taxTotal += $lineTax;

            $calculatedItems[] = [
                'description' => (string) ($item['description'] ?? ''),
                'product_id' => isset($item['product_id']) && $item['product_id'] !== '' ? (int) $item['product_id'] : null,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $lineDiscount,
                'tax_rate' => $taxRate,
                'line_subtotal' => $lineGross,
                'line_tax' => $lineTax,
                'line_total' => $lineTotal,
                'position' => (int) ($item['position'] ?? $index),
            ];
        }

        $total = $subtotal - $discountTotal + $taxTotal;

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'total' => $total,
            'items' => $calculatedItems,
        ];
    }
}

