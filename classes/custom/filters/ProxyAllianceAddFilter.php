<?php
class ProxyAllianceAddFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table border=0 cellpadding=1 cellspacing=1 width=600>#','<table class="alliance_list" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#(<td><form.*?</form></table>)<br></center>#si','\\1</td></tr></table>',$response_body);
		$response_body = preg_replace('#<center>Non Agression Pacts<br>(<table class="alliance_list"[^>]*?>)#','\\1<tr align=center class="row5"><td colspan="6">Non Agression Pact</td></td></tr>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=Alliancesubmitnap.php','?cmd=AllianceNapSubmit',$response_body);
		$response_body = str_replace('?cmd=AllianceNAP.php','?cmd=AllianceNap',$response_body);
		$response_body = str_replace('?cmd=AllianceList.php','?cmd=AllianceList',$response_body);
		$response_body = str_replace('?cmd=AllianceInvite.php','?cmd=AllianceInvite',$response_body);
	}
}
?>