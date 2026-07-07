<?php

namespace App\Recommendation;

use App\Models\Product;

class StoreRollup
{
    /**
     * @param  array<array{product:Product,qty:int}>  $lines
     * @return array<array{platform:string,total:int}>
     */
    public function summarize(array $lines): array
    {
        $platforms = [];
        foreach ($lines as $line) {
            foreach ($line['product']->prices as $price) {
                $platforms[$price->platform] = true;
            }
        }

        $rows = [];
        foreach (array_keys($platforms) as $platform) {
            $total = 0;
            foreach ($lines as $line) {
                $price = $line['product']->prices->firstWhere('platform', $platform);
                $unit = $price ? $price->price : $line['product']->cheapestPrice();
                $total += $unit * $line['qty'];
            }
            $rows[] = ['platform' => $platform, 'total' => $total];
        }

        usort($rows, fn ($a, $b) => $a['total'] <=> $b['total']);

        return array_slice($rows, 0, 3);
    }
}
