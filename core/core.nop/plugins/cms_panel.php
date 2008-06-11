<?php

	if($rh->principal->isAuth()) {
		$cookieOce = $_COOKIE['oce'];
		$getOce = $rh->ri->get('oce');
		if ($getOce) {        	setcookie('oce',$getOce,time()+60*60*24*7,$rh->base_url);
        	$oce = $getOce;
		} else if ($cookieOce) {            $oce = $cookieOce;
		} else {         	$oce = 'off';
		}
		$tpl->set('oce_on', $oce=='on');
		$tpl->set('cur_url',$rh->ri->hrefPlus('',''));
        return $tpl->parse('cms_panel.html');
	} else return '';

?>
