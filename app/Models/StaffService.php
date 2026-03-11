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

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
