<?php


use App\Events\CommentIncrement;
use App\Events\VoteEvent;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\CommentController;
use App\Http\Controllers\API\PostController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\VoteController;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResources([
        "post" => PostController::class,
        "comment" => CommentController::class,
    ]);
    Route::post("/auth/logout", [AuthController::class, 'logout']);

    Route::put("/vote/{id}", [PostController::class, 'vote']);

    Route::post("/update/profile", [UserController::class, 'updateProfileImage']);
    Route::post('/posts/{postId}/vote', [VoteController::class, 'vote']);
    Route::get('/posts/{postId}/votes', [VoteController::class, 'votesCount']);
    Route::get('/posts/up-voted', [PostController::class, 'UpVotedPost']);
});


Route::post("/test/channel", function (Request $request) {
    $post = Post::where("id", "5")->with('user')
        ->withCount([
            'votes as up_votes' => function ($query) {
                $query->where('vote_type', 'up');
            },
            'votes as down_votes' => function ($query) {
                $query->where('vote_type', 'down');
            }
        ])->first();
    // PostBroadCastEvent::dispatch($post);
    // CommentIncrement::dispatch(2);
    VoteEvent::dispatch($post);
    // TestEvent::dispatch($request->all());
    return response()->json(["message" => "data sent successfully!"]);
});

Route::post("/auth/login", [AuthController::class, 'login']);
Route::post("/auth/register", [AuthController::class, 'register']);
Route::post("/auth/checkCredentials", [AuthController::class, 'checkCredentias']);

Broadcast::routes(['middleware' => ['auth:sanctum']]);
