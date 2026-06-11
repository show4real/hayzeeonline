<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $brands = [
            'apple',
            'dell',
            'hp',
            'lenovo',
            'asus',
            'acer',
            'msi',
            'microsoft',
            'samsung',
            'lg',
            'razer',
            'huawei',
            'gigabyte',
            'sony',
            'toshiba',
            'google',
            'fujitsu',
            'chuwi',
            'xiaomi',
            'tecno',
            'infinix',
            'oppo',
            'vivo',
            'oneplus',
            'realme',
            'nokia',
            'motorola',
            'itel',
            'anker',
            'oraimo',
            'jbl',
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($brand)],
                ['name' => Str::title($brand), 'image_url' => '']
            );
        }
    }
}
