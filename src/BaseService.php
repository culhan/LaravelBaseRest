<?php

namespace KhanCode\LaravelBaseRest;

use Validator;
use KhanCode\LaravelBaseRest\ValidationException;

/**
 * code for system logic
 */
class BaseService
{
    public static function __callStatic($method, $parameters)
    {
    	$thisClass = get_called_class();
    	$load_service = new $thisClass;
        if(method_exists($load_service->repository, $method))  
		{  
			return call_user_func_array(array($load_service->repository, $method), $parameters);  
		} 
    }

    public function __call($method, $parameters)
    {
    	$thisClass = get_called_class();
    	$load_service = new $thisClass;
        if(method_exists($load_service->repository, $method))  
		{  
			return call_user_func_array(array($load_service->repository, $method), $parameters);  
		} 
	}
	
	/**
	 * [validate description]
	 * @param  [type] $data     [description]
	 * @param  array  $rules    [description]
	 * @param  array  $messages [description]
	 * @return [type]           [description]
	 */
	public function validate($data, $rules = [], $messages = [])
	{
		$rules = empty($rules) ? self::$rules : $rules;  
		if(empty($rules)) return true;
		$validator = Validator::make($data, $rules, $messages);		
		if($validator->fails()) \KhanCode\LaravelBaseRest\Helpers::set_error($validator->errors()->toArray());
		return true;
	}
}