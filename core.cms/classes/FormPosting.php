<?php
/**
 * Posting form
 *
 * Form for modules, from wich we have necessity to send emails to subscribers.
 * Config params:
 *     $this->post_from - "from" field of the sended emails
 *
 * @author: bjornd@jetstyle.ru
 */

Finder::useClass('FormCalendar');

class FormPosting extends FormCalendar
{

	public function handle()
	{
		$this->load();
		Finder::useClass('EmailQueue');
		$queue = new EmailQueue();
		$postInfo = $queue->getMessageStatus($this->config['table'],$this->item['id']);
		if ($postInfo['status'] == 'notsended') {
			$this->tpl->parse('posting_buttons.html:send_button','_send_button');
		} elseif ($postInfo['sended_count'] == 0) {
            $this->tpl->parse('posting_buttons.html:delete_post_button','_send_button');
		} else {
			$this->tpl->set('sended_count',$postInfo['sended_count']);
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

		if ($parentRes && $_POST["send"]) {
            $id = $this->item['id'];
            Finder::useClass('EmailQueue');
            $queue = new EmailQueue();
            $queue->addMessage(
            	$this->config['table'],
            	$this->item['id'],
            	$this->getPostFrom(),
            	$this->getPostSubject(),
            	$this->getPostText(),
            	$this->loadSubscribers()
            );
        } else if ($parentRes && $_POST["delete_post"]) {
        	Finder::useClass('EmailQueue');
            $queue = new EmailQueue();
            $queue->deleteMessage($this->config['table'],$this->item['id']);
        }

		return $parentRes;
	}

	protected function loadSubscribers() {
		$sql = "SELECT id,email FROM ??subscribers WHERE _state=0 AND active = 1 ";
		return $this->db->query($sql);
	}

	//Return text of email message
	protected function getPostText() {
    	return $this->item['text'];
	}

	//Return subject of email message
	protected function getPostSubject() {
    	return $this->item['title'];
	}

	//Return "from" field of email message
	protected function getPostFrom() {
    	return $this->config['post_from'];
	}

}

?>
