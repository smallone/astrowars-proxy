<?php
class ProxyTradeArtifactsFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#(<form[^>]*?>)(<br><center>[^<]*?<table class=")single(".*?</form><br>)#si','\\1\\2trade_arte\\3</td></tr></table>',$response_body);
		$response_body = str_replace('</TABLE><input type="submit" value="Use it!" class=smbutton></center>','<tr><td colspan="4" class="row4"><input type="submit" value="Use it!" class=smbutton></center></td></tr></table>',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=Tradesubmit3.php','?cmd=TradeArtifactSubmit',$response_body);
		$response_body = str_replace('?cmd=TradeAgreement.php','?cmd=TradeAgreement',$response_body);
		$response_body = str_replace('?cmd=TradeSell.php','?cmd=TradeSell',$response_body);
		$response_body = str_replace('?cmd=TradeBuy.php','?cmd=TradeBuy',$response_body);
	}
}
?>