<?php
class ProxyFleetFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#(<table class="content".*?You have no Fleet\.</b></center><br>[^<]*?)<table#si','\\1</td></tr></table><table',$response_body);
		$response_body = preg_replace('#<br>(<table class="inner_sub_content".*?</table>)<br>#si','\\1</td></tr></table>',$response_body);

		//workflow
		$response_body = str_replace('?cmd=Map/?hl=','?cmd=MapDetail&nr=',$response_body);
		$response_body = str_replace('?cmd=FleetLaunch.php/?','?cmd=FleetLaunch&',$response_body);
		
		//function
		$fleet = new Fleet();
		$fleet -> owner = $registry -> user_id;

		$query = new Query();
		$query -> addObject($fleet,$registry -> criteria);
		
		ObjectHandler::delete($query);
		
		$matches = array();
		preg_match_all('#<tr[^>]*?class="([^"]*?)"[^>]*?><td>(\s*(\d+):(\d+):(\d+) - (\w+) (\d+)\s*|<a[^>]*?>.*?</a>|.*?pending.*?|\s*?)</td><td>(<a href="[^"]*?MapDetail&nr=(\d+)[^"]*?"[^>]*?><small>[^<]*?(\d+)</small></a>|<small>\((\d+)\) (\d+)</small>|.*?)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td></tr>#si',$response_body,$matches,PREG_SET_ORDER);
		
		$months = array('Jan'=> 1,'Feb'=> 2,'Mar'=>3,'Apr'=>4,'May'=>5,'Jun'=>6,'Jul'=>7,'Aug'=>8,'Sep'=>9,'Oct'=>10,'Nov'=>11,'Dec'=>12);
		$year = gmdate('Y',time());
		$month = gmdate('m',time());
			
		foreach ($matches as $value)
		{
			$fleet = new Fleet();
			$fleet -> owner = $registry -> user_id;
			$fleet -> sid = is_numeric($value[9]) ? $value[9] : $value[11] ;
			$fleet -> pid = is_numeric($value[10]) ? $value[10] : $value[12] ;
			$fleet -> transport = $value[13];
			$fleet -> colony_ship = $value[14];
			$fleet -> destroyer = $value[15];
			$fleet -> cruiser = $value[16];
			$fleet -> battleship = $value[17];
			
			if (is_numeric($value[3]))
			{
				$fleet -> state = Fleet::MOVING;
				$fleet -> arrive = strtotime($value[7].' '.$value[6].' '.(($months[$value[6]] < $month) ? $year + 1 : $year).' '.$value[3].':'.$value[4].':'.$value[5]) - ($registry -> gmt - $registry -> server_gmt) * 3600;
			}
			else $fleet -> state = (str_replace('row','',$value[1]) == Fleet::SIEGING) ?  Fleet::SIEGING : Fleet::LOCAL;
			
			$fleet -> alliance = $registry -> alliance_id;
			$fleet -> player = $registry -> user_id;
			$fleet -> time = time();
			
			ObjectHandler::save($fleet);
		}
		
		$response_body = preg_replace('#<small>\((\d+)\) (\d+)</small>#','<small><a href="?cmd=MapDetail&nr=\\1">(\\1) \\2</a></small>',$response_body);
	}
}
?>