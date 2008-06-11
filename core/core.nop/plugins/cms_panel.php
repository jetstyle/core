<?php

	if($rh->principal->isAuth()) {		$tpl->set('oce_on',$rh->ri->get('oce') == 'on');
		$tpl->set('cur_url',$rh->ri->hrefPlus('',''));
        return $tpl->parse('cms_panel.html');
	} else return '';

?>
