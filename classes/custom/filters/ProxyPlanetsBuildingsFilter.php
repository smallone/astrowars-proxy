<?php
class ProxyPlanetsBuildingsFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<td[^>]*?><td[^>]*?>[^<]*?Points:([^<]*?)<\/td[^>]*?>#','<td class="points">Points: <b>\\1</b></td>',$response_body);
		$response_body = preg_replace('#<td[^>]*?>[^<]*?Rank:([^<]*?)<\/td[^>]*?>#','<td class="rank">Rank: <b>\\1</b></td>',$response_body);
		$response_body = str_replace('<td><br>','<td>',$response_body);
		$response_body = str_replace('</table>'."\n".'<br>','</table>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=PlanetsDetail.php/?','?cmd=PlanetsDetail&',$response_body);
		$response_body = str_replace('?cmd=PlanetsBuildings.php','?cmd=PlanetsBuildings',$response_body);
		
		//functions
		$planets = array();
		preg_match_all('#<tr[^>]*?><td><a[^>]*?>([^\d]*?) (\d+)</a></td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td><td>(\d+)</td></tr>#',$response_body,$planets,PREG_SET_ORDER);

		foreach($planets as $planet)
		{
			$pp['H'] = round(5 * pow(1.5,$planet[4]));
			$pp['R'] = round(5 * pow(1.5,$planet[5]));
			$pp['C'] = round(5 * pow(1.5,$planet[6]));
			$pp['S'] = round(5 * pow(1.5,$planet[7]));
			
			if ($planet[8] >= $pp['H']) $response_body = preg_replace('#(<td><a[^>]*?>'.$planet[1].' '.$planet[2].'</a></td><td>\d+</td>)<td>'.$planet[4].'</td>#','\\1<td class="rowc8">'.$planet[4].'</td>',$response_body);
			if ($planet[8] >= $pp['R']) $response_body = preg_replace('#(<td><a[^>]*?>'.$planet[1].' '.$planet[2].'</a></td><td>\d+</td><td[^>]*?>.*?</td>)<td>'.$planet[5].'</td>#','\\1<td class="rowc8">'.$planet[5].'</td>',$response_body);
			if ($planet[8] >= $pp['C']) $response_body = preg_replace('#(<td><a[^>]*?>'.$planet[1].' '.$planet[2].'</a></td><td>\d+</td><td[^>]*?>.*?</td><td[^>]*?>.*?</td>)<td>'.$planet[6].'</td>#','\\1<td class="rowc8">'.$planet[6].'</td>',$response_body);
			if ($planet[8] >= $pp['S']) $response_body = preg_replace('#(<td><a[^>]*?>'.$planet[1].' '.$planet[2].'</a></td><td>\d+</td><td[^>]*?>.*?</td><td[^>]*?>.*?</td><td[^>]*?>.*?</td>)<td>'.$planet[7].'</td>#','\\1<td class="rowc8">'.$planet[7].'</td>',$response_body);
		}
	}
}
?>