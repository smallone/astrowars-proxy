<?php
class ProxyTradeFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table class="content" cellspacing="0" cellpadding="0">([^<]*?<tr align=center>)#','<table class="inner_sub_content" cellspacing="0" cellpadding="2">\\1',$response_body);
		$response_body = preg_replace('#</TABLE></td></tr>[^<]*?</table>[^<]*?<br>#','</TABLE></td></tr></table></td></tr></table>',$response_body);
		$response_body = preg_replace('#<td>[^<]*?<table class="single" cellspacing="1" cellpadding="2">#','<td class="trade"><table class="trade" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#<br>[^<]*?(<table class="inner_sub_content" cellspacing="0" cellpadding="2">[^<]*?<tr align=center><td colspan="2") class="row4"#','\\1 class="row4 trade_header"',$response_body);

		//workflow
		$response_body = str_replace('?cmd=TradeAgreement.php','?cmd=TradeAgreement',$response_body);
		$response_body = str_replace('?cmd=TradeArtifacts.php','?cmd=TradeArtifacts',$response_body);
		$response_body = str_replace('?cmd=TradeSell.php','?cmd=TradeSell',$response_body);
		$response_body = str_replace('?cmd=TradeBuy.php','?cmd=TradeBuy',$response_body);
		$response_body = str_replace('?cmd=Tradeprices.txt','?cmd=TradePricesTxt',$response_body);
		$response_body = str_replace('?cmd=Tradeprices.xls','?cmd=TradePricesXls',$response_body);
				
		//features
		$response_body = preg_replace('#(</td></tr></table>[^<]*?<table class="menu" cellspacing="1" cellpadding="0">[^<]*?<td>)#','<center><div id="trade"></div></center>\\1',$response_body);

		$matches = array();
		preg_match_all('#<td>&nbsp;<a href="[^<]*?Stats/([^\.]*?)\.html">([^<]*?)</a></td>#',$response_body,$matches,PREG_SET_ORDER);
		
		foreach ($matches as $trade) $response_body = str_replace($trade[0],'<td>&nbsp;<a href="javascript:getTradeStat(\''.$trade[1].'\',\'trade\')">'.$trade[2].'</a></td>',$response_body);			
	}
}
?>