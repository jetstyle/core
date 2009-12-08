<?php


class Json {

	public function serialize ($data) {
		if (function_exists('json_encode')) return json_encode($data);
		else return  self::encode($data);
	} // end of method serialize

	public function unserialize ($string, $assoc=false, $depth=512) {
		if (function_exists('json_dencode')) return json_decode($data, $assoc, $depth);
		else throw new Exception("Unsupported Json::unserialize");
	} // end of method unserialize

	/**
	 * encode 
	 *
	 * @see http://tools.ietf.org/html/rfc4627
	 * 
	 * @param mixed $x 
	 * @return void
	 */
	static public function encode($x) {
		if (is_array($x)) {
			$isList = true;
			//foreach ($x as $k => $v) if (!is_int($k)) { $isList = false; break; }
			// alt: to represent arrays with sparse integer keys as objects too
			 $next = 0; foreach ($x as $k => $v) if ($k !== $next++) { $isList = false; break; }
		} else {
			$isList = 0;
		}

		if ($isList) {                                          // [ x0 , x1, x2 ]
			return '['.implode(',', array_map(array('self','encode'), $x)).']';
		} elseif ($isList === false || is_object($x)) {         // { k0:v0, k1:v1, k2:v2 }
		$t = array();
		foreach ((array)$x as $k => $v) $t[] = self::encode($k) . ':' . self::encode($v);
		return '{'.implode(',', $t).'}';
		} elseif (is_numeric($x)) {
			return is_float($x)
				? str_replace(',', '.', strval($x))                  // 1,2; 1.3
				: $x;                                                // 123
		} elseif (is_bool($x)) {                                 // true || false
			return $x ? 'true' : 'false';
		} elseif (is_null($x)) {                                 // null
			return 'null';
		} else {                                                 // "string"
			static $jsonReplaces = array(
				"\\" => '\\\\', "/"  => '\\/', "\n" => '\\n', "\t" => '\\t', "\0" => '\\u0000',
				"\r" => '\\r',  "\b" => '\\b', "\f" => '\\f', '"'  => '\\"');
			//return '"'.str_replace('"', '\\"', mb_convert_encoding($x, 'UTF-8')).'"';
			return '"' . strtr($x, $jsonReplaces) . '"';
		}
	}
	
	static public function decode($x, $assoc) {
		if (function_exists('json_decode')) return json_decode($x, $assoc);
		else throw new Exception("Unsupported Json::decode");
	}
}

