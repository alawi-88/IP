<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Program;

class BrandingProgram extends Model
{
    protected $fillable = [
        'program_id',
        'logo',
        'white_logo',
        'favicon',
        'primary_color',
        'secondary_color',
        'font',
        'is_published',
    ];

    protected $table = 'branding_programs';

    public function program()
    {
        return $this->belongsTo(Program::class, 'program_id');
    }

    /**
     * Get branding details by program id.
     *
     * @param int $programId
     * @return array|null
     */
    public static function getByProgramId(int $programId): ?array
    {
        $branding = self::where('program_id', $programId)->first();

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
