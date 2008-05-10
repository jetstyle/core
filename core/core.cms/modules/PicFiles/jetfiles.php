<?php
$rh = &$this->rh;

$rh->useClass("Jetfiles");
$jetfiles = new Jetfiles($rh, $this->rh->findScript_('modules', $this->moduleName.'/_files'));

$jetfiles->setRubric(intval($this->rh->ri->get('rubric_id')));

$rh->tpl->set('rubrics', $jetfiles->getRubrics());
$rh->tpl->set('files', $rh->jsonEncode($jetfiles->getItems()));
$rh->tpl->set('pager', $jetfiles->getPages());

echo $rh->tpl->parse('jetfiles.html');
die();
?>