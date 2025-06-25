##Progetto finale Cybersecurity##


<h2>CHALLENGE 1: Rate limiter mancante</h2>
<p><strong>Autore:</strong> Gianfranco Cito</p>

<h3>1. Descrizione dell'attacco</h3>
<p>
È stato simulato un attacco DoS (Denial of Service) sulla rotta pubblica <code>/articles/search</code>, inviando
numerose richieste consecutive tramite uno script bash. In assenza di limitazioni, il server accetta
tutte le richieste, causando un sovraccarico potenziale.
</p>

<p><strong>Script utilizzato:</strong></p>

<pre><code>#!/bin/bash
URL="http://cyber.blog:8000/articles/search?query=test"
for i in {1..50}
do
  curl -s $URL
done
</code></pre>

<p>
Eseguendo lo script si è verificato che il server rispondeva correttamente a tutte le richieste,
dimostrando l'assenza iniziale di una protezione contro attacchi di tipo DoS.
</p>

<p><strong>Screenshot attacco:</strong></p>
<img src="https://github.com/user-attachments/assets/27ae87b0-a9b1-4204-80ff-14ddb40243d8" alt="Attacco parte 1" width="600">
<img src="https://github.com/user-attachments/assets/2ae5f431-a487-4783-bae8-ffe81437a42a" alt="Attacco parte 2" width="600">

<hr>

<h3>2. Mitigazione nel controller (senza middleware)</h3>
<p>
La protezione è stata implementata direttamente nel metodo <code>articleSearch()</code> del controller, senza
utilizzare middleware. Dopo 10 richieste consecutive da uno stesso IP entro 60 secondi, l'IP viene
bloccato per 10 minuti. Ogni ulteriore richiesta durante il blocco riceve un errore <code>429</code>.
</p>

<p><strong>Codice implementato:</strong></p>

<pre><code>&lt;?php

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
</code></pre>

<p>
Dopo l'implementazione, lo script DoS riceve correttamente risposte HTTP 429 dopo 10 richieste.
L'attacco è quindi mitigato con successo.
</p>

<p><strong>Screenshot dopo mitigazione:</strong></p>
<img src="https://github.com/user-attachments/assets/84a12cef-f4f6-48fe-87ff-d0c8fd41d706" alt="Attacco mitigato" width="600">











# progetto-finale-cyber-Gianfranco-Cito
