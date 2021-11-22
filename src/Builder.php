<?php

namespace KhanCode\LaravelBaseRest;

use Closure;
use RuntimeException;
use BadMethodCallException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Illuminate\Support\Collection;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Concerns\BuildsQueries;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Query\Expression;

class Builder extends QueryBuilder
{
    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $viewingUuid;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $from_paginate = false;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $mappingSelect = [];

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    public $mappingSelectBindings = [];
    
    /**
     * Undocumented variable
     *
     * @var integer
     */
    public $useDistinct = 0;

    /**
     * Undocumented variable
     *
     * @var integer
     */
    public $useSearch = 0;

    /**
     * Undocumented variable
     *
     * @var array
     */
    public $builderSortableAndSearchableColumn = [];

    /**
     * Undocumented variable
     *
     * @var array
     */
    public $union_binding_where = [];

    /**
     * Execute the query as a "select" statement.
     *
     * @param  array  $columns
     * @return \Illuminate\Support\Collection
     */
    public function get($columns = ['*'])
    {
        $original = $this->columns;

        if (is_null($original)) {
            $this->columns = $columns;
        }

        $results = $this->processor->processSelect($this, $this->runSelect());

        $this->columns = $original;

        return collect($results);
    }
    
    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect()
    {
        $this->sql_viewing = $this->toSql();

        $this->addBinding( $this->union_binding_where, 'union');

        // proses query union
        if ( !empty($this->unions) ){
            foreach ($this->unions as $unions_key => $unions_value) {

                if(!empty($this->aggregate)){
                    $this->unions[$unions_key]['query'] = $this->unions[$unions_key]['query']
                        ->getQuery()
                        ->cloneWithout(['columns', 'orders', 'limit', 'offset', 'unionOrders'])
                        ->cloneWithoutBindings(['select', 'order'])
                        ->setAggregate('count', $this->withoutSelectAliases($this->columns));
                }
                
                if(!empty($this->useSearch)){
                    $this->unions[$unions_key]['query'] = $this->unions[$unions_key]['query']
                        ->cloneWithout(['unionOrders'])
                        ->setBuilderSortableAndSearchableColumn($this->builderSortableAndSearchableColumn)
                        ->builderSearch();
                    
                    $this->addBinding($this->unions[$unions_key]['query']->getBindings(), 'union');
                    
                }
            }

            if(!empty($this->aggregate)){
                $this->sql_viewing = $this->cloneWithout(['unionOrders'])->toSql();
            }else {
                $this->sql_viewing = $this->cloneWithout([])->toSql();
            }
        }

        $clonedSql = $this->cloneWithout(['columns','unionOrders','unions'])->toSql();
        $clonedNewSql = $clonedSql = str_replace(['select count(*) as aggregate from ', 'select * from '], '',$clonedSql);

        preg_match_all('~\(([^()]*)\)~', $clonedSql, $matches);
            
        if( !empty($matches[0]) ){
            foreach ($matches[0] as $key => $value) {
                $key_column = str_replace([ '(', ')' ],[ '', '' ],$value);

                if( isset($this->mappingSelect[$key_column]) ){
                    $new_value = str_replace($key_column, $this->mappingSelect[$key_column], $value);
                    $clonedSql = str_replace($value,$new_value,$clonedSql);
                }
            }
        }

        $this->sql_viewing = str_replace($clonedNewSql, $clonedSql, $this->sql_viewing);

        $return = $this->connection->select(
            $this->sql_viewing, $this->getBindings(), ! $this->useWritePdo
        );

        return $return;
    }

    /**
     * Undocumented function
     *
     * @param [type] $query
     * @return void
     */
    public function replaceMappingColumn($query)
    {
        # code...
    }

