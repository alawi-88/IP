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
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            $table->foreignId('nationality_id')
                ->nullable()
                
                ->constrained('nationalities');
            $table->foreignId('country_id')
                ->nullable()
                
                ->constrained('countries');
            $table->foreignId('residence_city_id')
                ->nullable()
                
                ->constrained('cities');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('participants')) {
            Schema::table('participants', function (Blueprint $table) {
            try { $table->dropForeign(['nationality_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('nationality_id'); } catch (\Exception $e) {}
            try { $table->dropForeign(['country_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('country_id'); } catch (\Exception $e) {}
            try { $table->dropForeign(['residence_city_id']); } catch (\Exception $e) {}
            try { $table->dropColumn('residence_city_id'); } catch (\Exception $e) {}
        });
        }
    }
};
