<?php
Finder::useClass('FormFiles');

class FormIframe extends FormFiles
{

	//  var $template_item = 'faq_form.html';

	protected function renderFields()
	{
		$renderResult = parent::renderFields();

		if ($renderResult)
		{
			$tpl = &$this->tpl;

			$item = &$this->getItem();

			if ($item[$this->idField])
			{
				if (is_array($this->config['href_for_iframe']))
				{
					foreach ($this->config['href_for_iframe'] AS $k => $href_for_iframe)
					{
						$tpl->set("_iframe_number", $k);
						$this->_parseIframe($href_for_iframe);
					}
				}
				else
				{
					$this->_parseIframe($this->config['href_for_iframe']);
				}
			}
			else
			{
				$tpl->set('_iframe', '<br />');
			}
		}

		return $renderResult;
	}

	protected function _parseIframe($href_for_iframe)
	{
		if(!$href_for_iframe)
		{
			return;
		}
		$tpl = & $this->tpl;
		$item = &$this->getItem();
		$wid = $item[$this->idField];

		$vis = isset ($_COOKIE["cf" . $wid]) ? $_COOKIE["cf" . $wid] : !$this->config['closed_iframe'];

		$tpl->set('_id', $wid);
		$tpl->set('_class_name_1', ($vis == "true" || $vis === true) ? "visible" : "invisible");
		$tpl->set('_class_name_2', ($vis == "false" || $vis === false) ? "visible" : "invisible");

		$tpl->set('prefix', $this->prefix);
		$tpl->set('__url', RequestInfo::$baseUrl . $href_for_iframe . $this->id . '&hide_toolbar=1');
		$tpl->parse('iframe.html', '_iframe', 1);
	}
}
?>
