<?php

$prp->Authorise();

if ($rh->GetVar('logout'))
{
	$prp->Logout($rh->logout_url ? $rh->logout_url : $_SERVER["HTTP_REFERER"]);
}

//��� �����������?
if ($prp->IsAuth())
{
	if ($rh->GetVar('ret_url')) $rh->redirect($rh->GetVar('ret_url'));
		else $rh->redirect($rh->url);
}

/* �������� ����� */
$template = 'login.html';
$rh->state->keep('ret_url');
$tpl->set('POST_STATE', $state->State(1));
$tpl->parse($template, 'html_body');

include ($rh->FindScript('handlers', '_page_attrs'));
$tpl->set('page_title', '�����������');

echo $tpl->parse('html.html');
?>