    /**
     * Get the count of the total records for the paginator.
     *
     * @param  array  $columns
     * @return int
     */
    public function getCountForPagination($columns = ['*'])
    {
        if( $this->useDistinct == 1){
            $results = $this->cloneWithout(['columns', 'orders', 'limit', 'offset', 'table'])
                    ->cloneWithoutBindings(['select', 'order'])
                    ->setAggregate('count', $this->withoutSelectAliases($columns))
                    ->from( \DB::raw('('.$this->cloneWithout([])->toSql() . ") as distinctTable ") )
                    ->get()->all();
            
            return (int) array_change_key_case((array) $results[0])['aggregate'];
        }
        
        $results = $this->runPaginationCountQuery($columns);

        // jika union maka akan di hitung semua
        if( !empty($this->unions) ){
            $aggregate = 0;
            if( !empty($results) ){
                foreach ($results as $result_value) {
                    $aggregate+=$result_value->aggregate;
                }
            }
            
            return $aggregate;
        }

        // Once we have run the pagination count query, we will get the resulting count and
        // take into account what type of query it was. When there is a group by we will
        // just return the count of the entire results set since that will be correct.
        if (isset($this->groups)) {
            return count($results);
        } elseif (! isset($results[0])) {
            return 0;
        } elseif (is_object($results[0])) {
            return (int) $results[0]->aggregate;
        }

        return (int) array_change_key_case((array) $results[0])['aggregate'];
    }

