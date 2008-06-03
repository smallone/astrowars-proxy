<?php
class ProxyParser
{
	const url = 'q';
    const form_name = '____pgfa';
    const basic_auth = '____pbavn';
    
    private $url = null;
    
    static $tags = array
		    (
		        'a'          => array('href'),
		        'img'        => array('src', 'longdesc'),
		        'image'      => array('src', 'longdesc'),
		        'body'       => array('background'),
		        'base'       => array('href'),
		        'frame'      => array('src', 'longdesc'),
		        'iframe'     => array('src', 'longdesc'),
		        'head'       => array('profile'),
		        'layer'      => array('src'),
		        'input'      => array('src', 'usemap'),
		        'form'       => array('action'),
		        'area'       => array('href'),
		        'link'       => array('href', 'src', 'urn'),
		        'meta'       => array('content'),
		        'param'      => array('value'),
		        'applet'     => array('codebase', 'code', 'object', 'archive'),
		        'object'     => array('usermap', 'codebase', 'classid', 'archive', 'data'),
		        'script'     => array('src'),
		        'select'     => array('src'),
		        'hr'         => array('src'),
		        'table'      => array('background'),
		        'tr'         => array('background'),
		        'th'         => array('background'),
		        'td'         => array('background'),
		        'bgsound'    => array('src'),
		        'blockquote' => array('cite'),
		        'del'        => array('cite'),
		        'embed'      => array('src'),
		        'fig'        => array('src', 'imagemap'),
		        'ilayer'     => array('src'),
		        'ins'        => array('cite'),
		        'note'       => array('src'),
		        'overlay'    => array('src', 'imagemap'),
		        'q'          => array('cite'),
		        'ul'         => array('src')
		    );

	public function ProxyParser($url)
	{
		$this -> url = $url;
	}
		    
    public function parseUrl(IRequest $request,&$url_parts)
    {
		if ($request -> hasParameter(self::form_name))
		{
		    $url  = $this -> decodeUrl($request -> {self::form_name});
		    $qstr = strpos($url, '?') !== false ? (strpos($url, '?') === strlen($url)-1 ? '' : '&') : '?';
		    $arr  = explode('&', $_SERVER['QUERY_STRING']);
		    
		    if (preg_match('#^\Q' . $request -> {self::form_name} . '\E#', $arr[0])) array_shift($arr);
		    
		    $url .= $qstr . implode('&', $arr);
		}
		else $url = $this -> decodeUrl($this -> url);
		
		// add http://
	    if (strpos($url, '://') === false)  $url = 'http://' . $url;
	    
	    // get URL-parts
		$this -> splitRequest($url,$url_parts);
		
		return true;
    }
    
