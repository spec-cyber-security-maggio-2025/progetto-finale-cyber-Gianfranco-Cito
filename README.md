##Progetto finale Cybersecurity##


CHALLENGE 1: Rate limiter mancante
 Autore: Gianfranco Cito
 1. Descrizione dell'attacco
 È stato simulato un attacco DoS (Denial of Service) sulla rotta pubblica /articles/search, inviando
 numerose richieste consecutive tramite uno script bash. In assenza di limitazioni, il server accetta
 tutte le richieste, causando un sovraccarico potenziale.
 Script utilizzato:
 #!/bin/bash
 URL="http://cyber.blog:8000/articles/search?query=test"
 for i in {1..50}
 do
  curl -s $URL
 done
 Eseguendo lo script si è verificato che il server rispondeva correttamente a tutte le richieste,
 dimostrando l'assenza iniziale di una protezione contro attacchi di tipo DoS.

![1](https://github.com/user-attachments/assets/27ae87b0-a9b1-4204-80ff-14ddb40243d8)

![2](https://github.com/user-attachments/assets/2ae5f431-a487-4783-bae8-ffe81437a42a)


 






 3. Mitigazione nel controller (senza middleware)
 La protezione è stata implementata direttamente nel metodo articleSearch() del controller, senza
 utilizzare middleware. Dopo 10 richieste consecutive da uno stesso IP entro 60 secondi, l'IP viene
 bloccato per 10 minuti. Ogni ulteriore richiesta durante il blocco riceve un errore 429.
 Codice implementato:
 public function articleSearch(Request $request)
 {
    $ip = $request->ip();
    $attemptKey = "rate_limit:$ip";
    $blockKey = "block_ip:$ip";
    if (Cache::has($blockKey)) {
        return response("IP bloccato per 10 minuti", 429);
    }
    $attempts = Cache::get($attemptKey, 0);
    if ($attempts >= 10) {
        Cache::put($blockKey, true, now()->addMinutes(10));
        Cache::forget($attemptKey);
        return response("IP bloccato", 429);
    }
    Cache::add($attemptKey, 0, 60);
    Cache::increment($attemptKey);
    $query = $request->input('query');
    $articles = Article::search($query)
        ->where('is_accepted', true)
        ->orderBy('created_at', 'desc')
        ->get();
    return view('articles.search-index', compact('articles', 'query'));
 }
 Dopo l'implementazione, lo script DoS riceve correttamente risposte HTTP 429 dopo 10 richieste.
 L'attacco è quindi mitigato con successo.

![3](https://github.com/user-attachments/assets/84a12cef-f4f6-48fe-87ff-d0c8fd41d706)







# progetto-finale-cyber-Gianfranco-Cito
