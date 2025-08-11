<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Appointment extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mutator to ensure name is always stored in uppercase
     */
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper(trim(strip_tags($value)));
    }

    /**
     * Mutator to ensure email is always stored in lowercase
     */
    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower(trim(strip_tags($value)));
    }
}
