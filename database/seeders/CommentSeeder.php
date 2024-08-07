<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Category;

class CommentSeeder extends Seeder
{
    public function run()
    {
        Post::factory()->count(10)->create()->each(function ($post) {
            Comment::factory()->count(3)->create([
                'commentable_id' => $post->id,
                'commentable_type' => Post::class,
            ]);
        });

        Category::factory()->count(5)->create()->each(function ($category) {
            Comment::factory()->count(2)->create([
                'commentable_id' => $category->id,
                'commentable_type' => Category::class,
            ]);
        });
    }
}
