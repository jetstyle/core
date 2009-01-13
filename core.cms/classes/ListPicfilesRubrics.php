<?php
Finder::useClass('ListComplete');

class ListPicfilesRubrics extends ListComplete
{
	protected function _delete()
	{
		Finder::useModel('DBModel');
		Finder::useClass('FileManager');
		$m = DBModel::factory('FilesModel/cms_list');
			
		// condition on rubric
		$files2rubricsModel = &$m->getForeignModel('rubric');
		$files2rubricsModel->where = $files2rubricsModel->where.( $files2rubricsModel->where ? " AND " : "" ). "{rubric_id} = ".DBModel::quote($this->id);
		
		$m->registerObserver('row', array($this, 'deleteFile'));
		$m->load();
		
		$model = &$this->getModel();
		$model->delete($model->quoteFieldShort($this->idField).'='.DBModel::quote($this->id));
	}
	
	public function deleteFile(&$model, &$row)
	{
		FileManager::getFile(null, $row[$model->getPk()])->delete();
	}
}
?>