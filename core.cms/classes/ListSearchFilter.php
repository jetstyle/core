<?php
/**
 * ListSearchFilter
 *
 * @author lunatic
 */
Finder::useClass('ListFilter');

class ListSearchFilter extends ListFilter
{
    const TYPE_LIKE = 'like';
    const TYPE_LT = 'lt';
    const TYPE_GT = 'gt';
    const TYPE_LTE = 'lte';
    const TYPE_GTE = 'gte';
    const TYPE_EQUAL = 'eq';

    protected $getVar = '';
    protected $neededConfigVars = array('field');

    protected $getVarValue = '';
    protected $preparedGetVarValue = '';

    protected $template = 'list_search_filter.html';

    protected $minWordLength = 2;
    protected $searchType;

    public function getValue()
    {
        switch ($this->searchType)
        {
            case self::TYPE_LIKE:
                return $this->preparedGetVarValue;
                break;

            case self::TYPE_EQUAL:
            case self::TYPE_GT:
            case self::TYPE_LT:
            case self::TYPE_GTE:
            case self::TYPE_LTE:
            default:
                return $this->getVarValue;
                break;
        }
    }

    public function apply(&$model)
    {
        $value = $this->getValue();

        if ($value)
        {
            $where = '';
            switch ($this->searchType)
            {
                case self::TYPE_LIKE:
                    $data = explode(' ', $value);
                    $newData = array();
                    foreach ($data AS $d)
                    {
                        $newData[] = '{'.$this->getConfig('field').'} LIKE '.DBModel::quote('%'.$d.'%');
                    }
                    $where = implode(' AND ', $newData);

                    break;

                case self::TYPE_EQUAL:
                    $where= '{'.$this->getConfig('field').'} = '.DBModel::quote($value);
                    break;

                case self::TYPE_GT:
                    $where= '{'.$this->getConfig('field').'} > '.DBModel::quote($value);
                    break;

                case self::TYPE_LT:
                    $where= '{'.$this->getConfig('field').'} < '.DBModel::quote($value);

                case self::TYPE_GTE:
                    $where= '{'.$this->getConfig('field').'} >= '.DBModel::quote($value);
                    break;

                case self::TYPE_LTE:
                    $where= '{'.$this->getConfig('field').'} <= '.DBModel::quote($value);
                    break;
            }

            if ($where)
            {
                $model->where .= ($model->where ? " AND " : "")." ".$where;
            }
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
        $this->getVar = $this->getConfig('get_var', $this->getConfig('field'));
        $this->getVarValue = RequestInfo::get($this->getVar);
        $this->searchType = $this->getConfig('search_type', self::TYPE_LIKE);

        if ($this->searchType == self::TYPE_LIKE)
        {
            $this->preparedGetVarValue = $this->prepareQuery($this->getVarValue);
        }
    }

    protected function prepareQuery($q)
    {
        $q = preg_replace("/[^.\w\x7F-\xFF\s]/", " ", $q);
        $q = trim(preg_replace("/\s(\S{1,".$this->minWordLength."})\s/", " ", preg_replace("/ +/", " ", " " . $q . " ")));
        return $q;
    }
}
?>
