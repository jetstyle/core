<?php

Finder::useClass('ListSimple');

class ListSorting extends ListSimple
{
	protected $template = "list_sorting.html"; 				//шаблон результата
	
	public function handle()
	{
		$this->renderSorting();
		$this->config->order_by = $_GET['sorting'];
		
		parent::handle();
	}
	
	protected function renderSorting()
	{
		foreach ($this->config->sortBy as $title => $field)
		{
			$sortBy[] = array(
				'title' => $title,
				'field' => $field,
				'current' => $field == $_GET['sorting'],
			);
		}
		$this->tpl->set('sort_by', $sortBy);
		$this->tpl->set('get_params', RequestInfo::getAll());
		$this->tpl->parse('list_sorting.html:sorting', '__sorting');
	}
}

?>