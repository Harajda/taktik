<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\CategoryCollection;
use App\Models\Category;
use App\Services\QueryBuilderService;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 *
 * @OA\Schema(
 *     schema="Category",
 *     type="object",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="posts", type="array", @OA\Items(ref="#/components/schemas/Post")),
 *     @OA\Property(property="comments", type="array", @OA\Items(ref="#/components/schemas/Comment"))
 * )
 * 
 * @OA\Schema(
 *     schema="CreateCategory",
 *     type="object",
 *     required={"id", "name"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 */
class CategoryController extends Controller
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
     *     path="/api/categories",
     *     summary="Get list of categories",
     *     tags={"Categories"},
     *     @OA\Response(
     *         response=200,
     *         description="List of categories",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Category")),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No categories found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Category::with('posts', 'comments');

        $modelName = 'category';
        $query = $this->queryBuilderService->applyFilters($query, $request, $modelName);
        $query = $this->queryBuilderService->applySorting($query, $request, $modelName);
        $query = $this->queryBuilderService->applyGrouping($query, $request, $modelName);
        $categories = $this->queryBuilderService->applyPagination($query, $request);

        return $this->apiResponse->sendResponse(
            new CategoryCollection($categories),
            $categories->isEmpty() ? 'No categories found' : 'Categories retrieved successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Post(
     *     path="/api/categories",
     *     summary="Create a new category",
     *     tags={"Categories"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Category Name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/CreateCategory"
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="errors", type="object"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     )
     * )
     */
    public function store(CategoryStoreRequest $request)
    {
        $category = Category::create($request->validated());

        return $this->apiResponse->sendResponse(
            new CategoryResource($category),
            'Category created successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="Get a single category by ID",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category retrieved successfully",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/Category"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $category = Category::with('posts', 'comments')->find($id);

        if (!$category) {
            return $this->apiResponse->sendError('Category not found', Response::HTTP_NOT_FOUND);
        }
        
        return $this->apiResponse->sendResponse(
            new CategoryResource($category),
            'Category retrieved successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Put(
     *     path="/api/categories/{id}",
     *     summary="Update a category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Updated Category Name")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             ref="#/components/schemas/CreateCategory"
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     )
     * )
     */
    public function update(CategoryStoreRequest $request, $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->apiResponse->sendError('Category not found', Response::HTTP_NOT_FOUND);
        }

        $category->update($request->validated());

        return $this->apiResponse->sendResponse(
            new CategoryResource($category),
            'Category updated successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/categories/{id}",
     *     summary="Delete a category",
     *     tags={"Categories"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Category not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="status", type="string")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return $this->apiResponse->sendError('Category not found', Response::HTTP_NOT_FOUND);
        }

        $category->delete();

        return $this->apiResponse->sendResponse(null, 'Category deleted successfully', Response::HTTP_OK);
    }
}