    /**
     * Undocumented function
     *
     * @param [type] $uuid
     * @return void
     */
    public function setMappingSelect($mappingSelect)
    {
        $this->mappingSelect = $mappingSelect;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [type] $uuid
     * @return void
     */
    public function setUseDistinct($useDistinct)
    {
        $this->useDistinct = $useDistinct;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [type] $uuid
     * @return void
     */
    public function setUseSearch($useSearch)
    {
        $this->useSearch = $useSearch;
        return $this;
    }

    /**
     * Undocumented function
     *
     * @param [Type] $var
     * @return void
     */
    public function setBuilderSortableAndSearchableColumn($sortableAndSearchableColumn)
    {
        $this->builderSortableAndSearchableColumn = $sortableAndSearchableColumn;
        return $this;
    }

    /**
     * Set the columns to be selected.
     *
     * @param  array|mixed  $columns
     * @return $this
     */
    public function select($columns = ['*'])
    {
        $this->columns = is_array($columns) ? $columns : func_get_args();

        $this->mappingSelect($this->columns);

        return $this;
    }

    /**
     * Add a new select column to the query.
     *
     * @param  array|mixed  $column
     * @return $this
     */
    public function addSelect($column)
    {
        $column = is_array($column) ? $column : func_get_args();

        $this->columns = array_merge((array) $this->columns, $column);

        $this->mappingSelect($this->columns);

        return $this;
    }

    /**
     * Undocumented function
     *
     * @param Type $var
     * @return void
     */
    public function mappingSelect($column)
    {
        if( is_array($column) ){
            foreach ($column as $key => $value) {
                
                if ( $this->useDistinct == 1 ) {
                    if ($value instanceof Expression) {
                        $value = new Expression($this->replaceColumnNameAlias($value->getValue(), 1));
                    }else {
                        $value = new Expression($this->replaceColumnNameAlias($value, 1));
                    }
                    $this->columns = [$value];
                    return;
                }

                $explodedSelect = $this->explodeSelect($value);
                $this->mappingSelect = array_merge($this->mappingSelect, [$explodedSelect['alias_query'] => $explodedSelect['query']]);
            }
        }else{
            $this->mappingSelect = array_merge($this->mappingSelect, $column);
        }
    }

    /**
     * Undocumented function
     *
     * @param [type] $select_statement
     * @return void
     */
    public function explodeSelect($select_statement)
    {
        if ($select_statement instanceof Expression) {
            $expressionVal = $select_statement->getValue();
        }else {
            $expressionVal = $select_statement;
        }
        
        if (strpos($expressionVal, ' as ') !== false) {
            $expressionVal = explode(' as ',$expressionVal);
            $countVal = count($expressionVal);
            $keyExpression = str_replace(["'",' '],["",''],$expressionVal[$countVal-1]);
            unset($expressionVal[$countVal-1]);
            return [
                'query' => implode(' as ',$expressionVal), 
                'alias_query'   => $keyExpression
            ];
        }else {
            return [
                'query' => $expressionVal, 
                'alias_query'   => $expressionVal
            ];
        }   
    }

    /**
     * Undocumented function
     *
     * @param Type $var
     * @return void
     */
    public function replaceColumnNameAlias($string, $for_distinct = 0)
    {
        if (strpos($string, '`') !== false) {
            $string = str_replace('`', '', $string);
        }

        if( !empty($this->mappingSelect) ){
            if( isset($this->mappingSelect[$string]) ){
                $new_value = $this->mappingSelect[$string];

                if( $for_distinct == 1 ){
                    $new_value .= ' as "'.$string.'"';
                }

                return $new_value;
            }else{
                preg_match_all("~\(([^()]*)\)~", $string, $matches);
                
                if( !empty($matches[0]) ){
                    foreach ($matches[0] as $key => $value) {
                        $key_column = str_replace([ '(', ')' ],[ '', '' ],$value);

                        if( isset($this->mappingSelect[$key_column]) ){
                            $new_value = str_replace($key_column, $this->mappingSelect[$key_column], $value);

                            if( $for_distinct == 1 ){
                                $new_value .= ' as "'.$key_column.'"';
                            }

                            $string = str_replace($value,$new_value,$string);
                        }
                    }
                }

            }
        }
        
        return $string;
    }

    /**
     * Undocumented function
     *
     * @param Type $var
     * @return void
     */
    public function replaceColumnNameAliasArray($arr_column = [])
    {
        $new_arr = [];
        if( !empty($arr_column) ){
            foreach ($arr_column as $key => $value) {
                $new_arr[$this->replaceColumnNameAlias($key)] = $value;
            }
        }
        
        return $new_arr;
    }

    /**
     * Add another query builder as a nested where to the query builder.
     *
     * @param  \Illuminate\Database\Query\Builder|static $query
     * @param  string  $boolean
     * @return $this
     */
    public function addNestedWhereQuery($query, $boolean = 'and')
    {
        if( !empty($this->unions)){
            foreach ($this->unions as $unions_key => $unions_value) {

                $unionMappingSelect = $this->unions[$unions_key]['query']->getQuery()->mappingSelect;

                foreach ($query->wheres as $w_key => $w_value) {
                    
                    preg_match_all('~\(([^()]*)\)~', $w_value['column'], $matches);

                    if( !empty($matches[0]) ){
                        foreach ($matches[0] as $key => $value) {
                            $key_column = str_replace([ '(', ')' ],[ '', '' ],$value);
                            
                            if( isset($unionMappingSelect[$key_column]) ){
                                $query->wheres[$w_key]['column'] = \DB::raw(str_replace($key_column, $unionMappingSelect[$key_column], $w_value['column']));
                            }
                        }
                    }

                    $this->union_binding_where[] = ($w_value['value']??$w_value['operator']);
                }

                $this->unions[$unions_key]['query'] = $this->unions[$unions_key]['query']->addNestedWhereQuery($query, $boolean);
            }
        }

        return parent::addNestedWhereQuery($query, $boolean);
    }

    /**
     * Add a basic where clause to the query.
     *
     * @param  string|array|\Closure  $column
     * @param  mixed   $operator
     * @param  mixed   $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        if( !empty($this->unions)){
            
            foreach ($this->unions as $unions_key => $unions_value) {
                
                $unionMappingSelect = $this->unions[$unions_key]['query']->getQuery()->mappingSelect;
                
                preg_match_all('~\(([^()]*)\)~', $column, $matches);

                if( !empty($matches[0]) ){
                    foreach ($matches[0] as $m_key => $m_value) {
                        $key_column = str_replace([ '(', ')' ],[ '', '' ],$m_value);
                        
                        if( isset($unionMappingSelect[$key_column]) ){
                            $column = str_replace($key_column, $unionMappingSelect[$key_column], $column);
                        }
                    }
                }

                $this->unions[$unions_key]['query'] = $this->unions[$unions_key]['query']->where($column, $operator, $value, $boolean);
                
                $this->union_binding_where[] = ($value??$operator);
            }
        }
        
        if ($column instanceof Closure) {
            return $this->whereNested($column, $boolean);
        }
        
        if( is_array($column)){
            $column = $this->replaceColumnNameAliasArray($column);
        }else{
            if ($column instanceof Expression) {
                $column = new Expression($this->replaceColumnNameAlias($column->getValue()));
            }else {
                $column = new Expression($this->replaceColumnNameAlias($column));
            }
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
	 * [FunctionName description]
	 * @param string $value [description]
	 */
	public function builderSearch()
	{		
        $request = \Request::all();
        
        $query = $this;

		if( isset($request['search_column']) && isset($request['search_text']) )
		{
			if( is_array($request['search_column']) )
			{				
				foreach ($request['search_column'] as $arr_search_column => $value_search_column) {
					$query = $query->builderSearchOperator($query, $request['search_column'][$arr_search_column], $request['search_text'][$arr_search_column], array_get($request,'search_operator.'.$arr_search_column,'like'), array_get($request,'search_conditions.'.$arr_search_column,'and') );
				}	
			}
			else
			{	
				$query = $query->builderSearchOperator($query, $request['search_column'], $request['search_text'], array_get($request,'search_operator','like'), array_get($request,'search_conditions','and') );
			}
		}

		if( !empty($request['search']) )
		{
            $sortableAndSearchableColumn = $query->builderSortableAndSearchableColumn;
            $mappingSelect = $query->mappingSelect;
			$query = $query->where(function ($query) use ($sortableAndSearchableColumn, $request, $mappingSelect) {
                
                $query->setBuilderSortableAndSearchableColumn($sortableAndSearchableColumn)
                    ->setMappingSelect($mappingSelect);

				foreach ($sortableAndSearchableColumn as $key => $value) {                	
                    if($value){
                        $query->builderSearchOperator($query, $value, $request['search'], 'like', 'or' );
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
	public function builderSearchOperator($query, $column, $text, $operator = 'like', $conditions = 'and')
	{	
		$functionCondition = 'where';
		if( $conditions == 'or')
			$functionCondition = 'orWhere';
					
		if( is_array($column) ) {
            $sortableAndSearchableColumn = $query->builderSortableAndSearchableColumn;
            $mappingSelect = $query->mappingSelect;

			$query->{$functionCondition}(function ($query) use ($column,$text,$operator,$conditions, $sortableAndSearchableColumn, $mappingSelect) {
                
                $query->setBuilderSortableAndSearchableColumn($sortableAndSearchableColumn)
                    ->setMappingSelect($mappingSelect);

				foreach ($column as $arr_search_column => $value_search_column) {
					$query = $query->builderSearchOperator($query, $value_search_column, $text[$arr_search_column], array_get($operator,$arr_search_column,'like'), array_get($conditions,$arr_search_column,'and') );
				}
			});
		}else {
			if( $operator == 'like' ){
				$query->builderSortableAndSearchableColumn[$column] = 'LOWER('.$query->builderSortableAndSearchableColumn[$column].')';
				$text = strtolower($text);
				
				$query->{$functionCondition}(\DB::raw('('.$query->builderSortableAndSearchableColumn[$column].')'),'like','%'.$text.'%');
            }else if( $operator == '=' ){
				$query->{$functionCondition}(\DB::raw('('.$query->builderSortableAndSearchableColumn[$column].')'),'=',$text);
            }else if( $operator == '>=' ){
				$query->{$functionCondition}(\DB::raw('('.$query->builderSortableAndSearchableColumn[$column].')'),'>=',$text);
            }else if( $operator == '<=' ){
				$query->{$functionCondition}(\DB::raw('('.$query->builderSortableAndSearchableColumn[$column].')'),'<=',$text);
            }else if( $operator == '>' ){
				$query->{$functionCondition}(\DB::raw('('.$query->builderSortableAndSearchableColumn[$column].')'),'>',$text);
            }else if( $operator == '<' ){
				$query->{$functionCondition}(\DB::raw('('.$query->builderSortableAndSearchableColumn[$column].')'),'<',$text);
            }else if( $operator == '<>' ){
                $query->{$functionCondition}(\DB::raw('('.$query->builderSortableAndSearchableColumn[$column].')'),'<',$text);
            }
		}		
		
		return $query;
	}
}
