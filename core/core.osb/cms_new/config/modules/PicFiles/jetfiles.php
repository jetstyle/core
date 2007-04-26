<?php
$rh =& $this->rh;

$rh->UseClass('Upload');
$upload =& new Upload($rh, $rh->front_end->file_dir);

	$rh->tpl->Assign('/node', $rh->url.'jetfiles');

	$rubric = $rh->getVar('rubric', 'integer');

	$res = $rh->db->query("
			SELECT id, title
			FROM ".$rh->project_name."_picfiles_topics
	");
	
	if(!empty($res))
	{
		$options = '<option value="0">all</option>';
		foreach($res AS $r)
		{
			$options .= '<option value="'.$r['id'].'" '.($r['id'] == $rubric ? 'selected="selected"' : "").' >'.$r['title'].'</option>';
		}
		$rh->tpl->Assign('rubrics', $options);
	}

	$res = $rh->db->query("
			SELECT COUNT(id) AS total
			FROM ".$rh->project_name."_picfiles
			".($rubric ? "WHERE topic_id =".$rh->db->quote($rubric) : "")."
	");

	if($res[0]['total'])
	{
		//постраничный рубрикатор
		$rh->UseClass('Arrows');
		$arrows = new Arrows( $rh );
		$arrows->outpice = 9;
		$arrows->mega_outpice = 5;
		$arrows->SetupSum( $res[0]['total'] );
		$arrows->Set($rh->state);
		//$arrows->href_suffix = $__href_suffix;
		$arrows->Restore();
		if( $arrows->mega_sum > 1 ){
			$arrows->Parse('arrows.html','pages');
		}

		$limit = $arrows->Limit();
		
		$res = $rh->db->query("
			SELECT id, title
			FROM ".$rh->project_name."_picfiles
			".($rubric ? "WHERE topic_id =".$rh->db->quote($rubric) : "")."
			LIMIT ".$limit[0].",".$limit[1]."			
		");
	
		if(is_array($res) && !empty($res))
		{
			foreach($res AS $i => $r)
			{
				if ($file = $upload->getFile('picfile_'.$r['id']))
				{
					$data = array(
						'id' => $r['id'],
						'title' => str_replace("'", "\'", $r['title']),
						'i' => $i,
						'src' => $rh->front_end->path_rel.'files/'.$file->name_short,
						'size' => $file->size,
						'ext' => $file->ext,
					);
					$rh->tpl->AssignRef('*', $data);
					$rh->tpl->parse('jetfiles.html:item', '_data', 1);
				}
			}
		}
		$rh->tpl->parse('jetfiles.html:main', '_files');
	}
	echo $rh->tpl->parse('jetfiles.html:html');
    die();
?>