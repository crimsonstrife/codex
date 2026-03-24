<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Sprint 12.3 — User notification preferences.
 *
 * Handles the email-notifications toggle on the profile page.
 */
class ProfilePreferencesController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'email_notifications' => 'boolean',
        ]);

        Auth::user()->update([
            'email_notifications' => $request->boolean('email_notifications'),
        ]);

        return back()->with('status', 'preferences-saved');
    }
}
