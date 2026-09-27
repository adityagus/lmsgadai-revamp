<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition()
    {
        return [
            'name' => $this->faker->unique()->randomElement([
                'Programming',
                'Design',
                'Marketing',
                'Business',
                'Photography',
                'Music',
                'Health & Fitness',
                'Language',
                'Technology',
                'Art & Crafts'
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
