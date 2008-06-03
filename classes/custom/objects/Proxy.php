<?php
class Proxy
{
	private $url = null;
	private $http_host = null;
	private $script_url = null;
	private $script_base = null;
	private $content_type = null;
	private $content_length = null;
	private $content_disp = null;
	
	public function Proxy($url,$build_url = true)
	{
		if ($build_url)
		{
			$url_extra = null;
			foreach ($_GET as $key => $value) if ($key != 'cmd') $url_extra .= (is_null($url_extra)) ? '?'.$key.'='.$value : '&'.$key.'='.$value;
			$url .= $url_extra;
		}
			
		$this -> http_host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
		$this -> script_url = 'http' . ((isset($_ENV['HTTPS']) && $_ENV['HTTPS'] == 'on') || $_SERVER['SERVER_PORT'] == 443 ? 's' : '') . '://' . $this -> http_host . ($_SERVER['SERVER_PORT'] != 80 && $_SERVER['SERVER_PORT'] != 443 ? ':' . $_SERVER['SERVER_PORT'] : '') . $_SERVER['PHP_SELF'];
		$this -> script_base = substr($this -> script_url, 0, strrpos($this -> script_url, '/')+1);
		$this -> parser = new ProxyParser($url);
		
		$this -> url = $url;
	}
	
	public function execute(IRequest $request, IResponse $response, IRegistry $registry,IProxyFilter $filter = null,$display = true,$redirect = false)
	{
			$url_parts = array();
			
			if ($this -> parser -> parseUrl($request,$url_parts))
			{
				$response_headers = $response_keys = array();
				
				$request_header = $this -> setRequestHeader($url_parts);
				
				$socket = $this -> getSocket($request_header,$url_parts);
				$this -> readResponseHeader($request,$response,$registry,$socket,$url_parts,$response_headers,$response_keys,$redirect);
				
				$response_body = $this -> readSocket($socket);
				
				if ($response_headers['content-type'][0] == 'text/html') $response_body = utf8_encode($response_body);
				
				$this -> parser -> parseBody($response_body,$url_parts,$this -> script_url,$this -> content_type);

				if ($filter instanceof ProxyFilter) $filter -> execute($request,$registry,$url_parts,$response_body);
				
				if ($display)
				{
					$response_keys['content-disposition'] = 'Content-Disposition';
					$response_headers['content-disposition'][0] = empty($this -> content_disp) ? ($this -> content_type == 'application/octet_stream' ? 'attachment' : 'inline') . '; filename="' . $url_parts['file'] . '"' : $this -> content_disp;
					$response_keys['content-length'] = 'Content-Length';
					$response_keys['content-length'][0] = strlen($response_body);    
					$response_headers   = array_filter($response_headers);
					$response_keys      = array_filter($response_keys);
									
					header(array_shift($response_keys));
					array_shift($response_headers);
									
					foreach ($response_headers as $name => $array) 
					{
						foreach ($array as $value)  $response -> addHeader($response_keys[$name],$value);
					}
					$response -> write($response_body);
				}	
			}
			else $response -> addHeader('Location','?');
	}
	
