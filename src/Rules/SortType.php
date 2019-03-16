<?php

namespace KhanCode\LaravelBaseRest\Rules;

use Illuminate\Contracts\Validation\Rule;

class SortType implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $this->attribute = $attribute;
        if (is_array($value)) {
            foreach ($value as $key_val => $value_val) {
                if ($value_val != 'desc' && $value_val != 'asc') {
                    return false;
                }
            }
        } else {
            if ($value != 'desc' && $value != 'asc') {
                return false;
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
        return trans('baseRestValidation.attributes.sortType', [
            'attr'  =>  $this->attribute,
        ]);
    }
}
