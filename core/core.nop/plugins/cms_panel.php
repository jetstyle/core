<?php

	if($rh->principal->isAuth()) {
		$tpl->set('cur_url',$rh->ri->hrefPlus('',''));
        return $tpl->parse('cms_panel.html');
	} else return '';

?>