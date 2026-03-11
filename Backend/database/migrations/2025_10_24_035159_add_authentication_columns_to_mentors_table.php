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
                if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            if (!Schema::hasColumn('mentors', 'profession')) {
                $table->json('profession');
            }
            if (!Schema::hasColumn('mentors', 'email')) {
                $table->string('email')->nullable();
            }
            if (!Schema::hasColumn('mentors', 'phone')) {
                $table->string('phone')->nullable();
            }
            if (!Schema::hasColumn('mentors', 'password')) {
                $table->string('password')->nullable();
            }
            if (!Schema::hasColumn('mentors', 'password_reset_code')) {
                $table->string('password_reset_code')->nullable();
            }
            if (!Schema::hasColumn('mentors', 'otp_code')) {
                $table->string('otp_code')->nullable();
            }
            if (!Schema::hasColumn('mentors', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable();
            }
        });
        }
        
        // First, update any empty emails to null to prevent unique constraint violations
        \DB::table('mentors')->where('email', '')->update(['email' => null]);
        
        // Check if email column exists and has unique constraint
        if (Schema::hasColumn('mentors', 'email')) {
            // Check if unique constraint already exists
            $indexes = \DB::select("SHOW INDEX FROM mentors WHERE Column_name='email' AND Non_unique=0");
            
            if (empty($indexes)) {
                // Update any empty or duplicate emails to ensure uniqueness
                $emails = \DB::table('mentors')->select('id', 'email')->get();
                $emailCounts = [];
                
                foreach ($emails as $mentor) {
                    if (!empty($mentor->email)) {
                        if (isset($emailCounts[$mentor->email])) {
                            // Duplicate found, make it unique by appending id
                            \DB::table('mentors')
                                ->where('id', $mentor->id)
                                ->update(['email' => $mentor->email . '_' . $mentor->id]);
                        } else {
                            $emailCounts[$mentor->email] = true;
                        }
                    }
                }
                
                // Add unique constraint
                if (Schema::hasTable('mentors')) {
                    Schema::table('mentors', function (Blueprint $table) {
                    $table->unique('email', 'mentors_email_unique');
                });
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('mentors')) {
            Schema::table('mentors', function (Blueprint $table) {
            if (Schema::hasColumn('mentors', 'profession')) { $table->dropColumn('profession'); }
            if (Schema::hasColumn('mentors', 'email')) { $table->dropColumn('email'); }
            if (Schema::hasColumn('mentors', 'phone')) { $table->dropColumn('phone'); }
            if (Schema::hasColumn('mentors', 'password')) { $table->dropColumn('password'); }
            if (Schema::hasColumn('mentors', 'password_reset_code')) { $table->dropColumn('password_reset_code'); }
            if (Schema::hasColumn('mentors', 'otp_code')) { $table->dropColumn('otp_code'); }
            if (Schema::hasColumn('mentors', 'last_login_at')) { $table->dropColumn('last_login_at'); }
        });
        }
    }
};
