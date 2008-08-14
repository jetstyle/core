<?php

class Link
{
	private static $allowedProto = null;
	
	public static function formatLink($val)
	{
		$ap = &self::getAllowedProto();
		if (is_array($ap))
		{
			foreach($ap AS $r)
			{
				if($r && !(strpos($val, $r) === false))
				{
					return $val;
				}
			}
		}
		return $val;		
	}
	
	private static function &getAllowedProto()
	{
		if (null === self::$allowedProto)
		{
			self::$allowedProto = explode(',', Config::get('link_allowed_protocols'));
		}
		return self::$allowedProto; 
	}
}

?>