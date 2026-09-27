<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Throwable;

class MaintenanceSettings
{
    public function estimatedHours(): ?string
    {
        try {
            $hours = DB::table('maintenance_settings')->where('id', 1)->value('estimated_hours');

            if (!is_numeric($hours) || !is_finite((float) $hours) || (float) $hours <= 0) {
                return null;
            }

            return rtrim(rtrim(number_format((float) $hours, 2, ',', '.'), '0'), ',');
        } catch (Throwable $exception) {
            // The error page must remain available during database maintenance.
            return null;
        }
    }
}
