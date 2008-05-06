<?php
$this->UseClass('ListAdvanced');

class ListComplete extends ListAdvanced {

	//шаблон мелкой формочки
	var $template_form = "list_advanced.html:Form";
	var $template_form_delete = "list_advanced.html:delete";

	function Handle() {
		$tpl = & $this->rh->tpl;

		//запоминаем ID "текущей" записи
		$this->id = $this->state->Keep($this->id_get_var, 'integer');

		//куда делать редирект?
		$this->_redirect = $this->url . '?' . $this->state->State(0, array (), true);

		//грузим данные
		$this->Load();

		//возможно, операции с формой
		if ($this->UpdateForm())
			$this->rh->Redirect($this->_redirect);

		//рендерим форму
		//очень похоже на то, что делается в FormSimple::Handle()
		$tpl->set('_title', $this->item[$this->SELECT_FIELDS[1]]);
		$tpl->set('_save_string', $this->item ? 'сохранить' : 'добавить');
		$tpl->set('prefix', $this->prefix);
		$tpl->set('POST_STATE', $this->state->State(1));
		if ($this->id) 
		{
			$tpl->Parse($this->template_form_delete, '__delete');
		}
		$tpl->set('__form_name', $this->prefix . '_list_form');
		$tpl->Parse($this->template_form, '__form');

		//ссылка на просмотр логов
		if ($this->id)
			$this->rh->logs->ParseLink($this->config->module_name, $this->id, '__logs');

		//по эатпу
		ListAdvanced :: Handle();
	}

	function Load() {
		if (!$this->loaded) {
			ListAdvanced :: Load();
			//найдём одну для редактирования
			$this->item = $this->FindById($this->id);
		}
	}

	function _Delete() {
		DBDataEdit :: Delete($this->id);
		$this->rh->logs->Put("Список: удаление", $this->id, $this->config->module_title, $this->item[$this->SELECT_FIELDS[1]], $this->_redirect);
	}

	function UpdateForm() {
		//delete
		if ($this->rh->GetVar($this->prefix . 'delete')) {
			$this->_Delete();
			return true;
		}
		//update
		if ($this->rh->GetVar($this->prefix . 'update')) {
			$this->UPDATE_FIELDS = array (
				$this->SELECT_FIELDS[1]
			);
			if ($this->id) {
				DBDataEdit :: Update($this->id);
				$this->rh->logs->Put('Список: модификация', $this->id, $this->config->module_title, $this->item[$this->SELECT_FIELDS[1]], $this->_redirect);
			} else
				$this->AddNew();
			return true;
		} else
			return false;
	}

	function AddNew() {
		//add new
		$this->new_suffix = '';
		$id = DBDataEdit :: AddNew($this->config->INSERT_FIELDS);
		//set _created,_order
		$this->rh->db->Execute("UPDATE " . $this->table_name . " SET _created=NULL,_order=id WHERE id='$id'");
		//пишем в логи
		$this->rh->logs->Put('Список: добавление', $id, $this->config->module_title, $this->rh->GetVar($this->prefix . $this->SELECT_FIELDS[1] . $this->suffix . $this->new_suffix), $this->_redirect . '&' . $this->id_get_var . '=' . $id);
		//return $id
		return $id;
	}

}
?>