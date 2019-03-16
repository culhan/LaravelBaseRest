<?php

namespace App\Http\Models;

use KhanCode\LaravelBaseRest\BaseModel;

class Users extends BaseModel
{
    public $table = 'users';

    protected $soft_delete = true;

    public $timestamps = true;

    /**
     * [scopeGetAll description].
     * @param  [type] $query [description]
     * @return [type]        [description]
     */
    public function scopeGetAll($query)
    {
        return $query->whereNull($this->table.'.deleted_by');
    }

    /**
     * [boot description].
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();
    }
}
