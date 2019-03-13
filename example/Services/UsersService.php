<?php
namespace App\Http\Services;

use Request;
use App\Http\Models\Users;
use App\Exceptions\DataEmptyException;
use KhanCode\LaravelBaseRest\BaseService;
use App\Http\Repositories\UsersRepository;

/**
 * code for system logic
 */
class UsersService extends BaseService
{

    /**
     * [__construct description]
     */
    public function __construct()
    {
        $this->model = new Users;
        $this->repository = new UsersRepository;
    }

}