<?php
class ProxyScienceFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<tr align=center><td width="135"></td>\s*<td colspan="3"></td></tr>#','',$response_body);
		$response_body = preg_replace('#</script>[^<]*?(<table\s*class="menu"\s*cellspacing="1"\s*cellpadding="0"[^>]*?>)#','</script></td></tr></table>\\1',$response_body);
					
		for($i = 0;$i < 2; $i++) $response_body = preg_replace('#(<input type="text"[^>]*?)class="text"(.*<b>Culture</b>)#si','\\1class="non_input"\\2',$response_body);
		
		$response_body = preg_replace('#(<input type="text"[^>]*?)class="text"#','\\1class="non_input_c"',$response_body);

		$response_body = str_replace('<td><br>','<td>',$response_body);
		$response_body = str_replace('</table> <br>','</table>',$response_body);
		$response_body = str_replace('<tr align=center><td width="135"></td>'."\n".'<td colspan="3" style="width: 350px;"></td></tr>','',$response_body);
		$response_body = str_replace('</table>'."\n".'<table class="menu"','</table></td></tr></table><table class="menu"',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=ScienceChange.php','?cmd=ScienceChange',$response_body);
			
		//functions
		$response_body = str_replace('<td colspan="2">Remain</td></tr>','<td colspan="3">Remain</td></tr>',$response_body);

		$response_body = preg_replace('#<tr align=center><td colspan="5" class="row4" >(.*?)</td>#','<tr class="row4" align=center><td></td><td></td><td>\\1</td><td colspan="3"></td>',$response_body);
		$response_body = preg_replace('#(<tr align=center class="row3" ><td)(><a[^>]*?>)(Bio|Eco|Energy|Math|Physics|Social)([^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td><td>([^<]*?</td><td>[^<]*?</td>))</tr>#e',"\"\\1\".\"\\2\\3\\4\".'<td class=\"sc_opt\"><a href=\"?cmd=ScienceSubmit&amp;science=f_'.strtolower('\\3').'\"><img src=\"imports/images/ok.png\" alt=\"\"></img></a></td></tr>'",$response_body);
		$response_body = preg_replace('#(<tr align=center class="row3" ><td)(><a[^>]*?>)(Cult)([^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td><td>((<form>.*?|[^<]*?)</td><td>(.*?</form>|[^<]*?)</td>))</tr>#e',"\"\\1\".\"\\2\\3\\4\".'<td class=\"sc_opt\"></td></tr>'",$response_body);
		$response_body = preg_replace('#(<tr align=center class="row8" ><td)(><a[^>]*?>)(Bio|Eco|Energy|Math|Physics|Social)([^<]*?</a>[^<]*?</td><td>[^<]*?</td><td><img[^>]*?><img[^>]*?></td><td>([^<]*?</td><td>[^<]*?</td>|[^<]*?<form><input[^>]*?></td><td><input[^>]*?></form></td>))</tr>#','\\1 \\2\\3\\4<td></td></tr>',$response_body);

		if ($registry -> settings instanceof Settings  && $registry -> settings -> race_calc)
		{
			$science = array();
			preg_match('#<b>Science</b></a> <a[^>]*?>\(\+(\d+) per hour\)</a>[^<]*?<b>(.\d+)%</b></td>#',$response_body,$science);
							
			$culture = array();
			preg_match('#<b>Culture</b></b> <a[^>]*?>\(\+(\d+) per hour\)</a> <b>(.\d+)%</b></td>#',$response_body,$culture);	
		
			if (count($science) > 0)
			{
				$science[1] = round($science[1] * (1 + $science[2]/100),0);
				$response_body = preg_replace('#(<b>Science</b></a> <a[^>]*?>\(\+\d+ per hour\)</a>[^<]*?<b>.\d+%</b>)#','\\1 <small class="calc">= '.$science[1].' / h</small>',$response_body);
			}
		
			if (count($culture) > 0)
			{
				$culture[1] = round($culture[1] * (1 + $culture[2]/100),0);
				$response_body = preg_replace('#(<b>Culture</b></b> <a[^>]*?>\(\+\d+ per hour\)</a> <b>.\d+%</b>)#','\\1 <small class="calc">= '.$culture[1].' / h</small>',$response_body);
			}
		}
	}
}
?>