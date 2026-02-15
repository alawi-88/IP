<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\SchemalessAttributes\Casts\SchemalessAttributes;

class FormStep extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'form_id',
        'name',
        'step_order',
        'field_ids',
    ];

    public $casts = [
        'field_ids' => SchemalessAttributes::class,
    ];

    protected array $logFields = [
        'name',
        'step_order',
        'field_ids',
        'form.name'
    ];

    protected string $moduleName = 'Form Step';
    protected string $logName = 'form_step';

    public function form()
    {
        return $this->belongsTo(Form::class);
    }
}
