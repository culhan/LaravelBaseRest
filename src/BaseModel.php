<?php

namespace KhanCode\LaravelBaseRest;

use Request;
use Validator;
use KhanCode\LaravelBaseRest\ValidationException;
use Illuminate\Database\Eloquent\Model;
use KhanCode\LaravelBaseRest\Rules\SortType;
use KhanCode\LaravelBaseRest\Rules\SortableAndSearchable;

class BaseModel extends Model
{
	protected $guarded	= 	[];
	
	protected $soft_delete 	=	false;

	protected $with_log	=	false;
	
	protected $casts = ['created_at' => 'string'];

	protected static $rules = [];

	/**
     * The name of the "created at" column.
     *
     * @var string
     */
    const CREATED_AT = 'created_time';

    /**
     * The name of the "updated at" column.
     *
     * @var string
     */
    const UPDATED_AT = 'modified_time';

    /**
     * The name of the "deleted at" column.
     *
     * @var string
     */
    const DELETED_AT = 'deleted_time';

	/**
	 * [$sortableAndSearchableColumn description]
	 * @var [type]
	 */
	public $sortableAndSearchableColumn = [];

	/**
	 * set All Model without timestamps
	 * @var boolean
	 */
	public $timestamps = false;

	/**
	 * [setSortableAndSearchableColumn description]
	 * @param array $value [description]
	 */
	public function scopeSetSortableAndSearchableColumn($query, $value=[])
	{
		$this->sortableAndSearchableColumn = $value;
	}

	/**
	 * [FunctionName description]
	 * @param string $value [description]
	 */
	public function scopeSearch($query)
	{
		if(empty($this->sortableAndSearchableColumn)) return $query;
		
		$request = Request::all();

		$this->validate($request, [
            'search_column' => [
                'required_with:search_text',
                new SortableAndSearchable($this->sortableAndSearchableColumn)
            ],
            'search_text'   => ['required_with:search_column'],
        ]);

		$queryOld = $this->getSql($query);
		$thisClass = get_class($this);
		$model = new $thisClass;
		$model->sortableAndSearchableColumn = $this->sortableAndSearchableColumn;
		$query = $model->setTable(\DB::raw('('.$queryOld.') as myTable'))->whereRaw("1=1");

		if( isset($request['search_column']) && isset($request['search_text']) )
		{
			if( is_array($request['search_column']) )
			{				
				foreach ($request['search_column'] as $arr_search_column => $value_search_column) {
					$query = $this->searchOperator($query, $request['search_column'][$arr_search_column], $request['search_text'][$arr_search_column], array_get($request,'search_operator.'.$arr_search_column,'like'));
				}	
			}
			else
			{	
				$query = $this->searchOperator($query, $request['search_column'], $request['search_text'], array_get($request,'search_operator','like'));
			}
		}

		if( !empty($request['search']) )
		{			
			$sortableAndSearchableColumn = $this->sortableAndSearchableColumn;
			$query->where(function ($query) use ($sortableAndSearchableColumn,$request) {
				foreach ($sortableAndSearchableColumn as $key => $value) {                	
                	if($value)$query->orWhere(\DB::raw($value), 'like', '%'.$request['search'].'%');
				}
            });

		}
        
        return $query;

	}

	/**
	 * [searchOperator description]
	 * @param  [type] $query    [description]
	 * @param  [type] $column   [description]
	 * @param  [type] $text     [description]
	 * @param  string $operator [description]
	 * @return [type]           [description]
	 */
	public function searchOperator($query, $column, $text, $operator = 'like')
	{			
		if( $operator == 'like' )
			$query->where(\DB::raw($this->sortableAndSearchableColumn[$column]),'like','%'.$text.'%');

		if( $operator == '=' )
			$query->where(\DB::raw($this->sortableAndSearchableColumn[$column]),'=',$text);

		if( $operator == '>=' )
			$query->where(\DB::raw($this->sortableAndSearchableColumn[$column]),'>=',$text);

		if( $operator == '<=' )
			$query->where(\DB::raw($this->sortableAndSearchableColumn[$column]),'<=',$text);

		if( $operator == '>' )
			$query->where(\DB::raw($this->sortableAndSearchableColumn[$column]),'>',$text);

		if( $operator == '<' )
			$query->where(\DB::raw($this->sortableAndSearchableColumn[$column]),'<',$text);

		return $query;
	}

