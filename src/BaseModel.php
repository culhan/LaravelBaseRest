<?php

namespace KhanCode\LaravelBaseRest;

use Request;
use Validator;
use KhanCode\LaravelBaseRest\Builder as QueryBuilder;
use Illuminate\Database\Eloquent\Model;
use KhanCode\LaravelBaseRest\ValidationException;

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
        $query = $query->setBuilderSortableAndSearchableColumn($value);

        return $query;
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
				
		if( Request::has('distinct_column') ) {

			$this->validate(Request::all(), [
				'distinct_column' => [
					'filled',
					new \KhanCode\LaravelBaseRest\Rules\SortableAndSearchable($this->sortableAndSearchableColumn),
				],
			]);

			if( !empty( Helpers::is_error() ) ) throw new ValidationException( Helpers::get_error() );

			$this->sortableAndSearchableColumn = [];
			foreach (Request::get('distinct_column') as $key => $value) {
				$this->sortableAndSearchableColumn[$value] = $value;
			}

		}
	}

	/**
	 * [encapsulatedQuery description]
	 *
	 * @param   [type]  $alias  [$alias description]
	 *
	 * @return  [type]          [return description]
	 */
    public function scopeEncapsulatedQuery($query, $alias = 'myTable')
	{
		return $query;
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
            'search_text'   => [
                ''
            ],			
        ]);
		
		if( !empty( Helpers::is_error() ) ) throw new ValidationException( Helpers::get_error() );

		$query = $query->setUseSearch(1)->encapsulatedQuery('myTable');
        
		if( isset($request['search_column']) && array_key_exists('search_text', $request) )
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

		if( isset($request['search']) )
		{			
            $sortableAndSearchableColumn = $this->sortableAndSearchableColumn;
            $mappingSelect = $query->getQuery()->mappingSelect;
			$query->where(function ($query) use ($sortableAndSearchableColumn, $request, $mappingSelect) {
				foreach ($sortableAndSearchableColumn as $key => $value) {                	
                    if($value){
                        $this->searchOperator($query, $value, $request['search'], 'like', 'or' );
                    }
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

		if( $conditions == 'in')
			$functionCondition = 'whereIn';

		if( $conditions == 'notin')
			$functionCondition = 'whereNotIn';
					
		if( is_array($column) ) {
            $sortableAndSearchableColumn = $this->sortableAndSearchableColumn;
            $mappingSelect = $query->getQuery()->mappingSelect;
            
			$query->{$functionCondition}(function ($query) use ($column,$text,$operator,$conditions, $sortableAndSearchableColumn, $mappingSelect) {
				foreach ($column as $arr_search_column => $value_search_column) {
					$query = $this->searchOperator($query, $value_search_column, $text[$arr_search_column], array_get($operator,$arr_search_column,'like'), array_get($conditions,$arr_search_column,'and') );
				}
                
			});
		}else {
            if( $operator == "null" ){
                $query->where(function($query) use ($column){
                    $query->orWhere(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'), '=', "")
                        ->orWhereNull(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'));
                });
            }else if( $operator == "not null" ){
                $query->where(function($query) use ($column){
                    $query->Where(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'), '<>', "")
                        ->WhereNotNull(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'));
                });
            }else if( $operator == 'like' ){
				$this->sortableAndSearchableColumn[$column] = 'LOWER('.$this->sortableAndSearchableColumn[$column].')';
				$text = strtolower($text);
				
				$query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'),'like','%'.$text.'%');
            }else if( $operator == '=' ){
				$query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'),'=',$text);
            }else if( $operator == '>=' ){
				$query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'),'>=',$text);
            }else if( $operator == '<=' ){
				$query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'),'<=',$text);
            }else if( $operator == '>' ){
				$query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'),'>',$text);
            }else if( $operator == '<' ){
				$query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'),'<',$text);
            }else if( $operator == '<>' ){
                $query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'),'<',$text);
            }else if( in_array($operator,['in','notin']) ){
                $query->{$functionCondition}(\DB::raw('('.$this->sortableAndSearchableColumn[$column].')'), explode(',',$text));
			}
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

        $processed = $model->getQuery()->processSelect();

	    $sql = $replace($processed->sql_viewing, $processed->getBindings());

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
		
		if(!empty($data)) {
			$request['distinct_column'] = $data;
		}

		if( !empty($request['distinct_column']) )
		{
			if( is_array($request['distinct_column']) )
			{
                foreach ($request['distinct_column'] as $key => $value) {
                    $this->validate(['distinct_column'=>$value], [
                        'distinct_column' => [
                            new \KhanCode\LaravelBaseRest\Rules\SortableAndSearchable($this->sortableAndSearchableColumn)
                        ],	
                    ]);
                }

				if( !empty( Helpers::is_error() ) ) throw new ValidationException( Helpers::get_error() );

				$colsDistinct = implode('),(',$request['distinct_column']);
				$query->setUseDistinct(1)->select(\DB::raw('distinct ('.$colsDistinct.')'));
			}
			else
			{
                $this->validate($request, [
                    'distinct_column' => [
                        new \KhanCode\LaravelBaseRest\Rules\SortableAndSearchable($this->sortableAndSearchableColumn)
                    ],	
                ]);

				if( !empty( Helpers::is_error() ) ) throw new ValidationException( Helpers::get_error() );

				$query->setUseDistinct(1)->select(\DB::raw('distinct ('.$request['distinct_column'].')'));
			}

			return $query->encapsulatedQuery('myTableDistinct');			
		}
	}

	/**
	 * [scopeSort description]
	 *
	 * @param   [type]  $query           [$query description]
	 * @param   [type]  $default_column  [$default_column description]
	 * @param   [type]  $default_type    [$default_type description]
	 *
	 * @return  [type]                   [return description]
	 */
	public function scopeSort($query,$default_column = NULL,$default_type = 'DESC')
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

		if( !empty( Helpers::is_error() ) ) throw new ValidationException( Helpers::get_error() );

		if( !empty($request['sort_column']) && !empty($request['sort_type']) )
		{
			if( is_array($request['sort_column']) )
			{
				foreach ($request['sort_column'] as $key_sort_column => $value_sort_column) {
					$query->orderBy(\DB::raw('('.$this->sortableAndSearchableColumn[$value_sort_column].')'),$request['sort_type'][$key_sort_column]);
				}
			}
			else
			{				
				$query->orderBy(\DB::raw('('.$this->sortableAndSearchableColumn[$request['sort_column']].')'),$request['sort_type']);
			}
		}else {			
			if(!empty($this->sortableAndSearchableColumn[$this->getKeyName()])) $query->orderBy(\DB::raw('('.(empty($default_column) ? $this->getKeyName():$default_column).')'),$default_type);
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
     * Undocumented function
     *
     * @param [type] $data
     * @return void
     */
    public function getLastModifiedSyncAttribute($date)
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
        return $query = $this->setTable(\DB::raw(strtok($this->table, ' ')." ".$raw));
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
    
    /**
     * Get a new query builder instance for the connection.
     *
     * @return \Illuminate\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $connection = $this->getConnection();

        return new QueryBuilder(
            $connection, $connection->getQueryGrammar(), $connection->getPostProcessor()
        );
    }
}
