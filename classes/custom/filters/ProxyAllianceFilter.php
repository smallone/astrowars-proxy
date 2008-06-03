<?php
class ProxyAllianceFilter extends ProxyFilter implements IProxyFilter
{
	public function parse(IRequest $request,IRegistry $registry,&$url_parts,&$response_body)
	{
		//layout
		$response_body = preg_replace('#<br><center>(<form.*?</form>)[^<]*?</TABLE>[^<]*?</center>[^<]*?<br>#si','\\1</table></td></tr></table>',$response_body);
		$response_body = preg_replace('#<td>[^<]*?<table class="single" cellspacing="1" cellpadding="2">#','<td class="alliance"><table class="alliance" cellspacing="1" cellpadding="2">',$response_body);
		$response_body = preg_replace('#(<table class="content"[^>]*?><tr><td>)<br><center><table class="content"(.*?)(<table class="menu")#si','\\1<table class="inner_sub_content"\\2</table></td></tr></table>\\3',$response_body);
		
		$response_body = str_replace("\n".'</center>'."\n".'<br>'."\n",'',$response_body);
		$response_body = str_replace('<tr align=center><td colspan="2" class="row4" >','<tr align=center><td colspan="2" class="ally_overview row4">',$response_body);
		
		//workflow
		$response_body = str_replace('?cmd=Alliancesubmit5.php','?cmd=AllianceDisband',$response_body);
		$response_body = str_replace('?cmd=Alliancesubmit2.php','?cmd=AllianceInfoSubmit',$response_body);
		$response_body = str_replace('?cmd=Alliancesubmit4.php','?cmd=AllianceLeaderSubmit',$response_body);
		$response_body = str_replace('?cmd=AllianceIncomings.php','?cmd=AllianceIncomings',$response_body);				
		$response_body = str_replace('?cmd=Alliancesubmit.php','?cmd=AllianceSubmit',$response_body);
		$response_body = str_replace('?cmd=AllianceList.php','?cmd=AllianceList',$response_body);
		$response_body = str_replace('?cmd=AllianceInvite.php','?cmd=AllianceInvite',$response_body);
		$response_body = str_replace('?cmd=AllianceNAP.php','?cmd=AllianceNap',$response_body);
		$response_body = str_replace('?cmd=AllianceCreate.php','?cmd=Alliance',$response_body);
		$response_body = str_replace('?q=http://www1.astrowars.com/0/Player/Profile.php/?','?cmd=PlayerProfile&',$response_body);

		//functions
		if ($registry -> alliance_id > 0)
		{
			$matches = array();
			preg_match_all('#<td>\d+</td><td><a[^>]*?\d+">[^<]*?</a></td>#',$response_body,$matches,PREG_SET_ORDER);
			
			$registry -> members = count($matches);
			
			$response_body = str_replace('</table></td><td class="alliance">','</table><br /><br /><br /><div id="scan"><input type="button" class="smbutton" value="scan all '.$registry -> members.' players" onclick="initAllyScan(\'scan\',\''.$registry -> members.'\')"><br /><br /><small>needs '.($registry -> members * 2).' + x seconds</small></div></td><td class="alliance">',$response_body);

			if ($registry -> settings instanceof Settings  && $registry -> settings -> list_alliance_member)
			{
				$response_body = preg_replace('#(<td>\d+</td><td><a[^>]*?(\d+)">[^<]*?</a></td>)(<td>\d+</td><td><a[^>]*?(\d+)">[^<]*?</a></td>)#','\\1<td><a href="javascript:getCmd(\'FeatureMessagesNew&id=\\2\',\'content\')"><img src="imports/images/mail_new3_small.png" alt="write message" title="write message" class="menu"></img></a></td></tr><tr align=center class="row5">\\3<td><a href="javascript:getCmd(\'FeatureMessagesNew&id=\\4\',\'content\')"><img src="imports/images/mail_new3_small.png" alt="write message" title="write message" class="menu"></img></a></td>',$response_body);
				$response_body = preg_replace('#(<tr align=center class="row5" ><td>\d+</td><td><a[^>]*?(\d+)">[^<]*?</a></td>)</tr>#','\\1<td><a href="javascript:getCmd(\'FeatureMessagesNew&id=\\2\',\'content\')"><img src="imports/images/mail_new3_small.png" alt="write message" title="write message" class="menu"></img></a></td></tr>',$response_body);
				
				$matches = array();
				preg_match_all('#(<tr[^>]*?>[^<]*?<td>\d+</td><td><a[^>]*?\d+">[^<]*?</a></td><td>.*?)</td></tr>#',$response_body,$matches);
				
				foreach ($matches[1] as $key => $value) $response_body = str_replace($value,$value.'<a href="?cmd=AllianceDetail&id='.$key.'"><img class="menu" src="imports/images/all_panel_small.png" alt="view in ally panel" title="view in ally panel"></img></a>',$response_body);

				$response_body = preg_replace('#(<tr[^>]*?>[^<]*?)<td>(\d+</td>)<td>#','\\1<td style="width: 45px;">\\2<td style="width: 153px;">',$response_body);
			}
		}
	}
}
?>