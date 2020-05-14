<?php
namespace App\Http\Repositories;

use Request;
use App\Http\Models\Users;
use KhanCode\LaravelBaseRest\BaseRepository;
use KhanCode\LaravelBaseRest\DataEmptyException;

/**
 * code for system logic
 */
class UsersRepository extends BaseRepository
{
    /**
     * [$module description]
     * @var string
     */
    static $module = 'Users';

    /**
     * [__construct description]
     */
    public function __construct()
    {
        $this->model = new Users;
    }

    /**
     * [getIndexData description]
     * @param  array  $sortableAndSearchableColumn [description]
     * @return [type]                              [description]
     */
    public function getIndexData(array $sortableAndSearchableColumn)
    {
        $this->model::validate(Request::all(), [
            'per_page'  =>  ['numeric'],
        ]);
        
        $data = $this->model
            ->getAll()
            ->setSortableAndSearchableColumn($sortableAndSearchableColumn)
            ->search()
            ->sort()
            ->distinct()
            ->paginate(Request::get('per_page'));

        $data->sortableAndSearchableColumn = $sortableAndSearchableColumn;
            
        if($data->total() == 0) throw new DataEmptyException(trans('validation.attributes.dataNotExist',['attr' => self::$module]));

        return $data;
    }

    /**
     * [getSingleData description]
     * @param  [type] $id [description]
     * @return [type]     [description]
     */
    public function getSingleData($id)
    {
        $return = $this->model
                ->getAll()
                ->where($this->model->table.'.'.$this->model->primary_key,$id)
                ->first();
                        
        if($return === null) throw new DataEmptyException(trans('validation.attributes.dataNotExist',['attr' => self::$module]));
        
        return $return;
    }
    
}