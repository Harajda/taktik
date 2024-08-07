<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\User;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition()
    {
        $commentableModels = [
            Post::class,
            Category::class,
        ];

        $commentableType = $this->faker->randomElement($commentableModels);
        $commentableId = $commentableType::factory()->create()->id;

        return [
            'content' => $this->faker->sentence,
            'user_id' => User::factory(),
            'commentable_id' => $commentableId,
            'commentable_type' => $commentableType,
        ];
    }
}
