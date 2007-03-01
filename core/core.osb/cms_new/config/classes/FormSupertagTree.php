<?
	
	$this->UseClass("FormSupertag");
	
class FormSupertagTree extends FormSupertag  {
	
  function Load(){
    $this->SELECT_FIELDS[] = "_path";
    FormSupertag::Load();
  }
	
	function Update(){
		$rh =& $this->rh;
		$db =& $rh->db;
    
    if( FormSupertag::Update() ){
  		include( $rh->FindScript('handlers','_update_tree_pathes') );
      return true;
    }else
      return false;
	}
  
}
	
?>