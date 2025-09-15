<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorController extends Controller
{
    public function edit(Request $request): View
    {
        $profile = $request->user()->profile;
        return view('creator.settings', [
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_creator' => ['required', 'boolean'],
            'monthly_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
        ]);

        /** @var Profile $profile */
        $profile = $request->user()->profile ?? new Profile(['user_id' => $request->user()->id]);
        $profile->fill([
            'is_creator' => (bool) $validated['is_creator'],
            'monthly_price' => $validated['monthly_price'] ?? $profile->monthly_price,
            'currency' => strtoupper($validated['currency'] ?? ($profile->currency ?? 'USD')),
        ]);
        $profile->save();

        return back()->with('status', 'Creator settings updated.');
    }
}
