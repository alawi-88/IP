<?php

namespace App\Exceptions;

use Illuminate\Validation\ValidationException;

/**
 * Custom exception for import validation errors that should not be logged
 * This exception is used to mark import rows as failed without logging them as errors
 * It extends ValidationException so Filament can handle it properly
 */
class ImportValidationException extends ValidationException
{
    /**
     * Report the exception.
     * Don't call parent::report() to prevent the exception from being logged
     *
     * @return void
     */
    public function report()
    {
        // Don't call parent::report() to prevent this exception from being logged
        // This exception is expected during imports and should only mark rows as failed
    }
}

