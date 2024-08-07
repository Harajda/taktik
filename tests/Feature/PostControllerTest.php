<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;


class PostControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $token;
    protected $category;

    public function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'password' => Hash::make('password123')
        ]);

        $response = $this->post('/api/login', [
            'email' => $this->user->email,
            'password' => 'password123'
        ]);

        $this->token = $response->json('access_token');

        $this->category = Category::factory()->create();
    }

    /** @test */
    public function it_can_list_posts()
    {
        Post::factory()->count(3)->create();

        $response = $this->get('/api/posts', [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'content',
                ]
            ]
        ]);
    }

    /** @test */
    public function it_can_store_a_post()
    {
        $response = $this->postJson('/api/posts', [
            'title' => 'New Post Title',
            'content' => 'Content of the new post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id
        ], [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'title' => 'New Post Title',
                'content' => 'Content of the new post'
            ]
        ]);
    }


    /** @test */
    public function it_can_show_a_post()
    {
        $post = Post::factory()->create();
        $response = $this->get('/api/posts/' . $post->id, [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id' => $post->id,
                'title' => $post->title,
                'content' => $post->content
            ]
        ]);
    }

    /** @test */
    public function it_can_update_a_post()
    {
        $post = Post::factory()->create();

        $response = $this->putJson('/api/posts/' . $post->id, [
            'title' => 'Updated Title',
            'content' => 'Updated content'
        ], [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                'id' => $post->id,
                'title' => 'Updated Title',
                'content' => 'Updated content'
            ]
        ]);
    }


    /** @test */
    public function it_can_delete_a_post()
    {
        $post = Post::factory()->create();
        $response = $this->delete('/api/posts/' . $post->id, [], [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/json',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
