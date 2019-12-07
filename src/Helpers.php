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
}