<?php
class Block
{
	protected $data = null;
	private   $tplParams = array();
	protected $config = array();
	protected $alwaysLoad = false;

	public function __construct($config = array())
	{
		$this->config = $config;
	}

	/**
	 * Вернуть конфиг
	 */
	public function getConfig($key="")
	{
                if ($key!=="" && isset($this->config[$key]))
                    return $this->config[$key];
        
		return $this->config;
	}

	public function &getData()
	{
		if (null === $this->data || $this->alwaysLoad)
		{
		    try
		    {
                        $this->constructData();
		    }
		    catch( Exception $e )
		    {
                        //Exceptions not to ignore
                        $processExceptions = array(EXCEPTION_MAIL, EXCEPTION_MAIL | EXCEPTION_SILENT);

                        if ( in_array( ExceptionHandler::getInstance()->getMethod($e), $processExceptions  ) )
                        {
                            ExceptionHandler::getInstance()->process($e);
                        }
                        else if ( $_GET['debug'] )
                        {
                            ExceptionHandler::getInstance()->process($e);
                        }

                        $this->data = null;
                    }
                }
                return $this->data;
	}

        /**
	 * Params, passed to tpl
	 * @param $params array
	 * @return void
	 */
	public function setTplParams(&$params)
	{
		$this->tplParams = &$params;
	}

	public function getTplParam($key)
	{
		return $this->tplParams[$key];
	}

        public function getParam($key)
        {
            if (isset($this->tplParams[$key]))
            {
                return $this->tplParams[$key];
            }
            elseif ($this->config[$key])
            {
                //если конфиг блока содиржит параметры для cms
                if (is_array($this->config[$key]) && isset($this->config[$key]['value']))
                    return $this->config[$key]['value'];
                else
                    return $this->config[$key];
            }
            else
            {
                return null;
            }
        }

	protected function setData($data)
	{
		$this->data = $data;
	}

	protected function constructData()
	{

	}
}
?>
