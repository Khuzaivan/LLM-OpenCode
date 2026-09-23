<?php

namespace Database\Seeders;

use App\Models\Produk;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['PRD-001', 'Laptop Asus Vivobook 14', 'Elektronik', 8500000, 10, 'Laptop kerja dengan prosesor Intel Core i5, RAM 8GB, SSD 512GB'],
            ['PRD-002', 'Mouse Wireless Logitech M331', 'Aksesoris', 185000, 25, 'Mouse silent click dengan koneksi wireless 2.4GHz'],
            ['PRD-003', 'Keyboard Mechanical RGB Gaming', 'Aksesoris', 450000, 15, 'Keyboard mechanical switch biru dengan lampu RGB'],
            ['PRD-004', 'Monitor LED LG 24 Inch Full HD', 'Elektronik', 1650000, 8, 'Monitor IPS 75Hz dengan bezel tipis dan port HDMI'],
            ['PRD-005', 'Headset Gaming HyperX Cloud Stinger', 'Audio', 680000, 12, 'Headset gaming dengan microphone noise cancelling'],
            ['PRD-006', 'Webcam Logitech C922 Pro HD', 'Aksesoris', 1250000, 7, 'Webcam streaming Full HD 1080p dengan dual microphone'],
            ['PRD-007', 'SSD Eksternal Samsung T7 1TB', 'Penyimpanan', 1950000, 14, 'SSD portabel dengan koneksi USB 3.2'],
            ['PRD-008', 'Speaker Bluetooth JBL Go 3', 'Audio', 550000, 20, 'Speaker mini tahan air dengan suara bass jernih'],
        ];

        foreach ($products as [$code, $name, $category, $price, $stock, $description]) {
            Produk::updateOrCreate(
                ['kode_produk' => $code],
                [
                    'nama_produk' => $name,
                    'kategori' => $category,
                    'harga' => $price,
                    'stok' => $stock,
                    'deskripsi' => $description,
                ],
            );
        }

        $this->command->info('8 produk berhasil disiapkan di tabel produk.');
    }
}