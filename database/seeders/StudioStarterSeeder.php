<?php

namespace Database\Seeders;

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;

class StudioStarterSeeder extends Seeder
{
    public function run(): void
    {
        $kitchen = Category::create(['name' => 'ครัว', 'slug' => 'kitchen', 'sort_order' => 1]);
        $bedroom = Category::create(['name' => 'เครื่องนอน', 'slug' => 'bedroom', 'sort_order' => 2]);

        $riceCooker = $this->product($kitchen, 'rice-cooker', 'หม้อหุงข้าว 1.8 ลิตร', ProductTier::Must, 590, [
            ['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes', 'often']],
        ]);
        ProductPrice::create(['product_id' => $riceCooker->id, 'platform' => 'Shopee', 'price' => 590]);
        ProductPrice::create(['product_id' => $riceCooker->id, 'platform' => 'Lazada', 'price' => 620]);

        $rice = $this->product($kitchen, 'rice-5kg', 'ข้าวสาร 5 กก.', ProductTier::Recommended, 180, [], ProductMode::Restock, 'monthly');
        $spoon = $this->product($kitchen, 'rice-spoon', 'ทัพพีตักข้าว', ProductTier::Optional, 45, []);
        $riceCooker->pairedProducts()->attach([$rice->id, $spoon->id]);

        $this->product($bedroom, 'mattress-3-5ft', 'ที่นอน 3.5 ฟุต', ProductTier::Must, 1890, []);
        $this->product($bedroom, 'stand-fan', 'พัดลมตั้งพื้น', ProductTier::Must, 690, []);
        $this->product($kitchen, 'detergent', 'ผงซักฟอก', ProductTier::Must, 60, [], ProductMode::Restock, 'weekly', 'occupants');
    }

    /**
     * @param  array<array{field:string,op:string,value:mixed}>  $triggers
     */
    private function product(
        Category $category,
        string $slug,
        string $name,
        ProductTier $tier,
        int $price,
        array $triggers,
        ProductMode $mode = ProductMode::MoveIn,
        ?string $cadence = null,
        ?string $scalesBy = null,
    ): Product {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'tier' => $tier,
            'mode' => $mode,
            'ref_price' => $price,
            'restock_cadence' => $cadence,
            'qty_scales_by' => $scalesBy,
            'triggers' => $triggers,
        ]);
    }
}
