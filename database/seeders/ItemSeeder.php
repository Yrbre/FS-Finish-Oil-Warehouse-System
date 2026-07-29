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
            ['item_no' => '1-02-001-0005', 'item_desc' => 'S-5', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-001-0006', 'item_desc' => 'C-10', 'item_uom' => 'KG', 'item_glclass' => 'CATALYST'],
            ['item_no' => '1-02-001-0031', 'item_desc' => 'ANTIMONY TRIOXIDE', 'item_uom' => 'KG', 'item_glclass' => 'CATALYST'],
            ['item_no' => '1-02-001-0009', 'item_desc' => 'S-3', 'item_uom' => 'KG', 'item_glclass' => 'CATALYST'],
            ['item_no' => '1-02-001-0011', 'item_desc' => 'D E G IMPORT', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-001-0011', 'item_desc' => 'D E G LOCAL', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-001-0013', 'item_desc' => 'TiO2 (SA-30)', 'item_uom' => 'KG', 'item_glclass' => 'CATALYST'],
            ['item_no' => '1-02-001-0015', 'item_desc' => 'TA-300', 'item_uom' => 'KG', 'item_glclass' => 'CATALYST'],
            ['item_no' => '1-02-001-0016', 'item_desc' => 'C-2 NIHON', 'item_uom' => 'KG', 'item_glclass' => 'CATALYST'],
            ['item_no' => '1-02-001-0017', 'item_desc' => 'C-2 MIKUNI', 'item_uom' => 'KG', 'item_glclass' => 'CATALYST'],
            ['item_no' => '1-02-001-0022', 'item_desc' => 'NET-3', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-001-0023', 'item_desc' => 'ES-40 (K2 EG)', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-001-0027', 'item_desc' => 'SA-1', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0017', 'item_desc' => 'T - 2850 K', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0022', 'item_desc' => 'BC - 8', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0023', 'item_desc' => 'TRICOL M-2080', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0026', 'item_desc' => 'OM-30', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0029', 'item_desc' => 'T - 2750 K', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0030', 'item_desc' => 'T-2000', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0033', 'item_desc' => 'T - 2450 K', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0044', 'item_desc' => 'T-2710-1', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0047', 'item_desc' => 'T-2100 A', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0048', 'item_desc' => 'SF-801 S', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0051', 'item_desc' => 'SF-100', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0052', 'item_desc' => 'D-4146 K-1', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0053', 'item_desc' => 'D-4149-1', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0055', 'item_desc' => 'AEM-5772', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0062', 'item_desc' => 'T-2428 A', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0063', 'item_desc' => 'T-2428 B', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0067', 'item_desc' => 'BOUFUZAI MIZ', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0070', 'item_desc' => 'T-2503', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0087', 'item_desc' => 'TWE-108C', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0093', 'item_desc' => 'BOUFUZAI 5', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0095', 'item_desc' => 'DOWTHERM A', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0096', 'item_desc' => 'TTW-233', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0097', 'item_desc' => 'SF-804 K', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0098', 'item_desc' => 'SF-804 KS', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0099', 'item_desc' => 'TTM 9267-1', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0102', 'item_desc' => 'D-9515', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0105', 'item_desc' => 'D-4175-1', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0108', 'item_desc' => 'SF - 920', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0109', 'item_desc' => 'SF - 930', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0110', 'item_desc' => 'T-890 K', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0113', 'item_desc' => 'D-4239', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0114', 'item_desc' => 'TTM-9390', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0115', 'item_desc' => 'TTM-9391', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0116', 'item_desc' => 'TTM-9392', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0117', 'item_desc' => 'TTM-9393', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0119', 'item_desc' => 'TTM-9383', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0120', 'item_desc' => 'TTM-9384', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0121', 'item_desc' => 'TTM-9385', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0118', 'item_desc' => 'TTW-235', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0059', 'item_desc' => 'E-005', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0137', 'item_desc' => 'T-600', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0124', 'item_desc' => 'ASA-821V', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0126', 'item_desc' => 'SFB-200', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0136', 'item_desc' => 'SF-340 V', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0127', 'item_desc' => 'ASA-370', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0125', 'item_desc' => 'TTM-9124', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0131', 'item_desc' => 'TTM-9413', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0128', 'item_desc' => 'TTM-9414', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0138', 'item_desc' => 'TTM-9416', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0148', 'item_desc' => 'TTM-9417', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0149', 'item_desc' => 'TTM-9438', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0129', 'item_desc' => 'TTM-9192', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0130', 'item_desc' => 'TTM-9196', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0134', 'item_desc' => 'SFB-302K', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0135', 'item_desc' => 'S-82', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0152', 'item_desc' => 'TERON E -26A', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0139', 'item_desc' => 'TWE-121 K-2', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0145', 'item_desc' => 'DELION F-3692', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0146', 'item_desc' => 'DELION F-1971', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
            ['item_no' => '1-02-002-0146', 'item_desc' => 'DELION F-3711', 'item_uom' => 'KG', 'item_glclass' => 'FINISH OIL'],
        ];

        foreach ($items as $item) {
            \App\Models\Item::firstOrCreate($item);
        }
    }
}
