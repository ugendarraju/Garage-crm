<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\Garage;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('garages', 'slug')) {
            Schema::table('garages', function (Blueprint $table) {
                $table->string('slug')->nullable()->unique()->after('name');
            });
        }

        // Populate existing garages with a default slug
        $garages = Garage::all();
        foreach ($garages as $garage) {
            if (empty($garage->slug) && !empty($garage->name)) {
                $baseSlug = Str::slug($garage->name) ?: ('garage-' . $garage->id);
                $slug = $baseSlug;
                $count = 1;
                while (Garage::where('slug', $slug)->where('id', '!=', $garage->id)->exists()) {
                    $slug = "{$baseSlug}-{$count}";
                    $count++;
                }
                $garage->slug = $slug;
                $garage->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('garages', 'slug')) {
            Schema::table('garages', function (Blueprint $table) {
                $table->dropColumn('slug');
            });
        }
    }
};
