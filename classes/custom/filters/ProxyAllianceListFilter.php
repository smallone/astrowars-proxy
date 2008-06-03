<?php
class ProxyAllianceListFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table border=0 cellpadding=1 cellspacing=1 width=600>#','<table class="alliance_list" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#(<table class="alliance_list".*?</table>)<br>#si','\\1</td></tr></table>',$response_body);
		$response_body = preg_replace('#<tr><td><center>(.*?)<br>(<table class="alliance_list"[^<]*?>)#si','<tr><td>\\2<tr align=center class="row5" ><td colspan="14">\\1</td></tr>',$response_body);
		
		$response_body = str_replace('</td></tr></table></td></tr></table></center>','</td></tr></table></td></tr></table>',$response_body);
		$response_body = str_replace('<tr><td colspan=12></td><td colspan=2 align=right></td></tr>','',$response_body);
		
		if (preg_match('#<b>next</b>#',$response_body))
		{
			$response_body = preg_replace('#(<tr align=center class="row5" ><td colspan="14"[^>]*?)>(.*?</td></tr>)#','\\1 style="padding: 5px;"><div style="float: right;"><a href="index.php?cmd=AllianceList&start='.($request -> start + 15).'"><img src="imports/images/next.png"></img></a></div>\\2',$response_body);
		}
		
		if (preg_match('#<b>previous</b>#',$response_body))
		{
			$response_body = preg_replace('#(<tr align=center class="row5" ><td colspan="14"[^>]*?)>(.*?</td></tr>)#','\\1 style="padding: 5px;"><div style="float: left;"><a href="index.php?cmd=AllianceList&start='.($request -> start - 15).'"><img src="imports/images/previous.png"></img></a></div>\\2',$response_body);
		}
				
		$response_body = preg_replace('#<tr><td colspan=12>.*?</tr>#','',$response_body);
		
		
		//workflow
		$response_body = str_replace('?cmd=AllianceDetail.php/?','?cmd=AllianceDetail&',$response_body);
		$response_body = str_replace('?cmd=AllianceList.php','?cmd=AllianceList',$response_body);
		$response_body = str_replace('?cmd=AllianceInvite.php','?cmd=AllianceInvite',$response_body);
		$response_body = str_replace('?cmd=AllianceNAP.php','?cmd=AllianceNap',$response_body);
		$response_body = str_replace('?cmd=AllianceList/?','?cmd=AllianceList&',$response_body);
	}
}
?>