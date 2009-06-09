<?php
Finder::useClass("controllers/Controller");
class PageTrackerController extends Controller
{
	//block views
	const BANNERS = 'banners';
	const ANNOUNCES = 'announces';
	
	protected $viewsByDay = array();
	
	
	protected $params_map = array(
		array('url', array('url'=>'url')),
		array('view', array('view'=>'view')),
		array('aggregate', array('aggregate' => 'aggregate')),
		array('default', array(NULL)),
	);
	
	public function handle()
	{
		@set_time_limit(0);
		ignore_user_abort(true);
        parent::handle();
	}

	public function handle_default(){}
	
	/**
	 * @param
	 *
	 */
	public function handle_view($config)
	{
		$db = Locator::get('db');
	
		$content_id = $config['nid'] ? $config['nid'] : $_GET['nid'];
		$obj_id = $config['oid'] ? $config['oid'] : $_GET['oid'];
		$url  = $config['url'] ? $config['url'] : $_GET['url'];
		$type = $config['type'] ? $config['type'] : $_GET['type'];
	
		Finder::useClass('pageTracker/PageTrackerSession');
		$sess = new PageTrackerSession();
		$sess->initSession();
				
		if (is_array($obj_id))
		{
		    foreach ($obj_id as $oid)
			$vals[] =	"(NOW(), 
				".$db->quote($sess['hash']).",
				".$db->quote($type).", 
				".$db->quote($url).", 
				".intval($content_id).", 
				".intval($oid).")";
		    $values = implode(",", $vals);
		}
		else
		    $values =	"(NOW(), 
				".$db->quote($sess['hash']).",
				".$db->quote($type).", 
				".$db->quote($url).", 
				".intval($content_id).", 
				".intval($obj_id).")";
				
				
		
		$db->execute("
			INSERT INTO ??stat_dirty_views
			(`datetime`, `session_hash`, `type`, `url`, `content_id`, `obj_id`)
			VALUES	$values			
		");
	}
	
	public function handle_url()
	{
		Finder::useClass('pageTracker/PageTrackerSession');
		$sess = new PageTrackerSession();
		$sess->initSession();
		
		$db = Locator::get('db');
		$db->execute("
			INSERT INTO ??stat_dirty_urls
			(`datetime`, `session_hash`, `type`, `destination`, `source`, `source_content_id`, `source_obj_id`, `time_on_page`)
			VALUES
			(
				NOW(), 
				".$db->quote($sess['hash']).",
				".$db->quote($_GET['type']).", 
				".$db->quote($_GET['dest']).", 
				".$db->quote($_GET['src']).", 
				".intval($_GET['nid']).", 
				".intval($_GET['oid']).",
				".intval($_GET['time'])."
			)
		");
	}
	
	public function handle_aggregate()
	{
		$db = &Locator::get('db');
		$time = time() - $sess->getExpireTime();
				
		$sql = "
			SELECT hash
			FROM ??stat_dirty_sess AS sess
			WHERE sess.last_activity < ".$time."
		";
		$viewsByDay = array();
		
		$result = $db->execute($sql);
		
		while ($r = $db->getRow($result))
		{
			$sqlViews = "
				SELECT DATE_FORMAT(`datetime`, 'Y-m-d') AS `date`, type, url, content_id, obj_id
				FROM ??stat_dirty_views
				WHERE session_hash = ".$db->quote($r['hash'])."
				ORDER BY `datetime` ASC
			";
			
			$viewsResult = $db->execute($sqlViews);
			
			while ($v = $db->getRow($viewsResult))
			{
				$viewsByDay[$v['date']][$v['type']][$v['obj_id']][$v['content_id']]['clicks']++;
			}
		}
		
		$insert = array();
		foreach ($viewsByDay AS $day => $r)
		{
			foreach ($r AS $type => $rr)
			{
				foreach ($rr AS $objId => $rrr)
				{
					foreach ($rrr AS $contentId => $data)
					{
						$insert[] = "(".$db->quote($day).", ".$db->quote($type).", ".$db->quote($objId).", ".$db->quote($contentId).", ".$db->quote($data['clicks']).")";
					}
				}
			}
		}
		
		if (count($insert) > 0)
		{
			$sql = "
				INSERT INTO ??stat_views
				(`day`, `type`, `obj_id`, `content_id`, `clicks`)
				VALUES
				".implode(",", $insert)."
				ON DUPLICATE KEY UPDATE clicks = clicks + VALUES(clicks)
			";
			$db->execute($sql);
		}
	}
	
	protected function postHandle()
	{
		die();
	}
}
?>