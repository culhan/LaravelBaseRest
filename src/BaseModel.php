<?php

namespace KhanCode\LaravelBaseRest;

use Request;
use Validator;
use KhanCode\LaravelBaseRest\ValidationException;
use Illuminate\Database\Eloquent\Model;

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
	 * @var array
	 */
	public $sortableAndSearchableColumn = [];

	/**
	 * relationColumn variable
	 *
	 * @var array
	 */
	public $relationColumn = [];

	/**
	 * set All Model without timestamps
	 * @var boolean
	 */
	public $timestamps = false;

	/**
	 * [$joinRaw description]
	 *
	 * @var [type]
	 */
	public $joinRaw = '';
	
	/**
	 * [setSortableAndSearchableColumn description]
	 * @param array $value [description]
	 */
	public function scopeSetSortableAndSearchableColumn($query, $value=[])
	{
		$this->sortableAndSearchableColumn = $value;
	}

	/**
	 * set relationColumn function
	 *
	 * @param [type] $query
	 * @param array $value
	 * @return void
	 */
	public function scopeSetRelationColumn($query, $value=[])
	{		
		$this->relationColumn = $value;		
		$this->sortableAndSearchableColumn += $this->relationColumn; 
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
                new \KhanCode\LaravelBaseRest\Rules\SortableAndSearchable($this->sortableAndSearchableColumn)
            ],
            'search_text'   => ['required_with:search_column'],			
        ]);

		$queryOld = $this->getSql($query);
		$thisClass = get_class($this);
		$model = new $thisClass;
		$model->setSortableAndSearchableColumn( $this->sortableAndSearchableColumn );
		$model->setRelationColumn( $this->relationColumn );
		$query = $model->setTable(\DB::raw('('.$queryOld.') as myTable'))->whereRaw("1=1");

		if( isset($request['search_column']) && isset($request['search_text']) )
		{
			if( is_array($request['search_column']) )
			{				
				foreach ($request['search_column'] as $arr_search_column => $value_search_column) {
					$query = $this->searchOperator($query, $request['search_column'][$arr_search_column], $request['search_text'][$arr_search_column], array_get($request,'search_operator.'.$arr_search_column,'like'), array_get($request,'search_conditions.'.$arr_search_column,'and') );
				}	
			}
			else
			{	
				$query = $this->searchOperator($query, $request['search_column'], $request['search_text'], array_get($request,'search_operator','like'), array_get($request,'search_conditions','and') );
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
	 *
	 * @param   [type]  $query       [$query description]
	 * @param   [type]  $column      [$column description]
	 * @param   [type]  $text        [$text description]
	 * @param   [type]  $operator    [$operator description]
	 * @param   [type]  $conditions  [$conditions description]
	 *
	 * @return  [type]               [return description]
	 */
	public function searchOperator($query, $column, $text, $operator = 'like', $conditions = 'and')
	{	
		$functionCondition = 'where';
		if( $conditions == 'or')
			$functionCondition = 'orWhere';
					
		if( is_array($column) ) {			
			$query->{$functionCondition}(function ($query) use ($column,$text,$operator,$conditions) {
				foreach ($column as $arr_search_column => $value_search_column) {
					$query = $this->searchOperator($query, $value_search_column, $text[$arr_search_column], array_get($operator,$arr_search_column,'like'), array_get($conditions,$arr_search_column,'and') );
				}
			});
		}else {
			if( $operator == 'like' )
				$query->{$functionCondition}(\DB::raw($this->sortableAndSearchableColumn[$column]),'like','%'.$text.'%');

			if( $operator == '=' )
				$query->{$functionCondition}(\DB::raw($this->sortableAndSearchableColumn[$column]),'=',$text);

			if( $operator == '>=' )
				$query->{$functionCondition}(\DB::raw($this->sortableAndSearchableColumn[$column]),'>=',$text);

			if( $operator == '<=' )
				$query->{$functionCondition}(\DB::raw($this->sortableAndSearchableColumn[$column]),'<=',$text);

			if( $operator == '>' )
				$query->{$functionCondition}(\DB::raw($this->sortableAndSearchableColumn[$column]),'>',$text);

			if( $operator == '<' )
				$query->{$functionCondition}(\DB::raw($this->sortableAndSearchableColumn[$column]),'<',$text);

			if( $operator == '<>' )
				$query->{$functionCondition}(\DB::raw($this->sortableAndSearchableColumn[$column]),'<',$text);
		}		
		
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
	 * distinct function
	 *
	 * @param [type] $query
	 * @return void
	 */
	public function scopeDistinct($query,$data=null)
	{
		$request = Request::all();		
		
		$this->validate($request, [
            'distinct_column' => [
                'filled',
                new \KhanCode\LaravelBaseRest\Rules\SortableAndSearchable($this->sortableAndSearchableColumn),
            ],
		]);
		
		if(!empty($data)) {
			$request['distinct_column'] = $data;
		}

		if( !empty($request['distinct_column']) )
		{
			if( is_array($request['distinct_column']) )
			{
				$colsDistinct = implode(',',$request['distinct_column']);
				$query->select(\DB::raw('distinct '.$colsDistinct));
			}
			else
			{
				$query->select(\DB::raw('distinct '.$request['distinct_column']));
			}
		}
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
                new \KhanCode\LaravelBaseRest\Rules\SortableAndSearchable($this->sortableAndSearchableColumn),
            ],
            'sort_type'   => [
            	'required_with:sort_column',
            	new \KhanCode\LaravelBaseRest\Rules\SortType(),
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
		if($validator->fails()) \KhanCode\LaravelBaseRest\Helpers::set_error($validator->errors()->toArray());
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
		return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', (is_null($date) ? '00-00-00 00:00:00' : $date) )->format('Y-m-d H:i:s');
	}

	/**
	 * check attribute function
	 *
	 * @param [type] $attr
	 * @return boolean
	 */
	public function hasAttribute($attr)
	{
		return array_key_exists($attr, $this->attributes);
	}

	/**
	 * custom join function
	 *
	 * @param [type] $query
	 * @param [type] $raw
	 * @return void
	 */
	public function scopeJoinRaw($query,$raw)
    {	
		$this->joinRaw = $raw;
        return $query = $this->setTable(\DB::raw($this->table." ".$raw));
	}
	
	/**
	 * [scopeUseIndex description]
	 * @param  [type] $query [description]
	 * @return [type]        [description]
	 */
	public function scopeIndex($query, $index_name, $type = 'FORCE')
	{
		$thisClass = get_class($this);
		$model = new $thisClass;		
		return $query->from(\DB::raw(''.$model->getTable().' '.$type.' INDEX ('.$index_name.') '.$this->joinRaw));
	}
}
