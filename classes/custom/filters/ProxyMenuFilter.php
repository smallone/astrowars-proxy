<?php
class ProxyMenuFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		# Session aktiv aber kein Login in AW
		if (!preg_match('#<title>.*?-(.*?)</title>#', $response_body)) return 1;

		//layout
		$response_body = preg_replace('#<td>\|</td>#','',$response_body);
		$response_body = preg_replace('#<TABLE\s*BORDER="0"\s*CELLSPACING="0"\s*CELLPADDING="0"\s*class="row7"\s*width="600"[^>]*?>#i','<table class="content" cellspacing="0" cellpadding="0">',$response_body);
		$response_body = preg_replace('#<TABLE\s*BORDER="0"\s*CELLSPACING="0"\s*CELLPADDING="0"\s*class="row3"\s*width="600"[^>]*?>#i','<table class="menu" cellspacing="1" cellpadding="0">',$response_body);
		$response_body = preg_replace('#<TABLE\s*BORDER="0"\s*CELLSPACING="1"\s*CELLPADDING="1"\s*class="row7"\s*width="600"[^>]*?>#i','<table class="inner_sub_content" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#<table\s*border="0"\s*CELLSPACING="1"\s*CELLPADDING="1"\s*width="600"[^>]*?>#i','<table class="inner_content" cellspacing="1" cellpadding="0">',$response_body);
		$response_body = preg_replace('#<TABLE BORDER="0"\s*CELLSPACING="0"\s*CELLPADDING="0"\s*class="row5"\s*width="600"[^>]*?>#i','<table class="inner_sub_menu" cellspacing="1" cellpadding="0">',$response_body);
		$response_body = preg_replace('#<TABLE BORDER="0" CELLSPACING="1" CELLPADDING="1" class="row7"[^>]*?>#i','<table class="single" cellspacing="1" cellpadding="2">',$response_body);

		$response_body = preg_replace('#<td\s*width="140"\s*class="row4"[^>]*?>\s*<\s*a\s*href[^>]*><b>Astro Wars</b></a></td>#','',$response_body);
		$response_body = preg_replace('#<tr\s*height=15\s*align=center><td width="140"\s*class="row4"[^>]*?>\s*<b>[^<]*?</b></td>#','',$response_body);
				
		// workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/"','?cmd=News"',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Map/','?cmd=Map',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Planets/','?cmd=Planets',$response_body);		
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Science/','?cmd=Science',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Fleet/','?cmd=Fleet',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Trade/','?cmd=Trade',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Alliance/','?cmd=Alliance',$response_body);

		//functions 
		$message = new Message();
		$message -> send_to = $registry -> user_id;
		$message -> state = Message::UNREAD;
					
		$query = new Query();
		$query -> addObject($message);
			
		$count = (ObjectHandler::count($query) > 0) ? ' <span style="color: red;">('.ObjectHandler::count($query).')</span>' : null;
					
		// online member 
		$criteria  = clone $registry -> criteria;
		$criteria -> addSort('time',Criteria::DESC);
			
		$query = new Query();
		$query -> addObject(new PlayerUpdate(),$criteria);
	
		$value = null;
			
		foreach (ObjectHandler::collect($query) as $key => $update)
		{
			if ($update -> time < time() - 16 * 60) continue;
				
			$player = new Player();
			$player -> id = $update -> player;
	
			ObjectHandler::load($player);
				
			$value .= ($key == 1) ? '<a href="?cmd=PlayerProfile&amp;id='.$player -> id.'">'.$player -> name.'</a>' : ', <a href="?cmd=PlayerProfile&amp;id='.$player -> id.'">'.$player -> name.'</a>';
		}
		
		$response_body = preg_replace('#(<table class="menu".*?</table>[^<]*?)(<table class="content".*?<table class="menu".*?</table>)#si','\\1<table class="tool_menu" cellspacing="0" cellpadding="0"><tr><td><table class="inner_tool_menu" cellspacing="1" cellpadding="2"><tr><td><a href="javascript:getCmd(\'FeatureSystems\',\'content\')">Systems</a></td><td><a href="javascript:getCmd(\'FeaturePlayers\',\'content\')">Players</a></td><td><a href="javascript:getCmd(\'FeatureFleets\',\'content\')">Fleets</a></td><td><a href="javascript:getCmd(\'FeatureMessages\',\'content\')">Messages'.$count.'</a></td><td><a href="javascript:getCmd(\'FeatureTools\',\'content\')">Tools</a></td><td><a href="javascript:getCmd(\'FeatureSettings\',\'content\')">Settings</a></td></tr></table></td></tr></table><div id="content">\\2</div>',$response_body);
		$response_body = str_replace('</body></html>','<div class="online"><span class="label">allies online:</span>'.$value.'</div></body></html>',$response_body);
	}
}
?>