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
        $kitchen = Category::create(['name' => 'ครัว', 'slug' => 'kitchen', 'sort_order' => 1, 'icon' => '🍳']);
        $bedroom = Category::create(['name' => 'เครื่องนอน', 'slug' => 'bedroom', 'sort_order' => 2, 'icon' => '🛏️']);
        $bathroom = Category::create(['name' => 'ห้องน้ำ', 'slug' => 'bathroom', 'sort_order' => 3, 'icon' => '🚿']);
        $cleaning = Category::create(['name' => 'ทำความสะอาด', 'slug' => 'cleaning', 'sort_order' => 4, 'icon' => '🧹']);
        $pantry = Category::create(['name' => 'ของกินตุน', 'slug' => 'pantry', 'sort_order' => 5, 'icon' => '🥫']);

        $cooks = [['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes', 'often']]];

        // Kitchen
        $riceCooker = $this->product($kitchen, 'rice-cooker', 'หม้อหุงข้าว 1.8 ลิตร', ProductTier::Must, 590, $cooks, icon: '🍚');
        $this->price($riceCooker, ['Shopee' => 590, 'Lazada' => 620, 'Makro' => 650]);
        $rice = $this->product($kitchen, 'rice-5kg', 'ข้าวสาร 5 กก.', ProductTier::Recommended, 180, [], ProductMode::Restock, 'monthly', icon: '🌾');
        $spoon = $this->product($kitchen, 'rice-spoon', 'ทัพพีตักข้าว', ProductTier::Optional, 45, [], icon: '🥄');
        $riceCooker->pairedProducts()->attach([$rice->id, $spoon->id]);

        $pan = $this->product($kitchen, 'frying-pan', 'กระทะ + ตะหลิว', ProductTier::Must, 250, $cooks, icon: '🍳');
        $this->price($pan, ['Shopee' => 250, 'Lazada' => 275]);
        $fridge = $this->product($kitchen, 'mini-fridge', 'ตู้เย็นเล็ก 3.2 คิว', ProductTier::Must, 3990, [], icon: '🧊');
        $this->price($fridge, ['Shopee' => 3990, 'Lazada' => 4290, 'Makro' => 4150]);
        $this->product($kitchen, 'kettle', 'กาต้มน้ำไฟฟ้า', ProductTier::Recommended, 350, [], icon: '🫖');
        $this->product($kitchen, 'plates', 'จานชาม เซ็ต 4', ProductTier::Optional, 180, [], icon: '🍽️');
        $this->product($kitchen, 'glasses', 'แก้วน้ำ 6 ใบ', ProductTier::Optional, 120, [], icon: '🥤');
        $this->product($kitchen, 'microwave', 'ไมโครเวฟ 20 ลิตร', ProductTier::Optional, 1790, [], icon: '🍲');

        // Bedroom
        $mattress = $this->product($bedroom, 'mattress-3-5ft', 'ที่นอน 3.5 ฟุต', ProductTier::Must, 1890, [], icon: '🛏️');
        $this->price($mattress, ['Shopee' => 1890, 'Lazada' => 1990]);
        $this->product($bedroom, 'stand-fan', 'พัดลมตั้งพื้น', ProductTier::Must, 690, [], icon: '🌀');
        $bedding = $this->product($bedroom, 'bedding-set', 'หมอน + ผ้าปูที่นอน', ProductTier::Recommended, 490, [], icon: '🛌');
        $mattress->pairedProducts()->attach($bedding->id);
        $this->product($bedroom, 'clothes-rack', 'ราวแขวนผ้า', ProductTier::Optional, 350, [], icon: '🧥');

        // Bathroom
        $toiletries = $this->product($bathroom, 'toiletries-set', 'ของใช้ห้องน้ำชุดเริ่มต้น', ProductTier::Must, 250, [], icon: '🧴');
        $towel = $this->product($bathroom, 'towels', 'ผ้าเช็ดตัว 2 ผืน', ProductTier::Recommended, 180, [], icon: '🧻');
        $toiletries->pairedProducts()->attach($towel->id);
        $this->product($bathroom, 'drying-rack', 'ที่ตากผ้าราว', ProductTier::Optional, 250, [], icon: '🧺');

        // Cleaning
        $this->product($cleaning, 'detergent', 'ผงซักฟอก', ProductTier::Must, 60, [], ProductMode::Restock, 'weekly', 'occupants', icon: '🧼');
        $this->product($cleaning, 'dish-soap', 'น้ำยาล้างจาน', ProductTier::Must, 45, [], ProductMode::Restock, 'monthly', icon: '🧽');
        $this->product($cleaning, 'trash-bin', 'ถังขยะมีฝา', ProductTier::Must, 120, [], icon: '🗑️');
        $this->product($cleaning, 'broom-set', 'ไม้กวาด + ที่โกยผง', ProductTier::Recommended, 150, [], icon: '🧹');
        $this->product($cleaning, 'cleaning-cloth', 'ผ้าเช็ดทำความสะอาด', ProductTier::Optional, 40, [], ProductMode::Restock, 'monthly', icon: '🧽');

        // Pantry
        $this->product($pantry, 'cooking-oil', 'น้ำมันพืช', ProductTier::Recommended, 60, $cooks, ProductMode::Restock, 'monthly', icon: '🛢️');
        $this->product($pantry, 'seasoning-set', 'เครื่องปรุงชุดเริ่มต้น', ProductTier::Optional, 150, $cooks, icon: '🧂');
        $this->product($pantry, 'instant-noodles', 'บะหมี่กึ่งสำเร็จรูป (แพ็ค)', ProductTier::Optional, 90, [], ProductMode::Restock, 'weekly', icon: '🍜');

        $work = Category::create(['name' => 'ทำงาน', 'slug' => 'work', 'sort_order' => 6, 'icon' => '💼']);
        $this->product($work, 'desk-lamp', 'โคมไฟตั้งโต๊ะ', ProductTier::Recommended, 290, [
            ['field' => 'work_style', 'op' => 'in', 'value' => ['home', 'hybrid']],
        ], icon: '💡');
        $this->product($work, 'laundry-rack', 'ราวตากผ้าในห้อง', ProductTier::Recommended, 350, [
            ['field' => 'laundry', 'op' => 'in', 'value' => ['hand', 'service']],
        ], icon: '🧺');
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
        string $icon = '📦',
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
            'icon' => $icon,
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
