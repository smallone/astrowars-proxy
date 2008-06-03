<?php
class ProxyResignFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#!!!+#','',$response_body);
		
		$response_body = str_replace('</table>'."\n".'<br>','</table>',$response_body);
		$response_body = str_replace('<td><br>','<td>',$response_body);
		$response_body = str_replace('<h1>','<center><span style="color: red;"><h1>',$response_body);
		$response_body = str_replace('<br>'."\n".'<br><br>','<br /><br /></span></center>',$response_body);
				
		//workflow
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/resign.php?','?cmd=ResignSubmit&',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Settings.php','?cmd=Settings',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/News/Logout.php','?cmd=Logout',$response_body);
	}
}
?>