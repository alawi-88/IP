<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompetitionLabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'competition_id',
        'key',
        'category',
        'label_en',
        'label_ar',
    ];

    /**
     * Default labels that get seeded when a competition is created.
     */
    public const DEFAULT_LABELS = [
        // Stage Names
        ['key' => 'stage_registration', 'category' => 'stages', 'label_en' => 'Registration', 'label_ar' => 'التسجيل'],
        ['key' => 'stage_team_formation', 'category' => 'stages', 'label_en' => 'Team Formation', 'label_ar' => 'تشكيل الفريق'],
        ['key' => 'stage_project_submission', 'category' => 'stages', 'label_en' => 'Project Submission', 'label_ar' => 'تقديم المشروع'],
        ['key' => 'stage_evaluation', 'category' => 'stages', 'label_en' => 'Evaluation', 'label_ar' => 'التقييم'],
        ['key' => 'stage_results', 'category' => 'stages', 'label_en' => 'Results', 'label_ar' => 'النتائج'],

        // Navigation
        ['key' => 'nav_home', 'category' => 'navigation', 'label_en' => 'Home', 'label_ar' => 'الرئيسية'],
        ['key' => 'nav_my_competitions', 'category' => 'navigation', 'label_en' => 'My Competitions', 'label_ar' => 'مسابقاتي'],
        ['key' => 'nav_profile', 'category' => 'navigation', 'label_en' => 'Profile', 'label_ar' => 'الملف الشخصي'],
        ['key' => 'nav_notifications', 'category' => 'navigation', 'label_en' => 'Notifications', 'label_ar' => 'الإشعارات'],

        // Buttons
        ['key' => 'btn_register', 'category' => 'buttons', 'label_en' => 'Register Now', 'label_ar' => 'سجل الآن'],
        ['key' => 'btn_submit_project', 'category' => 'buttons', 'label_en' => 'Submit Project', 'label_ar' => 'تقديم المشروع'],
        ['key' => 'btn_join_team', 'category' => 'buttons', 'label_en' => 'Join Team', 'label_ar' => 'انضم للفريق'],
        ['key' => 'btn_create_team', 'category' => 'buttons', 'label_en' => 'Create Team', 'label_ar' => 'إنشاء فريق'],
        ['key' => 'btn_view_details', 'category' => 'buttons', 'label_en' => 'View Details', 'label_ar' => 'عرض التفاصيل'],

        // Section Headers
        ['key' => 'section_about', 'category' => 'sections', 'label_en' => 'About', 'label_ar' => 'حول'],
        ['key' => 'section_timeline', 'category' => 'sections', 'label_en' => 'Timeline', 'label_ar' => 'الجدول الزمني'],
        ['key' => 'section_tracks', 'category' => 'sections', 'label_en' => 'Tracks', 'label_ar' => 'المسارات'],
        ['key' => 'section_terms', 'category' => 'sections', 'label_en' => 'Terms & Conditions', 'label_ar' => 'الشروط والأحكام'],
        ['key' => 'section_guidelines', 'category' => 'sections', 'label_en' => 'Guidelines', 'label_ar' => 'الإرشادات'],
        ['key' => 'section_events', 'category' => 'sections', 'label_en' => 'Events', 'label_ar' => 'الفعاليات'],
    ];

    public function competition(): BelongsTo
    {
        return $this->belongsTo(Competition::class);
    }

    /**
     * Seed default labels for a competition.
     */
    public static function seedDefaults(int $competitionId): void
    {
        foreach (self::DEFAULT_LABELS as $label) {
            self::firstOrCreate(
                [
                    'competition_id' => $competitionId,
                    'key' => $label['key'],
                ],
                $label
            );
        }
    }
}
