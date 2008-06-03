<?php
class ProxyIndexFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		// layout
		$response_body = preg_replace('#<table width="400" border=0 bgcolor="\#252525">.*?</table>#si','',$response_body);
		$response_body = preg_replace('#<table width="400" border=0 align="center">.*</form>#si','</form>',$response_body);
		$response_body = str_replace('style="background-image','style="margin-top: 10px;border: 1px solid #444;background-image',$response_body);
		$response_body = str_replace('<center>','<center><br /><br /><h1>smallone astrowars proxy</h1>',$response_body);
		
		// workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/register/login.php','?cmd=Login',$response_body);
	}
}
?>