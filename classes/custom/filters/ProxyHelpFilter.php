<?php
class ProxyHelpFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<td>(<a[^>]*>Latest</a>)#','<td class="first">\\1',$response_body);

		$response_body = str_replace('<tr align="center"><td colspan="2" class="row4" >Help</td></tr>','',$response_body);
		$response_body = str_replace('<td class="row3"  width="190">','<td class="first row3">',$response_body);
		$response_body = str_replace('<td class="row3" >','<td class="first row3">',$response_body);
		$response_body = str_replace('<table class="inner_content" cellspacing="1" cellpadding="0">','<table class="inner_content" cellspacing="1" cellpadding="3">',$response_body);
		$response_body = str_replace('<td><br>','<td>',$response_body);
		$response_body = str_replace('</table>'."\n".'<br>','</table>',$response_body);
		$response_body = str_replace('</td></tr>'."\n".'</td></tr></table>','</td></tr></table></td></tr></table>',$response_body);		
				
		//workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Settings.php','?cmd=Settings',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/settings.php','?cmd=Settings',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Logout.php','?cmd=Logout',$response_body);
	}
}
?>