<?php
$prp->Authorise();

if ($rh->GetVar('logout')) 
{
	$prp->Logout($rh->logout_url ? $rh->logout_url : $_SERVER["HTTP_REFERER"]);
}

//��� �����������?
if ($prp->IsAuth()) 
{
	$rh->redirect($rh->url);
}

/* �������� ����� */
$template = 'login.html';
$tpl->set('POST_STATE', $state->State(1));
$tpl->parse($template, 'html_body');

include ($rh->FindScript('handlers', '_page_attrs'));
$tpl->set('page_title', '�����������');

echo $tpl->parse('html.html');
?>