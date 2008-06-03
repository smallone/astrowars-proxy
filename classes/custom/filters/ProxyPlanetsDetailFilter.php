<?php
class ProxyPlanetsDetailFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<tr([^>]*?)><td class="row3"[^>]*?>#','<tr\\1 class="row3"><td>',$response_body);
		$response_body = preg_replace('#<tr([^>]*?)><td colspan="5"[^>]*?>#','<tr\\1 class="row5"><td colspan="5">',$response_body);
		$response_body = preg_replace('#<td>[^<]*?<td>#','<td>',$response_body);
		
		$response_body = str_replace('</table>'."\n".'<br>','</table>',$response_body);
		$response_body = str_replace('<tr align=center><td colspan="5">','<tr align=center class="row5"><td colspan="5">',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=PlanetsPremium_PP.php/?','?cmd=PlanetsPremium&',$response_body);
		$response_body = str_replace('?cmd=PlanetsBuildings.php','?cmd=PlanetsBuildings',$response_body);
		$response_body = str_replace('?cmd=PlanetsSpend_Points.php/?','?cmd=PlanetsSpendPoints&',$response_body);
		$response_body = str_replace('?cmd=PlanetsDetail.php/?','?cmd=PlanetsDetail&',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);
				
		//functions
		$upgrade['Hydroponic Farm'] = 'farm';
		$upgrade['Robotic Factory'] = 'fabrik';
		$upgrade['Galactic Cybernet'] = 'kultur';
		$upgrade['Research Lab'] = 'forschungslabor';
		$upgrade['Starbase'] = 'starbase';
		$upgrade['Transport'] = 'infantrieschiff';	
		$upgrade['Colony Ship'] = 'kolonieschiff';
		$upgrade['Destroyer'] = 'destroyer';
		$upgrade['Cruiser'] = 'cruiser';
		$upgrade['Battleship'] = 'battleship';

		$pp = array();
		preg_match('#<tr align=center class="row8" ><td><a[^>]*?>Production Points</a></td><td>\s*(\d+)\s*</td>#',$response_body,$pp);
		
		if ($registry -> settings instanceof Settings  && $registry -> settings -> race_calc)
		{
			$output = array();
			preg_match_all('#[^<]*?<a[^>]*?>\+(\d+)</a>#',$response_body,$output,PREG_SET_ORDER);
			
			if (count($output) > 0)
			{
				$prod = (!is_null($registry -> prod)) ? $output[1][1] * (1 + $registry -> prod/100) : $output[1][1];
			
				$growth_remain = array();
				preg_match('#</td><td>[^<]*?(\d+)</td></tr>#',$response_body,$growth_remain);
				
				$growth = (!is_null($registry -> growth)) ? $output[0][1] * (1 + $registry -> growth/100) : $output[0][1];
				$growth_time = ($growth > 0) ? $growth_remain[1]/$growth : $growth_remain[1];
			}
		}
		
		$matches = array();
		preg_match_all('#<tr align=center class="row3"><td><a[^>]*?>\s*?([^<]*?)\s*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?<td>\s*?([^\s]*)\s*?</td>#',$response_body,$matches,PREG_SET_ORDER);
				
		foreach($matches as $value)
		{
			if ($value[2] != 'N/A' && $value[2] <= $pp[1]) $response_body = preg_replace('#(<tr align=center class="row3"><td)(><a[^>]*?>\s*?'.$value[1].'[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?<td>\s*?(\d+)\s*?</td>)</tr>#','\\1 \\2<td><a href="?cmd=PlanetsSubmit&amp;produktion='.$upgrade[trim($value[1])].'&amp;points=\\3&amp;'.$url_parts['query'].'"><img src="imports/images/add.png" alt="add one level" title="add one level"></img></a></td></tr>',$response_body);				
			else if ($value[2] != 'N/A' && $registry -> settings instanceof Settings  && $registry -> settings -> race_calc)
			{	
				$time = round(($value[2] - $pp[1])/$prod);
				$response_body = preg_replace('#(<tr align=center class="row3"><td)(><a[^>]*?>\s*?'.$value[1].'[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?<td>\s*?)'.$value[2].'\s*?</td></tr>#','\\1 \\2<sup class="amount">'.$time.'h</sup> '.$value[2].'</td><td><img src="imports/images/no_add.png" alt=""></img></td></tr>',$response_body);	
			}
			else $response_body = preg_replace('#(<tr align=center class="row3"><td)(><a[^>]*?>\s*?'.$value[1].'[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?<td>\s*?'.$value[2].'\s*?</td>)</tr>#','\\1 \\2<td><img src="imports/images/no_add.png" alt=""></img></td></tr>',$response_body);	
		}
		
		$matches = array();
		preg_match_all('#<tr align=center class="row3"><td[^>]*?><a[^>]*?>\s*?([^<]*?)\s*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?<td colspan="2">\s*?(\d+)\/(\d+)\s*?</td>#',$response_body,$matches,PREG_SET_ORDER);
				
		foreach($matches as $value)
		{
			if ($pp[1] >= $value[3])
			{
				$amount = floor(($pp[1]+$value[2])/$value[3]);
				$need = $value[3] - $value[2];
		
				$response_body = preg_replace('#(<tr align=center class="row3"><td[^>]*?)(><a[^>]*?>\s*?'.$value[1].'[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?<td) colspan="2">(\s*?'.$value[2].'\/'.$value[3].'\s*?</td>)</tr>#','\\1 \\2><sup class="amount">'.$amount.'</sup>\\3</td><td><a href="?cmd=PlanetsSubmit&amp;produktion='.$upgrade[trim($value[1])].'&amp;points='.$need.'&amp;'.$url_parts['query'].'"><img src="imports/images/add.png" alt="build one '.trim($value[1]).'" title="build one '.trim($value[1]).'"></img></a></td></tr>',$response_body);	
			}
			else $response_body = preg_replace('#(<tr align=center class="row3"><td[^>]*?)(><a[^>]*?>\s*?'.$value[1].'[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?<td) colspan="2"(>\s*?'.$value[2].'\/'.$value[3].'\s*?</td>)</tr>#','\\1 \\2\\3<td><img src="imports/images/no_add.png" alt=""></img></td></tr>',$response_body);	
		}
		
		$response_body = preg_replace('#(<tr align=center class="row3"><td[^>]*?><a[^>]*?>[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?width=")([^"]*)("[^>]*?><img[^>]*?width=")([^"]*)("[^>]*?>)#e',"\"\\1\".(($2*350)/440).\"\\3\".(($4*350)/440).\"\\5\"",$response_body);
		$response_body = preg_replace('#(<tr align=center class="row8" ><td[^>]*?><a[^>]*?>[^<]*?</a>[^<]*?<a[^>]*?>[^<]*?</a></td><td>[^<]*?</td><td><img[^>]*?width=")([^"]*)("[^>]*?><img[^>]*?width=")([^"]*)("[^>]*?>)#e',"\"\\1\".(($2*350)/440).\"\\3\".(($4*350)/440).\"\\5\"",$response_body);
		$response_body = preg_replace('#(<tr align=center class="row8" ><td[^>]*?><a[^>]*?>[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?width=")([^"]*)("[^>]*?><img[^>]*?width=")([^"]*)("[^>]*?>)#e',"\"\\1\".(($2*350)/440).\"\\3\".(($4*350)/440).\"\\5\"",$response_body);
			
		$response_body = preg_replace('#<td>(Remain|Status)</td></tr>#','<td colspan="2">\\1</td></tr>',$response_body);
		$response_body = preg_replace('#(<tr align=center class="row8" ><td[^>]*?><a[^>]*?>[^<]*?</a>[^<]*?<a[^>]*?>[^<]*?</a></td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?)<td>([^<]*?</td></tr>)#','\\1<td colspan="2" class="cols">\\2',$response_body);
		$response_body = preg_replace('#(<tr align=center class="row8" ><td[^>]*?><a[^>]*?>[^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td>[^<]*?)<td>([^<]*?<a[^>]*?>[^<]*?</a>[^<]*?</td></tr>)#','\\1<td colspan="2" class="cols">\\2',$response_body);

		if ($registry -> settings instanceof Settings  && $registry -> settings -> race_calc && count($output) > 0)
		{
			$response_body = & preg_replace('#(</td><td colspan="2" class="cols">[^<]*?'.$growth_remain[1].')</td></tr>#','\\1 <small class="calc">= '.round($growth_time).'h</small></td></tr>',$response_body);
			$response_body = & preg_replace('#(<a[^>]*?>\+'.$output[1][1].')</a>#','\\1 <small class="calc">= '.round($prod).' /h</small>',$response_body);
		}
		
		$matches = array();
		preg_match('#<td colspan="5">(.+) \d+</td></tr>#si',$response_body,$matches);
		
		$starmap = new Starmap();
		$starmap -> name = trim($matches[1]);
		
		ObjectHandler::load($starmap);
		
		$response_body = preg_replace('#<td colspan="5">(.?.+ \d+)</td></tr>#si','<td colspan="5">SID '.$starmap -> id.' - <a href="?cmd=MapDetail&nr='.$starmap -> id.'">\\1</a>  ('.$starmap -> x.'/'.$starmap -> y.')</td></tr>',$response_body);
	}
}
?>