	// sets the request header informations
	public function &setRequestHeader(&$url_parts)
	{
		$header  = $_SERVER['REQUEST_METHOD'].' '. $url_parts['path'];
		
		if (isset($url_parts['query']))
		{
		    $header .= '?';
		    $query = preg_split('#([&;])#', $url_parts['query'], -1, PREG_SPLIT_DELIM_CAPTURE);
		    for ($i = 0, $count = count($query); $i < $count; $header .= implode('=', array_map('urlencode', array_map('urldecode', explode('=', $query[$i])))) . (isset($query[++$i]) ? $query[$i] : ''), $i++);
		}

		$header .= " HTTP/1.0\r\n";
		$header .= 'Host: ' . $url_parts['host'] . $url_parts['port_ext'] . "\r\n";
		$header .= 'X-Forwarded-For: '. $_SERVER['REMOTE_ADDR']."\r\n";
		$header .= 'Remote-Addr: '. $_SERVER['REMOTE_ADDR']."\r\n";

		if (isset($_SERVER['HTTP_USER_AGENT'])) $header .= 'User-Agent: ' . $_SERVER['HTTP_USER_AGENT'] . "\r\n";

		if (isset($_SERVER['HTTP_ACCEPT'])) $header .= 'Accept: ' . $_SERVER['HTTP_ACCEPT'] . "\r\n";
		else  $header .= "Accept: */*;q=0.1\r\n";

		// cookies
		if (!empty($_COOKIE))
		{
			$cookie  = '';
		    
		    foreach ($_COOKIE as $cookie_id => $cookie_content)
       	 	{
            	$cookie_id      = explode(';', rawurldecode($cookie_id));
            	$cookie_content = explode(';', rawurldecode($cookie_content));
    
           	 	if ($cookie_id[0] === 'COOKIE')
           	 	{
            	    $cookie_id[3] = str_replace('_', '.', $cookie_id[3]); //stupid PHP can't have dots in var names

            	    if (count($cookie_id) < 4 || ($cookie_content[1] == 'secure' && $url_parts['scheme'] != 'https')) continue;
            	    if ((preg_match('#\Q' . $cookie_id[3] . '\E$#i', $url_parts['host']) || strtolower($cookie_id[3]) == strtolower('.' . $url_parts['host'])) && preg_match('#^\Q' . $cookie_id[2] . '\E#', $url_parts['path']))  $cookie .= ($cookie != '' ? '; ' : '') . (empty($cookie_id[1]) ? '' : $cookie_id[1] . '=') . $cookie_content[0];

            	}
        	}
	    
		    if ($cookie != '') $header .= 'Cookie: '.$cookie."\r\n";
		 }

		 if ($_SERVER['REQUEST_METHOD'] == 'POST')
		 {
		 	$post_body = null;
		 	
		 	if (!empty($_FILES) && ini_get('file_uploads'))
		    {
		    	$data_boundary = '----' . md5(uniqid(rand(), true));
		        $array = $this -> setPostVars($_POST);
		    
		        foreach ($array as $key => $value)
		        {
		        	$post_body .= "--{$data_boundary}\r\n";
		            $post_body .= "Content-Disposition: form-data; name=\"$key\"\r\n\r\n";
		            $post_body .= urldecode($value) . "\r\n";
		        }
		            
		        $array = $this -> setPostFiles($_FILES);
		    
		        foreach ($array as $key => $file_info)
		        {
		        	$post_body .= "--{$data_boundary}\r\n";
		            $post_body .= "Content-Disposition: form-data; name=\"$key\"; filename=\"{$file_info['name']}\"\r\n";
		            $post_body .= 'Content-Type: ' . (empty($file_info['type']) ? 'application/octet-stream' : $file_info['type']) . "\r\n\r\n";
		    
		            if (is_readable($file_info['tmp_name']))
		            {
		            	$handle = fopen($file_info['tmp_name'], 'rb');
		                $post_body .= fread($handle, filesize($file_info['tmp_name']));
		                fclose($handle);
		             }
		                
		             $post_body .= "\r\n";
		         }
		            
		         $post_body       .= "--{$data_boundary}--\r\n";
		         $header .= "Content-Type: multipart/form-data; boundary={$data_boundary}\r\n";
		         $header .= "Content-Length: " . strlen($post_body) . "\r\n\r\n";
		         $header .= $post_body;
		    }
		    else
		    {
		    	$array = $this -> setPostVars($_POST);
		            
		        foreach ($array as $key => $value)
		        {
		        	$post_body .= !empty($post_body) ? '&' : '';
		            $post_body .= $key . '=' . $value;
		        }

		        $header .= "Content-Type: application/x-www-form-urlencoded\r\n";
		        $header .= "Content-Length: " . strlen($post_body) . "\r\n\r\n";
		        $header .= $post_body;
		        $header .= "\r\n";
		    }
		        
			$post_body = '';
		}
		else $header .= "\r\n";

		return $header;
	}
	
