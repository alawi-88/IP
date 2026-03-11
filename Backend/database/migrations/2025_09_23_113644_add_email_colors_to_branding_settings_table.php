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
        if (Schema::hasTable('branding_settings')) {
            Schema::table('branding_settings', function (Blueprint $table) {
            $table->string('email_bg_color')->nullable();
            $table->string('email_text_color')->nullable();
            $table->string('email_link_color')->nullable();
            $table->string('email_border_color')->nullable();
            $table->string('email_footer')->nullable();
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('branding_settings')) {
            Schema::table('branding_settings', function (Blueprint $table) {
            try { $table->dropColumn('email_bg_color'); } catch (\Exception $e) {}
            try { $table->dropColumn('email_text_color'); } catch (\Exception $e) {}
            try { $table->dropColumn('email_link_color'); } catch (\Exception $e) {}
            try { $table->dropColumn('email_border_color'); } catch (\Exception $e) {}
            try { $table->dropColumn('email_footer'); } catch (\Exception $e) {}
        });
        }
    }
};
