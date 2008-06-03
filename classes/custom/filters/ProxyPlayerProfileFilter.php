<?php
class ProxyPlayerProfileFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#(<table class="menu" cellspacing="1" cellpadding="0">[^<]*?)<td>#','\\1<tr><td class="player">',$response_body);
		$response_body = preg_replace('#<table border="0" cellspacing="1" class="row3" >#','<table class="profile" cellspacing="1" cellpadding="0">',$response_body);
		$response_body = preg_replace('#<table border="0" cellpadding="2" class="row6" >#','<table class="profile_stats" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#<table>(<tr valign="top"><td>)#','<table cellspacing="0" cellpadding="0">\\1',$response_body);
		
		$response_body = str_replace('<table class="profile" cellspacing="1" cellpadding="0"><tr><td>','<table class="profile" cellspacing="1" cellpadding="0"><tr><td valign="top">',$response_body);
		$response_body =  preg_replace('#(<tr><td class="row5"[^>]*?>[^<]*?</td><td)(>[^<]*?</td></tr>)#','\\1 align="center"\\2',$response_body);
		$response_body =  preg_replace('#(<tr><td class="row5"[^>]*?><a[^>]*?>[^<]*?</a></td><td)(>[^<]*?</td></tr>)#','\\1 align="center"\\2',$response_body);
		
		//workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Alliances/Info.php/?','?cmd=AlliancesInfo&',$response_body);
		$response_body = preg_replace('#<a href="[^"]*?"><b>Race Summary</b></a>#si','<b>Race Summary</b>',$response_body);

		//function		
		$intel = new Intel();
		$intel -> id = $_GET['id'];

		$query = new Query();
		$query -> addObject($intel,$registry -> criteria);
			
		foreach (ObjectHandler::collect($query) as $intel) { continue ;}	
		
		if (preg_match('#Intelligence Report#',$response_body))
		{
			$matches = array();
			preg_match_all('#<tr><td class="row5" >(\w+)</td><td[^>]*?>(\d+)</td></tr>#',$response_body,$matches,PREG_SET_ORDER);
						
			foreach($matches as $key => $value)
			{
				$value[1] = strtolower($value[1]);
				$intel ->  $value[1] = $value[2];
			}

			$matches = array();
			preg_match('#<tr><td class="row5" >Trade Revenue</td><td[^>]*?>(\d+)%</td></tr>#',$response_body,$matches);
			$intel ->  tr =  $matches[1];
			
			$matches = array();
			preg_match_all('#<li>([+-]\d+[%h]) (\w+) \(([+-]\d)\)</li>#',$response_body,$matches,PREG_SET_ORDER);
			
			foreach($matches as $value)
			{
				$value[2] = strtolower($value[2]);
				$intel ->  $value[2] = $value[1];
				
				 $value[2] .= '_rel';
				
				$intel ->  $value[2] = $value[3];
			}
			
			$intel ->  sul = (preg_match('#<li>Start Up Lab</li>#',$response_body)) ? 1 : 0;
			$intel ->  trader = (preg_match('#<li>Trader/li>#',$response_body)) ? 1 : 0;
			
			$intel -> time = time();
			$intel -> player = $registry -> user_id;
			$intel -> alliance = $registry -> alliance_id;
						
			ObjectHandler::save($intel);
		}
		else if ($intel -> time > 0)
		{
			$here_doc ='<td><table class="profile_stats" cellspacing="1" cellpadding="2">
				<tr><td colspan="2" class="row4 scanned" >'.gmdate('Y.m.d H:i:s',$intel -> time + $registry -> gmt * 3600).' GMT '.$registry -> gmt.'</td></tr>
				<tr><td class="row5">Biology</td><td align="center">'.$intel->biology.'</td></tr>
				<tr><td class="row5">Economy</td><td align="center">'.$intel->economy.'</td></tr>
				<tr><td class="row5">Energy</td><td align="center">'.$intel->energy.'</td></tr>
				<tr><td class="row5">Mathematics</td><td align="center">'.$intel->mathematics.'</td></tr>
				<tr><td class="row5">Physics</td><td align="center">'.$intel->physics.'</td></tr>
				<tr><td class="row5">Social</td><td align="center">'.$intel->social.'</td></tr>
				<tr><td class="row5">Trade Revenue</td><td align="center">'.$intel -> tr.'%</td></tr>
				<tr><td colspan="2" class="row4"><b>Race Summary</b></td></tr>
				<tr><td colspan="2">
				<ul type="square">';

			if ($intel -> trader) $here_doc .= '<li>Trader</li>';
			if ($intel -> sul) $here_doc .= '<li>Start Up Lab</li>';
			
			$here_doc .= '<li>'.$intel->growth.' growth ('.$intel->growth_rel.')</li>
				<li>'.$intel->science.' science ('.$intel->science_rel.')</li>
				<li>'.$intel->culture.' culture ('.$intel->culture_rel.')</li>
				<li>'.$intel->production.' production ('.$intel->production_rel.')</li>
				<li>'.$intel->speed.' speed ('.$intel->speed_rel.')</li>
				<li>'.$intel->attack.' attack ('.$intel->attack_rel.')</li>
				<li>'.$intel->defense.' defense ('.$intel->defense_rel.')</li>
				</ul></td></tr></table>';

			$response_body = preg_replace('#(<table class="profile_stats".*?</table></td>)#si','\\1'.$here_doc,$response_body);
		}
		
		# status
		$player = new Player();
		$player -> id = $_GET['id'];

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
		
		$response_body = $response_body = preg_replace('#(<tr><td colspan="2" class="row4" ><center><b>(<a href=[^>]*?>.*?</a>.*?|.*?))'.$player -> name.'(.*?</b></center></td></tr>)#','\\1<span '.$class.'>'.$player -> name.'</span>\\3',$response_body);
		
		# add proxy sub menu
		$value = '<a href="javascript:getCmd(\'FeaturePlayersState&id='.$player -> id.'\',\'content\')"><img src="imports/images/playerstate.png" alt="apply a new status" title="apply a new status" class="menu"></img></a>';
		$value .= '&nbsp;&nbsp;<a href="javascript:getCmd(\'FeaturePlayersBattles&id='.$player -> id.'\',\'content\')"><img src="imports/images/battles.png" alt="view battles" title="view battles" class="menu"></img></a>';
						
		if (in_array($player -> alliance,$registry -> coorperation['alliance']) || in_array($player -> id,$registry -> coorperation['player'])) 
		{
			$value .= '&nbsp;&nbsp;<a href="javascript:getCmd(\'FeatureFilteredFleets&pid='.$player -> id.'\',\'content\')"><img src="imports/images/viewfleet.png" alt="view fleets" title="view fleets" class="menu"></img></a>';
			if ($player -> id != $registry -> user_id) $value .= '&nbsp;&nbsp;<a href="javascript:getCmd(\'FeatureMessagesNew&id='.$player -> id.'\',\'content\')"><img src="imports/images/mail_new3.png" alt="send message" title="send message" class="menu"></img></a>';
		}
		
		$response_body = str_replace('<tr><td class="row5" >Local Time</td>','<tr><td colspan="2" class="row5" align="center" style="padding-top: 4px;">'.$value.'</td></tr><tr><td class="row5" >Local Time</td>',$response_body);
		
		$matches = array();
		preg_match('#<a href="(.+)" target="_blank">x:y\((.+)/(.+)\)</a>#',$response_body,$matches);

		if (count($matches) > 0)
		{
			$starmap = new Starmap();
			$starmap -> x = $matches[2];
			$starmap -> y = $matches[3];
			
			ObjectHandler::load($starmap);
			
			$response_body = preg_replace('#<a href=".+" target="_blank">(x:y\(.+/.+\))</a>#','<a href="?cmd=MapDetail&amp;nr='.$starmap -> id.'">\\1</a>',$response_body);
		}
	}
}
?>