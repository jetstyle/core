<?php
//@cms_panel.html

if (Config::get('db_disable'))
{
	return '';
}

if(Locator::get('principal')->isAuth()) 
{
	$cookieOce = $_COOKIE['oce'];
	$getOce = RequestInfo::get('oce');
	
	if ($getOce) 
	{
		setcookie('oce',$getOce,time()+60*60*24*7,RequestInfo::$baseUrl, RequestInfo::$cookieDomain);
		$oce = $getOce;
	} 
	else if ($cookieOce) 
	{
		$oce = $cookieOce;
	} 
	else 
	{
		$oce = 'off';
	}
	
	$tpl->set('oce_on', $oce=='on');
	$tpl->set('oce_off_href',RequestInfo::hrefChange('',array('oce' => 'off')));
	$tpl->set('oce_on_href',RequestInfo::hrefChange('',array('oce' => 'on')));
	$tpl->set('cur_url',RequestInfo::hrefChange('',array('oce' => '')));
	return $tpl->parse('cms_panel.html');
} 
else 
{
	return '';
}
?>