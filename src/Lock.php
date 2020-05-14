<?php

namespace KhanCode\LaravelBaseRest;

use Illuminate\Database\Eloquent\Model;

class Lock extends Model
{
    /**
     * [$table description]
     * @var string
     */
    public $table = "lock";

    /**
     * [$fillable description]
     * @var [type]
     */
    public $fillable = [
        'key',

        'updated_by',
        'created_by',
    ];

    /**
     * set All Model without timestamps
     * @var boolean
     */
    public $timestamps = true;

    /**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_by';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'updated_at';

    /**
     * [boot description]
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();

        self::creating(function($model){            
            if(user()) $model->created_by = user()->id;            
        });

        self::updating(function($model){
            if(user()) $model->updated_by = user()->id;
        });

    }
}