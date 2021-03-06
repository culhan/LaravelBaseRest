<?php

namespace App\Http\Controllers;

use App;
use Request;
use App\Http\Services\UsersService;
use App\Http\Controllers\Controller;

class UsersController extends Controller
{    

    /**
     * [__construct description]
     */
    public function __construct()
    {
        $this->service = new UsersService;
    }

    /**
     * [index description]
     * @return [type] [description]
     */
    public function index()
    {
        $data = $this->service->getIndexData([
                'id'    =>  'id',
                'name'  =>  'name',
                'fmodified_time'    =>  'fmodified_time',
            ]);
        
        return (App\Http\Resources\UsersResource::collection($data))
                ->additional([
                    'sortableAndSearchableColumn' =>    $data->sortableAndSearchableColumn
                ]);
    }

}