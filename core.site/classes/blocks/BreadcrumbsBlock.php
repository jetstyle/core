<?php
Finder::useClass('blocks/Block');

class BreadcrumbsBlock extends Block
{
	public function addItem($path, $title, $hide = 0)
	{
		//$data = &$this->getData();
		$this->data[] = array('href' => $path, 'title_short' => $title, 'hide' => $hide);
               //var_dump($this->data);
	}

	protected function constructData()
	{
		$current = array();
		if (Locator::exists('controller'))
		{
			$current = &Locator::get('controller');
		}
                
                if ($this->getParam("cms"))
                {   
                    //var_dump($this->data);
                    $this->setData($this->data);
                    // var_dump($this->data);
                }
                else
                {
                    
                    $model = DBModel::factory('Content')
                                                            ->clearFields()
                                                            ->addFields(array('id','_left', '_right', '_level', '_path', '_parent'))
                                                            ->addField('title_short', 'IF(LENGTH(title_short) > 0, title_short, title_pre)')
                                                            ->addField('href', '_path')
                                                            ->setOrder(array('_left' => 'ASC'))
                                                            ->load('_left <= '.DBModel::quote($current['_left']).' AND _right >= '.DBModel::quote($current['_right']));
                    $data = $model->getArray();
					
					if ($this->data)
					{
						$this->data = @array_merge($data, $this->data);	
					}
					else
					{
						$this->data = $data;
					}
                    
                    $this->setData($this->data);
                }
	}
}
?>