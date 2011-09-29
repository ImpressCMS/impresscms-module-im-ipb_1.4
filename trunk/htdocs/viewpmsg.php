<?php
/**
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license		http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package		core
* @since		XOOPS
* @author		http://www.xoops.org The XOOPS Project
* @version	$Id$
*/
/**
* Im-IPB 1.4 ImpressCMS CORE UPDATE
* ========================================
* Original XOOPS Modifications by Koudanshi
* @author		Koudanshi <koudanshi@gmx.net>
* Homepage: bbpixel.com
* ========================================
* ImpressCMS CORE Modifications
* @package	module	Im-IPB 1.4
* @author		Vaughan Montgomery <vaughan@impresscms.org>
* @version	1.4.0
* @Compatible	ImpressCMS 1.1.2
*/
$xoopsOption['pagetype'] = "pmsg";
include_once "mainfile.php";

if(!is_object($xoopsUser))
{
	$errormessage = _PM_SORRY.'<br />'._PM_PLZREG.'';
	redirect_header('user.php',2,$errormessage);
}
else
{
	// Im-IPB Core edit - use Im-IPB PM system
	$uid = intval($_GET['uid']);
	if($isbb)
	{
		@header('Location: '.ICMS_URL.'/modules/ipboard/index.php?act=Msg&CODE=01');
		exit();
	}
	// End of Im-IPB Core edit
	$pm_handler = xoops_gethandler('privmessage');
	if(isset($_POST['delete_messages']) && isset($_POST['msg_id']))
	{
		if(!$GLOBALS['xoopsSecurity']->check())
		{
			echo implode('<br />', $GLOBALS['xoopsSecurity']->getErrors());
			exit();
		}
		$size = count($_POST['msg_id']);
		$msg = $_POST['msg_id'];
		for($i = 0; $i < $size; $i++)
		{
			$pm = $pm_handler->get($msg[$i]);
			if($pm->getVar('to_userid') == $xoopsUser->getVar('uid')) {$pm_handler->delete($pm);}
			unset($pm);
		}
		redirect_header('viewpmsg.php',1,_PM_DELETED);
	}
	include ICMS_ROOT_PATH.'/header.php';
	$criteria = new Criteria('to_userid', intval($xoopsUser->getVar('uid')));
	$criteria->setOrder('DESC');
	$pm_arr = $pm_handler->getObjects($criteria);
	echo "<h4 style='text-align:center;'>". _PM_PRIVATEMESSAGE ."</h4><br /><a href='userinfo.php?uid=". intval($xoopsUser->getVar('uid'))."'>". _PM_PROFILE ."</a>&nbsp;<span style='font-weight:bold;'>&raquo;&raquo;</span>&nbsp;". _PM_INBOX ."<br /><br />";
	echo "<form name='prvmsg' method='post' action='viewpmsg.php'>";
	echo "<table border='0' cellspacing='1' cellpadding='4' width='100%' class='outer'>\n";
	echo "<tr align='center' valign='middle'><th><input name='allbox' id='allbox' onclick='xoopsCheckAll(\"prvmsg\", \"allbox\");' type='checkbox' value='Check All' /></th><th><img src='images/download.gif' alt='' border='0' /></th><th>&nbsp;</th><th>". _PM_FROM ."</th><th>". _PM_SUBJECT ."</th><th align='center'>". _PM_DATE ."</th></tr>\n";
	$total_messages = count($pm_arr);
	if($total_messages == 0)
	{
		echo "<tr><td class='even' colspan='6' align='center'>"._PM_YOUDONTHAVE."</td></tr> ";
		$display = 0;
	}
	else {$display = 1;}
	for($i = 0; $i < $total_messages; $i++)
	{
		$class = ($i % 2 == 0) ? 'even' : 'odd';
		echo "<tr align='left' class='$class'><td valign='top' width='2%' align='center'><input type='checkbox' id='msg_id[]' name='msg_id[]' value='".$pm_arr[$i]->getVar('msg_id')."' /></td>\n";
		if($pm_arr[$i]->getVar('read_msg') == 1) {echo "<td valign='top' width='5%' align='center'>&nbsp;</td>\n";}
		else {echo "<td valign='top' width='5%' align='center'><img src='images/read.gif' alt='"._PM_NOTREAD."' /></td>\n";}
		echo "<td valign='top' width='5%' align='center'><img src='images/subject/".$pm_arr[$i]->getVar('msg_image', 'E')."' alt='' /></td>\n";
		$postername = XoopsUser::getUnameFromId($pm_arr[$i]->getVar('from_userid'));
		echo "<td valign='middle' width='10%'>";
		// no need to show deleted users
		if($postername) {echo "<a href='userinfo.php?uid=".intval($pm_arr[$i]->getVar('from_userid'))."'>".$postername."</a>";}
		else {echo $xoopsConfig['anonymous'];}
		echo "</td>\n";
		echo "<td valign='middle'><a href='readpmsg.php?start=".intval(($total_messages-$i-1)),"&amp;total_messages=".intval($total_messages)."'>".$pm_arr[$i]->getVar('subject')."</a></td>";
		echo "<td valign='middle' align='center' width='20%'>".formatTimestamp($pm_arr[$i]->getVar('msg_time'))."</td></tr>";
	}
	
	if($display == 1)
	{
		echo "<tr class='foot' align='left'><td colspan='6' align='left'><input type='button' class='formButton' onclick='javascript:openWithSelfMain(\"".ICMS_URL."/pmlite.php?send=1\",\"pmlite\",800,680);' value='"._PM_SEND."' />&nbsp;<input type='submit' class='formButton' name='delete_messages' value='"._PM_DELETE."' />".$GLOBALS['xoopsSecurity']->getTokenHTML()."</td></tr></table></form>";
	}
	else
	{
		echo "<tr class='bg2' align='left'><td colspan='6' align='left'><input type='button' class='formButton' onclick='javascript:openWithSelfMain(\"".ICMS_URL."/pmlite.php?send=1\",\"pmlite\",800,680);' value='"._PM_SEND."' /></td></tr></table></form>";
	}
	include 'footer.php';
}
?>