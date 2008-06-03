<?php
class ProxyAllianceNapFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table border=0 cellpadding=1 cellspacing=1 width=600>#','<table class="alliance_list" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#(<table class="alliance_list".*?</table>)<br>#si','\\1</td></tr></table>',$response_body);
		$response_body = preg_replace('#<tr><td><center>(.*?)<br>(<table class="alliance_list"[^<]*?>)#si','<tr><td>\\2<tr align=center class="row5" ><td colspan="7">\\1</td></tr>',$response_body);
		$response_body = preg_replace('#<td>(<a[^>]*?><b>Add</b></a></td>)#si','<td colspan="2">\\1',$response_body);
		
		$response_body = str_replace('</td></tr></table></td></tr></table></center>','</td></tr></table></td></tr></table>',$response_body);
		$response_body = str_replace('<td>Established</td>','<td colspan="2">Established</td>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=AllianceRemove_NAP.php?','?cmd=AllianceRemoveNap&',$response_body);
		$response_body = str_replace('?cmd=AllianceAdd.php','?cmd=AllianceAdd',$response_body);
		$response_body = str_replace('?cmd=AllianceNAP.php','?cmd=AllianceNap',$response_body);
		$response_body = str_replace('?cmd=AllianceList.php','?cmd=AllianceList',$response_body);
		$response_body = str_replace('?cmd=AllianceInvite.php','?cmd=AllianceInvite',$response_body);
		$response_body = str_replace('?cmd=AllianceCreate.php','?cmd=Alliance',$response_body);
	}
}
?>