<?php

namespace KhanCode\LaravelBaseRest;

use Config;
use Illuminate\Support\Facades\Cache;

class Locking
{
	/**
	 * [checkAndWait description]
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	static function checkAndWait($key)
	{
		// $lock = Lock::where('key','=',$key)->first();
		if( Cache::has('lock'.$key) )
		// if(!empty($lock))
		{
			sleep(1);
			return self::checkAndWait($key);
		}
		
		Cache::forever('lock'.$key, '');		
		// Lock::create(['key'	=>	$key]);
		if( empty(Config::get('sitesetting.lock')) )
		{
			\Config::set('sitesetting.lock',[$key]);
		}
		else 
		{			
			\Config::set('sitesetting.lock',array_merge([$key],Config::get('sitesetting.lock')));
		}		
		return true;
	}

	/**
	 * [unlock description]
	 * @param  [type] $key [description]
	 * @return [type]      [description]
	 */
	static function unlock($key = 0)
	{		
		if( !empty(Config::get('sitesetting.lock')) )
		{			
			$arr_key = Config::get('sitesetting.lock');
			
			Cache::forget('lock'.$arr_key[(count($arr_key)-1)]);				
			// $lock = Lock::where('key','=',$arr_key[(count($arr_key)-1)])->delete();			
			array_pop($arr_key);
			\Config::set('sitesetting.lock',$arr_key);			
			
			// $transactionLevel = \DB::transactionLevel();
			// for ($i=0; $i < $transactionLevel; $i++) { 
				// \DB::commit();
			// }			
		}
	}
}