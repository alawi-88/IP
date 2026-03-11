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
                if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
                $table->unsignedTinyInteger('min_team_members')->default(2);
                $table->unsignedTinyInteger('max_team_members')->nullable();
                $table->boolean('team_fields_enabled')->default(true);

                $table->json('label_register_as')->nullable();
                $table->json('option_register_individual')->nullable();
                $table->json('option_register_team')->nullable();

                $table->json('label_team_name')->nullable();
                $table->json('label_team_logo')->nullable();
                $table->json('label_team_serial')->nullable();
                $table->json('help_team_serial')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('registration_form_configs')) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
                try {                 if (Schema::hasColumn('registration_form_configs', 'min_team_members')) { $table->dropColumn('min_team_members'); }
                if (Schema::hasColumn('registration_form_configs', 'max_team_members')) { $table->dropColumn('max_team_members'); }
                if (Schema::hasColumn('registration_form_configs', 'team_fields_enabled')) { $table->dropColumn('team_fields_enabled'); }
                if (Schema::hasColumn('registration_form_configs', 'label_register_as')) { $table->dropColumn('label_register_as'); }
                if (Schema::hasColumn('registration_form_configs', 'option_register_individual')) { $table->dropColumn('option_register_individual'); }
                if (Schema::hasColumn('registration_form_configs', 'option_register_team')) { $table->dropColumn('option_register_team'); }
                if (Schema::hasColumn('registration_form_configs', 'label_team_name')) { $table->dropColumn('label_team_name'); }
                if (Schema::hasColumn('registration_form_configs', 'label_team_logo')) { $table->dropColumn('label_team_logo'); }
                if (Schema::hasColumn('registration_form_configs', 'label_team_serial')) { $table->dropColumn('label_team_serial'); }
                if (Schema::hasColumn('registration_form_configs', 'help_team_serial')) { $table->dropColumn('help_team_serial'); } } catch (\Exception $e) {}
            });
        }
    }
};
