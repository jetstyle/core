<?php
/**
 *  ���������� ���������� �������� �����
 *
 */

$this->UseClass("controllers/BasicPage");
class BasicContentPage extends BasicPage
{
	function Handle()
	{
		// FIXME: �����, ��� parent::handle �������� $this->rend()
		$this->title = $this->rh->data['title'];

		parent::handle();

		$this->rh->tpl->set('*', $this->config);
		$this->rh->tpl->parse('_texts/textT.html', '_body');
		$this->rh->site_map_path = 'inner';
	}
}	
?>
