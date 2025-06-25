<?php

namespace App\Http\Controllers;


use App\Models\Tag;
use App\Models\User;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;


use Illuminate\Support\Facades\Cache;




class ArticleController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth', except: ['index', 'show', 'byCategory', 'byUser', 'articleSearch']),
        ];
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $articles = Article::where('is_accepted', true)->orderBy('created_at', 'desc')->get();
        return view('articles.index', compact('articles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('articles.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
         $request->validate([
        'title' => 'required|unique:articles|min:5',
        'subtitle' => 'required|min:5',
        'body' => 'required|min:10',
        'image' => 'required|image',
        'category' => 'required',
        'tags' => 'required'
    ]);

    $article = Article::create([
        'title' => $request->title,
        'subtitle' => $request->subtitle,
        'body' => $request->body,
        'image' => $request->file('image')->store('public/images'),
        'category_id' => $request->category,
        'user_id' => Auth::user()->id,
        'slug' => Str::slug($request->title),
    ]);

    $tags = explode(',', $request->tags);
    foreach($tags as $i => $tag) {
        $tags[$i] = trim($tag);
    }

    foreach($tags as $tag) {
        $newTag = Tag::updateOrCreate([
            'name' => strtolower($tag)
        ]);
        $article->tags()->attach($newTag);
    }

    Log::info('📝 Articolo creato', [
        'id' => $article->id,
        'titolo' => $article->title,
        'autore' => Auth::user()->id,
        'ip' => $request->ip()
    ]);

    return redirect(route('homepage'))->with('message', 'Articolo creato con successo');
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        return view('articles.show', compact('article'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article)
    {
        if(Auth::user()->id != $article->user_id){
            return redirect()->route('homepage')->with('alert', 'Accesso non consentito');
        }
        return view('articles.edit', compact('article'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Article $article)
    {
         $request->validate([
        'title' => 'required|min:5|unique:articles,title,' . $article->id,
        'subtitle' => 'required|min:5',
        'body' => 'required|min:10',
        'image' => 'image',
        'category' => 'required',
        'tags' => 'required'
    ]);

    $article->update([
        'title' => $request->title,
        'subtitle' => $request->subtitle,
        'body' => $request->body,
        'category_id' => $request->category,
        'slug' => Str::slug($request->title),
    ]);

    if ($request->image) {
        Storage::delete($article->image);
        $article->update([
            'image' => $request->file('image')->store('public/images')
        ]);
    }

    $tags = explode(',', $request->tags);
    foreach ($tags as $i => $tag) {
        $tags[$i] = trim($tag);
    }

    $newTags = [];
    foreach ($tags as $tag) {
        $newTag = Tag::updateOrCreate([
            'name' => strtolower($tag)
        ]);
        $newTags[] = $newTag->id;
    }
    $article->tags()->sync($newTags);

    Log::info('✏️ Articolo modificato', [
        'id' => $article->id,
        'titolo' => $article->title,
        'modificato_da' => Auth::user()->id,
        'ip' => $request->ip()
    ]);

    return redirect(route('writer.dashboard'))->with('message', 'Articolo modificato con successo');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
         foreach ($article->tags as $tag) {
        $article->tags()->detach($tag);
    }

    $deletedTitle = $article->title;
    $deletedId = $article->id;

    $article->delete();

    Log::warning('🗑️ Articolo eliminato', [
        'id' => $deletedId,
        'titolo' => $deletedTitle,
        'eliminato_da' => Auth::user()->id,
        'ip' => request()->ip()
    ]);

    return redirect()->back()->with('message', 'Articolo cancellato con successo');
    }

    public function byCategory(Category $category){
        $articles = $category->articles()->where('is_accepted', true)->orderBy('created_at', 'desc')->get();
        return view('articles.by-category', compact('category', 'articles'));
    }
    
    public function byUser(User $user){
        $articles = $user->articles()->where('is_accepted', true)->orderBy('created_at', 'desc')->get();
        return view('articles.by-user', compact('user', 'articles'));
    }

 public function articleSearch(Request $request)
{
    $ip = $request->ip();
    $attemptKey = "rate_limit:$ip";
    $blockKey = "block_ip:$ip";

    // 🔒 Se l'IP è bloccato
    if (Cache::has($blockKey)) {
        return response("⛔ IP bloccato per 10 minuti", 429);
    }

    // 📈 Conta accessi nell'ultimo minuto
    $attempts = Cache::get($attemptKey, 0);

    if ($attempts >= 10) {
        Cache::put($blockKey, true, now()->addMinutes(10));
        Cache::forget($attemptKey);
        Log::warning("🚫 IP $ip bloccato per 10 minuti");
        return response("⛔ IP $ip bloccato", 429);
    }

    // Incrementa tentativi
    Cache::add($attemptKey, 0, 60); // cache per 60 secondi
    Cache::increment($attemptKey);

    Log::info("✅ $ip ha fatto ".($attempts+1)." richieste");

    // 🔍 Logica di ricerca vera
    $query = $request->input('query');
    $articles = Article::search($query)
        ->where('is_accepted', true)
        ->orderBy('created_at', 'desc')
        ->get();

    return view('articles.search-index', compact('articles', 'query'));
}

    
}
