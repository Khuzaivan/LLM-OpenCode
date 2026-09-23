<?php

namespace Database\Seeders;

use App\Models\Document;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    /**
     * Seed the documents table with 50 realistic mock records.
     */
    public function run(): void
    {
        Document::query()->delete();
        Document::factory()->count(50)->create();

        $this->command->info('✓ 50 dokumen berhasil di-seed ke tabel documents.');
    }
}
