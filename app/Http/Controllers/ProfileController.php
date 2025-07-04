<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        // VERSIONE VULNERABILE: nessuna validazione, nessuna protezione
        $user = Auth::user();
        $user->update($request->all());

        return redirect()->route('profile.edit')->with('message', 'Profile updated');
    }
}
