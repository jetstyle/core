<?php
/**
 * ListSelectFilter
 *
 * @author lunatic lunatic@jetstyle.ru
 */
Finder::useClass('ListSelectFilter');

class ListSelectOptionsFilter extends ListSelectFilter
{
    protected $getVar = '';
    protected $neededConfigVars = array('options', 'field');

    protected $getVarValue = '';
    protected $data = array();

    protected $template = 'list_select_filter.html';

    protected function constructModel()
    {
        $this->data = $this->getConfig('options');
        foreach ($this->data as &$item)
        {
            if (isset($this->getVarValue) && strlen($this->getVarValue) > 0 && $item['id'] == $this->getVarValue)
            {
                $item['selected'] = true;
            }
        }

        return false;
    }
}
?>