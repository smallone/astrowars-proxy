<?php
class ProxyPlanetsFilter extends ProxyFilter implements IProxyFilter
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
		$response_body = str_replace('?cmd=PlanetsSpend_All_Points.php','?cmd=PlanetsSpendAll',$response_body);
		
		//functions
		$growth = array();
		preg_match('#Growth (.\d+)%#',$response_body,$growth);
				
		$prod = array();
		preg_match('#Production (.\d+)%#',$response_body,$prod);	

		if (count($growth) > 0) $registry -> growth = $growth[1];
		if (count($prod) > 0) $registry -> prod = $prod[1];
								
		if ($registry -> settings instanceof Settings  && $registry -> settings -> race_calc) 
		{
			$pp = array();
			preg_match('#<td>\+(\d+)</td></tr>[^<]*?</table>#',$response_body,$pp);
					
			$pp[1] = round($pp[1] * (1 + $registry -> prod/100),0);

			$response_body = preg_replace('#(Production (.\d+)%)#','\\1 <small class="calc">= '.$pp[1].' / h</small>',$response_body);
		}
	}
}
?>