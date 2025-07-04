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


<h2 style="color:#2c3e50;">🔐 CHALLENGE 3: Logs mancanti per operazioni critiche</h2>
<p><strong>Autore:</strong> Gianfranco Cito</p>

<h3>1. Descrizione del problema</h3>
<p>
Nel sistema originario mancavano log per le operazioni critiche, come accessi, registrazioni, creazione, modifica ed eliminazione di articoli e cambi di ruolo utente. 
L'assenza di questi log rendeva impossibile attribuire responsabilità in caso di attacchi 
(<em>violazione dei principi di accountability e non-repudiation</em>).
</p>

<h3>2. Attacco simulato</h3>
<p>
Un attacco DoS era stato simulato in precedenza. Tuttavia:
<ul>
  <li>Nessuna informazione sull’IP dell’attaccante</li>
  <li>Nessun tracciamento delle modifiche agli utenti</li>
  <li>Nessun log per le eliminazioni articoli</li>
</ul>
</p>

<h3>3. Mitigazione implementata</h3>
<p>
È stato integrato il sistema di logging di Laravel con chiamate a <code>Log::info()</code> e <code>Log::warning()</code> nei punti critici.
</p>

<h4>✔️ Logging implementato in:</h4>
<ul>
  <li>Login, logout, registrazione utente (tramite Event e Listener)</li>
  <li>Promozione utenti a ruoli speciali (admin, revisor, writer)</li>
  <li>Creazione, modifica, eliminazione articoli</li>
  <li>Blocco IP tramite rate limiting nel metodo <code>articleSearch()</code></li>
</ul>



<h3>4. Esempio di log generato</h3>
<pre style="background:#f4f4f4; padding:12px; border-left:4px solid #2ecc71;">
[2025-06-25 15:30:12] local.INFO: 📝 Articolo creato {"id":12,"titolo":"Cybersecurity nella Sanità","autore":5,"ip":"127.0.0.1"}
[2025-06-25 15:31:02] local.WARNING: 🗑️ Articolo eliminato {"id":9,"titolo":"Attacco XSS","eliminato_da":5,"ip":"127.0.0.1"}
[2025-06-25 15:33:10] local.INFO: Login effettuato {"utente":5,"email":"admin@cyber.blog","ip":"127.0.0.1","timestamp":"2025-06-25 15:33:10"}
</pre>

