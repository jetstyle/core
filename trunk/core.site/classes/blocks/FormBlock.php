<?php

Finder::useClass('blocks/Block');
class FormBlock extends Block
{

	protected function constructData()
	{
        $formConfigName = $this->getParam('form');

        if ($formConfigName)
        {
            Finder::useClass("forms/EasyForm");
            $config = array();
            $action = $this->getParam('action');
            if ($action)
            {
                $config['action'] = RequestInfo::$baseUrl.Router::linkTo($action);
            }
            $form = new EasyForm($formConfigName, $config);
            $data = $form->handle();
        }
        else
        {
            $data = '';
        }

		$this->setData( $data );
	}

}
?>