<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class timetable extends Model
{
    //
    protected $primaryKey = 'user_id';
    public $timestamps = false;
    public $incrementing = false;
    protected $fillable = array(
        'user_id'
        ,'mon'
        ,'tue'
        ,'wed'
        ,'thu'
        ,'fri'
    );
}
