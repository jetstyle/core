<?php
Finder::useClass('blocks/Block');
class GotoMenuBlock extends Block
{	
	protected function constructData()
	{
        $pre_data = array();
        $mod_config = Locator::get('controller')->moduleConstructor->getconfig();
        if ( $mod_config["module_name"] == "Content" ){
            
            $form = Locator::get('controller')->moduleConstructor->getForm();
            if ($form){
                $obj = $form->getObj();
                if ($obj){
                    $item = $obj->getItem();
                    if ($item){
                        $pre_data[] = $item->getArray();
                        $pre_data[] = array("separator"=>true);
                    }
                }
            }
        }

		$data = Locator::get('db')->query("
 			SELECT IF(LENGTH(title_short) > 0, title_short, title_pre) AS title, _path
 			FROM ??content
 			WHERE controller NOT IN ('', 'content', 'link') AND _state = 0
 			ORDER BY _level,_order
 		");
        $data = array_merge($pre_data, $data);

		$this->setData($data);
	}
}
?>
