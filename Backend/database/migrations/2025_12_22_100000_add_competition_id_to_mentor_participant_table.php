<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {

        // Drop foreign keys before dropping columns
        $__fks = DB::select("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mentor_participant' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
        $__fkNames = array_map(fn($fk) => $fk->CONSTRAINT_NAME, $__fks);
        if (in_array('mentor_participant_competition_id_foreign', $__fkNames)) {
            Schema::table('mentor_participant', fn(Blueprint $t) => $t->dropForeign(['competition_id']));
        }

                Schema::table('mentor_participant', function (Blueprint $table) {
            // Add column only if it doesn't already exist to avoid "Duplicate column" errors
            if (! Schema::hasColumn('mentor_participant', 'competition_id')) {
                $table->foreignId('competition_id')
                    ->nullable()
                    
                    ->constrained('competitions')
                    ->nullOnDelete();
            }
            // ملاحظة: تركنا الـ unique/index القديم كما هو لتجنّب كسر أي foreign key
            // ويمكن تعديل الـ constraints لاحقًا بعد مراجعة بنية الجدول في قاعدة البيانات.
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mentor_participant')) {
            Schema::table('mentor_participant', function (Blueprint $table) {
$table->dropUnique('mentor_participant_unique_v2');
            if (Schema::hasColumn('mentor_participant', 'competition_id')) { $table->dropColumn('competition_id'); }
            
            // Restore old unique constraint
            $table->unique(['mentor_id', 'participant_id']);
        });
        }
    }
};
