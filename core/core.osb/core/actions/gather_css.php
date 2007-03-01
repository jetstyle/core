<?
function action_gather_css( &$rh, $PARAMS ){
	
	$str =& $PARAMS['__string'];
	
	//check $rh->auto_css_filename
	if( !$rh->auto_css_filename  ) 	$rh->debug->Error("Auto CSS: file name is not set");
	//check $rh->auto_css_temp_dir
	if( !$rh->auto_css_temp_dir  ) 	$rh->debug->Error("Auto CSS: temp_dir is not set");
	if( !@is_dir($rh->auto_css_temp_dir)  ) 	$rh->debug->Error("Auto CSS: temp dir is not not a dir, actualy");
	if( !@is_writeable($rh->auto_css_temp_dir)  ) 	$rh->debug->Error("Auto CSS: temp dir is not writeable");
	
	//gather css via preg
	$regexp = "/\<style.*?(?:auto=true|auto='true'|auto=\"true\").*?\>(.*?)\<\/style\>/is";
	if( preg_match_all( $regexp, $str, $A ) ){
		
		//kill marked style entries
		$str = preg_replace($regexp, '', $str);
		
		//store this piece
		$fp = @fopen( $rh->auto_css_temp_dir.$tpl->_cur_tpl.'.css', 'w' );
		fputs( $fp, implode("",$A[1]) );
		fclose($fp);
		
		//construct the whole auto.css
		$dir = @opendir( $rh->auto_css_temp_dir );
		while ( ($file = readdir($dir)) !== false ){
			if( $file!='.' && $file!='..' )
				$css .= implode("",@file( $rh->auto_css_temp_dir.$file ))."\n";
		}
		closedir($dir); 
		
		//store auto.css
		if( !($fp = @fopen( $rh->auto_css_filename, 'w' )) )
			$rh->debug->Error("Auto CSS: file provided is not writeable");
		fputs( $fp, $css );
		fclose($fp);
	}
	
	return $str;
}

?>