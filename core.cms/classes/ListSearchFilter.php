<?php
/**
 * ListSearchFilter
 *
 * @author lunatic
 */
Finder::useClass('ListFilter');

class ListSearchFilter extends ListFilter
{
    protected $getVar = '';
    protected $neededConfigVars = array('field');

    protected $getVarValue = '';
    protected $preparedGetVarValue = '';

    protected $template = 'list_search_filter.html';

    protected $minWordLength = 2;

    public function getValue()
    {
        return $this->getVarValue;
    }

    public function apply(&$model)
    {
        if ($this->preparedGetVarValue)
        {
            $data = explode(' ', $this->preparedGetVarValue);
            $newData = array();
            foreach ($data AS $d)
            {
                $newData[] = '{'.$this->getConfig('field').'} LIKE '.DBModel::quote('%'.$d.'%');
            }
            $model->where .= ($model->where ? " AND " : "")." ".implode(' AND ', $newData);
        }
    }
    
    protected function loadData()
    {
        
    }

    protected function constructModel()
    {

    }

    protected function getTplData()
    {
        $tplData = array(
            'get_var' => $this->getVar,
            'data' => htmlentities($this->getVarValue, ENT_COMPAT, 'cp1251'),
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
        $this->preparedGetVarValue = $this->prepareQuery($this->getVarValue);
    }

    protected function prepareQuery($q)
    {
        $q = preg_replace("/[^.\w\x7F-\xFF\s]/", " ", $q);
        $q = trim(preg_replace("/\s(\S{1,".$this->minWordLength."})\s/", " ", preg_replace("/ +/", " ", " " . $q . " ")));
        return $q;
    }
}
?>