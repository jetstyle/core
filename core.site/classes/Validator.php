<?php
class Validator {
    
    public static function testValue($value, $method, $param = true)
    {
        Finder::useClass('Inflector');
        $methodName = 'check'.Inflector::camelize($method);
        if (method_exists(Validator, $methodName))
        {
            if ($param !== true)
            {
                return self::$methodName($value, $param);
            }
            else
            {
                return self::$methodName($value);
            }
        }
        else
        {
            return true;
        }
        /*else
        {
            throw new JSException('Method "'.$methodName.'" not found in validator.');
        }*/
    }
    
    private static function checkNotEmpty($value)
    {
        return $value != '';
    }
    
    private static function checkMinLength($value, $param)
    {
        return strlen($value) >= $param;
    }
    
    private static function checkMaxLength($value, $param)
    {
        return strlen($value) <= $param;
    }
    
    private static function checkIsNumeric($value)
    {
        return is_numeric($value);
    }
    
    private static function checkIsRegexp($value, $param)
    {
        return preg_match($param, $value) === 1;
    }
    
    private static function checkIsEmail($value)
    {
        return self::checkIsRegexp($value, '/^(([a-z\.\-\_0-9+]+)@([a-z\.\-\_0-9]+\.[a-z]+))$/i');
    }
    
}
?>
