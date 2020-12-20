<?php

namespace Patrixsmart\Adjustfly\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Adjustment extends Model
{
    use SoftDeletes, HasFactory;

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The relations to eager load on every query.
     *
     * @var array
     */
    protected $with = ['adjustable'];

    /**
    * Get the parent adjustable model.
    */
    public function adjustable()
    {
        return $this->morphTo();
    }
}
