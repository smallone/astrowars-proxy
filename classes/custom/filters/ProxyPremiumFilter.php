<?php
class ProxyPremiumFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table class="content" cellspacing="0" cellpadding="0">([^<]*?<tr align=center>)#','<table class="inner_sub_content" cellspacing="1" cellpadding="2">\\1',$response_body);
		$response_body = preg_replace('#<td>[^<]*?<table class="single" cellspacing="1" cellpadding="2">#','<td class="premium"><table class="premium" cellspacing="0" cellpadding="0">',$response_body);
		$response_body = preg_replace('#<td>[^<]*?</td>#','',$response_body);
		$response_body = preg_replace('#(<small>We use)#i','<br /><br />\\1',$response_body);	
		$response_body = str_replace('<td><br>','<td>',$response_body);	
		$response_body = str_replace('</table> <br>','</table>',$response_body);	
		
		//workflow
		$response_body = preg_replace('#action="[^"]*?"#','action="https://www.paypal.com/cgi-bin/webscr"',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Premium/','?cmd=Premium',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Settings.php','?cmd=Settings',$response_body);
		$response_body = str_replace('?cmd=PremiumBioScan.php','?cmd=PremiumBioScan',$response_body);
	}
}
?>