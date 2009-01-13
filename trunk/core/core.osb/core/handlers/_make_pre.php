<?php

function make_pre($table_name, $fields)
{
  global $rh;
  echo '<h3>'.$table_name.'</h3>';

  $res = $rh->db->execute('SHOW FIELDS FROM `'.$rh->project_name.'_'.$table_name.'`');
  $tmp = $res->getArray();

  $table_fields = array();
  for ($i = 0; $i < count($tmp); $i++)
  {
    $table_fields[$tmp[$i]['Field']] = $tmp[$i];
  }
  foreach ($fields as $field)
  {
    if (isset($table_fields[$field.'_pre']))
    {
      echo 'Drop `'.$field.'_pre`<br />';
      $rh->db->execute('ALTER TABLE `'.$rh->project_name.'_'.$table_name.'` DROP `'.$field.'_pre`');
    }
    echo 'Add `'.$field.'_pre`<br />';
    $field_pre_type = $table_fields[$field]['Type'];
    $field_pre_type = preg_replace('/^varchar\((\d+)\)$/i', 'varchar(255)', $field_pre_type);
    $rh->db->execute('ALTER TABLE `'.$rh->project_name.'_'.$table_name.'` ADD `'.$field.'_pre` '.$field_pre_type.' NOT NULL AFTER `'.$field.'`');
  }
  echo 'Fill pre-dublicates of fields: `'.implode('`, `', $fields).'`<br />';
  $res = $rh->db->execute('SELECT `id`, `'.implode('`, `', $fields).'` FROM `'.$rh->project_name.'_'.$table_name.'`');
  $items = $res->getArray();
  for ($i = 0; $i < count($items); $i++)
  {
    $sql_set = array();
    foreach ($fields as $field)
    {
      $items[$i][$field.'_pre'] = $rh->tpl->action('typografica', $items[$i][$field]);
      $sql_set[] = '`'.$field.'_pre` = \''.mysql_escape_string($items[$i][$field.'_pre']).'\'';
    }
    $rh->db->execute('UPDATE `'.$rh->project_name.'_'.$table_name.'` SET '.implode(', ', $sql_set).' WHERE `id` = '.$items[$i]['id']);
  }
}

?>