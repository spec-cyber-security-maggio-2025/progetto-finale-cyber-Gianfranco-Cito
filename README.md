## Rate limiter mancante

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
  curl -s $URL > /dev/null &
 done
 wait
 echo "Attacco completato"
 Eseguendo lo script si è verificato che il server rispondeva correttamente a tutte le richieste,
 dimostrando l'assenza iniziale di una protezione contro attacchi di tipo DoS.
 Screenshot effetto attacco (INSERIRE QUI):
 2. Mitigazione nel controller (senza middleware)
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
 Screenshot post-mitigazione (INSERIRE QUI)
# progetto-finale-cyber-Gianfranco-Cito
