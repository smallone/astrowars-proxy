<?php
class ProxyPlanetsPremiumFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#</td>[^<]*?<td>[^<]*?</tr>#','</td></tr>',$response_body);
				
		$response_body = str_replace('</form><br>','</form>',$response_body);
		$response_body = str_replace('<tr align=center><td colspan="6">','<tr align=center><td colspan="6" class="row5">',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=Planetssubmit_premium.php','?cmd=PlanetsPremiumSubmit',$response_body);
		$response_body = str_replace('?cmd=PlanetsPremium_PP.php/?','?cmd=PlanetsPremium&',$response_body);
		$response_body = str_replace('?cmd=PlanetsBuildings.php','?cmd=PlanetsBuildings',$response_body);
		$response_body = str_replace('?cmd=PlanetsSpend_Points.php/?','?cmd=PlanetsSpendPoints&',$response_body);
		$response_body = str_replace('?cmd=PlanetsDetail.php/?','?cmd=PlanetsDetail&',$response_body);
		$response_body = str_replace('?cmd=PlanetsPremium_PP.php?','?cmd=PlanetsPremium&',$response_body);
	}
}
?>