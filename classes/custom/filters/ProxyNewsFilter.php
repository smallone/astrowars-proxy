<?php
class ProxyNewsFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<td\s*width="135"\s*class="(row3|row12)"[^>]*?>#','<td class="first \\1">',$response_body);
		$response_body = preg_replace('#<td\s*width="138"\s*class="row3"[^>]*?>#','<td class="first">',$response_body);
		$response_body = preg_replace('#<table class="(inner_content|inner_sub_menu)" cellspacing="1" cellpadding="0">#','<table class="\\1" cellspacing="1" cellpadding="3">',$response_body);
		$response_body = preg_replace('#<td>(<a[^>]*>Latest</a>)#','<td class="first">\\1',$response_body);
		
		$response_body = str_replace('<tr align="center"><td colspan="2" class="row4" >News</td></tr>','',$response_body);
		$response_body = str_replace('<td><br>','<td>',$response_body);
		$response_body = str_replace('</table>'."\n".'<br>','</table>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=AllianceInfo.php/?','?cmd=AlliancesInfo&',$response_body);
		$response_body = str_replace('?cmd=TradeAccept.php?','?cmd=TradeAccept&',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Donation/','?cmd=Donation',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Premium/','?cmd=Premium',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Settings.php','?cmd=Settings',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Logout.php','?cmd=Logout',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Help.php','?cmd=Help',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News//?','?cmd=News&',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);

		//features
		$matches = array();
		preg_match_all('#<tr[^>]*?><td[^>]*?class="first row12"[^>]*?>(\d+):(\d+):(\d+) - (\w+) (\d+)</td><td[^>]*?><b>Attention !!!</b>[^<]*<br>([^\[]*?)<br> going [^\[]*?\[(\d+)\] (\d+)!<br>[^<]*?<a href="[^"]*?id=(\d+)">[^<]*?</a>\.</td></tr>#',$response_body,$matches,PREG_SET_ORDER);
		
		$months = array('Jan'=> 1,'Feb'=> 2,'Mar'=>3,'Apr'=>4,'May'=>5,'Jun'=>6,'Jul'=>7,'Aug'=>8,'Sep'=>9,'Oct'=>10,'Nov'=>11,'Dec'=>12);
		$year = gmdate('Y',time());
		$month = gmdate('m',time());
		
		foreach ($matches as $value)
		{
			$response_body = preg_replace('#(going[^<]*?)(<b>[^<]*?</b>[^\[]*?\['.$value[7].'\] '.$value[8].')!#','\\1<a href="index.php?cmd=MapDetail&nr='.$value[7].'">\\2</a>!',$response_body);
			
			$fleet = new Fleet();
			$fleet -> arrive = strtotime($value[5].' '.$value[4].' '.(($months[$value[4]] < $month) ? $year + 1 : $year).' '.$value[1].':'.$value[2].':'.$value[3]);
			
			$fleet -> state = Fleet::MOVING;
			$fleet -> owner = $value[9];
			$fleet -> sid = $value[7];
			$fleet -> pid = $value[8];
			
			$query = new Query();
			$query -> addObject($fleet,$registry -> criteria);
		
			ObjectHandler::delete($query);	
						
			$ships = array();			
			preg_match_all('#(\d+) (Transport|Destroyer|Cruiser|Battleship)#',$value[6],$ships,PREG_SET_ORDER);			
			
			foreach($ships as $type)
			{
				$name = strtolower($type[2]);
				$fleet -> $name = $type[1];
			}
			
			$fleet -> player = $registry -> user_id;
			$fleet -> alliance = $registry -> alliance_id;
			$fleet -> time = time();
			
			ObjectHandler::save($fleet);
		}
		
		if ($registry -> settings instanceof Settings  && $registry -> settings -> inner_news_fleet)
		{
			$player_fleet = new Fleet();
			$player_fleet -> owner =  $registry -> user_id;
			$player_fleet -> state = Fleet::MOVING;
			
			$query = new Query();
			$query -> addObject($player_fleet);

			$count = ObjectHandler::count($query);
			
			foreach ($matches as $value)
			{
				$here_add = null;
				
				# Flotten
				$fleet = new Fleet();
				$fleet -> sid = $value[7];
				$fleet -> pid = $value[8];
						
				$criteria  = clone $registry -> criteria;
				$criteria -> addSort('arrive',Criteria::DESC);
					
				$query = new Query();
				$query -> addObject($fleet,$criteria);
					
				foreach (ObjectHandler::collect($query) as $fleet) 
				{ 
					$player = new Player();
					$player -> id = $fleet -> owner;
						
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
					
					$options = null;
					$img = 'fleet.png';
					
					if ($fleet -> arrive > 0 && $fleet -> arrive > time()) $options = gmdate('Y.m.d H:i:s',$fleet -> arrive + $registry -> gmt * 3600);
					else
					{
						if ($fleet -> owner == 0) $options = '-';
						else if ($fleet -> arrive > $fleet -> time || $fleet -> time + 3600 < time())
						{
							$options = '?';
							$img = 'unknown.png';
						}
						else if ($fleet -> owner == $registry -> user_id && $count < 5) 
						{
							$options = '<a href="index.php?cmd=FleetLaunch&nr='.$fleet -> sid.'&id='.$fleet -> pid.'&inf='.$fleet -> transport.'&col='.$fleet -> colony_ship.'&des='.$fleet -> destroyer.'&cru='.$fleet -> cruiser.'&bat='.$fleet -> battleship.'" class="launch">launch</a>';
							$img = 'local.png';
						}
						else
						{
							$options = '-';
							$img = 'local.png';
						}
					}

					$here_add .= '<tr><td class="img"><img src="imports/images/'.$img.'"></img></td><td class="user"><a '.$class.' href="index.php?cmd=PlayerProfile&amp;id='.$player -> id.'">'.$player -> name.'</a></td><td class="time">'.$options.'</td><td class="cv">'.($fleet -> destroyer * 3 + $fleet -> cruiser * 24 + $fleet -> battleship * 60).' cv</td><td class="label">tr:</td><td class="value">'.$fleet ->  transport.'</a></td><td class="label">col:</td><td class="value">'.$fleet -> colony_ship.'</td><td class="label">ds:</td><td class="value">'.$fleet -> destroyer.'</td><td class="label">cr:</td><td class="value">'.$fleet -> cruiser.'</td><td class="label">bs:</td><td class="value">'.$fleet -> battleship.'</td></tr>';
				}
	
				if (!is_null($here_add)) $response_body = preg_replace('#(<tr[^>]*?><td[^>]*?>'.$value[1].':'.$value[2].':'.$value[3].' - '.$value[4].' '.$value[5].'</td><td[^>]*?><b>Attention !!!</b>[^<]*<br>'.$value[6].'<br> going [^\[]*?\['.$value[7].'\] '.$value[8].'</a>!<br>[^<]*?<a href="[^"]*?id='.$value[9].'"[^>]*?>[^<]*?</a>\.</td></tr>)#','\\1<tr class="fleet_add"><td colspan="2"><table class="fleet_entry fleet_news">'.$here_add.'</table></td></tr>',$response_body);
			}
		}
	}
}
?>