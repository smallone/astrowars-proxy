<?php
class ProxyDonationFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table class="content" cellspacing="0" cellpadding="0">([^<]*?<tr align=center>)#','<table class="inner_sub_content" cellspacing="1" cellpadding="2">\\1',$response_body);
		$response_body = preg_replace('#<td>[^<]*?<table class="single" cellspacing="1" cellpadding="2">#','<td class="donation"><table class="donation" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#<center>#','<br /><br /><center>',$response_body);
		$response_body = preg_replace('#</center>#','</center><br />',$response_body);
		$response_body = preg_replace('#(<small>We use)#i','<br /><br />\\1',$response_body);
		$response_body = preg_replace('#</select>#','</select><br /><br />',$response_body);
		$response_body = str_replace('<td><br>','<td>',$response_body);	
		$response_body = str_replace('</table>'."\r\n".'<br>','</table>',$response_body);
		$response_body = str_replace('</table> <br>','</table>',$response_body);		
		$response_body = str_replace('<br /><br /><center>','<center>',$response_body);
		
		//workflow
		$response_body = preg_replace('#action="[^"]*?"#','action="https://www.paypal.com/cgi-bin/webscr"',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Settings.php','?cmd=Settings',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Logout.php','?cmd=Logout',$response_body);
		
	}
}
?>