<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AIAssistantController extends Controller
{
    public function updateKey(Request $request)
    {
        $validated = $request->validate([
            'ai_api_key' => 'nullable|string'
        ]);

        $garageId = \App\Models\Garage::getActiveGarageId();
        if (!$garageId) abort(403);

        $garage = \App\Models\Garage::findOrFail($garageId);
        $garage->update(['ai_api_key' => $validated['ai_api_key']]);

        return redirect()->back()->with('success', 'AI API Key updated successfully.');
    }

    public function processCommand(Request $request)
    {
        $validated = $request->validate([
            'command' => 'required|string'
        ]);

        $garageId = \App\Models\Garage::getActiveGarageId();
        if (!$garageId) return response()->json(['error' => 'No active garage.'], 403);

        $garage = \App\Models\Garage::findOrFail($garageId);
        if (!$garage->ai_api_key) {
            return response()->json(['error' => 'API Key is missing. Please configure it in Profile settings.'], 400);
        }

        // Use the newly created service for Gemini API Function Calling
        $aiService = new \App\Services\GarageAIAssistant($garageId);
        $aiResponseText = $aiService->processPrompt($validated['command']);

        return response()->json([
            'message' => $aiResponseText
        ]);
    }
}
