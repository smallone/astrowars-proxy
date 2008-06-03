<?php
class ProxyGlobalFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		// layout
		$doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
		$css = '<link rel="stylesheet" type="text/css" href="css/black/style_new.css"></link>'."\n";
		$js = '<script src="proxy.js" type="text/JavaScript"></script><script src="jquery.js" type="text/JavaScript"></script>'."\n";
		$charset = '<meta http-equiv="content-type" content="text/html; charset=utf-8"></meta>'."\n";

		$response_body = preg_replace('#^.*?<html#si',$doctype.'<html',$response_body);
		$response_body = preg_replace('#<noscript>.*?</noscript>#si','',$response_body);

		$response_body = preg_replace('#<\s*/\s*head[^>]*?>#',$charset.$css.$js.'</head>',$response_body);
		$response_body = preg_replace('#<\s*style[^>]*?>.*?<\s*/\s*style\s*>#si','', $response_body);

		$info = '<span class="label">proxy:</span><span class="value">'.$_SERVER['HTTP_HOST'].'</span><span class="label">ip:</span><span class="value">'.$_SERVER['REMOTE_ADDR'].'</span>';
		$info .= (!is_null($registry -> user_name)) ? ' <span class="label">user:</span><span class="value">'.$registry -> user_name.'</span>' : ' <span class="label">user:</span><span class="value">?</span>';
		$info .= (!is_null($registry -> user_id)) ? ' <span class="label">id:</span><span class="value">'.$registry -> user_id.'</span>' : ' <span class="label">id:</span><span class="value">?</span>';
		$info .= (!is_null($registry -> clicks)) ? ' <span class="label">clicks:</span><span class="value" id="clicks">'.$registry -> clicks.'</span>' : ' <span class="label">clicks:</span><span class="value">?</span>';
		
		if (!is_null($registry -> login)) 
		{
			# re-save when countdown is over
			if ($registry -> login < time() - 16 * 60)
			{
				$update = new  PlayerUpdate();
				$update -> player = $registry -> user_id;
				$update -> alliance = $registry -> alliance_id;

				ObjectHandler::load($update);
				
				$update -> time = time();
								
				ObjectHandler::save($update);

				$registry -> login = $update -> time;
			}    		
			
			$time = floor((time() - $registry -> login)/60);
			if ($time == 0) 
			{
				$time = (time() - $registry -> login);
				$unit = 'sec';
			}
			else $unit = 'min';
			
			$info .= ' <span class="label">idle:</span><span class="value" id="idle_time">'.$time.' '.$unit.'</span><input type="hidden" name="last_update" id="last_update" value="'.$registry -> login.'"></input>';
		}	
		
		if (preg_match('#<title>.*?-(.*?)</title>#', $response_body,$hits))
		{
			$hits = split(':',$hits[1]);
		
			$diff = $hits[0] - gmdate('H',time());
			if ($diff < - 12) $diff += 24;
						
			$registry -> gmt = $diff;
			
			$diff = date('H',time()) - gmdate('H',time());
			if ($diff < - 12) $diff += 24;
			
			$registry -> server_gmt = $diff;

			$info .= '<span class="label">aw time:</span><span class="value"><span id="h">'.$hits[0].'</span>:<span id="m">'.$hits[1].'</span>:<span id="s">'.$hits[2].'</span>';
		} 
		else if(preg_match('#<font color="\#FF0000" size="5"><b>Please Login Again.</b></font>#',$response_body))
		{
			$url = '?';
			$count = 0;
			
			foreach($request -> getParameterNames() as $key)
			{
				if (!preg_match('#COOKIE#',$key) &&  !preg_match('#PHPSESSID#',$key))
				{
					if ($count > 0) $url .= '&';
					$url .= $key.'='.$request -> $key;
					
					$count++;
				}
			}
			
			if ($url != '?') $registry -> redirect = $url;
			
			header('Location: index.php');
			exit;
		}
		 
		// header advertisement
		$response_body = preg_replace('#<body[^>]*?>([^<]*?<center>)(.*?<script.*?|.*?<iframe.*?)<br><br>[^<]*?<table#si','<body onload="StartClock(\'h\',\'m\',\'s\');">\\1<div class="full_aw_container_top" id="container"><div class="aw_add_header" id="header_bar"><div class="inner_aw_add_header">\\2</div></div><div class="proxy_info">'.$info.'</div><div class="inner_aw_container"><table', $response_body);
		
		//premium header
		$response_body = preg_replace('#<body[^>]*?>[^<]*?<center>[^<]*?<br><br>[^<]*?<table#si','<body onload="StartClock(\'h\',\'m\',\'s\');"><center><div class="proxy_info">'.$info.'</div><div class="inner_aw_container"><table', $response_body);
		
		$response_body = str_replace('nowrap','', $response_body);
		$response_body = str_replace('<meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1">','',$response_body);

		//Glossary links
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Glossary//?','?cmd=Glossary&',$response_body);
		$response_body = preg_replace('#(<a href="[^>]*?cmd=Glossary&id=\d+"[^>]*?)>#','\\1 class="glossary">',$response_body);

		//footer add
		$response_body = str_replace('<table cellpadding="3" width="100%" cellspacing="0" border="0">','<table cellpadding="3" width="150" height="75" cellspacing="0" border="0">',$response_body); 
	}
}
?>