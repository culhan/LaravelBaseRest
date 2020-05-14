<?php 
namespace KhanCode\LaravelBaseRest;

/**
* 
*/
class DataEmptyException extends \Exception
{	
	public function responseJson()
	{
		return \Response::json(
	        [
	            'error' => [
	                'message' => 'Error Found.', 
	                'status_code' => 404,
	                'error' => (!empty($this->message)) ? $this->message : trans('admin/error.data_not_found')
	            ]
	        ], 
	    404);
	}
}