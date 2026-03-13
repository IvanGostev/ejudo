<?php

namespace App\Services;

use App\Models\UserCompany;
use App\Models\JudoJournal;
use Illuminate\Database\Eloquent\Builder;

class PolygonModeService
{
    public static function isEnabled(?UserCompany $company): bool
    {
        return $company && $company->polygons()->exists();
    }

    public static function getJournalBaseQuery(?UserCompany $company): Builder
    {
        $query = JudoJournal::where('company_id', $company->id);

        if (self::isEnabled($company)) {

            return $query;
        }


        return $query->whereNull('polygon_id');
    }
}
