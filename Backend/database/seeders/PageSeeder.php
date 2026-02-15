<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Page::updateOrCreate(
            [
                'slug' => 'privacy-policy',
            ],
            [
                'title' => [
                    'ar' => 'سياسة الخصوصية',
                    'en' => 'Privacy Policy',
                ],
                'content' => [
                    'ar' => 'محتوى الصفحة',
                    'en' => 'Page Content',
                ],
                'is_published' => true,
            ]);
    }
}
