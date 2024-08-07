<?php

namespace Database\Seeders;

use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;

class PostSeeder extends Seeder
{
    public function run()
    {
        User::all()->each(function ($user) {
            Post::factory()->count(2)->create(['user_id' => $user->id]);
        });
    }
}
