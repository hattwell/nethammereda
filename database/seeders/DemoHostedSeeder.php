<?php

namespace Database\Seeders;

use App\Enums\OrderCycleStatus;
use App\Enums\UserRole;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\OrderCycle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use LogicException;

class DemoHostedSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('lunch.hosted_demo_bootstrap_authorized', false)) {
            throw new LogicException('Hosted demo seeding requires the guarded initializer.');
        }

        User::query()->create([
            'name' => 'Просмотр админки',
            'email' => 'viewer@example.invalid',
            'password' => Str::random(64),
            'role' => UserRole::DemoViewer,
            'is_active' => true,
        ]);

        foreach ([
            'Супы' => ['Суп овощной', 'Борщ', 'Суп с лапшой'],
            'Горячее' => ['Курица с рисом', 'Овощное рагу', 'Котлета с пюре'],
            'Салаты' => ['Салат овощной', 'Салат с фасолью', 'Винегрет'],
            'Выпечка' => ['Пирожок с капустой', 'Булочка с корицей', 'Слойка с яблоком'],
        ] as $categoryName => $titles) {
            $category = MenuCategory::query()->create([
                'name' => $categoryName,
                'sort_order' => MenuCategory::query()->count() + 1,
                'is_active' => true,
            ]);

            foreach ($titles as $index => $title) {
                MenuItem::query()->create([
                    'category_id' => $category->id,
                    'title' => $title,
                    'description' => 'Вымышленное блюдо для демонстрации.',
                    'price' => 150 + $index * 60,
                    'is_active' => true,
                ]);
            }
        }

        OrderCycle::query()->create([
            'title' => 'Демонстрационная неделя',
            'starts_at' => now()->subHour(),
            'closes_at' => now()->addDays(6),
            'status' => OrderCycleStatus::Open,
        ]);
    }
}
