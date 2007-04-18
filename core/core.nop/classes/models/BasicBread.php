<?
$this->UseClass("models/Model");

class BasicBread extends Model
{
	var $fields = array('id', 'title_pre', '_left', '_right', '_level', '_path', '_parent');

    function load()
    {
		$this->rh->useClass('models/Content');
		$m =& new Content();
		if (isset($this->fields)) $m->fields = $this->fields;
		if (isset($this->fields_info)) $m->fields_info = $this->fields_info;
		$m->order = '_left ASC';
		$m->initialize($this->rh);

		// FIXME: lucky: как еще можно узнать реальный путь до страницы?
		$where = 
			' AND _left <= '.$m->quote($this->rh->data['_left'])
			.' AND _right >= '.$m->quote($this->rh->data['_right']);
		$m->load($where);

		$this->data = $m->data;
		return;
    }
    
    function addItem($item)
    {
        $this->data[] = $item;
    }
}

?>
