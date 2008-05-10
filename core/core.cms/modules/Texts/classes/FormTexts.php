<?php
$this->useClass('FormSimple');

class FormTexts extends FormSimple 
{
	protected $template_item = 'texts_form.html';

	public function handle()
	{
		$tpl =& $this->rh->tpl;

		$this->load();

		$tpl->set('prefix',$this->prefix);
		$tpl->setRef('*',$this->item);

		if( $this->item['type']==1 )
		{
			$tpl->parse( $this->template_item.':text_plain', 'text' );
		}
		else
		{
			$tpl->parse( $this->template_item.':text_rich', 'text' );
		}

		parent::handle();
	}

	function update()
	{
		$rh =& $this->rh;

		if( $_POST[ $this->prefix.'_supertag']=='' )
		{
			$this->config->supertag = 'title';
		}

		return parent::update();
	}

}

?>