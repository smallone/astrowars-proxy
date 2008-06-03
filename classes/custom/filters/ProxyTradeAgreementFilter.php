<?php
class ProxyTradeAgreementFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#(<form[^>]*?>)<br>(<center>.*?</form>)<br>#si','\\1\\2</td></tr></table>',$response_body);
		$response_body = str_replace('<table class="single" cellspacing="1" cellpadding="2">','<table class="inner_sub_content" cellspacing="1" cellpadding="2">',$response_body);
		
		//worflow
		$response_body = str_replace('?cmd=Tradesubmit5.php','?cmd=TradeAgreementSubmit',$response_body);
		$response_body = str_replace('?cmd=TradeAgreement.php','?cmd=TradeAgreement',$response_body);
		$response_body = str_replace('?cmd=TradeSell.php','?cmd=TradeSell',$response_body);
		$response_body = str_replace('?cmd=TradeBuy.php','?cmd=TradeBuy',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);
	}
}
?>