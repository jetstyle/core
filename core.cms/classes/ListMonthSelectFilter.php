<?php
/**
 * ListMonthSelectFilter
 *
 * @author lunatic
 */
Finder::useClass('ListSelectFilter');

class ListMonthSelectFilter extends ListSelectFilter
{
    protected $months;
    protected $currentMonth = 1;

    public function collectRows(&$model, &$row)
    {
        $this->fillDataTo($row['id']);

        $row['title'] = $this->months[$row['id'] - 1];
        $row['marked'] = true;
        $this->currentMonth = $row['id'] + 1;
        parent::collectRows($model, $row);
    }

    protected function loadData()
    {
        parent::loadData();
        if ($this->currentMonth > 1)
        {
            $this->fillDataTo(13);
        }
    }

    protected function fillDataTo($month)
    {
        for (; $this->currentMonth < $month; $this->currentMonth++)
        {
            $myRow = array(
                'id' => $this->currentMonth,
                'title' => $this->months[$this->currentMonth - 1]
            );
            $this->markSelected($model, $myRow);
            $this->data[] = $myRow;
        }
    }

    protected function init()
    {
        $this->months = Locator::get('msg')->get('Months1');
        parent::init();
    }
}
?>