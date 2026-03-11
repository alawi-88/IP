<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('contact_us')) {
            Schema::table('contact_us', function (Blueprint $table) {
            try { $table->dropForeign('contact_us_participant_id_foreign'); } catch (\Exception $e) {}

            $table->renameColumn('participant_id', 'model_id');

            $table->string('model_type');

            $table->index(['model_type', 'model_id'], 'contact_us_morph_idx');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('contact_us')) {
            Schema::table('contact_us', function (Blueprint $table) {
            try { $table->dropColumn('model_type'); } catch (\Exception $e) {}

            $table->renameColumn('model_id', 'participant_id');

            $table->foreign('participant_id')->references('id')->on('participants')->cascadeOnDelete();
        });
        }
    }
};
