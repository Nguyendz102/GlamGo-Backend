<?php

namespace App\Traits;

use App\Models\StatusModel;
use App\Models\CountryModel;

trait HasCommonRelations
{
    public function status()
    {
        return $this->belongsTo(StatusModel::class, 'status_id');
    }

    public function country()
    {
        return $this->belongsTo(CountryModel::class, 'country_id');
    }
}
