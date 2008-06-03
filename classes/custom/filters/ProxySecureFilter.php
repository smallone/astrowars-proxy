<?php
class ProxySecureFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		// layout
		$response_body = str_replace('width="30" alt="Security Mesasure"','width="120" alt="Security Mesasure"',$response_body);
		$response_body = str_replace('<b>Security Measure</b>','<h1>Security Measure</h1>',$response_body);
		$response_body = str_replace(' class="row11"  width="468">',' class="row11" style="text-align: center;width: 350px;border: 1px solid #444;">',$response_body);
		$response_body = str_replace('Enter the characters as they are shown in the box below. <br> This is not your password.','<center>Enter the characters as they are shown in the box below. <br />This is not your password.</center>',$response_body);
		$response_body = str_replace('<td colspan="3">','<td colspan="3" style="width: 350px;">',$response_body);
				
		// workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/secure.php','?cmd=Secure',$response_body);
		
		$response_body = preg_replace('#<form action="[^"]*?" method="post" name="login">#','<form method="post" name="login">',$response_body);
	}
}
?>