    public function parseBody(&$response_body,&$url_parts,&$script_url,&$content_type)
    {
    	if ($content_type == 'text/css')  $response_body = $this -> proxifyCss($response_body,$url_parts,$script_url);
   		else
   		{
		    preg_match_all('#(<\s*style[^>]*>)(.*?)(<\s*/\s*style[^>]*>)#is', $response_body, $matches, PREG_SET_ORDER);
		    for ($i = 0, $count_i = count($matches); $i < $count_i; ++$i) $response_body = str_replace($matches[$i][0], $matches[$i][1]. $this -> proxifyCss($matches[$i][2],$url_parts,$script_url).$matches[$i][3],$response_body);
		    
		    preg_match_all("#<\s*([a-zA-Z\?-]+)([^>]+)>#S", $response_body, $matches);
		    for ($i = 0, $count_i = count($matches[0]); $i < $count_i; ++$i)
		    {
		        if (!preg_match_all("#([a-zA-Z\-\/]+)\s*(?:=\s*(?:\"([^\">]*)\"?|'([^'>]*)'?|([^\s]*)))?#S", $matches[2][$i], $m, PREG_SET_ORDER))  continue;
		        
		        $rebuild    = false;
		        $extra_html = $temp = '';
		        $attrs      = array();
		
		        for ($j = 0, $count_j = count($m); $j < $count_j; $attrs[strtolower($m[$j][1])] = (isset($m[$j][4]) ? $m[$j][4] : (isset($m[$j][3]) ? $m[$j][3] : (isset($m[$j][2]) ? $m[$j][2] : false))), ++$j);
		        
		        if (isset($attrs['style']))
		        {
		            $rebuild = true;
		            $attrs['style'] = $this -> proxifyInlineCss($attrs['style'],$url_parts,$script_url);
		        }
		        
		    	if (isset($attrs['onclick']))
		        {
		            $rebuild = true;
		            $attrs['onclick'] = $this -> proxifyInlineJs($attrs['onclick'],$url_parts,$script_url,false);
		        }
		        
		        $tag = strtolower($matches[1][$i]);
		
		        if (isset(self::$tags[$tag]))
		        {
		            switch ($tag)
		            {
		                case 'a':

		                	if (isset($attrs['href']))
		                	{
		                		$url = $this -> completeUrl($attrs['href'],$url_parts,$script_url,false);
		                		
		                		if (!preg_match('#^javascript:#',$attrs['href']) && preg_match('#www1\.astrowars\.com\/0#',$url) && !preg_match('#http://#',$attrs['href']))
		                    	{
		                    	    $rebuild = true;
		                    	    $attrs['href'] = $this -> completeUrl($attrs['href'],$url_parts,$script_url);
		                    	}
		                   		else 
		                   		{
		                   			$rebuild = true;
		                   			$attrs['href'] = $url;
		                   		}
		                	}

		                    break;
		                case 'img':
		                    if (isset($attrs['src']))
		                    {
		                        $rebuild = true;
		                        if (!preg_match('#.php$#',$attrs['src'])) $attrs['src'] = $this -> completeUrl($attrs['src'],$url_parts,$script_url,false);
		                        else $attrs['src'] = $this -> completeUrl($attrs['src'],$url_parts,$script_url);
		                    }
		                    if (isset($attrs['longdesc']))
		                    {
		                        $rebuild = true;
		                        $attrs['longdesc'] = $this -> completeUrl($attrs['longdesc'],$url_parts,$script_url);
		                    }
		                    break;
		                case 'form':
		                    if (isset($attrs['action']))
		                    {
		                        $rebuild = true;
		                        
		                        if (trim($attrs['action']) === '') $attrs['action'] = $url_parts['path'];

		                        if (!isset($attrs['method']) || strtolower(trim($attrs['method'])) === 'get')
		                        {
		                            $extra_html = '<input type="hidden" name="' . self::form_name . '" value="' . $this -> encodeUrl($this -> completeUrl($attrs['action'],$url_parts,$script_url,false)) . '" />';
		                            $attrs['action'] = '';
		                            break;
		                        }
		                        
		                        $attrs['action'] = $this -> completeUrl($attrs['action'],$url_parts,$script_url);
		                    }
		                    break;
		                case 'base':
		                    if (isset($attrs['href']))
		                    {
		                        $rebuild = true;  
		                        $this -> splitRequest($attrs['href'], $url_parts);
		                        $attrs['href'] = $this -> completeUrl($attrs['href'],$url_parts,$script_url);
		                    }
		                    break;
		                case 'meta':
	
		                    if (isset($attrs['http-equiv'], $attrs['content']) && preg_match('#\s*refresh\s*#i', $attrs['http-equiv']))
		                    {
		                        if (preg_match('#^(\s*[0-9]*\s*;\s*url=)(.*)#i', $attrs['content'], $content))
		                        {                 
		                            $rebuild = true;
		                            $attrs['content'] =  $content[1] . $this -> completeUrl(trim($content[2], '"\''),$url_parts,$script_url);
		                        }
		                    }
		                    break;
		                case 'head':
		                    if (isset($attrs['profile']))
		                    {
		                        $rebuild = true;
		                        $attrs['profile'] = implode(' ', array_map(array($this,'completeUrl'), explode(' ', $attrs['profile']),$url_parts,$script_url));
		                    }
		                    break;
		                case 'applet':
		                    if (isset($attrs['codebase']))
		                    {
		                        $rebuild = true;
		                        $temp = $url_parts;
		                        $this -> splitRequest($this -> completeUrl(rtrim($attrs['codebase'], '/') . '/',$url_parts,$script_url,false), $url_parts);
		                        unset($attrs['codebase']);
		                    }
		                    if (isset($attrs['code']) && strpos($attrs['code'], '/') !== false)
		                    {
		                        $rebuild = true;
		                        $attrs['code'] = $this -> completeUrl($attrs['code'],$url_parts,$script_url);
		                    }
		                    if (isset($attrs['object']))
		                    {
		                        $rebuild = true;
		                        $attrs['object'] = $this -> completeUrl($attrs['object'],$url_parts,$script_url);
		                    }
		                    if (isset($attrs['archive']))
		                    {
		                        $rebuild = true;
		                        $attrs['archive'] = implode(',', array_map(array($this,'completeUrl'), preg_split('#\s*,\s*#', $attrs['archive']),$url_parts,$script_url));
		                    }
		                    if (!empty($temp))  $url_parts = $temp;

		                    break;
		                case 'object':
		                    if (isset($attrs['usemap']))
		                    {
		                        $rebuild = true;
		                        $attrs['usemap'] = $this -> completeUrl($attrs['usemap'],$url_parts,$script_url);
		                    }
		                    if (isset($attrs['codebase']))
		                    {
		                        $rebuild = true;
		                        $temp = $url_parts;
		                        $this -> splitRequest($this -> completeUrl(rtrim($attrs['codebase'], '/') . '/',$url_parts,$script_url, false), $url_parts);
		                        unset($attrs['codebase']);
		                    }
		                    if (isset($attrs['data']))
		                    {
		                        $rebuild = true;
		                        $attrs['data'] = $this -> completeUrl($attrs['data'],$url_parts,$script_url);
		                    }
		                    if (isset($attrs['classid']) && !preg_match('#^clsid:#i', $attrs['classid']))
		                    {
		                        $rebuild = true;
		                        $attrs['classid'] = $this -> completeUrl($attrs['classid'],$url_parts,$script_url);
		                    }
		                    if (isset($attrs['archive']))
		                    {
		                        $rebuild = true;
		                        $attrs['archive'] = implode(' ', array_map(array($this,'completeUrl'), explode(' ', $attrs['archive']),$url_parts,$script_url));
		                    }
		                    if (!empty($temp))  $url_parts = $temp;
	
		                    break;
		                case 'param':
		                    if (isset($attrs['valuetype'], $attrs['value']) && strtolower($attrs['valuetype']) == 'ref' && preg_match('#^[\w.+-]+://#', $attrs['value']))
		                    {
		                        $rebuild = true;
		                        $attrs['value'] = $this -> completeUrl($attrs['value'],$url_parts,$script_url);
		                    }
		                    break;
		                case 'frame':
		                case 'iframe':
		                    if (isset($attrs['src']))
		                    {
		                        $rebuild = true;
		                        $attrs['src'] = $this -> completeUrl($attrs['src'],$url_parts,$script_url,false) . '&nf=1';
		                    }
		                    if (isset($attrs['longdesc']))
		                    {
		                        $rebuild = true;
		                        $attrs['longdesc'] = $this -> completeUrl($attrs['longdesc'],$url_parts,$script_url);
		                    }
		                break;
		               	case 'table':
		                    if (isset($attrs['background']))
		                    {
		                        $rebuild = true;
		                        $attrs['background'] = $this -> completeUrl($attrs['background'],$url_parts,$script_url,false);
		                    }
		                break;
		                break;
		                case 'script':
		                    if (isset($attrs['src']))
		                    {
		                        $rebuild = true;
		                        $attrs['src'] = $this -> completeUrl($attrs['src'],$url_parts,$script_url,false);
		                    }
		                break;
		                default:
		                    foreach (self::$tags[$tag] as $attr)
		                    {
		                        if (isset($attrs[$attr]))
		                        {
		                            $rebuild = true;
		                            $attrs[$attr] = $this -> completeUrl($attrs[$attr],$url_parts,$script_url);
		                        }
		                    }
		                    break;
		            }
		        }
		    
		        if ($rebuild)
		        {
		            $new_tag = "<$tag";
		            foreach ($attrs as $name => $value)
		            {
		                $delim = strpos($value, '"') && !strpos($value, "'") ? "'" : '"';
		                $new_tag .= ' ' . $name . ($value !== false ? '=' . $delim . $value . $delim : '');
		            }
		
		            $response_body = str_replace($matches[0][$i], $new_tag . '>' . $extra_html, $response_body);
		        }
		    }
   		}
    }
    
