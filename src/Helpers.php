<?php

namespace KhanCode\LaravelBaseRest;

class Helpers
{
    /**
	 * set error to global
	 *
	 * @param   array  $error  [$error description]
	 *
	 * @return  [type]         [return description]
	 */
	static function set_error($errors, $message = '') {
		$old_error	= \Config::get('user_error');
        
        if(is_array($errors)) {
            foreach ($errors as $errors_key => $errors_value) {
                foreach ($errors_value as $error_key => $error_value) {				
                    $old_error[$errors_key][]	= $error_value;
                }
            }
        }else {
            $old_error[$errors][]	= $message;
        }

		\Config::set('user_error',$old_error);
		return $old_error;
    }
    
    /**
     * [get_error description]
     *
     * @return  [type]  [return description]
     */
    static function get_error() {
        return json_encode( \Config::get('user_error') );
    }

    /**
     * [is_error description]
     *
     * @return  [type]  [return description]
     */
    static function is_error() {
        if( empty(\Config::get('user_error')) ) {
            return false;
        }
        return true;
    }

    /**
	 * [isJson description]
	 *
	 * @param   [type]  $string  [$string description]
	 *
	 * @return  [type]           [return description]
	 */
	static function isJson($string) {
		if(!is_string($string)) return false;
		$res = json_decode($string);
		return (json_last_error() == JSON_ERROR_NONE && $res != $string);
	}

	/**
	 * [json_decode_array description]
	 *
	 * @param   [type]  $input  [$input description]
	 *
	 * @return  [type]          [return description]
	 */
	static function json_decode_recursive($input, $array_or_object = false) { 
		if( is_array($input) || is_object($input) ){						
			foreach ($input as $key => $value) {					
				if( isJson($value) || is_array($value) || is_object($value) ) {
					$input[$key] = self::json_decode_recursive($value, $array_or_object);
				}
			}
		}elseif(isJson($input)) {			
			$from_json =  json_decode($input, $array_or_object);  			
			$input = ($from_json) ? $from_json : $input;						
			$input = self::json_decode_recursive($input, $array_or_object);			
		}

		return $input;
	}
}