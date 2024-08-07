<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CommentStoreRequest;
use App\Http\Requests\CommentUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Comment;
use App\Http\Resources\CommentResource;
use App\Http\Resources\CommentCollection;
use App\Services\ApiResponseService;
use App\Services\QueryBuilderService;
/**
 * @OA\Schema(
 *     schema="Comment",
 *     type="object",
 *     required={"id", "content"},
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time")
 * )
 *
 * @OA\Schema(
 *     schema="CommentStore",
 *     type="object",
 *     required={ "content", "user_id", "commentable_id", "commentable_type"},
 *     @OA\Property(property="content", type="string"),
 *     @OA\Property(property="user_id", type="integer"),
 *     @OA\Property(property="commentable_id", type="integer"),
 *     @OA\Property(property="commentable_type", type="string")
 * )
 */
class CommentController extends Controller
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
     *     path="/api/comments",
     *     summary="Get list of comments",
     *     operationId="getCommentsList",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter for the list of comments",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comments retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No comments found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="No comments found")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = Comment::with('user', 'commentable');
        
        $modelName = 'comments';
        $query = $this->queryBuilderService->applyFilters($query, $request, $modelName);
        $query = $this->queryBuilderService->applySorting($query, $request, $modelName);
        $query = $this->queryBuilderService->applyGrouping($query, $request, $modelName);
        $comments = $this->queryBuilderService->applyPagination($query, $request);

        return $this->apiResponse->sendResponse(
            new CommentCollection($comments),
            $comments->isEmpty() ? 'No comments found' : 'Comments retrieved successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Post(
     *     path="/api/comments",
     *     summary="Create a new comment",
     *     operationId="createComment",
     *     tags={"Comments"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CommentStore")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Comment created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     )
     * )
     */
    public function store(CommentStoreRequest $request)
    {
        $comment = Comment::create($request->validated());

        return $this->apiResponse->sendResponse(
            new CommentResource($comment),
            'Comment created successfully',
            Response::HTTP_CREATED
        );
    }

    /**
     * @OA\Get(
     *     path="/api/comments/{id}",
     *     summary="Get a specific comment",
     *     operationId="getCommentById",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the comment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Comment not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $comment = Comment::with('user', 'commentable')->find($id);

        if (!$comment) {
            return $this->apiResponse->sendError('Comment not found', Response::HTTP_NOT_FOUND);
        }

        return $this->apiResponse->sendResponse(
            new CommentResource($comment),
            'Comment retrieved successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Put(
     *     path="/api/comments/{id}",
     *     summary="Update a comment",
     *     operationId="updateComment",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the comment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CommentStore")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Comment")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Comment not found")
     *         )
     *     )
     * )
     */
    public function update(CommentUpdateRequest $request, $id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return $this->apiResponse->sendError('Comment not found', Response::HTTP_NOT_FOUND);
        }

        $comment->update($request->validated());

        return $this->apiResponse->sendResponse(
            new CommentResource($comment),
            'Comment updated successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/comments/{id}",
     *     summary="Delete a comment",
     *     operationId="deleteComment",
     *     tags={"Comments"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the comment",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Comment deleted successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Comment deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Comment not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Comment not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return $this->apiResponse->sendError('Comment not found', Response::HTTP_NOT_FOUND);
        }

        $comment->delete();

        return $this->apiResponse->sendResponse(null, 'Comment deleted successfully', Response::HTTP_OK);
    }
}
