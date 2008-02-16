<?php
//debug
if ($this->enable_debug) 
{
	$this->UseClass("Debug"); 
	Debug::init();
} 
else 
{
	$this->UseClass("DebugDummy");
}

//state set
$this->UseClass("StateSet");
$this->state = & new StateSet($this);

//classes
$this->UseClass("Module");

//libs
Debug::trace("DBAL: before");

$this->UseClass("DBAL");
//$this->db = & new DBAL($this, true);
$this->db =& DBAL::getInstance( $this );
if ($this->db_set_encoding) 
{
	$this->db->query("SET CHARACTER SET " . $this->db_set_encoding);
}


//template engine
$this->UseClass("TemplateEngine");
$this->tpl = & new TemplateEngine($this);


//кэш объектов
$this->UseClass('ObjectCache');
$this->cache = & new ObjectCache($this);

//principal
if (!$this->pincipal_class)
{
	$this->pincipal_class = 'PrincipalHash';
}
$this->UseClass($this->pincipal_class);
eval ('$this->prp =& new ' . $this->pincipal_class . '($this);');

//predefined template variables
$this->tpl->set('/', $this->path_rel);
$this->tpl->set('images', $this->path_rel . 'images/');
$this->tpl->set('css', $this->path_rel . 'css/');
$this->tpl->set('js', $this->path_rel . 'js/');

$this->tpl->set('project_title', $this->project_title);


//корзина
$this->UseClass('Trash');
$this->trash = & new Trash($this);

//load settings from config table
if ($result = $this->db->execute('SELECT name, value FROM ' . $this->project_name . '_config')) 
{
	while ($r = $this->db->getRow($result)) 
	{
		config_replace($this, $r['name'], $r['value']);
	}
	
	$this->front_end->project_title = $this->project_title;
	$this->project_title = $this->project_title . ': CMS';
}

//шаблонные переменные для редактора
$this->tpl->set('fe_/', $this->front_end->path_rel);
$this->tpl->set('fe_images', $this->front_end->path_rel . $this->project_name . '/' . $this->front_end->skin . '/images/');
$this->tpl->set('fe_css', $this->front_end->path_rel . $this->project_name . '/css/');
$this->tpl->set('fe_js', $this->front_end->path_rel . $this->project_name . '/js/');

//логирование
if ($this->trace_logs) 
{
	$this->UseClass('Logs');
	$this->logs = & new Logs($this);
} 
else 
{
	$this->UseClass('LogsDummy');
	$this->logs = & new LogsDummy($this);
}
?>