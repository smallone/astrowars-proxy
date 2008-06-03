<?php
class ProxyAlliancesInfoFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<table border=0 cellpadding=1 cellspacing=1 width=600>#','<table class="alliance_list" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#(<table class="alliance_list".*?</table>)<br>#si','\\1</td></tr></table>',$response_body);
		$response_body = preg_replace('#<tr><td><center>(.*?)<br>(<table class="alliance_list"[^<]*?>)#si','<tr><td>\\2<tr align=center class="row5" ><td colspan="7">\\1</td></tr>',$response_body);
		
		$response_body = preg_replace('#</td></tr></table>(To join.*?)<table class="menu"#si','</td></tr><tr align="center"><td class="row5" colspan="7">\\1</td></tr></table></td></tr></table><table class="menu"',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=AllianceAccept.php/?','?cmd=AllianceAccept&',$response_body);
		$response_body = str_replace('?cmd=AllianceNAP.php','?cmd=AllianceNap',$response_body);
		$response_body = str_replace('?cmd=AllianceCreate.php','?cmd=Alliance',$response_body);
		$response_body = str_replace('?cmd=AllianceList.php','?cmd=AllianceList',$response_body);
		$response_body = str_replace('?cmd=AllianceInvite.php','?cmd=AllianceInvite',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);	

		//functions
		$alliance = new Alliance();
		$alliance -> tag = $_GET['tag'];

		ObjectHandler::load($alliance);
				
		$status = new Status();
		$status -> id = $alliance -> id;
		$status -> mode = Status::ALLY;

		$query = new Query();
		$query -> addObject($status,$registry -> tiny_criteria);
			
		foreach(ObjectHandler::collect($query) as $status) { continue; }

		$response_body = str_replace($alliance -> name,'<span class="s'.$status -> state.'">'.$alliance -> name.'</span>',$response_body);

		# add proxy sub menu
		$value = '<a href="javascript:getCmd(\'FeaturePlayersAllyState&tag='.$alliance -> tag.'\',\'content\')"><img src="imports/images/playerstate.png" alt="apply a new status" title="apply a new status" class="menu"></img></a>';
						
		if (in_array($alliance -> id,$registry -> coorperation['alliance'])) $value .= '&nbsp;&nbsp;<a href="javascript:getCmd(\'FeatureFilteredFleets&all=on\',\'content\')"><img src="imports/images/viewfleet.png" alt="view fleets" title="view fleets" class="menu"></img></a>';
		
		$response_body = preg_replace('#<td colspan="7">(.*?)</td>#','<td colspan="7"><div style="float: right;padding-top:2px;padding-right:4px;">'.$value.'</div><div style="float: left;padding-top:8px;width: 500px;">\\1</div>',$response_body);
	}
}
?>