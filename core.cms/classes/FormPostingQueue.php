<?php
/**
 * Form of the Posting module
 *
 * @author bjornd@jetstyle.ru
 */

Finder::useClass('FormSimple');

class FormPostingQueue extends FormSimple
{

	public function __construct( &$config )
	{
		parent::__construct($config);
	}

	public function handle()
	{
		$this->load();
		Finder::useClass('EmailQueue');
		$queue = new EmailQueue();
		$postInfo = $queue->getMessageStatus($this->item['fk_table'],$this->item['fk_id']);
		if ($this->item['id'] && $postInfo['sended_count'] == 0) {            $this->tpl->parse('posting_buttons.html:delete_post_button','_send_button');
		} else if($this->item['id']) {			$this->tpl->set('sended_count',$postInfo['sended_count']);
			$this->tpl->set('all_count',$postInfo['all_count']);
            $this->tpl->parse('posting_buttons.html:sended_count_text','_send_button');
		}
		parent::handle();
	}

	public function update()
	{
		$parentRes = parent::update();
		$this->loaded = false;
		$this->load();

		if ($parentRes && $_POST["delete_post"]) {
        	Finder::useClass('EmailQueue');
            $queue = new EmailQueue();
            $queue->deleteMessage($this->item['fk_table'],$this->item['fk_id']);
        }

		return $parentRes;
	}

}

?>