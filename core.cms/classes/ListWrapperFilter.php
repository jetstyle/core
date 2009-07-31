<?php
/**
 * ListWrapperFilter
 *
 * @author lunatic
 */
Finder::useClass('ListFilter');

class ListWrapperFilter extends ListFilter
{
    protected $filters = array();
    protected $template = 'list_wrapper_filter.html';

    public function getValue()
    {
        
    }

    public function apply(&$model)
    {
        foreach ($this->filters AS $filter)
        {
            $filter->apply($model);
        }
    }

    public function getByKey($key)
    {
        $filter = null;

        if (array_key_exists($key, $this->filters))
        {
            $filter = $this->filters[$key];
        }
        else
        {
            foreach ($this->filters AS $_filter)
            {
                if ($filter = $_filter->getByKey($key))
                {
                    break;
                }
            }
        }

        return $filter;
    }

    protected function constructModel()
    {
        
    }

    protected function getTplData()
    {
        $data = '';
        
        foreach ($this->filters AS $filter)
        {
            $data .= $filter->getHtml();
        }

        $tplData = array(
            'data' => $data,
            'title' => $this->getConfig('title')
        );
        
        return $tplData;
    }

    protected function init()
    {
        $filters = $this->getConfig('filters');
        if (is_array($filters) && !empty($filters))
        {
            foreach ($filters AS $key => $config)
            {
                if (!is_array($config))
                {
                    $config = array();
                }

                if (!$config['field'] && !is_numeric($key))
                {
                    $config['field'] = $key;
                }

                $this->filters[$key] = ListFilter::factory($config, $this->getListObj());
            }
        }
    }
}
?>