	// reads the response header informations
	public function readResponseHeader(IRequest $request, IResponse $response,IRegistry $registry, &$socket,&$url_parts,&$response_headers,&$response_keys,$redirect)
	{
		if (!is_null($registry -> user_id) && $url_parts['host'] === 'www1.astrowars.com') $registry -> clicks++;
				
		$line = fgets($socket, 8192);
		    
		while (strspn($line, "\r\n") !== strlen($line))
		{
			@list($name, $value) = explode(':', $line, 2);
		    $name = trim($name);
		    $response_headers[strtolower($name)][] = trim($value);
		    $response_keys[strtolower($name)] = $name;
		    $line = fgets($socket, 8192);
		}
		    
		sscanf(current($response_keys), '%s %s', $http_version, $response_code);
		    
		if (isset($response_headers['content-type']))  list($this -> content_type, ) = explode(';', str_replace(' ', '', strtolower($response_headers['content-type'][0])), 2);

		if (isset($response_headers['content-length']))
		{
			$this -> content_length = $response_headers['content-length'][0];
		    unset($response_headers['content-length'], $response_keys['content-length']);
		}
		if (isset($response_headers['content-disposition']))
		{
			$this -> content_disp = $response_headers['content-disposition'][0];
		    unset($response_headers['content-disposition'], $response_keys['content-disposition']);
		}
		
	    if (isset($response_headers['set-cookie']))
	    {
	        foreach ($response_headers['set-cookie'] as $cookie)
	        {
	            $name = $value = $expires = $path = $domain = $secure = $expires_time = '';
	
	            preg_match('#^\s*([^=;,\s]*)\s*=?\s*([^;]*)#',  $cookie, $match) && list(, $name, $value) = $match;
	            preg_match('#;\s*expires\s*=\s*([^;]*)#i',      $cookie, $match) && list(, $expires)      = $match;
	            preg_match('#;\s*path\s*=\s*([^;,\s]*)#i',      $cookie, $match) && list(, $path)         = $match;
	            preg_match('#;\s*domain\s*=\s*([^;,\s]*)#i',    $cookie, $match) && list(, $domain)       = $match;
	            preg_match('#;\s*(secure\b)#i',                 $cookie, $match) && list(, $secure)       = $match;
	    
	            $expires_time = empty($expires) ? 0 : intval(@strtotime($expires));
	            $expires = (!empty($expires) && time() - $expires_time < 0) ? '' : $expires;
	            $path    = empty($path)   ? '/' : $path;
	                
	            if (empty($domain)) $domain = $url_parts['host'];
	            else
	            {
	                $domain = '.' . strtolower(str_replace('..', '.', trim($domain, '.')));
	    
	   	            if ((!preg_match('#\Q' . $domain . '\E$#i', $url_parts['host']) && $domain != '.' . $url_parts['host']) || (substr_count($domain, '.') < 2 && $domain{0} == '.'))  continue;
	   	        }
	            if (count($_COOKIE) >= 15 && time()-$expires_time <= 0)  $set_cookie[] = $this -> addCookie(current($_COOKIE), '',1);
	            
	            #AW LOGIN
	            if ($name == 'c_user')  $registry -> user_name = urldecode($value);
	            else if ($name == 'login') 
	            {
	            	$registry -> user_id = urldecode($value);
	            	$registry -> clicks = 0;
					
	            	$player = new Player();
	            	$player -> id = $registry -> user_id;
	            	
	            	ObjectHandler::load($player);
	            	
	            	$registry -> alliance_id = $player -> alliance;
	            	
	            	$settings = new Settings();
	            	$settings -> player = $player -> id;
	            	
	            	ObjectHandler::load($settings);
	            	
	            	# Settings generieren
	            	if (is_null($settings -> time))
	            	{
	            		$settings -> time = time();
	            		ObjectHandler::save($settings);
	            		ObjectHandler::load($settings);

	            		$message = new Message();
	            		$message -> send_to = $player -> id;
	            		$message -> send_from = 0;
	            		$message -> time = time();
	            		$message -> text = "hiho,\n\nwelcome to this Astrowars proxy! Please have a look at \n\n'Settings' -> 'manage your proxy settings'\n\n to set your specific proxy settings. Have fun using this Proxy :P";
	            		$message -> subject = 'Welcome!';
	            		
	            		ObjectHandler::save($message);
	            	
	            	}
	            	
	            	$registry -> settings = $settings; 
	            	
	            	# Criteria generieren
	            	$c1 = new Criteria();
							
					if ($registry -> alliance_id > 0)
					{
						$c1 -> addInclude('id2',$registry -> alliance_id);
						$c1 -> addInclude('mode',Cooperation::SINGLE_ALLY);
			
						$c2 = new Criteria();
						$c2 -> addInclude('id2',$registry -> alliance_id);
						$c2 -> addInclude('mode',Cooperation::ALLY_ALLY);
						$c1 -> addObject($c2); 
					}
					else
					{
						$c1 = new Criteria();
						$c1 -> addInclude('id2',$registry -> user_id);
						$c1 -> addInclude('mode',Cooperation::SINGLE_SINGLE);
					
						$c2 = new Criteria();
						$c2 -> addInclude('id2',$registry -> user_id);
						$c2 -> addInclude('mode',Cooperation::ALLY_SINGLE);		
						$c1 -> addObject($c2); 							
					}
				
					$query = new Query();
					$query -> addObject(new Cooperation(),$c1);
			
					$c1 = new Criteria();
					$c1 -> addInclude('player',$registry -> user_id);

					$registry -> coorperation = array('player' => array(),'alliance' => array());
					$coorp =  $registry -> coorperation;
					
					if ($registry -> alliance_id > 0)
					{
						$coorp['alliance'][] = $registry -> alliance_id;
						
						$c2 = new Criteria();
						$c2 -> addInclude('alliance',$registry -> alliance_id);
						
						$c1 -> addObject($c2);
					}
					
					$registry -> tiny_criteria = clone $c1;
					$temp_ally_switch = null; // wenn keine Ally gesetzt aber ein Sharing existiert (immer erstes)
					
					foreach (ObjectHandler::collect($query) as $coorperation)
					{
						switch($coorperation -> mode)
						{
							case Cooperation::SINGLE_SINGLE:
									
								$coorperation_check = new Cooperation();
								$coorperation_check -> id1 = $registry -> user_id;
								$coorperation_check -> id2 = $coorperation -> id1;
								$coorperation_check -> mode = Cooperation::SINGLE_SINGLE;
								
								ObjectHandler::load($coorperation_check);
									
								if (!is_null($coorperation_check -> time))
								{
									$coorp['player'][] = $coorperation -> id1;	
																
									$c2 = new Criteria();
									$c2 -> addInclude('player',$coorperation -> id1);
									$c1 -> addObject($c2); 
									
									$c3 = new Criteria();
									$c3 -> addInclude('id',$coorperation -> id1);
								}	 
									
							break;
							case Cooperation::ALLY_SINGLE:
								
								$coorperation_check = new Cooperation();
								$coorperation_check -> id1 = $registry -> user_id;
								$coorperation_check -> id2 = $coorperation -> id1;
								$coorperation_check -> mode = Cooperation::SINGLE_ALLY;
								
								ObjectHandler::load($coorperation_check);

								if (!is_null($coorperation_check -> time))
								{
									$coorp['alliance'][] = $coorperation -> id1;
								
									$c2 = new Criteria();
									$c2 -> addInclude('alliance',$coorperation -> id1);
									
									$c1 -> addObject($c2);
									
									if ($registry -> alliance_id == 0)
									{
										if (is_null($temp_ally_switch) || !($temp_ally_switch instanceof Cooperation)) $temp_ally_switch =  $coorperation_check;
										else if ($coorperation_check -> time < $temp_ally_switch -> time) $temp_ally_switch = $coorperation_check;
									}
							}	 
							break;
							case Cooperation::SINGLE_ALLY:
								
								$coorperation_check = new Cooperation();
								$coorperation_check -> id1 = $registry -> alliance_id;
								$coorperation_check -> id2 = $coorperation -> id1;
								$coorperation_check -> mode = Cooperation::ALLY_SINGLE;
								
								ObjectHandler::load($coorperation_check);

								if (!is_null($coorperation_check -> time))
								{
									$coorp['player'][] = $coorperation -> id1;
									
									$c2 = new Criteria();
									$c2 -> addInclude('player',$coorperation -> id1);
									$c1 -> addObject($c2); 
									
									$c3 = new Criteria();
									$c3 -> addInclude('id',$coorperation -> id1);
								}	 
							break;
							case Cooperation::ALLY_ALLY:

								$coorperation_check = new Cooperation();
								$coorperation_check -> id1 = $registry -> alliance_id;
								$coorperation_check -> id2 = $coorperation -> id1;
								$coorperation_check -> mode = Cooperation::ALLY_ALLY;
								
								ObjectHandler::load($coorperation_check);

								if (!is_null($coorperation_check -> time))
								{
									$coorp['alliance'][] = $coorperation -> id1;
										
									$c2 = new Criteria();
									$c2 -> addInclude('alliance',$coorperation -> id1);
									
									$c1 -> addObject($c2);
								}	 
							break;
						}
					}
					
					// Ally switch
					if ($registry -> alliance_id == 0 && $temp_ally_switch instanceof Cooperation) $registry -> alliance_id = $temp_ally_switch -> id2;
										
					$registry -> coorperation = $coorp;
					$registry -> criteria = $c1;
					
					$query = new Query();
					$query -> addObject(new PlayerUpdate(),$registry -> criteria);
					
					$player_criteria = new Criteria();
					$player_criteria -> addInclude('id',$registry -> user_id);
					
					foreach(ObjectHandler::collect($query) as $update) 	
					{
						$player_criteria2 = new Criteria();
						$player_criteria2 -> addInclude('id',$update -> player);
						
						$player_criteria -> addObject($player_criteria2);
					}					
					
					$query = new Query();
					$query -> addObject(new Player,$player_criteria);
					
					$registry -> players_criteria = ObjectHandler::collect($query) ;

					# add login
					$login = new Login();
					$login-> player = $registry -> user_id;
					$login -> time = time();
					$login -> ip_address = $request -> getHeader('REMOTE_ADDR');
					$login -> user_agent = $request -> getHeader('HTTP_USER_AGENT');
					$login -> alliance = $registry -> alliance_id;
					
					ObjectHandler::save($login);
					
					# read / save idle time
	            	$update = new PlayerUpdate();
					$update -> player = $registry -> user_id;

					ObjectHandler::load($update);

					if ($update -> time < time() - 16 * 60)
					{
						$update -> time = time();
						$update -> alliance = $registry -> alliance_id;
					    
						ObjectHandler::save($update);

	            		$registry -> login = $update -> time;
	            		
					} else $registry -> login = $update -> time;
					
	            	// redirect set by AW on login
	            	if (!is_null($registry -> redirect)) 
	            	{
	            		$response -> addHeader('Location',$registry -> redirect);
	            		$registry -> redirect = null;
	            	}	
	            	else $response -> addHeader('Location','?cmd=News');
	            	
	            	$starmap = new Starmap();
	            	$starmap -> id = 1;
	            	
	            	ObjectHandler::load($starmap);
	            	
	            	if (is_null($starmap -> name)) $registry -> system_preload = true;
	            }

	            $set_cookie[] = $this -> addCookie("COOKIE;$name;$path;$domain", "$value;$secure", $expires_time);
	        }
	    }
	    
		if (isset($response_headers['set-cookie'])) unset($response_headers['set-cookie'], $response_keys['set-cookie']);
	    if (!empty($set_cookie))
	    {
	    	$response_keys['set-cookie'] = 'Set-Cookie';
	        $response_headers['set-cookie'] = $set_cookie;
	    }
	    	    
	    if (isset($response_headers['p3p']) && preg_match('#policyref\s*=\s*[\'"]?([^\'"\s]*)[\'"]?#i', $response_headers['p3p'][0], $matches))  $response_headers['p3p'][0] = str_replace($matches[0], 'policyref="' . $this -> parser -> completeUrl($matches[1],$url_parts,$this -> script_url) . '"', $response_headers['p3p'][0]);
	    if (isset($response_headers['refresh']) && preg_match('#([0-9\s]*;\s*URL\s*=)\s*(\S*)#i', $response_headers['refresh'][0], $matches)) $response_headers['refresh'][0] = $matches[1] . $this -> parser -> completeUrl($matches[2],$url_parts,$this -> script_url);
	    if (isset($response_headers['uri']))  $response_headers['uri'][0] = $this -> parser -> completeUrl($response_headers['uri'][0],$url_parts,$this -> script_url);
	    if (isset($response_headers['content-location'])) $response_headers['content-location'][0] = $this -> parser -> completeUrl($response_headers['content-location'][0],$url_parts,$this -> script_url);
	    if (isset($response_headers['connection'])) unset($response_headers['connection'], $response_keys['connection']);
	    if (isset($response_headers['keep-alive'])) unset($response_headers['keep-alive'], $response_keys['keep-alive']);
	
		if (isset($response_headers['location']) && !$redirect)  unset($response_headers['location']);
	    
	}
	
