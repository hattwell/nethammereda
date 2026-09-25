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
            'Супы' => [
                'Суп овощной' => '/images/menu/dish-069.png',
                'Борщ' => '/images/menu/dish-071.png',
                'Суп с лапшой' => '/images/menu/dish-070.png',
                'Суп-пюре' => '/images/menu/dish-068.png',
            ],
            'Горячее' => [
                'Курица с рисом' => '/images/menu/dish-012.png',
                'Овощное рагу' => '/images/menu/dish-003.png',
                'Котлета с пюре' => '/images/menu/dish-002.png',
                'Гречка с котлетой' => '/images/menu/dish-009.png',
            ],
            'Салаты' => [
                'Салат овощной' => '/images/menu/dish-054.png',
                'Салат с фасолью' => '/images/menu/dish-053.png',
                'Винегрет' => '/images/menu/dish-065.png',
                'Салат с кукурузой' => '/images/menu/dish-067.png',
            ],
            'Выпечка' => [
                'Пирожок с капустой' => '/images/menu/dish-043.png',
                'Булочка с корицей' => '/images/menu/dish-044.png',
                'Слойка с яблоком' => '/images/menu/dish-031.png',
                'Домашний пирог' => '/images/menu/dish-045.png',
            ],
        ] as $categoryName => $dishes) {
            $category = MenuCategory::query()->create([
                'name' => $categoryName,
                'sort_order' => MenuCategory::query()->count() + 1,
                'is_active' => true,
            ]);

            $index = 0;
            foreach ($dishes as $title => $imageUrl) {
                MenuItem::query()->create([
                    'category_id' => $category->id,
                    'title' => $title,
                    'description' => 'Вымышленное блюдо для демонстрации.',
                    'price' => 150 + $index * 60,
                    'image_url' => $imageUrl,
                    'is_active' => true,
                ]);
                $index++;
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
