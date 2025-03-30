use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TokenController;

Route::post('/login', [TokenController::class, 'login']);
Route::post('/logout', [TokenController::class, 'logout'])->middleware('api.auth');
Route::get('/user', [TokenController::class, 'user'])->middleware('api.auth'); 