<?php
/**
 * ListTreeControlSelectFilter
 *
 * @author lunatic lunatic@jetstyle.ru
 */
Finder::useClass('ListSelectFilter');

class ListTreeControlSelectFilter extends ListSelectFilter
{
    public function apply($model)
    {
        if ($this->getVarValue)
        {
            $depends = $this->getConfig('depends');
            if ($depends)
            {
                $filter = $this->getListObj()->getFiltersObject($depends);

                if (!$filter->getValue())
                {
                    return;
                }
            }

            $model->where .= ($model->where ? " AND " : "")." {".$this->getConfig('field')."} = ".DBModel::quote($this->getVarValue);
        }
    }
}
?>