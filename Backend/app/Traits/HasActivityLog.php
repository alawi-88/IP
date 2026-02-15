<?php

namespace App\Traits;

use Spatie\Activitylog\LogOptions;

trait HasActivityLog
{    
    public function getActivitylogOptions(): LogOptions
    {
        $logFields = property_exists($this, 'logFields') ? $this->logFields : ['*'];
        $moduleName = property_exists($this, 'moduleName') ? $this->moduleName : class_basename($this);
        $logName = property_exists($this, 'logName') ? $this->logName : strtolower($moduleName);

        return LogOptions::defaults()
            ->logOnly($logFields)
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName($logName)
            ->setDescriptionForEvent(function(string $eventName) use ($moduleName) {
                $userName = auth()->user()?->name ?? 'System';
                return "{$userName} {$eventName} a {$moduleName}";
            });
    }
}
