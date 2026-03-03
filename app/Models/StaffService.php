<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class StaffService extends Pivot
{
    protected $table = 'staff_services';

    protected $fillable = [
        'staff_id',
        'service_id',
    ];

    public $timestamps = false;
}
