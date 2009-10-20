<?php

if (!is_array($params)) {
    $string = $params;
    $esc_type = 'html';
} else {
    $string = isset($params[0]) ? $params[0] : $params['_'];
    $esc_type = $params['type'] ? $params['type'] : 'html';
}

  switch ($esc_type) {
      case 'html':
          $res = htmlspecialchars($string, ENT_QUOTES);
			 break;

      case 'htmlall':
          $res = htmlentities($string, ENT_QUOTES);
			 break;

      case 'url':
          $res = urlencode($string);
			 break;

      case 'quotes':
          // escape unescaped single quotes
          $res = preg_replace("%(?<!\\\\)'%", "\\'", $string);
			 break;

	case 'hex':
		// escape every character into hex
		$res = '';
		for ($x=0; $x < strlen($string); $x++) {
			$res .= '%' . bin2hex($string[$x]);
		}

		break;
          
	case 'hexentity':
		$res = '';
		for ($x=0; $x < strlen($string); $x++) {
			$res .= '&#x' . bin2hex($string[$x]) . ';';
		}
		break;

      case 'javascript':
          // escape quotes and backslashes and newlines
          $res = str_replace(array('\\','\'',"\r","\n"), array("\\\\", "\\'",'\r','\r'), $string);
			 break;

      default:
          $res = $string;
			 break;
  }

echo $res;

?>