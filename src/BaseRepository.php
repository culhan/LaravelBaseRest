<?php

namespace KhanCode\LaravelBaseRest;

use Validator;
use KhanCode\LaravelBaseRest\ValidationException;

/**
 * code for system logic
 */
class BaseRepository
{
	/**
	 * [__callStatic description]
	 * @param  [type] $method     [description]
	 * @param  [type] $parameters [description]
	 * @return [type]             [description]
	 */
	public static function __callStatic($method, $parameters)
    {    	
    	if (substr( $method, 0, 4 ) === "call") {
    		$method = lcfirst(str_replace('call', '', $method));
    		$thisClass = get_called_class();
	    	$thisClass = str_replace('Repositories', 'Services', str_replace('Repository', 'Service', $thisClass));
	    	$load_service = new $thisClass;
	        if(method_exists($load_service, $method))  
			{  
				return call_user_func_array(array($load_service, $method), $parameters);  
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
	public function validate($data, $rules = [], $messages = [])
	{
		$rules = empty($rules) ? $this->model->$rules : $rules;  
		$validator = Validator::make($data, $rules, $messages);
		if($validator->fails()) throw new ValidationException($validator->errors());
		return true;
	}
}