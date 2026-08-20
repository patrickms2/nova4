<?php

namespace App\Http\Controllers;

use App\Events\TaskUpdated;
use App\Http\Resources\TaskResource;
use App\Jobs\criticalJob;
use App\Models\Task;
use App\Enums\TaskPriority;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use App\Http\Requests\TaskRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * @OA\Info(
 *     title="Task Management API",
 *     version="1.0.0",
 *     description="This is a simple Task Management API built with Laravel",
 *     @OA\Contact(
 *         email="support@example.com"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 */



class TaskController extends Controller
{


/**
 * @OA\Get(
 *     path="/api/tasks",
 *     operationId="getTasks",
 *     tags={"Tasks"},
 *     summary="Retrieve a list of tasks",
 *     description="Fetches a paginated list of tasks for the authenticated user, utilizing Redis caching for performance.",
 *     security={{"sanctum": {}}},
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Page number for pagination",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     @OA\Parameter(
 *         name="per_page",
 *         in="query",
 *         description="Number of tasks per page",
 *         required=false,
 *         @OA\Schema(type="integer", default=10)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="List of tasks retrieved successfully",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Tasks retrieved successfully"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/Task"),
 *                 description="Array of tasks"
 *             ),
 *             @OA\Property(
 *                 property="meta",
 *                 type="object",
 *                 @OA\Property(property="current_page", type="integer", example=1),
 *                 @OA\Property(property="per_page", type="integer", example=10),
 *                 @OA\Property(property="total", type="integer", example=50)
 *             )
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="Unauthenticated")
 *         )
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Server error",
 *         @OA\JsonContent(
 *             @OA\Property(property="message", type="string", example="An error occurred while retrieving tasks"),
 *             @OA\Property(property="error", type="string", example="An unexpected error occurred")
 *         )
 *     )
 * )
 */
    public function index()
    {
        try{

            $userId = auth()->id();
            $cacheKey = "tasks:user:{$userId}:page:" . request('page', 1) . ":per_page:" . request('per_page', 10);

            $tasks = Cache::remember($cacheKey, 600, function () use ($userId) {
                return Task::where('user_id', $userId)
                ->paginate(request('per_page', 10));
            });

            Log::info("Tasks retrieved for user {$userId}, page: " . request('page', 1));
            
            return response()->json([
                'message' => 'Tasks retrieved successfully',
                    'data' => TaskResource::collection($tasks->items()),
                    'meta' => [
                        'current_page' => $tasks->currentPage(),
                        'per_page' => $tasks->perPage(),
                        'total' => $tasks->total(),
                        ],
                    
            ], 200);

        }
        catch (\Exception $e) {

            Log::error("Failed to retrieve tasks for user {$userId}: " . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while retrieving tasks',
                'error' => 'An unexpected error occurred',
            ], 500);

        }
    }

        

    /**
     * @OA\Post(
     *     path="/api/tasks",
     *     operationId="storeTask",
     *     tags={"Tasks"},
     *     summary="Create a new task",
     *     description="Creates a new task for the authenticated user and dispatches a job based on priority.",
     *     security={{"sanctum": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         description="Task data to create",
     *         @OA\JsonContent(
     *             required={"title", "priority"},
     *             @OA\Property(property="title", type="string", example="New Task", description="The title of the task"),
     *             @OA\Property(property="desc", type="string", example="Task description", description="The description of the task", nullable=true),
     *             @OA\Property(property="date", type="string", format="date", example="2023-08-21", description="Due date of the task", nullable=true),
     *             @OA\Property(property="priority", type="string", enum={"high", "medium", "low"}, example="high", description="Task priority"),
     *             @OA\Property(property="status", type="string", enum={"toDo", "inProgress", "done"}, example="toDo", description="Task status", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Task created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task #1 created successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Task", description="The created task details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The title field is required.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to create task"),
     *             @OA\Property(property="error", type="string", example="An unexpected error occurred while creating the task")
     *         )
     *     )
     * )
     */
    public function store(TaskRequest $request): JsonResponse
    {
        try {

            $this->authorize("create", Task::class);

            $validated = array_merge($request->validated(), [auth()->id()]);

            $newTask = Task::create($validated);

            $queue = $newTask->priority === TaskPriority::HIGH ? "critical" : "default";

            dispatch((new criticalJob($newTask)))->onQueue($queue);

            Log::info("Task {$newTask->id} created successfully by user " . auth()->id());

            return response()->json([
                'message' => `Task $newTask->id created successfully`,
                'task' => new TaskResource($newTask)
            ], 201);

        } catch (\Exception $e) {

            Log::error(`Task creation failed:` . $e->getMessage());
            return response()->json(
                [
                    'message' => 'failed to created task',
                    'error' => $e->getMessage()
                ],
                500
            );

        }
    }


    /**
     * @OA\Get(
     *     path="/api/tasks/{id}",
     *     operationId="getTask",
     *     tags={"Tasks"},
     *     summary="Retrieve a specific task",
     *     description="Fetches a task by ID if the authenticated user has permission. Uses Redis caching for performance.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The ID of the task to retrieve",
     *         required=true,
     *         @OA\Schema(type="string", example="1")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task retrieved successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Task", description="The retrieved task details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="error", type="string", example="You are not allowed to view this task")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Task not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task not found"),
     *             @OA\Property(property="error", type="string", example="Task with ID 1 does not exist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="An error occurred while retrieving the task"),
     *             @OA\Property(property="error", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function show(string $id): TaskResource|JsonResponse
    {
        try {

            $task = Cache::remember("task:{$id}", 3600, function () use ($id) {
                return Task::findOrFail($id);
            });

            $this->authorize("view", $task);

            return response()->json([
                'message' => 'Task retrieved successfully',
                'task' => new TaskResource($task)
            ], 200);


        } catch (AuthorizationException $e) {

            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'You are not allowed to view this task',
            ], 403);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Task not found!',
                'error' => 'The request task does not exist'
            ], 404);

        } catch (\Exception $e) {

            Log::error(`Failed to retrieve task ID $id: ` . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while retrieving the task'
            ], 500);
        }

    }

    /**
     * @OA\Put(
     *     path="/api/tasks/{id}",
     *     operationId="updateTask",
     *     tags={"Tasks"},
     *     summary="Update a specific task",
     *     description="Updates an existing task if the authenticated user has permission.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The ID of the task to update",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         description="Updated task data",
     *         @OA\JsonContent(
     *             @OA\Property(property="title", type="string", example="Updated Task", description="The title of the task", nullable=true),
     *             @OA\Property(property="desc", type="string", example="Updated description", description="The description of the task", nullable=true),
     *             @OA\Property(property="date", type="string", format="date", example="2023-08-22", description="Due date of the task", nullable=true),
     *             @OA\Property(property="priority", type="string", enum={"high", "medium", "low"}, example="medium", description="Task priority", nullable=true),
     *             @OA\Property(property="status", type="string", enum={"toDo", "inProgress", "done"}, example="inProgress", description="Task status", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Task updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task #1 updated successfully"),
     *             @OA\Property(property="data", ref="#/components/schemas/Task", description="The updated task details")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="error", type="string", example="You are not allowed to update this task")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Task not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task not found"),
     *             @OA\Property(property="error", type="string", example="Task with ID 1 does not exist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation failed",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="The priority field must be one of: high, medium, low")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to update task"),
     *             @OA\Property(property="error", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function update(TaskRequest $request, string $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $task = Task::lockForUpdate()->findOrFail($id);
            $task->update($validated);

            Cache::forget("task:{$id}");

            $queue = $validated['priority'] === TaskPriority::HIGH ? "critical" : "default";
            dispatch((new criticalJob($validated)))->onQueue($queue);

            Log::info("Task {$id} updated successfully by user " . auth()->user());

            return response()->json([
                'message' => "Task number {$id} updated successfully",
                'data' => $task->fresh()
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Task not found',
                'error' => "Task with ID {$id} does not exist",
            ], 404);
        } catch (\Exception $e) {
            Log::error("Failed to update task");
            return response()->json([
                "message" => "Failed to update task",
                "error" => 'An unexpected error occurred'
            ], 500);
        }

    }

    /**
     * @OA\Delete(
     *     path="/api/tasks/{id}",
     *     operationId="deleteTask",
     *     tags={"Tasks"},
     *     summary="Delete a specific task",
     *     description="Deletes an existing task if the authenticated user has permission.",
     *     security={{"sanctum": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="The ID of the task to delete",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=204,
     *         description="Task deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task #1 deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthenticated")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Unauthorized"),
     *             @OA\Property(property="error", type="string", example="You are not allowed to delete this task")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Task not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Task not found"),
     *             @OA\Property(property="error", type="string", example="Task with ID 1 does not exist")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to delete task"),
     *             @OA\Property(property="error", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function destroy(string $id)
    {
        try {

            $task = Task::lockForUpdate()->findOrFail($id);

            $this->authorize('delete', $task);

            $task->delete();

            Cache::forget("task:{$id}");

            event(new TaskUpdated('reRender'));

            Log::info("Task {$id} deleted successfully by user " . auth()->id());

            return response()->json([
                'message' => "Task number {$id} deleted successfully",
            ], 200);

        } catch (AuthorizationException $e) {

            return response()->json([
                'message' => 'Unauthorized',
                'error' => 'You are not allowed to delete this task',
            ], 403);

        } catch (ModelNotFoundException $e) {

            return response()->json([
                'message' => 'Task not found',
                'error' => "Task with ID {$id} does not exist",
            ], 404);

        } catch (\Exception $e) {

            Log::error("Failed to delete task {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Failed to delete task',
                'error' => 'An unexpected error occurred',
            ], 500);

        }
    }
}
