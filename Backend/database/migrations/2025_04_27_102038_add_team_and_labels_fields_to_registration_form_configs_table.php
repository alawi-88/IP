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
        Schema::table('registration_form_configs', function (Blueprint $table) {
            // Now add the new fields
            $table->unsignedTinyInteger('min_team_members')->default(2)->after('max_age');
            $table->unsignedTinyInteger('max_team_members')->nullable()->after('min_team_members');
            $table->boolean('team_fields_enabled')->default(true)->after('max_team_members');

            $table->json('label_register_as')->nullable()->after('team_fields_enabled');
            $table->json('option_register_individual')->nullable()->after('label_register_as');
            $table->json('option_register_team')->nullable()->after('option_register_individual');

            $table->json('label_team_name')->nullable()->after('option_register_team');
            $table->json('label_team_logo')->nullable()->after('label_team_name');
            $table->json('label_team_serial')->nullable()->after('label_team_logo');
            $table->json('help_team_serial')->nullable()->after('label_team_serial');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registration_form_configs', function (Blueprint $table) {
            Schema::table('registration_form_configs', function (Blueprint $table) {
                $table->dropColumn([
                    'min_team_members',
                    'max_team_members',
                    'team_fields_enabled',
                    'label_register_as',
                    'option_register_individual',
                    'option_register_team',
                    'label_team_name',
                    'label_team_logo',
                    'label_team_serial',
                    'help_team_serial',
                ]);
            });
        });
    }
};
