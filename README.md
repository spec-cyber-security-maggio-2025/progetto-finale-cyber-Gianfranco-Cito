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


<h2>🔐 CHALLENGE 2: Operazioni critiche in GET (CSRF Attack)</h2>

<p><strong>Autore:</strong> Gianfranco Cito</p>

<h3>1. Descrizione dell'attacco</h3>

<p>
Nel progetto iniziale, alcune rotte critiche come <code>/admin/{user}/set-admin</code> erano esposte tramite metodo <strong>GET</strong>.
Questo apre la strada a un attacco <strong>CSRF (Cross-Site Request Forgery)</strong> sfruttando l'autenticazione attiva dell'utente.
</p>

![USER SEMPLICE](https://github.com/user-attachments/assets/392a4de0-be17-4c65-95c8-912269e1bd4f)

![da user ad admin attacco riuscito](https://github.com/user-attachments/assets/71389a03-b3d2-4f90-a747-ff1290124048)




<p>
L’attaccante crea una pagina HTML contenente un link nascosto che viene cliccato automaticamente dopo 5 secondi.
Se la vittima è loggata con privilegi di amministrazione, l’azione viene eseguita a sua insaputa.
</p>

<h4>💣 Codice della pagina HTML dell’attacco:</h4>

<pre>
&lt;a id="csrf-link" href="http://cyber.blog:8000/admin/2/set-admin" style="display:none;"&gt;Trigger&lt;/a&gt;
&lt;script&gt;
  setTimeout(() =&gt; {
    document.getElementById('csrf-link').click();
  }, 5000);
&lt;/script&gt;
</pre>

<p><strong>Effetto:</strong> l’utente con ID 2 ottiene il ruolo admin, senza alcuna conferma o protezione.</p>

<hr>

<h3>2. Mitigazione</h3>

<p>Per prevenire attacchi CSRF:</p>
<ul>
  <li>La rotta è stata cambiata da <code>GET</code> a <code>PATCH</code> in <code>web.php</code>:</li>
</ul>

<pre>
Route::patch('/admin/{user}/set-admin', [AdminController::class, 'setAdmin'])->name('admin.setAdmin');
</pre>

<ul>
  <li>I pulsanti nella dashboard sono stati modificati da link HTML a form protetti con token CSRF:</li>
</ul>

<pre>
&lt;form method="POST" action="{{ route('admin.setAdmin', $user) }}"&gt;
    @csrf
    @method('PATCH')
    &lt;button type="submit" class="btn btn-secondary"&gt;Enable admin&lt;/button&gt;
&lt;/form&gt;
</pre>

![dopo mitigazione](https://github.com/user-attachments/assets/e51a3ac1-5197-417c-bb3a-0cdd01af1586)


<p>✅ Laravel ora blocca le richieste non autorizzate e impedisce modifiche da pagine esterne.</p>

<hr>

<h3>3. Verifica della Mitigazione</h3>

<p>
Dopo la modifica, se l’attaccante prova a rieseguire la pagina HTML maliziosa, Laravel risponde con:
</p>

<pre>
405 Method Not Allowed
</pre>

![errore pagina dopo mitigazione](https://github.com/user-attachments/assets/90e8c8e7-d027-4229-b6f0-c4db2051d925)


<p><strong>Risultato:</strong> attacco CSRF bloccato. L’elevazione dei privilegi da dominio esterno non è più possibile.</p>

<p>
✅ <strong>Challenge completata con successo.</strong>
</p>












# progetto-finale-cyber-Gianfranco-Cito
