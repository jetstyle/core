<?php
Finder::useClass('ListSimple');

class ListPicfiles extends ListSimple
{
	protected function &getModel()
	{
		if (!$this->model)
		{
			Finder::useModel('DBModel');
			$this->model = DBModel::factory('FilesModel/cms_list');
			
			$this->model->where = $this->model->where . ($this->config->where ? ($this->model->where ? ' AND ' : '') . $this->config->where : '') ;
			
			// condition on rubric
			$files2rubricsModel = &$this->model->getForeignModel('rubric');
			$files2rubricsModel->where = $files2rubricsModel->where.( $files2rubricsModel->where ? " AND " : "" ). "{rubric_id} = ".DBModel::quote(RequestInfo::get('topic_id'));
		}

		return $this->model;
	}
}
?>