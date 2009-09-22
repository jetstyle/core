<?php
/**
 * ListLettersFilter
 *
 * @author lunatic lunatic@jetstyle.ru
 */
Finder::useClass('ListFilter');

class ListLettersFilter extends ListFilter
{
    protected $getVar = '';
    protected $neededConfigVars = array('model', 'field');

    protected $getVarValue = '';
    protected $data = array();

    protected $template = 'list_letters_filter.html';

    public function getValue()
    {
        return $this->getVarValue;
    }

    public function apply(&$model)
    {
        if ($this->getVarValue || $this->getConfig('always_apply'))
        {
            if ($model instanceof DBModel)
            {
                $model->where .= ($model->where ? " AND " : "")." {".$this->getConfig('field')."} LIKE ".DBModel::quote($this->getVarValue.'%');
            }
            else
            {
                $model .= ($where ? " AND " : "")." ".$this->getConfig('field')." LIKE ".DBModel::quote($this->getVarValue.'%');
            }
        }
    }

    public function markSelected(&$model, &$row)
    {
        if ($row['letter'] == $this->getVarValue)
        {
            $row['selected'] = true;
        }
    }

    public function collectRows(&$model, &$row)
    {
        $row['href'] = RequestInfo::hrefChange('', array($this->getVar => $row['selected'] ? '' : $row['letter']));
        $this->data[] = $row;
    }

    protected function constructModel()
    {
        $model = DBModel::factory($this->getConfig('model'));
        $model->registerObserver('row', array($this, 'markSelected'));
        $model->registerObserver('row', array($this, 'collectRows'));

        return $model;
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