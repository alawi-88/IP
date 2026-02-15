<?php

namespace App\Http\Controllers;

use Filament\Actions\Exports\Enums\ExportFormat;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

use function Filament\authorize;

class DownloadExport
{
    public function __invoke(Request $request, Export $export)
    {
        if (filled(Gate::getPolicyFor($export::class))) {
            authorize('view', $export);
        } else {
            abort_unless($export->user()->is(auth()->user()), 403);
        }

        $format = ExportFormat::tryFrom($request->query('format'));;

        abort_unless($format !== null, 404);

        return url('filament_exports/' . $export->id . '/'  . $export->file_name .'.'. $format->value);
    }
}
