<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema; // Schemaファサードをインポート

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        // 安全装置を一時的に無効化
        Schema::disableForeignKeyConstraints();

        // 既存のデータをクリア
        DB::table('stores')->truncate();

        // 安全装置を再度有効化
        Schema::enableForeignKeyConstraints();

        $stores = [
            ['name' => 'A店', 'type' => 'physical'],
            ['name' => 'B店', 'type' => 'physical'],
            ['name' => 'C店', 'type' => 'physical'],
            ['name' => 'オンラインストア', 'type' => 'online'],
        ];

        DB::table('stores')->insert($stores);
    }
}