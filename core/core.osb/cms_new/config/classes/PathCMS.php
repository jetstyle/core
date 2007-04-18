<?
	$this->UseClass('Path');
	
class PathCMS extends Path {
	
	function Handle($path_str){
		$rh =& $this->rh;
		
		$this->path_orig = $path_str;
		
		$B = explode( '/', rtrim($path_str) );
		
		if(!$rh->GLOBALS['page']){
			$rh->GLOBALS['page'] = $B[0];
			$this->path_trail = str_replace( $B[0].'/', '', $path_str );
		}
		if( $rh->GLOBALS['page']=='do' ){
			//запрос на обработку модуля
			if(!$rh->GLOBALS['module']) $rh->GLOBALS['module'] = $B[1];
			if(!$rh->GLOBALS['mode']) $rh->GLOBALS['mode'] = $B[2];
			
			if(count($B)>3)
				$this->path_trail = implode('/',array_splice( $B, 3 ));
			
			$rh->state->GET_FREEZED['page'] = true;
			$rh->state->GET_FREEZED['module'] = true;
			$rh->state->GET_FREEZED['mode'] = true;
		}
	}
}
	
?>