<?php
class ProxyMapFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = str_replace('border="1"></a>','style="border: 1px solid orange"></a>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=MapDetail.php/?','?cmd=MapDetail&',$response_body);
		$response_body = str_replace('?cmd=MapCoordinates.php','?cmd=MapCoordinates',$response_body);
	}
}
?>