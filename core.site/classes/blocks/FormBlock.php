<?php

Finder::useClass('blocks/Block');
class FormBlock extends Block
{
    //конфиг ФОРМ (ключ - имя формы)
    protected $config = array();

	protected function constructData()
	{
        $formConfigName = $this->getParam('form');

        if ($formConfigName)
        {
            Finder::useClass("forms/Form");
            $config = array();
            $action = $this->getParam('action');
            if ($action)
            {
                $config['action'] = RequestInfo::$baseUrl.Router::linkTo($action);
            }
			$config['on_after_event'] = array(array(&$this, 'OnAfterEventForm'));


            if (!empty($this->config[ $this->getParam("form") ]))
                $config = array_merge($this->config[$this->getParam("form")]);

            $form = new Form($formConfigName, $config);
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

    public function setConfig($config)
    {
        $this->config = $config;

    }
}
?>
