<?php
/*

  Контроль доступа "по поддеревьям $params"

  PrincipalSecurity_tree( &$principal )

  -------------------

================================================================== v.1 (alatar@npj)
*/

class PrincipalSecurity_tree extends PrincipalSecurity
{
	function OnLogin( &$user_data )
   	{ 
		return true;
	}
	
	function Check( &$user_data, $params="edit" )  
	{ 
		//admin
		
		if ($this->rh->principal->Security("role","admin")){
			return GRANTED;
		}
		$ids=array();
		//print_r($this->rh->path_to_root);
		//die($params["action"]);
		
		foreach ($this->rh->path_to_root as $value)	{
			$ids[] = $value["user_id"];
		//	var_dump($value);
		}
		//print_r($this->rh->principal->data);
		if (!in_array($this->rh->principal->data["user_id"],$ids) 
			|| ($this->rh->path_to_root[count($this->rh->path_to_root)-1][parent_id]==$this->rh->users_homedir_id 
			&& ($params["action"]=='edit' || $params["action"]=='delete') )){
			return DENIED;	
		} else {
			return GRANTED;	
		}
	}
	
    
	

// EOC{ PrincipalSecurity_tree }
}


?>