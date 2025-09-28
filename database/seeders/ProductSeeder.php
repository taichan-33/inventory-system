<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema; // Schemaファサードをインポート


class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Product::truncate();
        Schema::enableForeignKeyConstraints();

        $products = [
            ['name' => 'クルー丈 リブソックス (白)', 'sku' => 'SOCKS001', 'price' => 500],
            ['name' => 'クルー丈 リブソックス (黒)', 'sku' => 'SOCKS002', 'price' => 500],
            ['name' => 'スニーカー用 アンクルソックス', 'sku' => 'SOCKS003', 'price' => 450],
            ['name' => '5本指 ビジネスソックス', 'sku' => 'SOCKS004', 'price' => 800],
            ['name' => 'ウール混 厚手ソックス (グレー)', 'sku' => 'SOCKS005', 'price' => 1200],
            ['name' => 'ショート丈 カラフルソックス (赤)', 'sku' => 'SOCKS006', 'price' => 400],
            ['name' => 'ショート丈 カラフルソックス (青)', 'sku' => 'SOCKS007', 'price' => 400],
            ['name' => '抗菌 防臭ソックス (ビジネス用)', 'sku' => 'SOCKS008', 'price' => 750],
            ['name' => 'ハイソックス スポーツ用 (白)', 'sku' => 'SOCKS009', 'price' => 600],
            ['name' => 'ハイソックス スポーツ用 (黒)', 'sku' => 'SOCKS010', 'price' => 600],
            ['name' => 'シルク混 ソックス (ベージュ)', 'sku' => 'SOCKS011', 'price' => 1500],
            ['name' => 'カシミヤ混 ラグジュアリーソックス', 'sku' => 'SOCKS012', 'price' => 2500],
            ['name' => 'ライン入り ストリートソックス', 'sku' => 'SOCKS013', 'price' => 700],
            ['name' => 'メッシュ 通気性ソックス (夏用)', 'sku' => 'SOCKS014', 'price' => 550],
            ['name' => 'ヒートテック風 保温ソックス (冬用)', 'sku' => 'SOCKS015', 'price' => 900],
            ['name' => '滑り止め付き キッズソックス', 'sku' => 'SOCKS016', 'price' => 350],
            ['name' => 'キャラクター柄 キッズソックス', 'sku' => 'SOCKS017', 'price' => 400],
            ['name' => 'フットカバー 浅履きソックス', 'sku' => 'SOCKS018', 'price' => 300],
            ['name' => 'アーチサポート付き スポーツソックス', 'sku' => 'SOCKS019', 'price' => 850],
            ['name' => 'ビジネス用 リブソックス (ネイビー)', 'sku' => 'SOCKS020', 'price' => 700],
        ];


        // データをループ処理で挿入
        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
