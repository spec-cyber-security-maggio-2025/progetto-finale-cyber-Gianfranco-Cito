<?php

namespace App\Http\Controllers;

use Exception;
use App\Models\Tag;
use App\Models\User;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\HttpService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    protected $httpService;

    public function __construct(HttpService $httpService)
    {
        $this->httpService = $httpService;
    } 

 public function dashboard()
{
    // 1) Collect the pending role‐requests
    $adminRequests   = User::whereNull('is_admin')->get();
    $revisorRequests = User::whereNull('is_revisor')->get();
    $writerRequests  = User::whereNull('is_writer')->get();

    $financialData = json_decode($this->httpService->getRequest('http://localhost:8001/financialApp/user-data.php'));

    // 2) Always initialize a default shape for the financial data
    $financialData = ['users' => []];

    try {
        $response = $this->httpService
                         ->getRequest('http://internal.finance:8001/user-data.php');
                         
                       

        if (empty($response)) {
            throw new Exception('Empty HTTP response from finance service.');
        }

        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('JSON decode error: ' . json_last_error_msg());
        }

        // Only use it if it actually has a users array
        if (isset($decoded['users']) && is_array($decoded['users'])) {
            $financialData = $decoded;
        } else {
            Log::warning('Finance API returned unexpected structure', [
                'payload' => $decoded
            ]);
        }

    } catch (Exception $e) {
        Log::error('Finance API error: ' . $e->getMessage());
        // optionally: session()->flash('error', 'Could not load financial data.');
    }

    // 3) Pass *all* four variables to the view
    return view('admin.dashboard', compact(
        'adminRequests',
        'revisorRequests',
        'writerRequests',
        'financialData'
    ));
}



    public function setAdmin(User $user){
    $user->is_admin = true;
    $user->save();

    Log::warning("Assegnazione ruolo ADMIN", [
        'assegnato_a' => $user->id,
        'eseguito_da' => Auth::id(),
        'ip' => request()->ip(),
        'timestamp' => now()
    ]);

    return redirect(route('admin.dashboard'))->with('message', "$user->name is now administrator");
}

public function setRevisor(User $user){
    $user->is_revisor = true;
    $user->save();

    Log::info("Assegnazione ruolo REVISOR", [
        'assegnato_a' => $user->id,
        'eseguito_da' => Auth::id(),
        'ip' => request()->ip(),
        'timestamp' => now()
    ]);

    return redirect(route('admin.dashboard'))->with('message', "$user->name is now revisor");
}

public function setWriter(User $user){
    $user->is_writer = true;
    $user->save();

    Log::info("Assegnazione ruolo WRITER", [
        'assegnato_a' => $user->id,
        'eseguito_da' => Auth::id(),
        'ip' => request()->ip(),
        'timestamp' => now()
    ]);

    return redirect(route('admin.dashboard'))->with('message', "$user->name is now writer");
}













    public function editTag(Request $request, Tag $tag){
        $request->validate([
            'name' => 'required|unique:tags',
        ]);
        $tag->update([
            'name' => strtolower($request->name),
        ]);
        return redirect()->back()->with('message', 'Tag successfully updated');
    }

    public function deleteTag(Tag $tag){
        foreach($tag->articles as $article){
            $article->tags()->detach($tag);
        }
        $tag->delete();

        return redirect()->back()->with('message', 'Tag successfully deleted');
    }

    public function editCategory(Request $request, Category $category){
        $request->validate([
            'name' => 'required|unique:categories',
        ]);
        $category->update([
            'name' => strtolower($request->name),
        ]);

        return redirect()->back()->with('message', 'Category successfully updated');
    }

    public function deleteCategory(Category $category){
    Log::warning("Eliminazione categoria", [
        'categoria' => $category->name,
        'eseguito_da' => Auth::id(),
        'ip' => request()->ip(),
        'timestamp' => now()
    ]);

    $category->delete();
    return redirect()->back()->with('message', 'Category successfully deleted');
}


    public function storeCategory(Request $request){
        $category = Category::create([
            'name' => strtolower($request->name),
        ]);
        
        return redirect()->back()->with('message', 'Category successfully created');
    }

    public function storeTag(Request $request){
        $tag = Tag::create([
            'name' => strtolower($request->name),
        ]);
        
        return redirect()->back()->with('message', 'Tag successfully created');
    }
}