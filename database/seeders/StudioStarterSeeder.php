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
    /** @var array<string, Product> */
    private array $bySlug = [];

    public function run(): void
    {
        $kitchen = Category::create(['name' => 'ครัว', 'slug' => 'kitchen', 'sort_order' => 1]);
        $bedroom = Category::create(['name' => 'เครื่องนอน', 'slug' => 'bedroom', 'sort_order' => 2]);
        $bathroom = Category::create(['name' => 'ห้องน้ำ', 'slug' => 'bathroom', 'sort_order' => 3]);
        $cleaning = Category::create(['name' => 'ทำความสะอาด', 'slug' => 'cleaning', 'sort_order' => 4]);
        $pantry = Category::create(['name' => 'ของกินตุน', 'slug' => 'pantry', 'sort_order' => 5]);

        $cooks = [['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes', 'often']]];

        // Kitchen
        $riceCooker = $this->product($kitchen, 'rice-cooker', 'หม้อหุงข้าว 1.8 ลิตร', ProductTier::Must, 590, $cooks);
        $this->price($riceCooker, ['Shopee' => 590, 'Lazada' => 620, 'Makro' => 650]);
        $rice = $this->product($kitchen, 'rice-5kg', 'ข้าวสาร 5 กก.', ProductTier::Recommended, 180, [], ProductMode::Restock, 'monthly');
        $spoon = $this->product($kitchen, 'rice-spoon', 'ทัพพีตักข้าว', ProductTier::Optional, 45, []);
        $riceCooker->pairedProducts()->attach([$rice->id, $spoon->id]);

        $pan = $this->product($kitchen, 'frying-pan', 'กระทะ + ตะหลิว', ProductTier::Must, 250, $cooks);
        $this->price($pan, ['Shopee' => 250, 'Lazada' => 275]);
        $fridge = $this->product($kitchen, 'mini-fridge', 'ตู้เย็นเล็ก 3.2 คิว', ProductTier::Must, 3990, []);
        $this->price($fridge, ['Shopee' => 3990, 'Lazada' => 4290, 'Makro' => 4150]);
        $this->product($kitchen, 'kettle', 'กาต้มน้ำไฟฟ้า', ProductTier::Recommended, 350, []);
        $this->product($kitchen, 'plates', 'จานชาม เซ็ต 4', ProductTier::Optional, 180, []);
        $this->product($kitchen, 'glasses', 'แก้วน้ำ 6 ใบ', ProductTier::Optional, 120, []);
        $this->product($kitchen, 'microwave', 'ไมโครเวฟ 20 ลิตร', ProductTier::Optional, 1790, []);

        // Bedroom
        $mattress = $this->product($bedroom, 'mattress-3-5ft', 'ที่นอน 3.5 ฟุต', ProductTier::Must, 1890, []);
        $this->price($mattress, ['Shopee' => 1890, 'Lazada' => 1990]);
        $this->product($bedroom, 'stand-fan', 'พัดลมตั้งพื้น', ProductTier::Must, 690, []);
        $bedding = $this->product($bedroom, 'bedding-set', 'หมอน + ผ้าปูที่นอน', ProductTier::Recommended, 490, []);
        $mattress->pairedProducts()->attach($bedding->id);
        $this->product($bedroom, 'clothes-rack', 'ราวแขวนผ้า', ProductTier::Optional, 350, []);

        // Bathroom
        $toiletries = $this->product($bathroom, 'toiletries-set', 'ของใช้ห้องน้ำชุดเริ่มต้น', ProductTier::Must, 250, []);
        $towel = $this->product($bathroom, 'towels', 'ผ้าเช็ดตัว 2 ผืน', ProductTier::Recommended, 180, []);
        $toiletries->pairedProducts()->attach($towel->id);
        $this->product($bathroom, 'drying-rack', 'ที่ตากผ้าราว', ProductTier::Optional, 250, []);

        // Cleaning
        $this->product($cleaning, 'detergent', 'ผงซักฟอก', ProductTier::Must, 60, [], ProductMode::Restock, 'weekly', 'occupants');
        $this->product($cleaning, 'dish-soap', 'น้ำยาล้างจาน', ProductTier::Must, 45, [], ProductMode::Restock, 'monthly');
        $this->product($cleaning, 'trash-bin', 'ถังขยะมีฝา', ProductTier::Must, 120, []);
        $this->product($cleaning, 'broom-set', 'ไม้กวาด + ที่โกยผง', ProductTier::Recommended, 150, []);
        $this->product($cleaning, 'cleaning-cloth', 'ผ้าเช็ดทำความสะอาด', ProductTier::Optional, 40, [], ProductMode::Restock, 'monthly');

        // Pantry
        $this->product($pantry, 'cooking-oil', 'น้ำมันพืช', ProductTier::Recommended, 60, $cooks, ProductMode::Restock, 'monthly');
        $this->product($pantry, 'seasoning-set', 'เครื่องปรุงชุดเริ่มต้น', ProductTier::Optional, 150, $cooks);
        $this->product($pantry, 'instant-noodles', 'บะหมี่กึ่งสำเร็จรูป (แพ็ค)', ProductTier::Optional, 90, [], ProductMode::Restock, 'weekly');
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
        $product = Product::create([
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

        $this->bySlug[$slug] = $product;

        return $product;
    }

    /**
     * @param  array<string, int>  $platformPrices
     */
    private function price(Product $product, array $platformPrices): void
    {
        foreach ($platformPrices as $platform => $price) {
            ProductPrice::create(['product_id' => $product->id, 'platform' => $platform, 'price' => $price]);
        }
    }
}
