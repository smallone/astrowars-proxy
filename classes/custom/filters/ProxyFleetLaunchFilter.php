<?php
class ProxyFleetLaunchFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<br>([^<]*?<form.*?</form>[^<]*?)<br>#si','<br /><center>\\1<br /></center></td></tr></table>',$response_body);
		$response_body = str_replace('<table class="single"','<table class="fleet"',$response_body);
		$response_body = str_replace('<td>max</td></tr>','<td>max</td><td></td></tr>',$response_body);
		$response_body = str_replace('<td colspan="3" style="width: 350px;">','<td colspan="4" style="width: 350px;">',$response_body);
		$response_body = str_replace('<tr align=center><td colspan="3" class="row9" >','<tr align=center><td colspan="4" class="row9">',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=Fleetsend.php','?cmd=FleetSend',$response_body);
		$response_body = str_replace('?cmd=Map/?','?cmd=Map&',$response_body);
		
		//functions
		if ($registry -> settings instanceof Settings && $registry -> settings -> merge_destinations)
		{
			$response_body = preg_replace('#(<input type="radio" name="destination" value="\d+")checked>#','\\1 selected="selected">',$response_body);
			$response_body = preg_replace('#<tr align=center><td class="row3" ><a[^>]*?>([^<]*?)</a></td><td><input type="radio" name="destination" value="(\d+)"([^>]*?)></td></tr>#','<option value="\\2" id="\\2"\\3>ID \\2 - \\1</option>',$response_body);
			$response_body = str_replace('<td colspan="4" style="width: 350px;">Destination</td></tr>','<td colspan="4" style="width: 350px;">Destination</td></tr><tr align=center><td class="row3"><select id="destination" name="destination">',$response_body);
			$response_body = str_replace('</option><tr','</option></select></td><td><a href="javascript:getSelectCmd(\'FeatureLaunchSystem\',\'map\',document.getElementById(\'destination\'))"><img src="imports/images/view_sys.png" alt="view system" title="view system"></img></a></td><tr',$response_body);
			
			if (!preg_match('#selected="selected"#',$response_body)) $response_body = preg_replace('#(<option value="'.$request -> nr.'"\s*id="'.$request -> nr.'"[^>]*?)(>ID '.$request -> nr.'[^<]*?</option>)#','\\1 selected="selected"\\2',$response_body);
			
			if (!preg_match('#<option[^>]*?selected="selected"[^>]*?>#',$response_body))
			{
				$starmap = new Starmap();
				$starmap -> id = $request -> nr;
				
				ObjectHandler::load($starmap);
				
				$response_body = str_replace('<select id="destination" name="destination">','<select id="destination" name="destination"><option value="'.$starmap -> id.'" id="'.$starmap -> id.'">ID '.$starmap -> id.' - '.$starmap -> name.'</option><option> ------------------------------------------------------- </option>',$response_body);			
			}
			
			$response_body = str_replace('<br /></center></td></tr></table>','<br /></center><div id="map"></div></td></tr></table>',$response_body);
		}

		$response_body =  str_replace('<input type="checkbox" name="calc" value="1">','<input type="checkbox" name="calc" value="1" checked="checked">',$response_body);
		
	}
}
?>