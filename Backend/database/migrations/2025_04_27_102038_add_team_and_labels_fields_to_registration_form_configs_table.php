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
                if (!Schema::hasColumn('registration_form_configs', 'min_team_members')) { $table->unsignedTinyInteger('min_team_members')->default(2); }
                if (!Schema::hasColumn('registration_form_configs', 'max_team_members')) { $table->unsignedTinyInteger('max_team_members')->nullable(); }
                if (!Schema::hasColumn('registration_form_configs', 'team_fields_enabled')) { $table->boolean('team_fields_enabled')->default(true); }

                if (!Schema::hasColumn('registration_form_configs', 'label_register_as')) { $table->json('label_register_as')->nullable(); }
                if (!Schema::hasColumn('registration_form_configs', 'option_register_individual')) { $table->json('option_register_individual')->nullable(); }
                if (!Schema::hasColumn('registration_form_configs', 'option_register_team')) { $table->json('option_register_team')->nullable(); }

                if (!Schema::hasColumn('registration_form_configs', 'label_team_name')) { $table->json('label_team_name')->nullable(); }
                if (!Schema::hasColumn('registration_form_configs', 'label_team_logo')) { $table->json('label_team_logo')->nullable(); }
                if (!Schema::hasColumn('registration_form_configs', 'label_team_serial')) { $table->json('label_team_serial')->nullable(); }
                if (!Schema::hasColumn('registration_form_configs', 'help_team_serial')) { $table->json('help_team_serial')->nullable(); }
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
