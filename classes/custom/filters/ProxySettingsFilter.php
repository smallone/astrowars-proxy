<?php
class ProxySettingsFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = str_replace('<tr><td class="row4"  colspan="3">Settings</td></tr>','',$response_body);
		$response_body = str_replace('</table>'."\n".'<br>','</table>',$response_body);
		$response_body = str_replace('<td><br>','<td>',$response_body);	
		$response_body = str_replace('<table border="0" width="100%">','<table class="inner_content" cellspacing="1" cellpadding="3">',$response_body);
		$response_body = str_replace('<td class="row3" >','<td class="row3" style="width: 250px;">',$response_body);
		
		//workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Test.php','?cmd=SettingsTestMail',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Settings.php','?cmd=Settings',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/confirm.php','?cmd=Resign',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Logout.php','?cmd=Logout',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/submit.php','?cmd=SubmitSettings',$response_body);
	}
}
?>