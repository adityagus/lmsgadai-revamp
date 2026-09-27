<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CourseFactory extends Factory
{
    protected $model = Course::class;

    public function definition()
    {
        $name = $this->faker->sentence(3);
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'tagline' => $this->faker->sentence(6),
            'description' => $this->faker->paragraph(3),
            'thumbnail' => 'courses/' . $this->faker->uuid() . '.jpg',
            'category_id' => Category::factory(),
            'is_popular' => $this->faker->boolean(30), // 30% chance popular
            'students' => 'null',
            'details' => 'null',
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function popular()
    {
        return $this->state([
            'is_popular' => true,
        ]);
    }

    public function unpopular()
    {
        return $this->state([
            'is_popular' => false,
        ]);
    }
}
