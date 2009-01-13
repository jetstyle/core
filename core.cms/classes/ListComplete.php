<?php
Finder::useClass('ListSimple');

class ListComplete extends ListSimple
{
	//������ ������ ��������
	protected $template_form = "list_complete.html:Form";
	protected $template_form_delete = "list_complete.html:delete";

	public function handle()
	{
		$tpl = &$this->tpl;

		//������ ������
		$this->load();

		//��������, �������� � ������
		if ($this->updateForm())
		{
			Controller::redirect(RequestInfo::hrefChange('', array('rnd' => mt_rand(1, 255))));
		}

		//�������� �����
		//����� ������ �� ��, ��� �������� � FormSimple::Handle()
		$tpl->set('_title', $this->item[$this->config->SELECT_FIELDS[1]]);
		$tpl->set('_save_string', $this->item ? '���������' : '��������');
		$tpl->set('prefix', $this->prefix);

		$this->renderDeleteButton();

		$tpl->set('__form_name', $this->prefix . '_list_form');
		$tpl->parse($this->template_form, '__form');

		parent :: handle();
	}

	public function load()
	{
		if (!$this->loaded)
		{
			parent::load();
			if (!empty($this->items))
			{
				foreach ($this->items AS &$item)
				{
					if ($item[$this->idField] == $this->id)
					{
						$this->item = &$item;
						break;
					}
				}
			}
		}
	}
	
	protected function renderDeleteButton()
	{
		if ($this->id)
		{
			$this->tpl->parse($this->template_form_delete, '__delete');
		}
	}
	
	protected function _delete()
	{
		$model = &$this->getModel();
		$model->delete($model->quoteFieldShort($this->idField).'='.DBModel::quote($this->id));
	}

	protected function updateForm() 
	{
		//delete
		if ($_POST[$this->prefix . 'delete'])
		{
			$this->_delete();
			RequestInfo::free($this->idGetVar);
			return true;
		}
		//update
		elseif ($_POST[$this->prefix . 'update'])
		{
			if ($this->id)
			{
				$data = array($this->config->SELECT_FIELDS[1] => $_POST[$this->prefix . $this->config->SELECT_FIELDS[1]]);
				$model = &$this->getModel();
				$model->update($data, $model->quoteFieldShort($this->idField).'='.DBModel::quote($this->id));
			}
			else
			{
				$this->insert();
				RequestInfo::set($this->idGetVar, $this->id);
			}
			return true;
		}
		else
		{
			return false;
		}
	}

	protected function insert()
	{
		$model = &$this->getModel();

		$data = array('title' => $_POST[$this->prefix.'title'], '_created' => date('Y-m-d H:i:s'));
		
		if (is_array($this->config->INSERT_FIELDS) && !empty($this->config->INSERT_FIELDS))
		{
			foreach ($this->config->INSERT_FIELDS AS $fieldName => $value)
			{
				$data[$fieldName] = $value;
			}
		}
		
		$this->id = $model->insert($data);

		// update order
		$data = array('_order' => $this->id);
		$model->update($data, $model->quoteFieldShort($this->idField).'='.DBModel::quote($this->id));
	}
}
?>