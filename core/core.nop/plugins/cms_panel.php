<?php

	if($rh->principal->isAuth()) {		$tpl->set('oce_on',$rh->ri->get('oce') == 'on');
        return $tpl->parse('cms_panel.html');
	} else return '';

?>
