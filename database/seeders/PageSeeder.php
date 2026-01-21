<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $pages = [
            ['title' => 'О сервисе', 'slug' => 'about'],
            ['title' => 'Тарифы и цены', 'slug' => 'pricing'],
            ['title' => 'Частые вопросы', 'slug' => 'faq'],
            ['title' => 'Контакты', 'slug' => 'contacts'],
            ['title' => 'Публичная оферта', 'slug' => 'offer'],
            ['title' => 'Политика конфиденциальности', 'slug' => 'privacy'],
            ['title' => 'Согласие на обработку данных', 'slug' => 'agreement'],
            ['title' => 'Условия использования', 'slug' => 'terms'],
            ['title' => 'Возврат средств', 'slug' => 'refund'],
            ['title' => 'Поддержка', 'slug' => 'support'],
            ['title' => 'Шаблоны документов', 'slug' => 'templates'],
            ['title' => 'Партнерам', 'slug' => 'partners'],
        ];

        foreach ($pages as $page) {
            \App\Models\Page::firstOrCreate(
                ['slug' => $page['slug']],
                [
                    'title' => $page['title'],
                    'content' => '<p>Страница ' . $page['title'] . ' находится в разработке.</p>'
                ]
            );
        }
    }
}
