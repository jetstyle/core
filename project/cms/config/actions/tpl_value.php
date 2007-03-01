<?
	function action_tpl_value( &$rh, &$PARAMS )
	{
		/*
		if ($PARAMS['level']>1)
		{
			$ret = "{{!tpl_value ".$PARAMS["value"].( $PARAMS['level']-1 > 1 ? " level=".--$PARAMS['level'] : "" )."}}";
		}
		else
		*/
		$ret = $PARAMS["value"]."}}";

		return $ret;
	}
	
?>