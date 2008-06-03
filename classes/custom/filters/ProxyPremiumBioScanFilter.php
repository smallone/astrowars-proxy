<?php
class ProxyPremiumBioScanFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table class="content" cellspacing="0" cellpadding="0">([^<]*?<tr align=center>)#','<table class="inner_sub_content" cellspacing="1" cellpadding="2">\\1',$response_body);
		$response_body = str_replace('<td><br>','<td>',$response_body);
		$response_body = str_replace('</table> <br>','</table>',$response_body);

		//workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Premium/','?cmd=Premium',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);
		
		//function
		$matches = array();
		preg_match_all('#<td><a href="[^<]*?&id=(\d+)">([^<]*?)</a></td><td>(\d+)</td>#',$response_body,$matches,PREG_SET_ORDER);
		
		$response_body = preg_replace('#<table class="single".*?</table>#si','',$response_body);
		
		$left = null;
		$right = null;
		$users = null;
		
		foreach ($matches as $key => $user)
		{
			$player = new Player();
			$player -> id = $user[1];
			
			ObjectHandler::load($player);
			
			$class = null;
				
			if ($player -> alliance > 0)
			{
				$status = new Status();
				$status -> id = $player -> alliance;
				$status -> mode = Status::ALLY;
	
				$query = new Query();
				$query -> addObject($status,$registry -> tiny_criteria);
				
				foreach(ObjectHandler::collect($query) as $status) { continue; }
							
				if (is_null($status -> state))
				{
					$status = new Status();
					$status -> id = $player -> id;
					$status -> mode = Status::SINGLE;
	
					$query = new Query();
					$query -> addObject($status,$registry -> tiny_criteria);
					
					foreach(ObjectHandler::collect($query) as $status) { continue; }
				}
								
				$class = (!is_null($status -> state)) ? ' class="s'.$status -> state.'"': '';
				
			}
			else if ($player -> id != 2)
			{
				$status = new Status();
				$status -> id = $player -> id;
				$status -> mode = Status::SINGLE;

				$query = new Query();
				$query -> addObject($status,$registry -> tiny_criteria);

				foreach(ObjectHandler::collect($query) as $status) { continue; }
					
				$class = (!is_null($status -> state)) ? ' class="s'.$status -> state.'"': '';
			}
			
			$tmp = '<li class="scan">lvl '.$user[3].' - <a href="index.php?cmd=PlayerProfile&id='.$player -> id.'" '.$class.'>'.$player -> name.'</a></li>';

			if ($key < ceil(count($matches)/2)) $left .= $tmp;
			else $right .= $tmp;

			$users .= ($key == 0) ? $player -> id : ','.$player -> id;
			
		}
		
		$response_body = str_replace('<tr align=center valign=top><td>','<tr align=center valign=top class="row5"><td align="left"><ul style="list-style: square">'.$left.'</ul></td><td align="left"><ul style="list-style: square">'.$right.'</ul></td></tr><tr class="row5"><td colspan="2" align="center"><br /><div id="scan"><input type="button" class="smbutton" value="scan all '.count($matches).' players" onclick="initPlayerScan(\'scan\',new Array('.$users.'))"><br /><br /><small>needs '.(count($matches) * 2).' + x seconds</small></div><br />',$response_body);
	}
}
?>