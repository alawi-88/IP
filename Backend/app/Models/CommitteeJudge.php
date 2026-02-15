<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Traits\LogsActivity;

class CommitteeJudge extends Model
{
    use LogsActivity, HasActivityLog;

    protected $table = 'committee_judges';

    protected $fillable = [
        'committee.title',
        'judge_id',
    ];

    protected array $logFields = [
        'committee.title',
        'judge.name'
    ];

    protected string $moduleName = 'Committee Judge';
    protected string $logName = 'committee_judge';

    public function committee(): BelongsTo
    {
        return $this->belongsTo(Committee::class);
    }

    public function judge(): BelongsTo
    {
        return $this->belongsTo(Judge::class);
    }
}
