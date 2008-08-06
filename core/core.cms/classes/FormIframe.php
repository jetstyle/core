<?
$this->UseClass('FormFiles');

class FormIframe extends FormFiles
{

	//  var $template_item = 'faq_form.html';

	function Handle()
	{
		$tpl = & $this->rh->tpl;
		//load item
		$this->Load();

		//добавляем iframe с редактированием вопросов
		if ($this->item['id'])
		{
			if (is_array($this->config->href_for_iframe))
			{
				foreach ($this->config->href_for_iframe as $k => $href_for_iframe)
				{
					$tpl->set("_iframe_number", $k);
					$this->_parseIframe($href_for_iframe);
				}
			} else
			{
				$this->_parseIframe($this->config->href_for_iframe);
			}
		} else
			$tpl->set('_iframe', '<br />');

		//по этапу
		FormFiles :: Handle();
	}

	function _parseIframe($href_for_iframe)
	{
		if(!$href_for_iframe)
		{
			return;
		}
		$tpl = & $this->rh->tpl;
		$wid = $this->item['id'];

		$vis = isset ($_COOKIE["cf" . $wid]) ? $_COOKIE["cf" . $wid] : !$this->config->closed_iframe;

		//var_dump( $vis );
		$tpl->set('_id', $wid);
		$tpl->set('_class_name_1', ($vis == "true" || $vis === true) ? "visible" : "invisible");
		$tpl->set('_class_name_2', ($vis == "false" || $vis === false) ? "visible" : "invisible");

		$tpl->set('prefix', $this->prefix);
		$tpl->set('__url', $this->rh->path_rel . $href_for_iframe . $this->id . '&hide_toolbar=1');
		//die($this->rh->path_rel.$this->config->href_for_iframe.$this->id.'&hide_toolbar=1' );
		$tpl->Parse('iframe.html', '_iframe', 1);

	}

	function Update()
	{
		$rh = & $this->rh;
		$db = & $rh->db;

		//    if( $rh->GLOBALS[ $this->prefix.'_supertag'.$this->suffix ]=='' )
		//      $this->config->supertag = 'title';

		if ($this->config->update_tree)
		{
			if (!FormSimple :: Update())
				return false;
			include ($rh->FindScript('handlers', '_update_tree_pathes'));
			return true;
		}

		return FormFiles :: Update();
	}

}
?>
