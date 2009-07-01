<?php
/**
 * ListSelectFilter
 *
 * @author lunatic
 */
Finder::useClass('ListFilter');

class ListSelectFilter extends ListFilter
{
    protected $getVar = '';
    protected $neededConfigVars = array('model', 'field');

    protected $getVarValue = '';

    protected $template = 'list_select_filter.html';

    public function getHtml()
    {
        $model = DBModel::factory($this->getConfig('model'));
        $model->registerObserver('row', array($this, 'markSelected'));
        $model->load();

        $tpl = Locator::get('tpl');
        $tplData = array(
            'get_var' => $this->getVar,
            'data' => $model,
            'title' => $this->getConfig('title')
        );
        $tpl->setRef('*', $tplData);
        return $tpl->parse($this->template);
    }

    public function apply($model)
    {
        if ($this->getVarValue)
        {
            $model->where .= ($model->where ? " AND " : "")." {".$this->getConfig('field')."} = ".DBModel::quote($this->getVarValue);
        }
    }

    public function markSelected(&$model, &$row)
    {
        if ($row['id'] === $this->getVarValue)
        {
            $row['selected'] = true;
        }
    }

    protected function init()
    {
        $this->getVar = $this->getConfig('get_var');

        if (!$this->getVar)
        {
            $this->getVar = $this->getConfig('field');
        }

        $this->getVarValue = RequestInfo::get($this->getVar);
    }
}
?>