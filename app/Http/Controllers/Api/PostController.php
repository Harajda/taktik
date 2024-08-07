<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PostStoreRequest;
use App\Http\Requests\PostUpdateRequest;
use App\Http\Resources\PostResource;
use App\Http\Resources\PostCollection;
use App\Models\Post;
use App\Services\QueryBuilderService;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
/**
 * @OA\Schema(
 *     schema="Post",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="user_id", type="integer", format="int64"),
 *     @OA\Property(property="category_id", type="integer", format="int64"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="user", type="array", @OA\Items(ref="#/components/schemas/Users")),
 *     @OA\Property(property="category", type="array", @OA\Items(ref="#/components/schemas/Category")),
 *     @OA\Property(property="comments", type="array", @OA\Items(ref="#/components/schemas/Comment"))
 * )
 * @OA\Schema(
 *     schema="PostCreate",
 *     type="object",
 *     required={"title", "content", "user_id", "category_id"},
 *     @OA\Property(property="title", type="string"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="user_id", type="integer", format="int64"),
 *     @OA\Property(property="category_id", type="integer", format="int64")
 * )
 */
class PostController extends Controller
{
    protected $apiResponse;
    protected $queryBuilderService;

    public function __construct(ApiResponseService $apiResponse, QueryBuilderService $queryBuilderService)
    {
        $this->apiResponse = $apiResponse;
        $this->queryBuilderService = $queryBuilderService;
    }
    
    /**
     * @OA\Get(
     *     path="/posts",
     *     summary="List all posts",
     *     description="Retrieve a list of posts with optional filters, sorting, grouping, and pagination.",
     *     tags={"Posts"},
     *     @OA\Parameter(name="sort_by", in="query", description="Field to sort by", @OA\Schema(type="string")),
     *     @OA\Parameter(name="sort_order", in="query", description="Order of sorting", @OA\Schema(type="string", enum={"asc", "desc"})),
     *     @OA\Parameter(name="per_page", in="query", description="Number of posts per page", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="page", in="query", description="Page number for pagination", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="group_by", in="query", description="Fields to group the results by", @OA\Schema(type="array", @OA\Items(type="string"))),
     *     @OA\Response(response="200", description="Successfully retrieved list of posts", 
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Post")),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(response="404", description="No posts found", 
     *       @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="status", type="string")
     *       )
     *     )
     * )
     * 
     */
    public function index(Request $request)
    {
        $cacheKey = 'posts_' . hash('sha256', $request->fullUrl());
        
        $posts = Cache::rememberForever($cacheKey, function () use ($request) {
            $query = Post::with('user', 'category', 'comments');
            $modelName = 'posts';
            $query = $this->queryBuilderService->applyFilters($query, $request, $modelName);
            $query = $this->queryBuilderService->applySorting($query, $request, $modelName);
            $query = $this->queryBuilderService->applyGrouping($query, $request, $modelName);
            return $this->queryBuilderService->applyPagination($query, $request);
        });
        $response = $posts->isEmpty()
            ? $this->apiResponse->sendResponse([], 'No posts found', Response::HTTP_OK)
            : $this->apiResponse->sendResponse(new PostCollection($posts), 'Posts retrieved successfully', Response::HTTP_OK);

        return $response;
    }

    /**
     * @OA\Post(
     *     path="/posts",
     *     summary="Create a new post",
     *     description="Create a new post with the given data.",
     *     tags={"Posts"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PostCreate")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Category Name")
     *         )
     *     ),
     *     @OA\Response(response="201", description="Post created successfully", 
     *       @OA\JsonContent(ref="#/components/schemas/PostCreate")
     *     ),
     *     @OA\Response(response="400", description="Invalid request", 
     *       @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="errors", type="object"),
     *         @OA\Property(property="status", type="string")
     *       )
     *     )
     * )
     */
    public function store(PostStoreRequest $request)
    {
        $post = Post::create($request->validated());

        Cache::flush();

        return $this->apiResponse->sendResponse(
            new PostResource($post),
            'Post created successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * @OA\Get(
     *     path="/posts/{id}",
     *     summary="Retrieve a specific post",
     *     description="Retrieve a post by its ID.",
     *     tags={"Posts"},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID of the post to retrieve", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Successfully retrieved the post", 
     *       @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(response="404", description="Post not found", 
     *       @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *       )
     *     )
     * )
     */
    public function show($id)
    {
        $post = Post::with('user', 'category', 'comments')->find($id);

        if (!$post) {
            return $this->apiResponse->sendError('Post not found', Response::HTTP_NOT_FOUND);
        }

        return $this->apiResponse->sendResponse(
            new PostResource($post),
            'Post retrieved successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Put(
     *     path="/posts/{id}",
     *     summary="Update a specific post",
     *     description="Update a post by its ID.",
     *     tags={"Posts"},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID of the post to update", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/PostCreate")
     *     ),
     *     @OA\Response(response="200", description="Post updated successfully", 
     *       @OA\JsonContent(ref="#/components/schemas/Post")
     *     ),
     *     @OA\Response(response="404", description="Post not found", 
     *       @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     )
     * )
     */
    public function update(PostUpdateRequest $request, $id)
    {
        $post = Post::find($id);

        if (!$post) {
            return $this->apiResponse->sendError('Post not found', Response::HTTP_NOT_FOUND);
        }

        $post->update($request->validated());

        Cache::flush();

        return $this->apiResponse->sendResponse(
            new PostResource($post),
            'Post updated successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Delete(
     *     path="/posts/{id}",
     *     summary="Delete a post",
     *     description="Delete a post by its ID.",
     *     tags={"Posts"},
     *     @OA\Parameter(name="id", in="path", required=true, description="ID of the post to delete", @OA\Schema(type="integer")),
     *     @OA\Response(response="200", description="Post deleted successfully", 
     *       @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *       )
     *     ),
     *     @OA\Response(response="404", description="Post not found", 
     *       @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *       )
     *     )
     * )
     */
    public function destroy($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return $this->apiResponse->sendError('Post not found', Response::HTTP_NOT_FOUND);
        }

        $post->delete();

        Cache::flush();

        return $this->apiResponse->sendResponse(null, 'Post deleted successfully', Response::HTTP_OK);
    }
}