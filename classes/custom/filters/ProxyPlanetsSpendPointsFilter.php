<?php
class ProxyPlanetsSpendPointsFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table class="inner_sub_content"[^<]*?>(.*?)</table>(.*?<table class=")single("[^<]*?>)#si','<center>\\2planet_spend\\3\\1',$response_body);
		
		$response_body = str_replace("\n\n".'<td>'."\n\n",'',$response_body);
		$response_body = str_replace('<td class="row4" >','<td class="row5" colspan="4" style="padding: 5px;">',$response_body);
		$response_body = str_replace('<td class="row1">','<td class="row4" colspan="2">',$response_body);
		$response_body = str_replace('<td><input type="radio"','<td class="row4" style="padding-bottom: 5px;"><input type="radio"',$response_body);
		$response_body = str_replace('<input type="submit" value="Spend PP"','</td></tr><tr><td class="row4" colspan="4"><input type="submit" value="Spend PP"',$response_body);
		$response_body = str_replace('</form></td></tr>'."\r\n".'</table>','</form></td></tr></table><br />',$response_body);
		
		$response_body = str_replace('<td></td></tr>','</tr>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=PlanetsBuildings.php','?cmd=PlanetsBuildings',$response_body);
		$response_body = str_replace('?cmd=Planetssubmit.php','?cmd=PlanetsSubmit',$response_body);
		$response_body = str_replace('?cmd=Planetssubmit2.php','?cmd=PlanetsSubmitSu',$response_body);

		//functions
		$response_body = preg_replace('#<input type="text" name="points" size="3" class=text value="\d+"#','<input type="text" name="points" size="3" class="text"',$response_body);
	}
}
?>