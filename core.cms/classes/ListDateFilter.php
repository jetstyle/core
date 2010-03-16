<?php
/**
 * ListDateFilter
 *
 * @author lunatic
 */
Finder::useClass('ListSearchFilter');

class ListDateFilter extends ListSearchFilter
{
    protected $template = 'list_date_filter.html';
    protected $searchType;
	
	protected $dateOut = '$3-$2-$1';
	protected $dateIn = '/(\d+)\.(\d+)\.(\d+)(.*)/i';
	
    public function getValue()
    {
		return $this->preparedGetVarValue;
    }

    protected function init()
    {
        $this->getVar = $this->getConfig('get_var', $this->getConfig('field'));
        $this->getVarValue = RequestInfo::get($this->getVar);
        $this->searchType = $this->getConfig('search_type', self::TYPE_EQUAL);
        $this->preparedGetVarValue = $this->prepareQuery($this->getVarValue);
    }

    protected function prepareQuery($q)
    {
        return preg_replace($this->dateIn, $this->dateOut, $q);
    }
}
?>