    // encodes an URL with base64
    private function encodeUrl($url)
    {
    	return $url;
    	#return rawurlencode($url);
    }
    
    // decodes an URL with base64   
    private function decodeUrl($url)
    {
    	return $url;
    	#return str_replace(array('&amp;', '&#38;'), '&', rawurldecode($url));
    }    
    
    // splits a request in its parts (using PHP function "parse_url")
	private function splitRequest($url, &$url_parts)
	{
	    $url_parts = @parse_url($url);
	
	    if (!empty($url_parts))
	    {
	        $path = array();

	        $url_parts['port_ext'] = '';
	        $url_parts['base']     = $url_parts['scheme'] . '://' . $url_parts['host'];
	
	        if (isset($url_parts['port']))  $url_parts['base'] .= $url_parts['port_ext'] = ':' . $url_parts['port'];
	        else $url_parts['port'] = $url_parts['scheme'] === 'https' ? 443 : 80;

	        $url_parts['path'] = isset($url_parts['path']) ? $url_parts['path'] : '/';
	        $url_parts['path'] = explode('/', $url_parts['path']);
	    
	        foreach ($url_parts['path'] as $dir)
	        {
	            if ($dir === '..')  array_pop($path);
	            else if ($dir !== '.')
	            {
	                for ($dir = rawurldecode($dir), $new_dir = '', $i = 0, $count_i = strlen($dir); $i < $count_i; $new_dir .= strspn($dir{$i}, 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789$-_.+!*\'(),?:@&;=') ? $dir{$i} : rawurlencode($dir{$i}), ++$i);
	                $path[] = $new_dir;
	            }
	        }
	
	        $url_parts['path']     = str_replace('//','/',str_replace('/%7E', '/~', '/' . ltrim(implode('/', $path), '/')));
	        $url_parts['file']     = substr($url_parts['path'], strrpos($url_parts['path'], '/')+1);
	        $url_parts['dir']      = substr($url_parts['path'], 0, strrpos($url_parts['path'], '/'));
	        $url_parts['base']    .= $url_parts['dir'];
	        $url_parts['prev_dir'] = substr_count($url_parts['path'], '/') > 1 ? substr($url_parts['base'], 0, strrpos($url_parts['base'], '/')+1) : $url_parts['base'] . '/';
 
	        return true;
	    }
	    
	    return false;
	}
  
