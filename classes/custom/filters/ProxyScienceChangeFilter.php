<?php
class ProxyScienceChangeFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = str_replace('<br>'."\r\n".'<table border="0">','<table class="science" cellspacing="1" cellpadding="2">',$response_body);		
		$response_body = str_replace('</form>'."\r\n".'<br>','</form>',$response_body);
				
		//workflow
		$response_body = str_replace('?cmd=ScienceChange.php','?cmd=ScienceChange',$response_body);
		$response_body = str_replace('?cmd=Sciencesubmit.php','?cmd=ScienceSubmit',$response_body);
	}
}
?>