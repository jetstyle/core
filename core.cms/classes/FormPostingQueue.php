<?php
/**
 * Form of the Posting module
 *
 * @author bjornd@jetstyle.ru
 */

Finder::useClass('FormSimple');

class FormPostingQueue extends FormSimple
{
	protected function renderButtons()
	{
		parent::renderButtons();

		$item = &$this->getItem();

		Finder::useClass('EmailQueue');
		$queue = new EmailQueue();
		$postInfo = $queue->getMessageStatus($item['fk_table'],$item['fk_id']);
		if ($item['id'] && $postInfo['sended_count'] == 0) {
            $this->tpl->parse('posting_buttons.html:delete_post_button','_send_button');
		} else if($item['id']) {
			$this->tpl->set('sended_count',$postInfo['sended_count']);
			$this->tpl->set('all_count',$postInfo['all_count']);
            $this->tpl->parse('posting_buttons.html:sended_count_text','_send_button');
		}
		parent::handle();
	}

	public function update()
	{
		$parentRes = parent::update();

		if ($parentRes && $_POST["delete_post"]) {
			$this->cleanUp();
			$item = &$this->getItem();

        	Finder::useClass('EmailQueue');
            $queue = new EmailQueue();
            $queue->deleteMessage($item['fk_table'],$item['fk_id']);
        }

		return $parentRes;
	}

}

?>