<?php

namespace App\Services;

use App\Models\Garage;
use App\Models\JobCard;
use App\Models\JobCardItem;
use App\Models\Vehicle;
use App\Models\VehicleBrand;
use App\Models\VehicleModel;
use App\Models\PartBrand;
use App\Models\PartCategory;
use App\Models\Inventory;
use App\Models\Supplier;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GarageAIAssistant
{
    protected $apiKey;
    protected $garageId;

    public function __construct($garageId)
    {
        $this->garageId = $garageId;
        $garage = Garage::findOrFail($garageId);
        $this->apiKey = $garage->ai_api_key;
    }

    public function processPrompt($prompt)
    {
        if (empty($this->apiKey)) {
            return "Error: No Google Gemini API Key configured for this garage. Please add it in your Profile settings.";
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$this->apiKey}";

        $payload = [
            "systemInstruction" => [
                "parts" => [
                    ["text" => "You are a highly capable AI Assistant for PistonCRM (a CRM for auto garages). Your job is to assist the garage owner. You have access to tools to perform actions on their database. If the user asks to add an item/part to a vehicle's job card, extract the vehicle number and part name, and call the add_item_to_job_card tool. If the user asks to populate vehicle models for a brand, find out the real-world models for that brand, and call the save_vehicle_models tool. If the user asks to add manufacturer brands or part brands, use the save_part_brands tool. If the user asks to add part categories, use the save_part_categories tool. If the user asks to add items to inventory, use the add_inventory_items tool. If the user asks to add suppliers, use the add_suppliers tool. Always confirm the action was taken to the user in a friendly manner."]
                ]
            ],
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [
                        ["text" => $prompt]
                    ]
                ]
            ],
            "tools" => [
                [
                    "functionDeclarations" => [
                        [
                            "name" => "add_item_to_job_card",
                            "description" => "Adds a spare part to an active job card for a specific vehicle registration number.",
                            "parameters" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "vehicle_number" => ["type" => "STRING", "description" => "The registration number of the vehicle, e.g., KA01EU1234"],
                                    "part_name" => ["type" => "STRING", "description" => "The name of the part to add, e.g., Chain, Oil Filter"]
                                ],
                                "required" => ["vehicle_number", "part_name"]
                            ]
                        ],
                        [
                            "name" => "save_vehicle_models",
                            "description" => "Saves a list of vehicle models for a specific brand into the database.",
                            "parameters" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "brand_name" => ["type" => "STRING", "description" => "The name of the vehicle brand, e.g., Royal Enfield, Honda"],
                                    "models" => [
                                        "type" => "ARRAY",
                                        "items" => ["type" => "STRING"],
                                        "description" => "A comprehensive list of popular vehicle models for this brand."
                                    ]
                                ],
                                "required" => ["brand_name", "models"]
                            ]
                        ],
                        [
                            "name" => "save_part_brands",
                            "description" => "Saves a list of part/manufacturer brands (e.g. Bosch, NGK, Denso) into the database.",
                            "parameters" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "brands" => [
                                        "type" => "ARRAY",
                                        "items" => ["type" => "STRING"],
                                        "description" => "A list of part brand names to save."
                                    ]
                                ],
                                "required" => ["brands"]
                            ]
                        ],
                        [
                            "name" => "save_part_categories",
                            "description" => "Saves a list of part categories into the database.",
                            "parameters" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "categories" => [
                                        "type" => "ARRAY",
                                        "items" => ["type" => "STRING"],
                                        "description" => "A list of category names to save."
                                    ]
                                ],
                                "required" => ["categories"]
                            ]
                        ],
                        [
                            "name" => "add_suppliers",
                            "description" => "Adds new suppliers to the garage's supplier ledger.",
                            "parameters" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "suppliers" => [
                                        "type" => "ARRAY",
                                        "items" => [
                                            "type" => "OBJECT",
                                            "properties" => [
                                                "name" => ["type" => "STRING"],
                                                "phone" => ["type" => "STRING"],
                                                "address" => ["type" => "STRING"]
                                            ],
                                            "required" => ["name"]
                                        ]
                                    ]
                                ],
                                "required" => ["suppliers"]
                            ]
                        ],
                        [
                            "name" => "add_inventory_items",
                            "description" => "Adds new parts/items to the garage's inventory.",
                            "parameters" => [
                                "type" => "OBJECT",
                                "properties" => [
                                    "items" => [
                                        "type" => "ARRAY",
                                        "items" => [
                                            "type" => "OBJECT",
                                            "properties" => [
                                                "part_name" => ["type" => "STRING"],
                                                "part_code" => ["type" => "STRING"],
                                                "category" => ["type" => "STRING"],
                                                "brand" => ["type" => "STRING"],
                                                "current_stock" => ["type" => "INTEGER"],
                                                "current_price" => ["type" => "NUMBER"]
                                            ],
                                            "required" => ["part_name", "current_stock", "current_price"]
                                        ]
                                    ]
                                ],
                                "required" => ["items"]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        try {
            $response = Http::timeout(30)->withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json'
            ])->post($url, $payload);

            $data = $response->json();

            if (!isset($data['candidates'][0]['content']['parts'])) {
                Log::error('Gemini API Error: ' . json_encode($data));
                return "I'm sorry, I encountered an error communicating with the AI service. " . json_encode($data);
            }

            $parts = $data['candidates'][0]['content']['parts'];
            
            // Check for function call
            foreach ($parts as $part) {
                if (isset($part['functionCall'])) {
                    $functionName = $part['functionCall']['name'];
                    $args = $part['functionCall']['args'] ?? [];
                    
                    $functionResponseData = $this->executeFunction($functionName, $args);
                    
                    // Send function response back to Gemini to get final text
                    $secondPayload = $payload;
                    // Append model's function call
                    $secondPayload['contents'][] = [
                        "role" => "model",
                        "parts" => [$part]
                    ];
                    // Append our function response
                    $secondPayload['contents'][] = [
                        "role" => "user",
                        "parts" => [
                            [
                                "functionResponse" => [
                                    "name" => $functionName,
                                    "response" => ["name" => $functionName, "content" => $functionResponseData]
                                ]
                            ]
                        ]
                    ];

                    $secondResponse = Http::timeout(30)->withoutVerifying()->withHeaders(['Content-Type' => 'application/json'])->post($url, $secondPayload);
                    $secondData = $secondResponse->json();
                    
                    if (isset($secondData['candidates'][0]['content']['parts'][0]['text'])) {
                        return $secondData['candidates'][0]['content']['parts'][0]['text'];
                    }
                }
                
                if (isset($part['text'])) {
                    return $part['text'];
                }
            }

            return "Command processed successfully.";
            
        } catch (\Exception $e) {
            Log::error('Gemini API Exception: ' . $e->getMessage());
            return "An error occurred while processing your request: " . $e->getMessage();
        }
    }

    protected function executeFunction($name, $args)
    {
        try {
            if ($name === 'add_item_to_job_card') {
                return $this->addItemToJobCard($args['vehicle_number'], $args['part_name']);
            }
            if ($name === 'save_vehicle_models') {
                return $this->saveVehicleModels($args['brand_name'], $args['models']);
            }
            if ($name === 'save_part_brands') {
                return $this->savePartBrands($args['brands']);
            }
            if ($name === 'save_part_categories') {
                return $this->savePartCategories($args['categories']);
            }
            if ($name === 'add_suppliers') {
                return $this->addSuppliers($args['suppliers']);
            }
            if ($name === 'add_inventory_items') {
                return $this->addInventoryItems($args['items']);
            }
            return ["success" => false, "message" => "Unknown function."];
        } catch (\Exception $e) {
            return ["success" => false, "message" => $e->getMessage()];
        }
    }

    protected function addItemToJobCard($vehicleNumber, $partName)
    {
        $normalizedSearch = preg_replace('/[^a-zA-Z0-9]/', '', $vehicleNumber);
        
        $vehicle = Vehicle::where('garage_id', $this->garageId)
            ->whereRaw("REPLACE(REPLACE(registration_number, ' ', ''), '-', '') LIKE ?", ["%{$normalizedSearch}%"])
            ->first();

        if (!$vehicle) {
            return ["success" => false, "message" => "Vehicle with number {$vehicleNumber} not found in this garage."];
        }

        $jobCard = JobCard::where('vehicle_id', $vehicle->id)
            ->where('garage_id', $this->garageId)
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->latest()
            ->first();

        if (!$jobCard) {
            return ["success" => false, "message" => "No active job card found for vehicle {$vehicleNumber}."];
        }

        JobCardItem::create([
            'garage_id' => $this->garageId,
            'job_card_id' => $jobCard->id,
            'item_type' => 'spare_part',
            'item_name' => $partName,
            'quantity' => 1,
            'unit_price' => 0,
            'total' => 0,
        ]);

        return ["success" => true, "message" => "Successfully added {$partName} to Job Card {$jobCard->job_card_number}."];
    }

    protected function saveVehicleModels($brandName, $models)
    {
        $brand = VehicleBrand::firstOrCreate(
            ['name' => $brandName, 'garage_id' => $this->garageId],
            ['is_active' => true]
        );

        $addedCount = 0;
        foreach ($models as $modelName) {
            $exists = VehicleModel::where('brand_id', $brand->id)
                ->where('name', $modelName)
                ->exists();
                
            if (!$exists) {
                VehicleModel::create([
                    'brand_id' => $brand->id,
                    'garage_id' => $this->garageId,
                    'name' => $modelName,
                    'type' => 'motorcycle',
                    'is_active' => true,
                ]);
                $addedCount++;
            }
        }

        return ["success" => true, "message" => "Successfully processed brand {$brandName}. Added {$addedCount} new models."];
    }

    protected function savePartBrands($brands)
    {
        $addedCount = 0;
        foreach ($brands as $brandName) {
            $exists = PartBrand::where('garage_id', $this->garageId)
                ->where('name', $brandName)
                ->exists();
                
            if (!$exists) {
                PartBrand::create([
                    'garage_id' => $this->garageId,
                    'name' => $brandName,
                ]);
                $addedCount++;
            }
        }

        return ["success" => true, "message" => "Successfully added {$addedCount} new part brands."];
    }

    protected function savePartCategories($categories)
    {
        $addedCount = 0;
        foreach ($categories as $catName) {
            $exists = PartCategory::where('garage_id', $this->garageId)
                ->where('name', $catName)
                ->exists();
                
            if (!$exists) {
                PartCategory::create([
                    'garage_id' => $this->garageId,
                    'name' => $catName,
                ]);
                $addedCount++;
            }
        }
        return ["success" => true, "message" => "Successfully added {$addedCount} new part categories."];
    }

    protected function addSuppliers($suppliers)
    {
        $addedCount = 0;
        foreach ($suppliers as $sup) {
            Supplier::create([
                'garage_id' => $this->garageId,
                'name' => $sup['name'],
                'phone' => $sup['phone'] ?? null,
                'address' => $sup['address'] ?? null,
                'is_active' => true,
            ]);
            $addedCount++;
        }
        return ["success" => true, "message" => "Successfully added {$addedCount} new suppliers."];
    }

    protected function addInventoryItems($items)
    {
        $addedCount = 0;
        foreach ($items as $item) {
            Inventory::create([
                'garage_id' => $this->garageId,
                'part_name' => $item['part_name'],
                'part_code' => $item['part_code'] ?? null,
                'category' => $item['category'] ?? null,
                'brand' => $item['brand'] ?? null,
                'current_stock' => $item['current_stock'] ?? 0,
                'current_price' => $item['current_price'] ?? 0,
                'min_stock_level' => 5,
                'is_active' => true,
            ]);
            $addedCount++;
        }
        return ["success" => true, "message" => "Successfully added {$addedCount} new inventory items."];
    }
}
