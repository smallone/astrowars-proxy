<?php
abstract class ProxyFilter
{
	private $_filters = array();
	
	public function addFilter(IProxyFilter $filter)
	{
		$this -> _filters[] = $filter;
	}
	
	public function execute(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		if ($this instanceof IProxyFilter) 
		{
			$return =  $this -> parse($request,$registry,$url_parts,$response_body);
			if ($return > 0) return $return; 
		}
		
		foreach($this -> _filters as $filter)
		{
			$return =  $filter -> execute($request,$registry,$url_parts,$response_body);
			if ($return > 0) return $return; 
		}
	}
}
?>