	public function completeUrl($url,&$url_parts,&$script_url,$proxify = true)
	{
	    $url = trim($url);
	    
	    if ($url === '') return '';
	    
	    $hash_pos = strrpos($url, '#');
	    $fragment = $hash_pos !== false ? '#' . substr($url, $hash_pos) : '';
	    $sep_pos  = strpos($url, '://');
	    
	    if ($sep_pos === false || $sep_pos > 5)
	    {
	        switch ($url{0})
	        {
	            case '/':
	                $url = substr($url, 0, 2) === '//' ? $url_parts['scheme'] . ':' . $url : $url_parts['scheme'] . '://' . $url_parts['host'] . $url_parts['port_ext'] . $url;
	                break;
	            case '?':
	                $url = $url_parts['base'] . '/' . $url_parts['file'] . $url;
	                break;
	            case '#':
	                $proxify = false;
	                break;
	            case 'm':
	                if (substr($url, 0, 7) == 'mailto:')
	                {
	                    $proxify = false;
	                    break;
	                }
	            default:
	                $url = $url_parts['base'] . '/' . $url;
	        }
	    }
	
	    return $proxify ? $script_url.'?'.self::url.'=' . $this -> encodeUrl($url) . $fragment : $url;
	}
    
	private function proxifyCss($css,&$url_parts,&$script_url)
	{
	   $css = $this -> proxifyInlineCss($css,$url_parts,$script_url);
		   
	   preg_match_all("#@import\s*(?:\"([^\">]*)\"?|'([^'>]*)'?)([^;]*)(;|$)#i", $css, $matches, PREG_SET_ORDER);
	
	   for ($i = 0, $count = count($matches); $i < $count; ++$i)
	   {
	       $delim = '"';
	       $url   = $matches[$i][2];
	
	       if (isset($matches[$i][3]))
	       {
	           $delim = "'";
	           $url = $matches[$i][3];
	       }
	
	       $css = str_replace($matches[$i][0], '@import ' . $delim . $this -> proxifyCssUrl($matches[$i][1],$url_parts,$script_url) . $delim . (isset($matches[$i][4]) ? $matches[$i][4] : ''), $css);
	   }
	   
	   return $css;
	}
	
	private function proxifyCssUrl($url,&$url_parts,&$script_url)
	{
	    $url   = trim($url);
	    $delim = strpos($url, '"') === 0 ? '"' : (strpos($url, "'") === 0 ? "'" : '');
	
	    return $delim . preg_replace('#([\(\),\s\'"\\\])#', '\\$1', $this -> completeUrl(trim(preg_replace('#\\\(.)#', '$1', trim($url, $delim))),$url_parts,$script_url,false)) . $delim;
	}
	
	private function proxifyInlineCss($css,&$url_parts,&$script_url)
	{
	    preg_match_all('#url\s*\(\s*(([^)]*(\\\))*[^)]*)(\)|$)?#i', $css, $matches, PREG_SET_ORDER);
	    for ($i = 0, $count = count($matches); $i < $count; ++$i)  $css = str_replace($matches[$i][0], 'url(' . $this -> proxifyCssUrl($matches[$i][1],$url_parts,$script_url) . ')', $css);

		return $css;
	}
	
	private function proxifyInlineJs($js,&$url_parts,&$script_url)
	{
		preg_match_all('#href\s*=\s*(.+)#i', $js, $matches, PREG_SET_ORDER);
		
	    for ($i = 0, $count = count($matches); $i < $count; ++$i)  $js = str_replace($matches[$i][0], 'href=' . $this -> proxifyJsUrl($matches[$i][1],$url_parts,$script_url), $js);

		return $js;
	}
	
	private function proxifyJsUrl($url,&$url_parts,&$script_url)
	{
	    $url   = trim($url);
	    $delim = strpos($url, '"') === 0 ? '"' : (strpos($url, "'") === 0 ? "'" : '');
	
	    return $delim . preg_replace('#([\(\),\s\'"\\\])#', '\\$1', $this -> completeUrl(trim(preg_replace('#\\\(.)#', '$1', trim($url, $delim))),$url_parts,$script_url)) . $delim;
	}
}
?>