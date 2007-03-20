<?
  //подготовим принципал

  if($prp->acl_default){
    $prp->is_granted_default = false;
    $prp->ACL['*'] = array( ROLE_GOD );
    $prp->acl_default = false;
  }
  $prp->Authorise();

?>