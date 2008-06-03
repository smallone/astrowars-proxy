<?php
class ProxyColorFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		// layout
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?181818[^\s>]?#i',' class="row1" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?282828[^\s>]?#i',' class="row2" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?404040[^\s>]?#i',' class="row3" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?202060[^\s>]?#i',' class="row4" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?303030[^\s>]?#i',' class="row5" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?101010[^\s>]?#i',' class="row6" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?000000[^\s>]?#i',' class="row7" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?206060[^\s>]?#i',' class="row8" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?602020[^\s>]?#i',' class="row9" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?305050[^\s>]?#i',' class="row10" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?202020[^\s>]?#i',' class="row11" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?894900[^\s>]?#i',' class="row12" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?603030[^\s>]?#i',' class="row13" ', $response_body);
		$response_body = & preg_replace('#\s*bgcolor\s*=[^\#>]*?\#?606000[^\s>]?#i',' class="row14" ', $response_body);
			
		$response_body = & str_replace('this.style.backgroundColor="#206060"','this.className="row4"', $response_body);
		$response_body = & str_replace('this.style.backgroundColor="#404040"','this.className="row3"', $response_body);
		$response_body = & str_replace('this.style.backgroundColor="#404040"','this.className="row9"', $response_body);
		$response_body = & str_replace('this.style.backgroundColor="#303030"','this.className="row5"', $response_body);
	}
}
?>