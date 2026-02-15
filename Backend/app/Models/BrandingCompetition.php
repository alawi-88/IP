<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Competition;

class BrandingCompetition extends Model
{
    protected $fillable = [
        'competition_id',
        'logo',
        'white_logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'font',
        'is_published',
    ];

    protected $table = 'branding_competitions';

    public function competition()
    {
        return $this->belongsTo(Competition::class, 'competition_id');
    }

    /**
     * Get branding details by competition id.
     *
     * @param int $competitionId
     * @return array|null
     */
    public static function getByCompetitionId(int $competitionId): ?array
    {
        $branding = self::where('competition_id', $competitionId)->first();

        return $branding?->getBrandingDetails();
    }

    /**
     * Return the branding details for this instance as array.
     *
     * @return array
     */
    public function getBrandingDetails(): array
    {
        return [
            'logo' => url('storage/'.$this->logo) ?? null,
            // 'white_logo' => url('storage/'.$this->white_logo) ?? null,
            'favicon' => url('storage/'.$this->favicon) ?? null,
            'primary_color' => $this->primary_color,
            'secondary_color' => $this->secondary_color,
            'font' => $this->font,
            'is_published' => $this->is_published,
        ];
    }
}
