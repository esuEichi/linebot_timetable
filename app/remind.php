<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class remind extends Model
{
    //
    protected $fillable = array(
        'user_id'
        ,'message'
    );
}
