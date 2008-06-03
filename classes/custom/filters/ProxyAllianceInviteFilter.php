<?php
class ProxyAllianceInviteFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#(<br><center>[^<]*?<form[^>]*?>[^<]*?<table class=")single(".*?</form>[^<]*?</TABLE>[^<]*?</center>[^<]*?<br>)#si','\\1ally_add\\2</td></tr></table>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=Alliancesubmit3.php','?cmd=AllianceInvitesubmit',$response_body);
		$response_body = str_replace('?cmd=AllianceList.php','?cmd=AllianceList',$response_body);
		$response_body = str_replace('?cmd=AllianceInvite.php','?cmd=AllianceInvite',$response_body);
		$response_body = str_replace('?cmd=AllianceNAP.php','?cmd=AllianceNap',$response_body);
	}
}
?>