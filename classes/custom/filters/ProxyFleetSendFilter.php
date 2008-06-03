<?php
class ProxyFleetSendFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<br>(.*?)</br><br>#si','<br /><center>\\1</center><br /></td></tr></table>',$response_body);

		//functions
		if(preg_match('#To launch your fleet deactivate the Arrival Time Calculator#',$response_body))
		{
			$time = array();
			preg_match_all('#<b>Calculated arrival time: (\w+) (\d+) - (\d+):(\d+):(\d+).<br>#',$response_body,$time,PREG_SET_ORDER);
		
			$form = '<div class="hr"></div><form action="?cmd=FleetSend" method="post">';
			
			foreach ($_POST as $key => $value) if ($key != 'calc') $form .= '<input type="hidden" name="'.$key.'" value="'.$value.'" />';
			
			$form .= '<input type="submit" value="Launch!!!" class=smbutton></form><br />to arrive at <b><span id="h2">'.$time[0][3].'</span>:<span id="m2">'.$time[0][4].'</span>:<span id="s2">'.$time[0][5].'</span></b> GMT '.$registry -> gmt;
			
			$response_body = str_replace('</center><br />',$form.'</center><br />',$response_body);
			$response_body = preg_replace('#<body onload="([^"]*?)"#','<body onload="\\1StartClock(\'h2\',\'m2\',\'s2\');"',$response_body);

		
			if ($registry -> settings instanceof Settings && $registry -> settings -> launch_display_target)
			{
				$starmap = new Starmap();
				$starmap -> id = $request -> destination;
				
				ObjectHandler::load($starmap);
				
				$response_body = str_replace('</center><br />','<div class="hr"></div>You are targeting <b><a href="javascript:getCmd(\'FeatureLaunchSystem&value='.$starmap -> id.'\',\'map\')">'.$starmap -> name.' # '.$request -> planet.'</a></b><br /><br /><div id="map"></div></center>',$response_body);	
			}
		}
	}
}
?>