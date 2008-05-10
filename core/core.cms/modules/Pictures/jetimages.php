<?php

$rh = &$this->rh;

$rh->useClass("Jetimages");
$jetimages = new Jetimages($rh, $this->rh->findScript_('modules', $this->moduleName.'/_files'));

$jetimages->setRubric(intval($this->rh->ri->get('rubric_id')));

$rh->tpl->set('rubrics', $jetimages->getRubrics());
$rh->tpl->set('files', $rh->jsonEncode($jetimages->getItems()));
$rh->tpl->set('pager', $jetimages->getPages());

echo $rh->tpl->parse('jetimages.html');
die();
?>