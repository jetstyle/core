<?php

Finder::useClass('blocks/Block');
class FormBlock extends Block
{
    private $on_after_event = array();

	protected function constructData()
	{
        $formConfigName = $this->getParam('form');
        $this->on_after_event[] = array(&$this, 'OnAfterEventForm');
        if ($formConfigName)
        {
            Finder::useClass("forms/EasyForm");
            $config = array();
            $action = $this->getParam('action');
            if ($action)
            {
                $config['action'] = RequestInfo::$baseUrl.Router::linkTo($action);
            }
//			$config['on_after_event'] = array(array(&$this, 'OnAfterEventForm'));
            $config['on_after_event'] = $this->on_after_event;

            $form = new EasyForm($formConfigName, $config);
			if ($_COOKIE[$form->form->name.'_sended'])
			{
				setcookie($form->form->name.'_sended', false, time()-3600);
				$data = Locator::get('tpl')->parse($form->config['text_after_event']);
			}
			else
			{
				$data = $form->handle();	
			}
        }
        else
        {
            $data = '';
        }

		$this->setData( $data );
	}
	
	public function OnAfterEventForm($event, $form)
	{
		if ($form->config['text_after_event'])
		{
			setcookie($form->name.'_sended', true, time()+3600);	
		}
	}

    public function addAfterEvent($event_handler)
    {
        $this->on_after_event[] = $event_handler;
    }
}
?>
