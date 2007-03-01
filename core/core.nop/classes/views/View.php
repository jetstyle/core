<?php
/*
 * Базовый класс View
 */

class View
{
    var $models = array();
    //rh чтобы ставить сайтмап
    function View(&$rh)
    {
        $this->rh =& $rh;
    }
    
    function handle()
    {
    	$this->beforeHandle();
    	
    	$this->_handle();
    	
    	//$this->afterHandle();    	
    }

    /*
     * Общая пачка функционала ДО отработки _handle 
     */
    function beforeHandle()
    {
    	return;
    	//вывод анонсов
    	
    	$this->_renderAnnounces();
    	//вывод меню
    	$this->_renderMenu();
    	//вывод хлеба
    	$this->_renderBread();
    	//вывод заголовка страницы
    	$this->_renderTitle();
    }
    
    /*
     * переопределяется в наследниках
     */
    function _handle()
    {
    	
    }
    
    function addModel(&$model, $key='')
    {
        if (is_object($model))
        {
            if(empty($key)) $key = get_class($model);
            $this->models[strtolower($key)] = $model->data;
        }
        else if (is_array($model))
        {
            
            $this->models[$key] = $model;
        }
    }
    
    /*        
     * вывод анонса, вызывается изо всех вьюх кроме HP
     */
    function _renderAnnounces()
    {
	    if ($this->rh->data['announce_id']>0)
	    {
	        $step = $this->rh->upload->GetFile("announces/picture_".$this->rh->data['announce_id']);   
	        if ($step)
	        {
	            $this->rh->data['announce_src'] = $this->rh->base_url.$step->name_full;
	            $this->rh->tpl->setRef("node", $this->rh->data);
	            $this->rh->tpl->parse("_common/sales.html:Announce", "_announceItem");
	        }
	    }
    }
    
    /*
     * вывод меню
     */
    function _renderMenu()
    {
    	//var_dump($this->models['menu']);
    	//echo '<hr>';
    	if ($this->models['menu'])
    	{
	    	$this->rh->UseClass("views/MenuView");
	        $menu_view =& new MenuView($this->rh);
	        $menu_view->addModel($this->models['menu'], 'menu');
	        $menu_view->handle();

			//TODO: так нельзя делать	        
	        unset($this->models['menu']);
    	}
    }
    
    /*
     * вывод хлеба
     */
    function _renderBread()
    {
        $this->rh->UseClass("views/BreadView");
        $bread =& new BreadView($this->rh);
        $bread->addModel($this->models['bread'], 'bread');
        $bread->Handle();
    }
    
    /*
     * Вывод Заоголовка
     */
    function _renderTitle()
    {
	    $this->rh->UseClass("views/TitleView");
	    $title =& new TitleView($this->rh);
	    $title->addModel($this->models['bread'], 'bread');
	    $title->Handle();
	    $this->rh->UseClass("ListObject");
    }
}    
    
?>
