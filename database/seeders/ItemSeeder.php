<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            ['item_no' => 'ITM001', 'item_desc' => 'Item 1', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => 'ITM002', 'item_desc' => 'Item 2', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => 'ITM003', 'item_desc' => 'Item 3', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
        ];

        foreach ($items as $item) {
            \App\Models\Item::firstOrCreate($item);
        }
    }
}
