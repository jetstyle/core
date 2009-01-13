<?php
/**
 * Json.
 * 
 * Convert data to JSON and back.
 * 
 * @author lunatic <lunatic@jetstyle.ru>
 *
 */
class Json
{
	private function __construct(){}
	
	public static function encode($input)
	{
		$out = array();
		if (is_array ($input))
		{
			foreach ($input AS $key => $value)
			{
				if (is_array($value))
				{
					$out[] = self::quote($key) . ":" . self::encode($value);
				}
				else
				{
					$out[] = self::quote($key) . ":" . self::quote($value);
				}
			}
		}
		return "{" . implode(",", $out) . "}";
	}
	
	public static function decode($input)
	{
		return json_decode($input);
	}
	
	private static function quote($value)
	{
		if (is_numeric($value))
			return $value;
		else
			return "'".addslashes($value)."'";
	}
}

?>