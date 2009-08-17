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
		$result = '';

        if (is_array ($input))
		{
			$keys = array_keys($input);
            if ($keys[0] === 0 && $keys[count($keys) - 1] === count($keys) - 1)
            {
                $result = self::encodeAsArray($input);
            }
            else
            {
                $result = self::encodeAsObject($input);
            }
		}

        $result = str_replace(array("\n", "\r"), '', $result);

        return $result;
	}

	public static function decode($input, $assoc = false)
	{
		return json_decode($input, $assoc);
	}

    private static function encodeAsObject($input)
    {
        $out = array();

        if (is_array($input))
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

    private static function encodeAsArray($input)
    {
        $out = array();

        if (is_array($input))
        {
            foreach ($input AS $value)
            {
                if (is_array($value))
                {
                    $out[] = self::encode($value);
                }
                else
                {
                    $out[] = self::quote($value);
                }
            }
        }

        return "[" . implode(",", $out) . "]";
    }

	private static function quote($value)
	{
		if (is_numeric($value))
			return $value;
		else
			return '"'.addslashes($value).'"';
	}
}

?>