![1](https://github.com/user-attachments/assets/2b7ff08c-2920-4c60-be0b-b143ea82e22b)

![2](https://github.com/user-attachments/assets/ccd5d9cd-efce-43a4-a9fa-5d1310675b7f)

![3](https://github.com/user-attachments/assets/6b7a5c25-96f9-4a35-ac41-54ff91dbccb3)




<h3>5. Verifica</h3>
<p>
Consultando il file <code>storage/logs/laravel.log</code> è ora possibile risalire ad ogni operazione sensibile,
facilitando audit trail e analisi post-attacco.
</p>

![4](https://github.com/user-attachments/assets/91d1b6c6-835b-4181-96bd-725aa867f3a4)


<h2 style="color:#2c3e50;">CHALLENGE 4: Manomissione input (SSRF + Misconfigured CORS)</h2>
<p><strong>Autore:</strong> Gianfranco Cito</p>

<h3>🧪 1. Scenario dell'attacco</h3>
<p>
Nella pagina di creazione articolo era presente un componente Livewire <code>&lt;livewire:latest-news /&gt;</code> che suggeriva notizie recenti da NewsAPI. L'utente poteva selezionare la lingua (IT / EN) tramite un menu a tendina che controllava l’URL usato per la richiesta.
</p>

<p>
Ispezionando il DOM era possibile modificare il valore <code>&lt;option value="..."&gt;</code> inserendo un URL arbitrario, ad esempio verso un host interno come <code>http://internal.finance:8001/user-data.php</code>.
</p>

<p>
Poiché Laravel eseguiva direttamente la fetch del valore selezionato, un utente malintenzionato poteva forzare l’applicazione a effettuare una richiesta HTTP a una risorsa interna: <strong>SSRF (Server-Side Request Forgery)</strong>.
</p>

<pre><code>
&lt;option value="http://internal.finance:8001/user-data.php" selected&gt;NewsAPI - IT&lt;/option&gt;
</code></pre>

<p>
Con CORS male configurato e senza restrizioni lato server, l'utente otteneva in risposta dati finanziari sensibili.
</p>

<hr>

<h3>🔧 2. Mitigazione lato Livewire (LatestNews.php)</h3>
<p>Per evitare la manipolazione dell’HTML lato client, la logica è stata riscritta usando una <strong>whitelist</strong> di URL ammessi. Qualsiasi URL non presente nella lista viene ignorato.</p>

<pre><code>
public function fetchNews()
{
    $allowedApis = [
        'it' =&gt; 'https://newsapi.org/v2/top-headlines?country=it&amp;apiKey=' . env('NEWS_API_KEY'),
        'en' =&gt; 'https://newsapi.org/v2/top-headlines?country=us&amp;apiKey=' . env('NEWS_API_KEY'),
    ];

    if (!isset($allowedApis[$this->selectedApi])) {
        $this->news = ['error' =&gt; 'API non autorizzata'];
        return;
    }

    $url = $allowedApis[$this->selectedApi];
    $this->news = json_decode($this->httpService-&gt;getRequest($url), true);
}
</code></pre>

<p>✅ L’utente può ora selezionare solo API predefinite (IT o EN). Qualunque altro URL viene scartato.</p>

<hr>

<h3>🛡️ 3. Mitigazione lato HttpService (HttpService.php)</h3>
<p>Per maggiore sicurezza, viene impedito a utenti non admin di effettuare richieste verso indirizzi interni:</p>

<pre><code>
public function getRequest(string $url)
{
    $user = auth()-&gt;user();

    if (str_contains($url, 'internal.finance') &amp;&amp; (!$user || !$user-&gt;is_admin)) {
        abort(403, 'Accesso non autorizzato a risorsa interna');
    }

    try {
        $client = new Client();
        $response = $client-&gt;request('GET', $url);
        return $response-&gt;getBody()-&gt;getContents();
    } catch (\Exception $e) {
        return json_encode(['error' =&gt; 'Richiesta fallita']);
    }
}
</code></pre>

<p>✅ Anche in caso di bypass HTML, la richiesta verrà bloccata lato server.</p>

<hr>

<h3>🔍 4. Verifica della mitigazione</h3>
<ul>
  <li>Modificando l’HTML e forzando un URL esterno → restituisce <code>API non autorizzata</code></li>
  <li>Se un utente writer prova a raggiungere <code>internal.finance</code> → Laravel mostra <code>403 Forbidden</code></li>
</ul>

<p>
✅ Attacco SSRF mitigato con successo sia a livello di interfaccia che di backend.
</p>

<hr>

<p style="margin-top: 20px;"><strong>Challenge completata con successo ✅</strong></p>



<h1 style="color:#2c3e50;">🛡️ CHALLENGE 5: Validazione contenuto articolo non corretta</h1>
<h2>Autore: Gianfranco Cito</h2>

<h3>1.  Descrizione dell'attacco</h3>
<p>
Durante la creazione di un articolo su <code>/articles/create</code>, è possibile sfruttare strumenti come <strong>BurpSuite</strong> per intercettare e modificare la richiesta POST. In questo modo, un utente malintenzionato può iniettare uno <strong>script XSS (Stored Cross-Site Scripting)</strong> direttamente nel contenuto del campo <code>body</code>, eludendo l’editor visuale.
</p>

<h4> Payload XSS usati</h4>
<pre>
&lt;script&gt;alert('XSS riuscito!')&lt;/script&gt;
&lt;img src="x" onerror="alert('XSS')"&gt;
</pre>

<p>
Una volta salvato l'articolo, lo script viene eseguito ogni volta che un altro utente visita la pagina <code>/articles/{id}</code>, dimostrando un attacco XSS persistente.
</p>
![hacked](https://github.com/user-attachments/assets/cbb8e97d-62ce-4237-a787-f6eccf85c32e)


<h4>📸 Esempio BurpSuite</h4>
<img src="https://portswigger.net/web-security/images/stored-xss.png" alt="Esempio Burp XSS" width="500">

![burpsuite](https://github.com/user-attachments/assets/1dcbf78a-d4b6-463a-b010-48f6ac701de0)
![repeater](https://github.com/user-attachments/assets/b5a2e083-03a0-4589-9aab-7881f1e21646)



<hr>

<h3>2. 🔐 Mitigazione</h3>
<p>
Per prevenire l'inserimento di codice dannoso, è stata implementata una <strong>sanificazione lato server</strong> del campo <code>body</code>, tramite <code>strip_tags()</code> con whitelist limitata di tag sicuri.
</p>

<h4>📌 Codice aggiornato nel controller:</h4>

![mitigazione 1](https://github.com/user-attachments/assets/24bbc3ca-9e0b-4700-8223-7b9b389dbfa9)






<pre><code>// Esempio nel metodo store() e update()
'body' => strip_tags($request->body, '&lt;p&gt;&lt;b&gt;&lt;i&gt;&lt;ul&gt;&lt;li&gt;&lt;a&gt;&lt;strong&gt;&lt;em&gt;'),
</code></pre>

<p>Inoltre, viene mantenuto il rendering HTML sicuro con il costrutto Laravel <code>{!! ... !!}</code> nella view <code>articles/show.blade.php</code>, solo dopo che i contenuti sono stati sanitizzati:</p>

<pre><code>&lt;p&gt;{!! $article-&gt;body !!}&lt;/p&gt;
</code></pre>
![mitigazione 2](https://github.com/user-attachments/assets/f568e520-ddd0-4ec7-b10c-ba833ed99307)



<hr>

<h3>3.  Verifica della mitigazione</h3>
<p>
Dopo la mitigazione, eventuali tag <code>&lt;script&gt;</code> o eventi inline come <code>onerror</code> vengono automaticamente rimossi, impedendo l'esecuzione di JavaScript dannoso.
</p>

<h4> Risultato finale:</h4>
<ul>
  <li>✔️ Nessun alert mostrato</li>
  <li>✔️ Nessun payload salvato nel database</li>
  <li>✔️ Stored XSS mitigato con successo</li>
</ul>

![Screenshot 2025-07-04 100042](https://github.com/user-attachments/assets/967b6e9b-7dbe-4f76-8923-a3e683ff8c99)


![Screenshot 2025-07-04 100103](https://github.com/user-attachments/assets/fcc37cad-c27d-41a3-bcb8-697139947aab)



<section id="challenge-6">
  <h2>CHALLENGE 6: Uso non corretto della proprietà <code>fillable</code> nei modelli</h2>

  <h3>Scenario</h3>
  <p>
    A causa di una scarsa conoscenza del framework, i campi che il modello accetta in mass assignment
    non sono stati dichiarati correttamente. Tipicamente i dati provengono da form e finiscono
    direttamente sul modello senza alcun filtro.
  </p>

  <h3>Attacco (Mass Assignment)</h3>
  <p>
    Un utente malintenzionato può alterare via browser il form aggiungendo campi come
    <code>is_admin</code>, <code>is_revisor</code> o <code>is_writer</code>, 
    ottenendo così un’elevazione di privilegi involontaria.
  </p>

  <h3>Implementazione Vulnerabile</h3>
  <ul>
    <li>Rotte e controller per la pagina di profilo utente permettono di modificare:
      <ul>
        <li>nome</li>
        <li>email</li>
        <li>password</li>
      </ul>
    </li>
    <li>Il modello <code>User</code> inizialmente non dichiara restrizioni, quindi accetta
      in mass assignment tutti i campi presenti nella request.</li>
  </ul>

  <h3>Mitigazione</h3>
  <p>
    Utilizzare la proprietà <code>protected $fillable</code> nel modello per elencare
    solo i campi ammessi dal form. Qualsiasi altro campo inviato tramite request verrà ignorato,
    prevenendo escalation di privilegi.
  </p>

  <h4>Esempio di modello <code>User</code> corretto</h4>
  <pre><code class="language-php">
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // Solo i campi gestiti dal form di profilo
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
  </code></pre>

  <h4>Uso in controller</h4>
  <pre><code class="language-php">
// Nel controller ProfiloController
public function update(Request $request)
{
    $data = $request->validate([
        'name'     => 'required|string|max:255',
        'email'    => 'required|email|unique:users,email,' . Auth::id(),
        'password' => 'nullable|string|min:8|confirmed',
    ]);

    Auth::user()->update($data);

    return back()->with('message', 'Profilo aggiornato con successo.');
}
  </code></pre>
</section>








# progetto-finale-cyber-Gianfranco-Cito
