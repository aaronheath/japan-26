<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreWhitelistedEmailRequest;
use App\Models\WhitelistedEmail;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WhitelistedEmailController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('admin/whitelisted-emails', [
            'emails' => WhitelistedEmail::query()
                ->orderBy('email')
                ->get(),
        ]);
    }

    public function store(StoreWhitelistedEmailRequest $request): RedirectResponse
    {
        WhitelistedEmail::create([
            'email' => strtolower($request->validated('email')),
        ]);

        return back();
    }

    public function destroy(WhitelistedEmail $whitelistedEmail): RedirectResponse
    {
        $whitelistedEmail->delete();

        return back();
    }
}
