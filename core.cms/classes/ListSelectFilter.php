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
    protected $data = array();

    protected $template = 'list_select_filter.html';

    public function getHtml()
    {
        $this->loadData();

        $tpl = Locator::get('tpl');
        $tpl->setRef('*', $this->getTplData());
        return $tpl->parse($this->template);
    }

    public function getValue()
    {
        return $this->getVarValue;
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
        if ($row['id'] == $this->getVarValue)
        {
            $row['selected'] = true;
        }
    }

    public function collectRows(&$model, &$row)
    {
        $this->data[] = $row;
    }

    protected function loadData()
    {
        $model = $this->getModel();
        if ($model)
        {
            $model->load();
        }
    }

    protected function getModel()
    {
        $model = DBModel::factory($this->getConfig('model'));
        $model->registerObserver('row', array($this, 'markSelected'));
        $model->registerObserver('row', array($this, 'collectRows'));

        $this->applyDependencies($model);

        return $model;
    }

    protected function applyDependencies(&$model)
    {
        $depends = $this->getConfig('depends');
        if ($depends)
        {
            $filter = $this->getListObj()->getFilterObject($depends);

            if (!$filter->getValue())
            {
                $model = null;
            }
            else
            {
                $filter->apply($model);
            }
        }
    }

    protected function getTplData()
    {
        $tplData = array(
            'get_var' => $this->getVar,
            'data' => $this->data,
            'title' => $this->getConfig('title')
        );
        
        return $tplData;
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