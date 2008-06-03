<?php
class ProxyPlanetsSpendAllFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<td>(<input type="submit" value="Spend (PP|SU)" class=smbutton>)#','<td class="row1">\\1',$response_body);
		$response_body = preg_replace('#<table class="inner_sub_content"[^<]*?>(.*?)</table>(.*?<table class=")single("[^<]*?>)#si','\\2planet_spend\\3\\1',$response_body);

		$response_body = str_replace('<td class="row4" >','<td class="row5" colspan="2" style="padding: 5px;">',$response_body);
		$response_body = str_replace('<td class="row1">','<td class="row4" colspan="2">',$response_body);
		$response_body = str_replace('<td><input type="radio"','<td class="row3" style="padding-bottom: 5px;"><input type="radio"',$response_body);

		//workflow
		$response_body = str_replace('?cmd=PlanetsBuildings.php','?cmd=PlanetsBuildings',$response_body);
		$response_body = str_replace('?cmd=Planetssubmitall.php','?cmd=PlanetsSubmitAll',$response_body);
	}
}
?>