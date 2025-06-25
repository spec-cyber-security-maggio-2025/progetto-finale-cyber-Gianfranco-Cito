<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class LogSuccessfulLogout
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
      \Log::info('Logout effettuato', [
        'utente' => $event->user->id,
        'email' => $event->user->email,
        'ip' => request()->ip(),
        'timestamp' => now()
    ]);
    }
}
