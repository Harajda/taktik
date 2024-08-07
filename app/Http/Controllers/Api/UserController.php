<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\LoginUserRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserCollection;
use App\Models\User;
use App\Services\QueryBuilderService;
use App\Services\ApiResponseService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
/**
 * @OA\Schema(
 *     schema="Users",
 *     type="object",
 *     @OA\Property(property="id", type="integer", format="int64"),
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="created_at", type="string", format="date-time"),
 *     @OA\Property(property="updated_at", type="string", format="date-time"),
 *     @OA\Property(property="posts", type="array", @OA\Items(ref="#/components/schemas/Post")),
 *     @OA\Property(property="comments", type="array", @OA\Items(ref="#/components/schemas/Comment"))
 * )
 * @OA\Schema(
 *     schema="UserCreate",
 *     type="object",
 *     required={"name", "email", "password"},
 *     @OA\Property(property="name", type="string"),
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="password", type="string")
 * )
 * @OA\Schema(
 *     schema="LoginUser",
 *     type="object",
 *     required={"email", "password"},
 *     @OA\Property(property="email", type="string", format="email"),
 *     @OA\Property(property="password", type="string")
 * )
 */
class UserController extends Controller
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
     *     path="/api/users",
     *     summary="Get list of users",
     *     operationId="getUsersList",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="filter",
     *         in="query",
     *         description="Filter for the list of users",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Users")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="No users found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="No users found")
     *         )
     *     )
     * )
     */
    public function index(Request $request)
    {
        $query = User::with('posts', 'comments');
        
        $modelName = 'users';
        $query = $this->queryBuilderService->applyFilters($query, $request, $modelName);
        $query = $this->queryBuilderService->applySorting($query, $request, $modelName);
        $query = $this->queryBuilderService->applyGrouping($query, $request, $modelName);
        $users = $this->queryBuilderService->applyPagination($query, $request);

        return $this->apiResponse->sendResponse(
            new UserCollection($users),
            $users->isEmpty() ? 'No users found' : 'Users retrieved successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Create a new user",
     *     operationId="createUser",
     *     tags={"Users"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserCreate")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="User created successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Users")
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
    public function store(UserStoreRequest $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return $this->apiResponse->sendResponse(
            new UserResource($user),
            'User created successfully',
            Response::HTTP_CREATED
        );
    }
    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Get a specific user",
     *     operationId="getUserById",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User retrieved successfully",
     *         @OA\JsonContent(ref="#/components/schemas/Users")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function show($id)
    {
        $user = User::with('posts', 'comments')->find($id);

        if (!$user) {
            return $this->apiResponse->sendError('User not found', Response::HTTP_NOT_FOUND);
        }

        return $this->apiResponse->sendResponse(
            new UserResource($user),
            'User retrieved successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Update a user",
     *     operationId="updateUser",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UserCreate")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User updated successfully",
     *         @OA\JsonContent(ref="#/components/schemas/UserCreate")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function update(UserUpdateRequest $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->apiResponse->sendError('User not found', Response::HTTP_NOT_FOUND);
        }

        // Dynamicky nastavíme ID používateľa pre validáciu
        $request->setUserId($id);
        
        $data = $request->validated();

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return $this->apiResponse->sendResponse(
            new UserResource($user),
            'User updated successfully',
            Response::HTTP_OK
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Delete a user",
     *     operationId="deleteUser",
     *     tags={"Users"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="User deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="User not found")
     *         )
     *     )
     * )
     */
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->apiResponse->sendError('User not found', Response::HTTP_NOT_FOUND);
        }

        $user->delete();

        return $this->apiResponse->sendResponse(null, 'User deleted successfully', Response::HTTP_OK);
    }


    /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="User login",
     *     tags={"Authentication"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/LoginUser")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Login successful",
     *         @OA\JsonContent(
     *              @OA\Property(property="token", type="string", example="Bearer token")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Unauthorized")
     *         )
     *     )
     * )
     */
    public function login(LoginUserRequest $request) 
    {
        $credentials = $request->only('email', 'password');
        if (!Auth::attempt($credentials)) {
            return $this->apiResponse->sendError('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }
                
        $user = Auth::user();
        $token = $user->createToken('Personal Access Token')->plainTextToken;

        return $this->apiResponse->sendResponse(
            ['token' => $token],
            'Login successful',
            Response::HTTP_OK
        );
    }
}
