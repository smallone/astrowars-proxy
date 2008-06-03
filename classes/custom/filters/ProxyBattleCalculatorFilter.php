<?php
class ProxyBattleCalculatorFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#.+(<form.+</form>[^<].?</td></tr>[^<].?</table>)[^<].+<table width="400".+</html>#si','<html><head><link rel="stylesheet" type="text/css" href="css/black/style_new.css"></link></head><body style="background-color: #111"><br /><center>\\1</center></body></html>',$response_body);
		$response_body = str_replace('<table border=0>','<table class="input" cellpadding="2" cellspacing="1" style="font-size: 10pt;">',$response_body);
		$response_body = str_replace('<tr class="row5" ><td>','<tr class="row3"><td class="label">',$response_body);
		$response_body = str_replace('<tr><td>','<tr><td class="label">',$response_body);
		$response_body = str_replace('size="5"','size="6"',$response_body);
		$response_body = str_replace('<td><input type="text"','<td class="value center"><input type="text"',$response_body);	
		$response_body = str_replace('class=text></td><td>','class=text></td><td class="value center" style="width: 50px;">',$response_body);
		$response_body = str_replace('class=text></td></tr>','class=text></td><td class="value center"></td></tr>',$response_body);
		$response_body = str_replace('<td colspan="2"><b>','<td colspan="2" class="header"><b>',$response_body);
		$response_body = str_replace('<td colspan="3">','<td colspan="4" class="right"><br />',$response_body);
		$response_body = str_replace('<td colspan="2">','<td colspan="2" class="center">',$response_body);
		$response_body = str_replace('</select></td><td>','</select></td><td class="center">',$response_body);
		
		
		$response_body = str_replace('<b>Reset</b>','<br /><b>Reset</b>',$response_body);
		
		
		$response_body = preg_replace('#<a href="http://www.astrowars.com/portal/[^"].+">([^<].+)</a>#','\\1',$response_body);
		
		//workflow
		$response_body = preg_replace('#<form[^>].+>#','<form name="login" id="login" method="post"><input type="hidden" name="cmd" value="BattleCalculator"/>',$response_body);
		$response_body = str_replace('<input type="submit" name="submit2" value="calculate battle" class=smbutton>','<input type="submit" name="submit2" value="calculate battle" class="smbutton">',$response_body);
		$response_body = str_replace('href="http://www.astrowars.com/about/battlecalculator/"','href="?cmd=BattleCalculator"',$response_body);
	}
}
?>