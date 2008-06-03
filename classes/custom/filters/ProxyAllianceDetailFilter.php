<?php
class ProxyAllianceDetailFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table border=0 cellpadding=1 cellspacing=1 width=600>#','<table class="alliance_list" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#(<table class="alliance_list".*?</table>)<br>#si','\\1</td></tr></table>',$response_body);
		$response_body = preg_replace('#<tr><td><center>(.*?)<br>(<table class="alliance_list"[^<]*?>)#si','<tr><td>\\2<tr align=center class="row5" ><td colspan="14">\\1</td></tr>',$response_body);
		
		$response_body = str_replace('</td></tr></table></td></tr></table></center>','</td></tr></table></td></tr></table>',$response_body);

		$response_body = preg_replace('#<td><table border=0 cellpadding=1 cellspacing=1 width=100%>#','<td class="info"><table class="info" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#<table class="alliance_list([^<]*?><tr[^<]*?><td>Intelligence)#','<table class="alliance_list info_addon\\1',$response_body);
		$response_body = preg_replace('#(Trade Revenue</td><td>[^<]*?</td></tr><tr class="row5" >)<td colspan=2>#','\\1<td colspan=2 class="agree">',$response_body);
		$response_body = preg_replace('#<td><ul type=square>#','<td class="info"><ul type=square>',$response_body);

		$response_body = preg_replace('#<table[^>]*?>(<tr[^>]*?><td colspan=2>News</td>)#','<table class="alliance_list" cellpadding="3" cellspacing="1" style="margin-top: -1px;">\\1',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=AllianceList.php','?cmd=AllianceList',$response_body);
		$response_body = str_replace('?cmd=AllianceInvite.php','?cmd=AllianceInvite',$response_body);
		$response_body = str_replace('?cmd=AllianceNAP.php','?cmd=AllianceNap',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);
				
		//function
		$months = array('Jan'=> 1,'Feb'=> 2,'Mar'=>3,'Apr'=>4,'May'=>5,'Jun'=>6,'Jul'=>7,'Aug'=>8,'Sep'=>9,'Oct'=>10,'Nov'=>11,'Dec'=>12);
		$year = gmdate('Y',time());
		$month = gmdate('m',time());
		
		$matches = array();
		preg_match('#<td colspan="14">([^<]*?)</td>#',$response_body,$matches);
		
		$player = new Player();
		$player -> name = $matches[1];
		
		ObjectHandler::load($player);
		
		$response_body = str_replace($matches[1],'<a href="?cmd=PlayerProfile&id='.$player -> id.'">'.$matches[1].'</a>',$response_body);
		
		$matches = array();
		preg_match_all('#<tr[^>]*?class="([^"]*?)"[^>]*?><td[^>]*?>(\d+)</td><td>(\d+)</td>(<td>(\d+)</td><td>(\d+|N/A)</td><td>(\d+|N/A)</td><td>(\d+|N/A)</td><td>(\d+|N/A)</td><td>(\d+|N/A)</td>|<td colspan=6>(\d+):(\d+):(\d+) - (\w+) (\d+)</td>)<td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td></tr>#si',$response_body,$matches,PREG_SET_ORDER);

		// Flotten des Spieler löschen
		$fleet = new Fleet();
		$fleet -> owner = $player -> id;
		
		$query = new Query();
		$query -> addObject($fleet);
		
		ObjectHandler::delete($query);
		
		// Flotten bereinigen
		$fleet = new Fleet();
		$fleet -> alliance = $player -> alliance;
		$fleet -> state = Fleet::MOVING;
		
		$criteria = new Criteria();
		$criteria -> addExclude('arrive',time(),Criteria::LOWER_EQUAL);
			
		$query = new Query();
		$query -> addObject($fleet,$criteria);
			
		ObjectHandler::delete($query);
		
		$fleet = new Fleet();
		$fleet -> alliance = $player -> alliance;
		$fleet -> state = Fleet::SIEGING;
		$fleet -> owner = 0;
		
		$criteria = new Criteria();
		$criteria -> addExclude('time',time()-3600,Criteria::LOWER_EQUAL);
			
		$query = new Query();
		$query -> addObject($fleet,$criteria);
			
		ObjectHandler::delete($query);
				
		$deleted = array();
		
		foreach ($matches as $value)
		{
			$response_body = preg_replace('#(<tr[^>]*?class="'.$value[1].'"[^>]*?><td[^>]*?>)'.$value[2].'</td>#','\\1<a href="javascript:getCmd(\'FeatureLaunchSystem&value='.$value[2].'\',\'map\')">'.$value[2].'</a></td>',$response_body);

			if (!isset($deleted[$value[2]][$value[3]]))
			{
				$fleet = new Fleet();
				$fleet -> sid = $value[2];
				$fleet -> pid = $value[3];
				$fleet -> owner = 0;
				
				$query = new Query();
				$query -> addObject($fleet,$criteria);
			
				ObjectHandler::delete($query);
				
				$deleted[$value[2]][$value[3]] = true;
			}
			
			if ($value[16] + $value[17] + $value[18] + $value[19] + $value[20] > 0)
			{
				$fleet = new Fleet();
				
				$fleet -> sid = $value[2];
				$fleet -> pid = $value[3];
				$fleet -> transport = $value[16];
				$fleet -> colony_ship = $value[17];
				$fleet -> destroyer = $value[18];
				$fleet -> cruiser = $value[19];
				$fleet -> battleship = $value[20];
				
				if (is_numeric($value[11]))
				{
					$fleet -> state = Fleet::MOVING;
					$fleet -> arrive = strtotime($value[15].' '.$value[14].' '.(($months[$value[14]] < $month) ? $year + 1 : $year).' '.$value[11].':'.$value[12].':'.$value[13]) - ($registry -> gmt - $registry -> server_gmt) * 3600;
				}
				else $fleet -> state = (str_replace('row','',$value[1]) == Fleet::SIEGING) ?  Fleet::SIEGING : Fleet::LOCAL;
				
				$fleet -> owner = ($fleet -> state == Fleet::SIEGING && is_numeric($value[6])) ? 0 : $player -> id;
				
				$fleet -> alliance = $registry -> alliance_id;
				$fleet -> player = $registry -> user_id;
				$fleet -> time = time();
				
				ObjectHandler::save($fleet);
			}
		}
		
		$matches = array();
		preg_match_all('#<tr[^>]*?><td[^>]*?>(\d+):(\d+):(\d+) - (\w+) (\d+)</td><td[^>]*?>\s*?<b>Attention !!!</b>[^<]*<br>([^\[]*?)<br> going [^\[]*?\[(\d+)\] (\d+)!<br>[^<]*?<a href="[^"]*?id=(\d+)">[^<]*?</a>\.</td></tr>#',$response_body,$matches,PREG_SET_ORDER);
				
		foreach ($matches as $value)
		{
			$response_body = preg_replace('#(going[^<]*?)(<b>[^<]*?</b>[^\[]*?\['.$value[7].'\] '.$value[8].')!#','\\1<a href="index.php?cmd=MapDetail&nr='.$value[7].'">\\2</a>!',$response_body);
			
			$fleet = new Fleet();
			$fleet -> arrive = strtotime($value[5].' '.$value[4].' '.(($months[$value[4]] < $month) ? $year + 1 : $year).' '.$value[1].':'.$value[2].':'.$value[3]) - ($registry -> gmt - $registry -> server_gmt) * 3600;
			
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
		
		// DISPLAY
		
		if ($registry -> settings instanceof Settings  && $registry -> settings -> inner_incoming_fleet)
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
							
				if (!is_null($here_add)) $response_body = preg_replace('#(<tr[^>]*?><td[^>]*?>'.$value[1].':'.$value[2].':'.$value[3].' - '.$value[4].' '.$value[5].'</td><td[^>]*?>\s*?<b>Attention !!!</b>[^<]*<br>'.$value[6].'<br> going [^\[]*?\['.$value[7].'\] '.$value[8].'</a>!<br>[^<]*?<a href="[^"]*?id='.$value[9].'"[^>]*?>[^<]*?</a>\.</td></tr>)#','\\1<tr class="fleet_add"><td colspan="2"><table class="fleet_entry fleet_news">'.$here_add.'</table></td></tr>',$response_body);
			}
		}
		
		# output calculation
		$race = array();
		preg_match_all('#<li>(.\d+)%\s(\w*)\s[^<]*?</li>#',$response_body,$race,PREG_SET_ORDER);
				
		$stats['science'] = 0;
		$stats['culture'] = 0;
		$stats['production'] = 0;

		foreach($race as $stat) $stats[$stat[2]] = $stat[1];

		$arte['BM'][0] = 'culture';
		$arte['AL'][0] = 'science';
		$arte['CP'][0] = 'growth';
		$arte['CD'][0] = 'production';
		$arte['CR'][0] = 'growth';
		$arte['CR'][1] = 'culture';
		$arte['MJ'][0] = 'science';
		$arte['MJ'][1] = 'production';
		$arte['HoR'][0] = 'science';
		$arte['HoR'][1] = 'production';
		$arte['HoR'][2] = 'culture';
		$arte['HoR'][3] = 'growth';

		$trade = array();
		preg_match('#<td class="row5" >Trade Revenue</td><td>(\d+)%</td>#',$response_body,$trade);
				
		$output = array();
		preg_match_all('#<td class="row5" >(\w+)</td><td>.(\d+)/h</td>#',$response_body,$output,PREG_SET_ORDER);
				
		foreach($output as $value) $stats[strtolower($value[1])] = (1 + $stats[strtolower($value[1])]/100) * (1 + $trade[1]/100) * $value[2];

		$artefact = array();
		preg_match('#<td class="row5" >Artifact</td><td>(\w+)\s(\d)</td>#',$response_body,$artefact);
				
		if (count($artefact) > 0) foreach($arte[$artefact[1]] as $addon) $stats[$addon] = $stats[$addon] * (1 + $artefact[2]/10);
				
		$response_body = preg_replace('#(</tr></table><table class="menu")#','<tr><td class="row4 overall"><small>science:&nbsp;&nbsp;'.round($stats['science']).' /h&nbsp;&nbsp;&nbsp;culture:&nbsp;&nbsp;'.round($stats['culture']).' /h&nbsp;&nbsp;&nbsp;production:&nbsp;&nbsp;'.round($stats['production']).' /h</small></td></tr><tr><td></td></tr>\\1',$response_body);
		$response_body = preg_replace('#(</tr>)(<table class="alliance_list info_addon")#','\\1<table cellspacing="0" cellpadding="0" style="margin-top: -1px;"><tr><td><div id="map"></div></td></tr></table>\\2',$response_body);
		
		# next / prev navigation
		if (!is_null($request -> id) && is_numeric($request -> id) && $request -> id >= 0 && $request -> id < $registry -> members)
		{
			if ($request -> id == 0) $response_body = preg_replace('#(<b>NAPs</b></a></td>)#','\\1<td><a href="?cmd=AllianceDetail&amp;id=1">Next</a></td>',$response_body);
			else if ($request -> id == $registry -> members - 1) $response_body = preg_replace('#(<b>NAPs</b></a></td>)#','\\1<td><a href="?cmd=AllianceDetail&amp;id='.($request -> id - 1).'">Previous</a></td>',$response_body);
			else $response_body = preg_replace('#(<b>NAPs</b></a></td>)#','\\1<td><a href="?cmd=AllianceDetail&amp;id='.($request -> id - 1).'">Previous</a></td><td><a href="?cmd=AllianceDetail&amp;id='.($request -> id + 1).'">Next</a></td>',$response_body);	
		}
	}
}
?>