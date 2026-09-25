<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Garage;
use App\Services\GarageAIAssistant;

class AIAssistantController extends Controller
{
    public function updateKey(Request $request)
    {
        if (!auth()->check() || !in_array(auth()->user()->role, ['owner', 'super_admin'])) {
            abort(403, 'Unauthorized access.');
        }

        $validated = $request->validate([
            'ai_api_key' => 'nullable|string|max:500'
        ]);

        $garageId = Garage::getActiveGarageId();
        if (!$garageId) abort(403, 'No active garage selected.');

        $garage = Garage::findOrFail($garageId);
        if ($request->filled('ai_api_key')) {
            $garage->update(['ai_api_key' => trim($validated['ai_api_key'])]);
        }

        return redirect()->back()->with('success', 'AI API Key updated successfully.');
    }

    public function processCommand(Request $request)
    {
        if (!auth()->check() || auth()->user()->role === 'customer') {
            return response()->json(['error' => 'Unauthorized access.'], 403);
        }

        $validated = $request->validate([
            'command' => 'required|string|max:1000'
        ]);

        $garageId = Garage::getActiveGarageId();
        if (!$garageId) {
            return response()->json(['error' => 'No active garage selected.'], 403);
        }

        $garage = Garage::findOrFail($garageId);
        if (!$garage->ai_api_key) {
            return response()->json(['error' => 'API Key is missing. Please configure it in Profile settings.'], 400);
        }

        // Use the service for Gemini API Function Calling
        $aiService = new GarageAIAssistant($garageId);
        $aiResponseText = $aiService->processPrompt($validated['command']);

        return response()->json([
            'message' => $aiResponseText
        ]);
    }
}
