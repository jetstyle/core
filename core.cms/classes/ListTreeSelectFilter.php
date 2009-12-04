<?php
/**
 * ListTreeSelectFilter
 *
 * @author lunatic lunatic@jetstyle.ru
 */
Finder::useClass('ListSelectFilter');

class ListTreeSelectFilter extends ListSelectFilter
{
    protected $template = 'list_tree_select_filter.html';

    protected function constructModel()
    {
        $model = DBModel::factory($this->getConfig('model'));

        $model->registerObserver('row', array($this, 'addIndent'));
        $model->registerObserver('row', array($this, 'markSelected'));
        $model->registerObserver('row', array($this, 'collectRows'));

        return $model;
    }

    public function addIndent(&$model, &$row)
    {
        //$row['indent'] = str_pad('', ($row['_level'] - 1) * 2, '-');
	$row['indent'] = str_repeat('&nbsp;', ($row['_level'] - 1) * 2);
    }

}
?>