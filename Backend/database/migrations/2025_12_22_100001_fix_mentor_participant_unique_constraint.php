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
        // تم إلغاء أي تعديل على الـ unique/index هنا لتجنب كسر الـ foreign keys
        // تُركت الميجريشن كـ no-op (بدون تغييرات فعلية).
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
