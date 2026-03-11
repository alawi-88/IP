<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Migrate existing AI enhancement data to the new table
        $existingConfigs = DB::table('form_ai_scoring_configs')
            ->whereNotNull('ai_enhancement_enabled')
            ->orWhere('ai_enhancement_enabled', true)
            ->get();

        foreach ($existingConfigs as $config) {
            if ($config->ai_enhancement_enabled || !empty($config->ai_enhancement_fields)) {
                DB::table('form_ai_enhancement_configs')->insert([
                    'form_id' => $config->form_id,
                    'ai_enhancement_enabled' => $config->ai_enhancement_enabled ?? false,
                    'ai_enhancement_fields' => $config->ai_enhancement_fields,
                    'created_at' => $config->created_at,
                    'updated_at' => $config->updated_at,
                ]);
            }
        }

        // Remove AI enhancement fields from form_ai_scoring_configs table
        if (Schema::hasTable('form_ai_scoring_configs')) {
            Schema::table('form_ai_scoring_configs', function (Blueprint $table) {
            try { $table->dropColumn([
                'ai_enhancement_enabled',
                'ai_enhancement_context',
                'ai_enhancement_instructions',
                'ai_enhancement_fields',
            ]); } catch (\Exception $e) {}
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add AI enhancement fields back to form_ai_scoring_configs
        if (Schema::hasTable('form_ai_scoring_configs')) {
            Schema::table('form_ai_scoring_configs', function (Blueprint $table) {
            $table->boolean('ai_enhancement_enabled')->default(false);
            $table->text('ai_enhancement_context')->nullable();
            $table->text('ai_enhancement_instructions')->nullable();
            $table->json('ai_enhancement_fields')->nullable();
        });
        }

        // Migrate data back
        $enhancementConfigs = DB::table('form_ai_enhancement_configs')->get();
        
        foreach ($enhancementConfigs as $enhancement) {
            DB::table('form_ai_scoring_configs')
                ->where('form_id', $enhancement->form_id)
                ->update([
                    'ai_enhancement_enabled' => $enhancement->ai_enhancement_enabled,
                    'ai_enhancement_fields' => $enhancement->ai_enhancement_fields,
                ]);
        }
    }
};
