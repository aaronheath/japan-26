<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AddressLookupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressLookupController extends Controller
{
    public function __construct(
        protected AddressLookupService $addressLookupService,
    ) {}

    public function autocomplete(Request $request): JsonResponse
    {
        $request->validate([
            'query' => ['required', 'string', 'min:3'],
        ]);

        $suggestions = $this->addressLookupService->autocomplete($request->input('query'));

        return response()->json($suggestions);
    }

    public function placeDetails(string $placeId): JsonResponse
    {
        $details = $this->addressLookupService->placeDetails($placeId);

        return response()->json($details);
    }
}
