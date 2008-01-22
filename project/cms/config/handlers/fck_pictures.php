<?
/* @author: nop
 * @date: 18:34 25.05.2006
 */

    //$rh->DIRS["templates"][2] = $rh->path_full.'FCKeditor/editor/';
    $tpl->CFG['skip_cache'] =1;
    if ($rh->path->path_trail != "")
    {
        
        //echo ($rh->FindScript('templates', 'for_js/pict.html'));
        //echo $tpl->action('cutoff_html',$tpl->parse('for_js/pict.html'));
    	$tpl->assign ('tpl_pict', str_replace('"', "'", $tpl->action('cutoff_html',$tpl->parse('for_js/pict.html'))));
        $tpl->assign ('tpl_preview', str_replace('"', "'", $tpl->action('cutoff_html',$tpl->parse('for_js/pict_preview.html'))));

        $rh->DIRS["templates"][2]    = $rh->path_full.'FCKeditor/editor/';
      	$rh->HeadersNoCache();
    	
    	$rh->UseClass('Upload');
    	$upload =& new Upload($rh, $rh->front_end->file_dir);
    	
    	$table_topics = $rh->project_name.'_pictures_topics';
    	$table_picts = $rh->project_name.'_pictures';
    	
    	$rh->UseClass('DBDataView');
    	
    	//load topics, render topics select
    	$topics = new DBDataView( $rh, $table_topics, array('id','title'), '_state=0', '_order ASC');
    	$topics->Load();
    	$nt = count($topics->ITEMS);
    	for($i=0;$i<$nt;$i++)
    		$topics_options .= "<option value='".$topics->ITEMS[$i]['id']."'>".iconv('windows-1251', 'utf-8',$topics->ITEMS[$i]['title'])."\n";

    	
    	//грузим картинки, раскладываем по ID и topic_id
    	$picts = new DBDataView( $rh, $table_picts, array('id','title','topic_id'), 'topic_id>0 AND _state=0', 'topic_id ASC, _order ASC');
    	$picts->Load();
    	$n = count($picts->ITEMS);
    	for($i=0;$i<$n;$i++){
    		$r = (object)$picts->ITEMS[$i];
    		if( 
          ($file_small = $upload->GetFile("pictures/picture_small_".$r->id)) ||  
          ($file_small = $upload->GetFile("pictures/picture_".$r->id))
          ){
                $r->title = iconv('windows-1251', 'utf-8',  $r->title);
    			//заполняем списки для разделов
    			//$src_small = $rh->path_rel.'pict.php?img='.$file_small->link;
    			
    			$src_small = $rh->front_end->path_rel.'files/'.$file_small->name_short;
    			
    			$BY_TOPICS[$r->topic_id][] = array( $r->id, htmlspecialchars($r->title) );
    			//рендерим общий массив
      		$arr = getimagesize($file_small->name_full);
    			$_str = ",".$arr[0].",".$arr[1];
    			if( ($file_big = $upload->GetFile("pictures/picture_".$r->id)) && $file_big->name_short!=$file_small->name_short )
                {
    				//$src_big = $rh->path_rel.'pict.php?img='.$file_big->link.'&title='.urlencode($r->title);
    				$src_big = $rh->front_end->path_rel.'files/'.$file_big->name_short.'?title='.urlencode($r->title).'&popup=1';
    				$arr = getimagesize($file_big->name_full);
    				$_str .= ",".$arr[0].",".$arr[1];
    			}else
    				$src_big = "";
    //			$rv_str .= "arrrv[".$r->id."] = ['".$src_small."','".$src_big."','".str_replace(' ','_',str_replace("'","\'",$r->title))."',".$r->id.$_str."];\n";
    			$rv_str .= "arrrv[".$r->id."] = ['".$src_small."','".$src_big."','".str_replace("'","\'",$r->title)."',".$r->id.$_str."];\n";
    		}
    	}
    	
    	//рендерим js-масивы по разделам
    	for($i=0;$i<$nt;$i++){
    		$r = (object)$topics->ITEMS[$i];
    		$arrv = $arrt = "";
    		$A =& $BY_TOPICS[ $r->id ];
    		$n = count($A);
    		for($j=0;$j<$n;$j++){
    			$arrt .= ( $j ? ', ' : '' ).'"'.$A[$j][1].'"';
    			$arrv .= ( $j ? ', ' : '' ).'"'.$A[$j][0].'"';
    		}
    		$titles_str .= "arrt[".$r->id."] = new Array(".$arrt.");\n";
    		$values_str .= "arrv[".$r->id."] = new Array(".$arrv.");\n";
    	}

    	//вставляем в шаблон
    	$tpl->Assign( 'TOPICS', $topics_options );
    	$tpl->Assign( 'TITLES', $titles_str );
    	$tpl->Assign( 'VALUES', $values_str );
    	$tpl->Assign( 'RETURN_VALUES', $rv_str );

    	//возврашаем шаблон
    	$tpl->parse('nop_insert_image.html', 'foo');
        
        echo $tpl->parse($rh->path->path_trail);
    }
    else
    {
        $fck_templates = str_replace("//", "/", $rh->path_full.'FCKeditor/editor/');
        $rh->DIRS["templates"][2] = $fck_templates;
        //var_dump($rh->DIRS["templates"]);
        //die();
        echo $tpl->parse('nop_fckdialog.html');
    }
    die();  
?>