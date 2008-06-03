<?php
class ProxyCustomizeRaceFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#(<body[^>]*?>)#','\\1<center>',$response_body);
		$response_body = preg_replace('#<td>\+([1234])</td>#','<td>/\\1</td>',$response_body);
		$response_body = preg_replace('#<td>\-([1234])</td>#','<td>+\\1</td>',$response_body);
		$response_body = preg_replace('#<td>/([1234])</td>#','<td>-\\1</td>',$response_body);

		
		$response_body = str_replace('<table border="0"><tr valign="top"><td>','<table cellspacing="0" cellpadding="0"  style="text-align: center;background-color: #050505;border: 1px solid #222"><tr valign="top"><td>',$response_body);
		$response_body = str_replace('<table border="0">','<table cellspacing="1" cellpadding="2" style="text-align: center;width: 100%;">',$response_body);
		$response_body = str_replace('value must be zero for all races<br>','value must be zero for all races<br><br>',$response_body);
		$response_body = str_replace('<INPUT TYPE="text"','<INPUT style="text-align: center" TYPE="text"',$response_body);
		$response_body = str_replace('<ul type="square">','<ul type="square" style="text-align: left;">',$response_body);
		$response_body = str_replace('<h1>Create your own race!</h1>','<div style="margin: auto;width: 600px;"><h1>Create your own race!</h1>',$response_body);
		$response_body = str_replace('</FORM>','</FORM></div>',$response_body);
		$response_body = str_replace('auswahl[]','raceid',$response_body);
		
		//workflow
		$response_body = str_replace('<FORM name="race" method="post">','<FORM name="race" action="index.php" method="get"><input type="hidden" name="cmd" value="Start"><input type="hidden" name="raceid" value="0">',$response_body);
		$response_body = str_replace('name="name2"','name="name"',$response_body);
		$response_body = str_replace('name="id2"','name="id"',$response_body);
		$response_body = str_replace('name="pw2"','name="pw"',$response_body);
	}
}
?>