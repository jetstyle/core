<?
	
	$this->UseClass("FormFiles");
	
class FormSupertag extends FormFiles  {
	
	
	function Update(){
		$rh =& $this->rh;
		$db =& $rh->db;
		
		if( $rh->GLOBALS[ $this->prefix."_supertag".$this->suffix ]=="" )
			$this->config->supertag = "title";
    
		return FormFiles::Update();
	}
  
}
	
?>
