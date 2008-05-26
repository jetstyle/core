<?php
//рисуем тулбар
if( $rh->render_toolbar )
{
	if ($rh->db_host=="localhost" && $_SERVER['REMOTE_ADDR']==$_SERVER['SERVER_ADDR'])
	{  	  
	$tpl->set('localhost', 1);
	}
	$this->useClass('Toolbar');
	$toolbar = new Toolbar($this);
	$toolbar->handle();
	
	//блок пользователя
    if( $prp->IsAuth() ){
      //аттрибуты пользователя
      $tpl->set('login', $prp->user['login']);
      $tpl->set('role', $prp->ROLES[ $prp->user['role_id'] ]);
      $tpl->set('href', $rh->url.'login?logout=1');
      $tpl->parse( 'blocks/menu.html:auth', 'user_part' );
    }else{
      //форма авторизации
      $tpl->set('POST_STATE',$state->State(1));
      $tpl->parse( 'blocks/menu.html:not_auth', 'user_part' );
    }
	
}
?>