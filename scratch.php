<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$garage = App\Models\Garage::whereNotNull('ai_api_key')->first();
if (!$garage || !$garage->ai_api_key) {
    die("No API Key\n");
}

$service = new App\Services\GarageAIAssistant($garage->id);

// Hack the URL in the service just for this test
$reflection = new ReflectionClass($service);
$property = $reflection->getProperty('apiKey');
$property->setAccessible(true);
$apiKey = $property->getValue($service);

$url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}";
$payload = [
    "contents" => [["role" => "user", "parts" => [["text" => "Hi"]]]]
];
$response = Illuminate\Support\Facades\Http::withoutVerifying()->post($url, $payload);
echo "Response: " . $response->body() . "\n";
