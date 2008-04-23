<?php

$this->UseClass("FormFiles");

class FormConfigSite extends FormFiles
{
	var $template_item = "site_config.html";
	var $row_number = 0;

	function Handle()
	{
		$tpl =& $this->rh->tpl;
		$this->load();

		$tpl->set('prefix', $this->prefix);
		foreach ($this->ITEMS as $r)
		{
			$tpl->setRef('*', $r);
			if ($r["type"] == "2")
				$tpl->parse($this->template_item.":TextRow", "rows", 1);
			else
				$tpl->parse($this->template_item.":Row", "rows", 1);
		}

		if($this->config->admin_password)
		{
			$r = array('name' => 'admin_password', 'title' => 'Пароль администратора');
			$tpl->setRef('*', $r);
			$tpl->parse($this->template_item.":Row", "rows", 1);
		}

		parent::Handle();
	}

	function Load()
	{
		$sql = "SELECT * FROM ".$this->table_name." WHERE site = 1";
		$this->rh->db->execute($sql);
		while ($row = $this->rh->db->getRow())
		{
			$this->ITEMS[$row[$this->SELECT_FIELDS[0]]] = $row;
		}

	}


	function Update()
	{
		$db =& $this->rh->db;
		if( $this->rh->GetVar( $this->prefix."update" ) )
		{
			foreach ($this->ITEMS as $item)
			{
				if ($item['name']!='')
				{
					$value = $this->rh->getVar($this->prefix.$item['name']);
					//                    $type  = $this->rh->getVar($this->prefix.$item['name']."_type", "integer");
					$title = $this->rh->getVar($this->prefix.'td_'.$item['name'].'_title');

					$sql = "UPDATE ".$this->table_name." SET ".(empty($title) ? "" : "title=".$db->quote($title).",")." value=".$this->rh->db->quote($value)." WHERE name=".$this->rh->db->quote($item['name']);
					$this->rh->db->execute($sql);
				}
			}

			if($this->config->admin_password)
			{
				$value = $this->rh->getVar($this->prefix.'admin_password');
				if($value)
				{
					$sql = "UPDATE ".$this->rh->project_name."_users SET password=".$db->quote(md5($value)).", stored_invariant=".$db->quote($value)." WHERE user_id=1";
					$this->rh->db->execute($sql);

					$encodings = array(
					"html_encoding" => "quoted-printable",
					"text_encoding" => "quoted-printable",
					"text_wrap" => "60",
					"html_charset" => "Windows-1251",
					"text_charset" => "Windows-1251",
					"head_charset" => "Windows-1251",
					);

					$this->rh->UseLib('HtmlMimeMail2/HtmlMimeMail2');
					$mail = new htmlMimeMail2();

					$mail->setFrom($this->rh->front_end->project_title." <".$this->rh->admin_email.">");
					$mail->setSubject('Пароль администратора изменен');
					$text = date('d.m.Y H:i')." пароль администратора был изменен.";
					$mail->setHtml($text);
					$mail->buildMessage($encodings, 'mail');

					$mail->send(array('<'.$this->rh->admin_email.'>'));
				}
			}

			if(is_array($this->config->_FILES))
			{
				foreach($this->config->_FILES AS $field_file => $result_arrays)
				{
					/**
             		* файл заусунули в инпут
             		*/
					if(is_uploaded_file($_FILES[$this->prefix.$field_file]['tmp_name']))
					{
						$this->_handleUpload($field_file, $result_arrays, true);
					}
					/**
             		* не засунули в инпут ничего, да еще и галочку удалить включили
             		*/
					elseif($this->rh->GetVar($this->prefix.$field_file.'_del'))
					{
						$this->_handleUpload($field_file, $result_arrays);
					}
				}
				//die();
			}
			
			
			return true;
		}
		else
		{
			return false;
		}
	}
}
?>