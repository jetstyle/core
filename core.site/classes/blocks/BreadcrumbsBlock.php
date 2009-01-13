<?php
Finder::useClass('blocks/Block');

class BreadcrumbsBlock extends Block
{
	public function addItem($path, $title, $hide = 0)
	{
		$data = &$this->getData();
		$data[] = array('href' => $path, 'title_short' => $title, 'hide' => $hide);
	}

	protected function constructData()
	{
		$current = array();
		if (Locator::exists('controller'))
		{
			$current = &Locator::get('controller');
		}

		$model = DBModel::factory('Content')
							->clearFields()
							->addFields(array('id','_left', '_right', '_level', '_path', '_parent'))
							->addField('title_short', 'IF(LENGTH(title_short) > 0, title_short, title_pre)')
							->addField('href', '_path')
							->setOrder(array('_left' => 'ASC'))
							->load('_left <= '.DBModel::quote($current['_left']).' AND _right >= '.DBModel::quote($current['_right']));

		$this->setData($model->getArray());
	}
}
?>