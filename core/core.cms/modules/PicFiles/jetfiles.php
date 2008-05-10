<?php
$rh = &$this->rh;

$rh->useClass("Jetfiles");
$jetfiles = new Jetfiles($rh, $this->rh->findScript('modules', $this->module_name.'/_files'));

$jetfiles->setRubric(intval($this->rh->getVar('rubric_id')));

$rh->tpl->set('rubrics', $jetfiles->getRubrics());
$rh->tpl->set('files', $rh->jsonEncode($jetfiles->getItems()));
$rh->tpl->set('pages', $jetfiles->getPages());

$rh->state->keep('rubric_id');

echo $rh->tpl->parse('jetfiles.html');
die();

?>