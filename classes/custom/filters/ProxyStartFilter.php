<?php
class ProxyStartFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#(<body[^>]*?>)#','\\1<center>',$response_body);
		$response_body = str_replace('frontpage!','frontpage!<br /><br/><a href="index.php">smallone astrowars proxy</a>',$response_body);
		
		//workflow
		$response_body = str_replace('you can now login on <a href="http://www.astrowars.com/">http://www.astrowars.com</a>','you can now login <b>proxified</b> on <a href="index.php">smallone astrowars proxy</a>',$response_body);
	}
}
?>