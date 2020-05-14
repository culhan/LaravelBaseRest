<?php

namespace KhanCode\LaravelBaseRest\Rules;

use Illuminate\Contracts\Validation\Rule;

class SortableAndSearchable implements Rule
{
    /**
     * 
     */
    public $sortAbleNSearchAbleColumn;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct(array $data)
    {
        $this->sortAbleNSearchAbleColumn = $data;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->value = $value;
        if( is_array($value) )
        {
            return $this->checkPassesArray($value);                        
        }
        else
        {
            if( !isset($this->sortAbleNSearchAbleColumn[$value]) )
            {
                $this->notExist = 1;
                return false;
            }
            else
            {
                if( !$this->sortAbleNSearchAbleColumn[$value] )
                {             
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * [checkPasses description]
     *
     * @param   Type  $data  [$var description]
     *
     * @return  [type]      [return description]
     */
    public function checkPassesArray($data)
    {
        foreach ($data as $column) {
            $this->value = $column;
            if( !is_array($column) ) {
                if( !isset($this->sortAbleNSearchAbleColumn[$column]) )
                {
                    $this->notExist = 1;
                    return false;
                }
                else
                {
                    if( !$this->sortAbleNSearchAbleColumn[$column] )
                    {             
                        return false;
                    }
                }
            }else {
                return $this->checkPassesArray($column);
            }
        }

        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if(isset($this->notExist))
        {
            return trans('baseRestValidation.attributes.sortableAndSearchableExist', ['value' => $this->value]);    
        }

        return trans('baseRestValidation.attributes.sortableAndSearchable', ['value' => $this->value]);
    }
}
