<?php
$prp->Authorise();

if ($rh->GetVar('logout')) 
{
	$prp->Logout($rh->logout_url ? $rh->logout_url : $_SERVER["HTTP_REFERER"]);
}

//уже авторизован?
if ($prp->IsAuth()) 
{
	$rh->redirect($rh->url);
}

/* отрисуем форму */
$template = 'login.html';
$tpl->set('POST_STATE', $state->State(1));
$tpl->parse($template, 'html_body');

include ($rh->FindScript('handlers', '_page_attrs'));
$tpl->set('page_title', 'јвторизаци€');

echo $tpl->parse('html.html');
?>