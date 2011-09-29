<?php   
//+----------------------------------------------
//| Auto cheking new messages from DB and notify
//| By: Koudanshi
//| Date: 21-May-2004  
//+----------------------------------------------
include ("./../mainfile.php");
$act = 'chkmsg';

if ( isset($HTTP_POST_VARS['act']) ) {
	$act = trim($HTTP_POST_VARS['act']);
} elseif ( isset($HTTP_GET_VARS['act']) ) {
	$act = trim($HTTP_GET_VARS['act']);
}

if ($xoopsUser) {
  if ($act=="chkmsg")
  {

//    list($new_msg) = $xoopsDB->fetchRow($xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("ipb_messages")." WHERE recipient_id = '".$xoopsUser->getVar("uid")."' AND vid='in' AND read_state='0' "));
	if ($isbb){
    	list($new_msg) = $xoopsDB->fetchRow($xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("users")." WHERE uid = '".$xoopsUser->getVar("uid")."' AND new_msg<>0 "));
    } else {
		$pm_handler =& xoops_gethandler('privmessage');
		$criteria = new CriteriaCompo(new Criteria('read_msg', 0));
		$criteria->add(new Criteria('to_userid', $xoopsUser->getVar('uid')));
		$new_msg = $pm_handler->getCount($criteria);
	}
    if($new_msg) {
      ?>
       <script language='JavaScript' type="text/javascript">
         window.open('chkmsg.php?act=show','NewPM','width=250,height=110,resizable=no,scrollbars=no'); 
       //-->
       </script>    
      <?   
    }//End popup notify
  }
  
  ?><script>setTimeout("top.autoupdate.location.reload()", 60*1000)</script><?
    
  if ($act=="show"){
	if ($isbb){
	    list($new_msg) = $xoopsDB->fetchRow($xoopsDB->query("SELECT COUNT(*) FROM ".$xoopsDB->prefix("ipb_messages")." WHERE recipient_id = '".$xoopsUser->getVar("uid")."' AND vid='in' AND read_state='0' "));
	    $url=ICMS_URL."/modules/ipboard/index.php?s=&act=Msg&CODE=01";
    } else {
		$pm_handler =& xoops_gethandler('privmessage');
		$criteria = new CriteriaCompo(new Criteria('read_msg', 0));
		$criteria->add(new Criteria('to_userid', $xoopsUser->getVar('uid')));
		$new_msg = $pm_handler->getCount($criteria);
		$url=ICMS_URL."/viewpmsg.php";
	}
    
    if (strtolower($xoopsConfig['language'])=="vietnamese"){
      define("LANG_TITLE","Thông báo tin nhắn");
      define("LANG_CONTENT","Chào ".$xoopsUser->getVar('uname')."<br><br>Bạn có <b><font color=\"red\">".$new_msg."</font></b> tin nhắn mới, bạn có thể <a target=\"_blank\" href='".$url."'>nhấn vào đây</a> để xem.<br><br><a href=\"javascript:window.close();\">Đóng cửa sổ này</a>");
      
    } else {
      define("LANG_TITLE","Message notification");
      define("LANG_CONTENT","Hello ".$xoopsUser->getVar('uname')."<br><br>You have <b><font color=\"red\">".$new_msg."</font></b> new messages, you can <a target=\"_blank\" href='".$url."'>click here</a> to read.<br><br><a href=\"javascript:window.close();\">Close this window?</a>");
    }    
    ?>
      <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"> 
      <html xml:lang="en" lang="en" xmlns="http://www.w3.org/1999/xhtml"> 
       <head> 
      	<link rel="stylesheet" type="text/css" media="all" href="../xoops.css" />
        <link rel="stylesheet" type="text/css" media="all" href="../themes/default/styleNN.css" />
        <meta http-equiv="content-type" content="text/html; charset=utf-8" /> 
        <title><? echo LANG_TITLE ?></title>
       </head>
       <body>
       <table class="outer">
         <tr><td class="odd">
         <div style='text-align:left; font-size:8pt'>
           <? echo LANG_CONTENT ?>
         </div>
         </td><tr>       
       </table> 
       <BGSOUND SRC="#" ID="beep" AUTOSTART="TRUE">
       <script>
         document.all.beep.src='msg.wav';
         setTimeout("self.close()",60*1000)
       </script> 
       </body>
      </html>    
    <?
  }//End show new window    
}//End is user

?>