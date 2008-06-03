<?php
class ProxyMapDetailFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = str_replace('<tr align=center><td colspan="5">','<tr align=center class="row5"><td colspan="6">',$response_body);
		$response_body = str_replace("\n\n".'<br>'."\n",'',$response_body);
		
		// workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);
		$response_body = str_replace('?cmd=MapCoordinates.php','?cmd=MapCoordinates',$response_body);

		//function
		$response_body = str_replace('<td>Owner</td></tr>','<td>Owner</td><td>Ally</td><td></td></tr>',$response_body);
		$response_body = str_replace(')</b></td></tr>',')</b> - SID '.$_GET['nr'].'</td></tr>',$response_body);
		
		$matches = array();
		preg_match_all('#<tr class="([^"]*?)"[^>]*?><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(([^<]*?)|<a href="[^"]*?&id=(\d+)">([^<]*?)</a>)</td></tr>#',$response_body,$matches,PREG_SET_ORDER);
		
		if (count($matches) > 0)
		{
			$system = new System();
			$system -> sid = $request -> nr;
			
			$query = new Query();
			$query -> addObject($system,$registry -> criteria);
			
			ObjectHandler::delete($query);
		}
		
		if ($registry -> system_preload)
		{
			$sytem_match = array();
			preg_match('#Planets at <b>([^<]*?)</b> \(([^/]*?)/([^\)]*?)\)</b>#',$response_body,$sytem_match);
			
			if (count($sytem_match) > 0)
			{
				$starmap = new Starmap();
				$starmap -> name = $sytem_match[1];
				$starmap -> x = $sytem_match[2];
				$starmap -> y = $sytem_match[3];
				$starmap -> id = $request -> nr;
			
				ObjectHandler::save($starmap);
			}
		}
		
		# System scannen
		foreach($matches as $value)
		{
			$system = new System();
			$system -> sid = $request -> nr;
			$system -> pid = $value[2];
			$system -> population = $value[3];
			$system -> starbase = $value[4];
			$system -> owner = (isset($value[7])) ? $value[7] : 0;
			$system -> state = str_replace('row','',$value[1]);
			
			$system -> time = time();
			$system -> player = $registry -> user_id;
			$system -> alliance = $registry -> alliance_id;

			ObjectHandler::save($system);
			
			$player = new Player();
			$player -> id = $system -> owner;
			
			ObjectHandler::load($player);
			
			$ally_add = null;
											
			if ($player -> alliance > 0)
			{
				$alliance = new Alliance();
				$alliance -> id = $player -> alliance;
					
				ObjectHandler::load($alliance);
						
				$ally_add = '<a href="index.php?cmd=AlliancesInfo&tag='.$alliance -> tag.'">'.$alliance -> tag.'</a>';
			}
			
			$response_body = preg_replace('#(<tr class="'.$value[1].'"[^>]*?><td>'.$value[2].'</td><td>'.$value[3].'</td><td>'.$value[4].'</td><td>.*?</td>)</tr>#','\\1<td>'.$ally_add.'</td><td><a href="javascript:getCmd(\'FeatureComment&sid='.$system -> sid.'&pid='.$system -> pid.'\',\'content\')"><img src="imports/images/edit.png" alt="" title=""></img></a></td></tr>',$response_body);
		}
		
		// system ausser Sicht
		if (!preg_match('#<table class="inner_sub_content"#',$response_body))
		{
			$message = new Message();
			$message -> send_to = $registry -> user_id;
			$message -> state = Message::UNREAD;
				
			$query = new Query();
			$query -> addObject($message);
			
			$count = (ObjectHandler::count($query) > 0) ? ' <span style="color: red;">('.ObjectHandler::count($query).')</span>' : null;
					
			$criteria  = clone $registry -> criteria;
			
			$c1 = new Criteria();
			$c1 -> addInclude('alliance',0);
			$c1 -> addInclude('player',0);
			
			$criteria -> addObject($c1); 
			$criteria -> addSort('pid',Criteria::ASC);
			$criteria -> addSort('time',Criteria::DESC);
						
			$system = new System();
			$system -> sid = $_GET['nr'];
			
			$query = new Query();
			$query -> addObject($system,$criteria);

			$menu = '<table class="tool_menu" cellspacing="0" cellpadding="0"><tr><td><table class="inner_tool_menu" cellspacing="1" cellpadding="2"><tr><td><a href="javascript:getCmd(\'FeatureSystems\',\'content\')">Systems</a></td><td><a href="javascript:getCmd(\'FeaturePlayers\',\'content\')">Players</a></td><td><a href="javascript:getCmd(\'FeatureFleets\',\'content\')">Fleets</a></td><td><a href="javascript:getCmd(\'FeatureMessages\',\'content\')">Messages'.$count.'</a></td><td><a href="javascript:getCmd(\'FeatureTools\',\'content\')">Tools</a></td><td><a href="javascript:getCmd(\'FeatureSettings\',\'content\')">Settings</a></td></tr></table></td></tr></table><div id="content">';
			
			if (ObjectHandler::count($query) > 0)
			{
				$starmap = new Starmap();
				$starmap -> id = $system -> sid;

				ObjectHandler::load($starmap);
				
				$here_doc = $menu.'<table class="content" cellspacing="0" cellpadding="0"><tr><td><table class="inner_sub_content" cellspacing="1" cellpadding="2">
					<tr align=center class="row5"><td colspan="6">Planets at <b>'.$starmap -> name.'</b> ('.$starmap -> x.'/'.$starmap -> y.') - SID '.$system -> sid.'</td></tr>
					<tr align=center class="row4" ><td>ID</td><td><a href="index.php?cmd=Glossary&id=22" class="glossary">Population</a></td>
					<td><a href="index.php?cmd=Glossary&id=16" class="glossary">Starbase</a></td><td>Owner</td><td>Ally</td><td></td></tr>';
				
				$last_system = null;
				$time = null;
				
				foreach (ObjectHandler::collect($query) as $system) 
				{
					if (is_null($last_system) || $system ->  pid != $last_system -> pid)
					{
						$here_doc .= '<tr class="row'.$system -> state.'" align=center><td>'.$system -> pid.'</td><td>'.$system -> population.'</td><td>'.$system -> starbase.'</td><td>';
						$edit = '<td><a href="javascript:getCmd(\'FeatureComment&sid='.$system -> sid.'&pid='.$system -> pid.'\',\'content\')"><img src="imports/images/edit.png" alt="" title=""></img></a></td>';
							
						if ($system -> owner == 2) $here_doc .= 'unknown</td><td></td>'.$edit.'</tr>';
						else if ($system -> owner > 0)
						{
							$player = new Player();
							$player -> id = $system -> owner;
							
							ObjectHandler::load($player);
	
							$here_add = null;
																		
							if ($player -> alliance > 0)
							{
								$alliance = new Alliance();
								$alliance -> id = $player -> alliance;
							
								ObjectHandler::load($alliance);
								
								$here_add = '<a href="index.php?cmd=AlliancesInfo&tag='.$alliance -> tag.'">'.$alliance -> tag.'</a>';
							}
							
							$here_doc .= '<a href="index.php?cmd=PlayerProfile&id='.$player -> id.'">'.$player -> name.'</a></td><td>'.$here_add.'</td>'.$edit.'</tr>';
							
						}
						else $here_doc .= 'Free Planet</td><td></td>'.$edit.'</tr>';
						
						$time = $system -> time;
					}
						
					$last_system = $system;
				}					
				
				$adds = (preg_match('#<div id="add_header"></div>#',$response_body)) ? '<div id="add_footer"></div><div class="aw_add_footer" id="footer_bar"></div>' : '';
				
				$here_doc .= '<tr><td colspan="6" class="row4 scanned" >'.gmdate('Y.m.d H:i:s',$time + $registry -> gmt* 3600).' GMT '.$registry -> gmt.'</td></tr></table></td></tr></table><table class="menu" cellspacing="1" cellpadding="0"><td><a href="index.php?cmd=Map">Overview</a></td>
					<td><a href="index.php?cmd=MapCoordinates">Coordinates</a></td></tr></table></div>'.$adds.'</body></html>';
				
				$response_body = str_replace('<table class="content" cellspacing="0" cellpadding="0"><tr><td>',$here_doc,$response_body);
			}
			else
			{
				$here_doc = $menu.'<table class="content" cellspacing="0" cellpadding="0"><tr><td><table class="inner_sub_content" cellspacing="1" cellpadding="2">
					<center><h2>system not found</h2></center></td></tr></table></td></tr></table><table class="menu" cellspacing="1" cellpadding="0"><td><a href="index.php?cmd=Map">Overview</a></td>
					<td><a href="index.php?cmd=MapCoordinates">Coordinates</a></td></tr></table><br></div></body></html>';
				$response_body = str_replace('<table class="content" cellspacing="0" cellpadding="0"><tr><td>',$here_doc,$response_body);
			}
		}
		
		if ($registry -> settings instanceof Settings  && $registry -> settings -> inner_fleet)
		{
			$player_fleet = new Fleet();
			$player_fleet -> owner =  $registry -> user_id;
			$player_fleet -> state = Fleet::MOVING;
			
			$query = new Query();
			$query -> addObject($player_fleet);

			$count = ObjectHandler::count($query);
			
			# Flotten
			$fleet = new Fleet();
			$fleet -> sid = $_GET['nr'];
	
			$criteria  = clone $registry -> criteria;
			$criteria -> addSort('pid');
			$criteria -> addSort('arrive',Criteria::DESC);
			
			$query = new Query();
			$query -> addObject($fleet,$criteria);
			
			$last_id = null;
			$value = null;
			
			foreach (ObjectHandler::collect($query) as $fleet) 
			{ 
				if (!is_null($last_id) &&  $fleet -> pid != $last_id)
				{
					$response_body = preg_replace('#(<tr[^>]*?><td>'.$last_id.'</td>.*?</tr>)#','\\1<tr class="fleet_add"><td colspan="6"><table class="fleet_entry" id="'.$last_id.'">'.$value.'</table></td></tr>',$response_body);
					$value = null;
				}
				
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
		
				$value .= '<tr><td class="img"><img src="imports/images/'.$img.'"></img></td><td class="user"><a '.$class.' href="index.php?cmd=PlayerProfile&amp;id='.$player -> id.'">'.$player -> name.'</a></td><td class="time">'.$options.'</td><td class="cv">'.($fleet -> destroyer * 3 + $fleet -> cruiser * 24 + $fleet -> battleship * 60).' cv</td><td class="label">tr:</td><td class="value">'.$fleet ->  transport.'</a></td><td class="label">col:</td><td class="value">'.$fleet -> colony_ship.'</td><td class="label">ds:</td><td class="value">'.$fleet -> destroyer.'</td><td class="label">cr:</td><td class="value">'.$fleet -> cruiser.'</td><td class="label">bs:</td><td class="value">'.$fleet -> battleship.'</td></tr>';
					
				$last_id = $fleet -> pid;
			}
	
			if (!is_null($value)) $response_body = preg_replace('#(<tr[^>]*?><td>'.$last_id.'</td>.*?</tr>)#','\\1<tr class="fleet_add"><td colspan="6"><table class="fleet_entry" id="'.$last_id.'">'.$value.'</table></td></tr>',$response_body);
		}
		
		if ($registry -> settings instanceof Settings  && $registry -> settings -> inner_comment)
		{
			# Comments
			$comment = new Comment();
			$comment -> sid = $_GET['nr'];
					
			$query = new Query();
			$query -> addObject($comment,$registry -> criteria);
					
			foreach (ObjectHandler::collect($query) as $comment)
			{
				if (strlen($comment-> message) > 50) $comment -> message = substr($comment -> message,0,50).'...';

				$comment -> message =  utf8_encode($comment -> message);
				
				$player = new Player();
				$player -> id = $comment -> player;
								
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
				
				$value = '<tr><td class="img"><img src="imports/images/comment.png"></img></td><td class="user"><a '.$class.' href="index.php?cmd=PlayerProfile&amp;id='.$player -> id.'">'.$player -> name.'</a></td><td class="message" colspan="12">'.$comment -> message.'</td></tr>';
							
				$matches = array();
				
				if (preg_match('#<tr[^>]*?><td>'.$comment -> pid.'</td>.*?</tr><tr class="fleet_add"><td colspan="6"><table class="fleet_entry" id="'.$comment -> pid.'">#',$response_body,$matches)) $response_body = str_replace($matches[0],$matches[0].$value,$response_body);
				else
				{
					$response_body = preg_replace('#(<tr[^>]*?><td>'.$comment -> pid.'</td>.*?</tr>)#','\\1<tr class="comment_add"><td colspan="6"><table class="comment_entry">'.$value.'</table></td></tr>',$response_body);
				}	
				
			}
		}
		
		# System States
		$system = new System();
		$system -> sid = $_GET['nr'];
		
		$criteria  = clone $registry -> criteria;
			
		$c1 = new Criteria();
		$c1 -> addInclude('alliance',0);
		$c1 -> addInclude('player',0);
			
		$criteria -> addObject($c1); 
		$criteria -> addSort('pid',Criteria::ASC);
		
		$query = new Query();
		$query -> addObject($system,$criteria);
					
		foreach (ObjectHandler::collect($query) as $system)
		{
			if ($system -> owner > 0)
			{
				$player = new Player();
				$player -> id = $system -> owner;

				ObjectHandler::load($player);
				
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
					}

					if (!is_null($status -> state)) $response_body = preg_replace('#<a (href="[^"]*?id='.$player -> id.'">'.$player -> name.'</a></td><td><a)#','<a class="s'.$status -> state.'" \\1 class="s'.$status -> state.'" ',$response_body);

				}
				else if ($player -> id != 2)
				{
					$status = new Status();
					$status -> id = $player -> id;
					$status -> mode = Status::SINGLE;
	
					$query = new Query();
					$query -> addObject($status,$registry -> tiny_criteria);

					foreach(ObjectHandler::collect($query) as $status) { continue; }
					
					if (!is_null($status -> state)) $response_body = preg_replace('#<a (href="[^"]*?id='.$player -> id.'">'.$player -> name.'</a>)#','<a class="s'.$status -> state.'" \\1',$response_body);
				}
			}
		}

		# add proxy sub menu
		$response_body = preg_replace('#<tr align=center class="row5"><td colspan="6">(.*?)</td>#','<tr align=center class="row5"><td colspan="6"><div style="float: right;padding-top:2px;padding-right:4px;"><a href="javascript:getCmd(\'FeatureSystemsBattles&sid='.$request -> nr.'\',\'content\')"><img src="imports/images/battles.png" alt="view battles" title="view battles" class="menu"></img></a>&nbsp;&nbsp;<a href="javascript:getCmd(\'FeatureFilteredFleets&sid='.$request -> nr.'\',\'content\')"><img src="imports/images/viewfleet.png" alt="view fleets" title="view fleets" class="menu"></img></a>&nbsp;&nbsp;<a href="javascript:getCmd(\'FeatureTacticalMap&sid='.$request -> nr.'\',\'content\')"><img src="imports/images/tacmap.png" alt="tactical map" title="tactical map" class="menu"></img></a>&nbsp;&nbsp;<a href="javascript:getCmd(\'FeatureSystemBbCodeExport&sid='.$request -> nr.'\',\'content\')"><img src="imports/images/bbcode.png" alt="export to bbcode" title="export to bbcode" class="menu"></img></a></div><div style="float: left;padding-top:8px;width: 450px;">\\1</div>',$response_body);
		
	}
}
?>