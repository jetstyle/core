<?php
    $rh =& $this->rh;

    $rh->UseClass('Upload');
    $upload =& new Upload($rh, $rh->front_end->file_dir."pictures/");


	$rh->tpl->set('/node', $rh->url.'jetimages');

	$rubric = $rh->getVar('rubric', 'integer');
	$rh->state->keep('rubric');
	
	$res = $rh->db->query("
			SELECT id, title
			FROM ".$rh->project_name."_pictures_topics
	");

	if(is_array($res) && !empty($res))
	{
		$options = '<option value="0">all</option>';
		foreach($res AS $r)
		{
			$options .= '<option value="'.$r['id'].'" '.($r['id'] == $rubric ? 'selected="selected"' : "").' >'.$r['title'].'</option>';
		}
		$rh->tpl->set('rubrics', $options);
	}

	$res = $rh->db->query("
			SELECT COUNT(id) AS total
			FROM ".$rh->project_name."_pictures
			WHERE _state = 0 ".($rubric ? "AND topic_id =".$rh->db->quote($rubric) : ""));
	//$res = $res->getArray();

	if($res[0]['total'])
	{

		//постраничный рубрикатор
		$rh->UseClass('Arrows');
		$arrows = new Arrows( $rh );
		$arrows->outpice = 15;
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
			SELECT id,title
			FROM ".$rh->project_name."_pictures
            WHERE _state = 0 ".($rubric ? "AND topic_id =".$rh->db->quote($rubric) : "")."          
			LIMIT ".$limit[0].",".$limit[1]."			
		");
		//$res = $res->getArray();
	    
		if(is_array($res) && !empty($res))
		{
            include($this->rh->findScript('modules', $this->module_name.'/_files'));
            
            $preview_filename = $this->_FILES['file_small'][0]['filename'];
            
			foreach($res AS $i => $r)
			{
                //echo '<hr>'.'picture_'.$r['id'].'_preview';
                
                //есть большая
				if ($file_big = $upload->getFile('picture_'.$r['id']))
				{
					//есть превью
					//if ($file = $upload->getFile('picture_'.$r['id'].'_preview'))
                    if ($file = $upload->getFile(str_replace("*", $r['id'], $preview_filename)))
					{
						//var_dump($rh->front_end->path_rel.'files/'.$this->upload_dir.'/'.$file->name_short);
						$A = @getimagesize($file_big->name_full);
						$A1 = @getimagesize($file->name_full);
						$data = array(
							'id' => $r['id'],
							'i' => $i,
							'title' => str_replace("'", "\'", $r['title']),
							'src_small' => $rh->front_end->path_rel.'files/'.$this->upload_dir.'/'.$file->name_short,
							'src_big' => $rh->front_end->path_rel.'files/'.$this->upload_dir.'/'.$file_big->name_short,
							'width' => $A[0],
							'height' => $A[1],
							'width_small' => $A1[0],
							'height_small' => $A1[1],
						);
						$rh->tpl->setRef('*', $data);
						$rh->tpl->parse('jetimages.html:item', '_data', 1);
					}
                    else
                    {
                        
                    }
				}
			}
		}
		$rh->tpl->parse('jetimages.html:main', '_images');
	}
	echo $rh->tpl->parse('jetimages.html:html');
    die();

?>