	/**
	 * [getSql description]
	 * @param  [type] $model [description]
	 * @return [type]        [description]
	 */
	public function getSql($model)
	{
	    $replace = function ($sql, $bindings)
	    {
	        $needle = '?';
	        foreach ($bindings as $replace){
	            $pos = strpos($sql, $needle);
	            if ($pos !== false) {
	                if (gettype($replace) === "string") {
	                     $replace = ' "'.addslashes($replace).'" ';
	                }
	                $sql = substr_replace($sql, $replace, $pos, strlen($needle));
	            }
	        }
	        return $sql;
	    };
	    $sql = $replace($model->toSql(), $model->getBindings());

	    return $sql;
	}

	/**
	 * [FunctionName description]
	 * @param string $value [description]
	 */
	public function scopeSort($query)
	{
		$request = Request::all();

		$this->validate($request, [
            'sort_column' => [
                'required_with:sort_type',
                new SortableAndSearchable($this->sortableAndSearchableColumn),
            ],
            'sort_type'   => [
            	'required_with:sort_column',
            	new SortType(),
            ],
    	]);
		
		if( !empty($request['sort_column']) && !empty($request['sort_type']) )
		{
			if( is_array($request['sort_column']) )
			{
				foreach ($request['sort_column'] as $key_sort_column => $value_sort_column) {
					$query->orderBy($this->sortableAndSearchableColumn[$value_sort_column],$request['sort_type'][$key_sort_column]);
				}
			}
			else
			{
				$query->orderBy($this->sortableAndSearchableColumn[$request['sort_column']],$request['sort_type']);
			}
		}

	}

	/**
	 * [validate description]
	 * @param  [type] $data     [description]
	 * @param  array  $rules    [description]
	 * @param  array  $messages [description]
	 * @return [type]           [description]
	 */
	public static function validate($data, $rules = [], $messages = [])
	{
		$rules = empty($rules) ? self::$rules : $rules;  
		if(empty($rules)) return true;
		$validator = Validator::make($data, $rules, $messages);
		if($validator->fails()) throw new ValidationException($validator->errors());
		return true;
	}	

	/**
	 * [scopeActive description]
	 * @param  [type] $query [description]
	 * @return [type]        [description]
	 */
	public function scopeActive($query)
	{
		return $query->whereNull($this->table.'.deleted_at');
	}

	/**
     * [scopeGetAll description]
     * @param  [type] $query [description]
     * @return [type]        [description]
     */
    public function scopeGetAll($query)
    {
        return $query;
    }

	/**
     * [delete description]
     * @return [type] [description]
     */
    public function delete()
    {
		if (is_null($this->getKeyName())) {
            throw new Exception('No primary key defined on model.');
        }

        // If the model doesn't exist, there is nothing to delete so we'll just return
        // immediately and not do anything else. Otherwise, we will continue with a
        // deletion process on the model, firing the proper events, and so forth.
        if (! $this->exists) {
            return;
        }

        if ($this->fireModelEvent('deleting') === false) {
            return false;
        }

        // Here, we'll touch the owning models, verifying these timestamps get updated
        // for the models. This will allow any caching to get broken on the parents
        // by the timestamp. Then we will go ahead and delete the model instance.
        $this->touchOwners();

        if( $this->soft_delete )
        {
        	\DB::table($this->table)
        		->where($this->primaryKey, $this->id)
        		->update([
        			static::DELETED_AT	=>	date('Y-m-d H:i:s'),
        			'deleted_by' 	=> 	user()->id,
        			'deleted_from'	=>	$_SERVER['REMOTE_ADDR'],
        		]);
        }
        else
        {
        	$this->performDeleteOnModel();
        }

        // Once the model has been deleted, we will fire off the deleted event so that
        // the developers may hook into post-delete operations. We will then return
        // a boolean true as the delete is presumably successful on the database.
        $this->fireModelEvent('deleted', false);

        return true;		        
    }

    /**
     * [getModifiedTimeAttribute description]
     * @param  [type] $date [description]
     * @return [type]       [description]
     */
    public function getModifiedTimeAttribute($date)
	{
	    return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $date)->format('Y-m-d H:i:s');
	}
}