	// connects to the specyfied Url
	private function &getSocket(&$request_header,&$url_parts)
	{
		if ($socket = @fsockopen(($url_parts['scheme'] === 'https' && xtension_loaded('openssl') ? 'ssl://' : 'tcp://') . $url_parts['host'], $url_parts['port'], $err_no, $err_str, 30))
		{
			fwrite($socket, $request_header);
		}
		else 
		{
			die('Connect to "'.$this -> url.'" failed');
		}
			
		return $socket;
	}
	
	// reads data from socket and closes the socket
	private function readSocket(&$socket)
	{
		$response_body = null;

		while ($data = @fread($socket, 8192)) $response_body .= $data;
		fclose($socket);
		
		return $response_body;
	}
	
	// adds a cookie
	private function addCookie($name, $value, $expires = 0)
	{
	   	return rawurlencode(rawurlencode($name)) . '=' . rawurlencode(rawurlencode($value)) . (empty($expires) ? '' : '; expires=' . gmdate('D, d-M-Y H:i:s \G\M\T', $expires)) . '; path=/; domain=.' . $this -> http_host;
	}
		
	private function setPostVars(&$array, $parent_key = null)
	{
	    $temp = array();
	
	    foreach ($array as $key => $value)
	    {
	        $key = isset($parent_key) ? sprintf('%s[%s]', $parent_key, urlencode($key)) : urlencode($key);
	        if (is_array($value)) $temp = array_merge($temp,$this -> setPostVars($value, $key));
	        else $temp[$key] = urlencode($value);
	    }
	    
	    return $temp;
	}
		
	private function setPostFiles(&$array, $parent_key = null)
	{
	    $temp = array();
	
	    foreach ($array as $key => $value)
	    {
	        $key = isset($parent_key) ? sprintf('%s[%s]', $parent_key, urlencode($key)) : urlencode($key);
	        if (is_array($value))  $temp = array_merge_recursive($temp, $this -> setPostFiles($value, $key));
	        else if (preg_match('#^([^\[\]]+)\[(name|type|tmp_name)\]#', $key, $m))  $temp[str_replace($m[0], $m[1], $key)][$m[2]] = $value;
	    }
	
	    return $temp;
	}
}
?>