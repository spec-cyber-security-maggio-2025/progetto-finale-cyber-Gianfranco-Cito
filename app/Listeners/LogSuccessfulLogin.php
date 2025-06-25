<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
       \Log::info('Login effettuato', [
        'utente' => $event->user->id,
        'email' => $event->user->email,
        'ip' => request()->ip(),
        'timestamp' => now()
    ]);
    }
}
