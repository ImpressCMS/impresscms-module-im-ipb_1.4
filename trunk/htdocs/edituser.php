<?php
/**
* @copyright	http://www.xoops.org/ The XOOPS Project
* @copyright	XOOPS_copyrights.txt
* @copyright	http://www.impresscms.org/ The ImpressCMS Project
* @license	http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License (GPL)
* @package	core
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
/**
* Generates form and validation for editing users
* @package kernel 
* @subpackage users
*/
$xoopsOption['pagetype'] = 'user';
include 'mainfile.php';
include_once ICMS_ROOT_PATH.'/class/xoopsformloader.php';

// If not a user, redirect
if(!is_object($xoopsUser)) {redirect_header('index.php',3,_US_NOEDITRIGHT);}

$op = (isset($_GET['op']))?trim(filter_input(INPUT_GET, $_GET['op'])):((isset($_POST['op']))?trim(filter_input(INPUT_GET, $_POST['op'])):'editprofile');

$config_handler = xoops_gethandler('config');
$xoopsConfigUser = $config_handler->getConfigsByCat(XOOPS_CONF_USER);

if($op == 'saveuser')
{
	if(!$GLOBALS['xoopsSecurity']->check())
	{
		redirect_header('index.php',3,_US_NOEDITRIGHT.'<br />'.implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
	}
	$uid = 0;
	if(!empty($_POST['uid'])) {$uid = intval($_POST['uid']);}
	if(empty($uid) || $xoopsUser->getVar('uid') != $uid) {redirect_header('index.php',3,_US_NOEDITRIGHT);}
		
	$errors = array();
	$myts = MyTextSanitizer::getInstance();
	
	if($xoopsConfigUser['allow_chgmail'] == 1)
	{
		$email = '';
		if(!empty($_POST['email'])) {$email = $myts->stripSlashesGPC(trim($_POST['email']));}
		if($email == '' || !checkEmail($email)) {$errors[] = _US_INVALIDMAIL;}
	}
	$username = xoops_getLinkedUnameFromId($uid);
	$password = '';
	if(!empty($_POST['password'])) {$password = $myts->stripSlashesGPC(trim($_POST['password']));}
	if($password !== '' && $_POST['change_pass'] == 1)
	{
		if(strlen($password) < $xoopsConfigUser['minpass']) {$errors[] = sprintf(_US_PWDTOOSHORT,$xoopsConfigUser['minpass']);}
		$vpass = '';
		if(!empty($_POST['vpass'])) {$vpass = $myts->stripSlashesGPC(trim($_POST['vpass']));}
		if($password != $vpass) {$errors[] = _US_PASSNOTSAME;}
		if($password == $username || $password == icms_utf8_strrev($username, true) || strripos($password, $username) === true)
		{
			$errors[] = _US_BADPWD;
		}
	}
	if(count($errors) > 0)
	{
		include ICMS_ROOT_PATH.'/header.php';
		echo '<div>';
		foreach($errors as $er) {echo '<span style="color: #ff0000; font-weight: bold;">'.$er.'</span><br />';}
		echo '</div><br />';
		$op = 'editprofile';
	}
	else
	{
		$member_handler = xoops_gethandler('member');
		$edituser = $member_handler->getUser($uid);
		$edituser->setVar('name', $_POST['name']);
		if($xoopsConfigUser['allow_chgmail'] == 1) {$edituser->setVar('email', $email, true);}
		$edituser->setVar('url', formatURL($_POST['url']));
		$edituser->setVar('user_icq', $_POST['user_icq']);
		$edituser->setVar('user_from', $_POST['user_from']);
		$edituser->setVar('openid', isset($_POST['openid']) ? trim($_POST['openid']) : '');
		if($xoopsConfigUser['allwshow_sig'] == 1)
		{
			if($xoopsConfigUser['allow_htsig'] == 0)
			{
				$signature = strip_tags($myts->xoopsCodeDecode($_POST['user_sig'], 1));
				$edituser->setVar('user_sig', xoops_substr($signature, 0, intval($xoopsConfigUser['sig_max_length'])));
			}
			else
			{
				$signature = $myts->displayTarea($_POST['user_sig'], 1, 1, 1, 1, 1, 'display');
				$edituser->setVar('user_sig', xoops_substr($signature, 0, intval($xoopsConfigUser['sig_max_length'])));
			}
		}
		$user_viewemail = (!empty($_POST['user_viewemail'])) ? 1 : 0;
		$edituser->setVar('user_viewemail', $user_viewemail);
		$user_viewoid = (!empty($_POST['user_viewoid'])) ? 1 : 0;
		$edituser->setVar('user_viewoid', $user_viewoid);
		$edituser->setVar('user_aim', $_POST['user_aim']);
		$edituser->setVar('user_yim', $_POST['user_yim']);
		$edituser->setVar('user_msnm', $_POST['user_msnm']);
		if($password != '')
		{
			$salt = icms_createSalt();
			$edituser->setVar('salt', $salt, true);
			$edituser->setVar('enc_type', $xoopsConfigUser['enc_type'], true);
			$pass = icms_encryptPass($password, $salt);
			$edituser->setVar('pass', $pass, true);
		}
		$attachsig = !empty($_POST['attachsig']) ? 1 : 0;
		$edituser->setVar('attachsig', $attachsig);
		$edituser->setVar('timezone_offset', $_POST['timezone_offset']);
		$edituser->setVar('uorder', $_POST['uorder']);
		$edituser->setVar('umode', $_POST['umode']);
		$edituser->setVar('notify_method', $_POST['notify_method']);
		$edituser->setVar('notify_mode', $_POST['notify_mode']);
		$edituser->setVar('bio', xoops_substr($_POST['bio'], 0, 255));
		$edituser->setVar('user_occ', $_POST['user_occ']);
		$edituser->setVar('user_intrest', $_POST['user_intrest']);
		$edituser->setVar('user_mailok', $_POST['user_mailok']);
		if(isset($_POST['theme_selected']))
		{
			$edituser->setVar('theme', $_POST['theme_selected']);
			$_SESSION['xoopsUserTheme'] = $_POST['theme_selected'];
			$xoopsConfig['theme_set'] = $_SESSION['xoopsUserTheme'];
		}
		else {$edituser->setVar('theme', $xoopsConfig['theme_set']);}
		if(!empty($_POST['usecookie'])) {setcookie($xoopsConfig['usercookie'], $xoopsUser->getVar('uname'), time()+ 31536000);}
		else {setcookie($xoopsConfig['usercookie']);}
		if(!$member_handler->insertUser($edituser))
		{
			include ICMS_ROOT_PATH.'/header.php';
			echo $edituser->getHtmlErrors();
			include ICMS_ROOT_PATH.'/footer.php';
		}
		else {redirect_header('userinfo.php?uid='.$uid, 1, _US_PROFUPDATED);}
		exit();
	}
}

if($op == 'editprofile')
{
	// Im-IPB Core edit - use ICMS CP or Im-IPB
	if($isbb && !$INFO['xbbc_ucp'] && isset($uid)) {@header('location: '.ICMS_URL.'/modules/ipboard/index.php?act=UserCP&CODE=04');}
	elseif($isbb && !$INFO['xbbc_ucp']) {@header('location: '.ICMS_URL.'/modules/ipboard/index.php?act=UserCP&CODE=01');}
	// End of Im-IPB Core edit

	include_once ICMS_ROOT_PATH.'/header.php';
	include_once ICMS_ROOT_PATH.'/include/comment_constants.php';
	echo '<a href="userinfo.php?uid='.intval($xoopsUser->getVar('uid')).'">'._US_PROFILE.'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'._US_EDITPROFILE.'<br /><br />';
	$form = new XoopsThemeForm(_US_EDITPROFILE, 'userinfo', 'edituser.php', 'post', true);
	$uname_label = new XoopsFormLabel(_US_NICKNAME, $xoopsUser->getVar('uname'));
	$form->addElement($uname_label);
	$name_text = new XoopsFormText(_US_REALNAME, 'name', 30, 60, $xoopsUser->getVar('name', 'E'));
	$form->addElement($name_text);
	$email_tray = new XoopsFormElementTray(_US_EMAIL, '<br />');
	if($xoopsConfigUser['allow_chgmail'] == 1)
	{
		$email_text = new XoopsFormText('', 'email', 30, 255, $xoopsUser->getVar('email'));
	}
	else {$email_text = new XoopsFormLabel('', $xoopsUser->getVar('email'));}
	$email_tray->addElement($email_text);
	$email_cbox_value = $xoopsUser->user_viewemail() ? 1 : 0;
	$email_cbox = new XoopsFormCheckBox('', 'user_viewemail', $email_cbox_value);
	$email_cbox->addOption(1, _US_ALLOWVIEWEMAIL);
	$email_tray->addElement($email_cbox);
	$form->addElement($email_tray);
	
	$icmsauthConfig = $config_handler->getConfigsByCat(XOOPS_CONF_AUTH);
	if($icmsauthConfig['auth_openid'] == 1)
	{
		$openid_tray = new XoopsFormElementTray(_US_OPENID_FORM_CAPTION, '<br />');
		$openid_text = new XoopsFormText('', 'openid', 30, 255, $xoopsUser->getVar('openid'));
		$openid_tray->setDescription(_US_OPENID_FORM_DSC);
		$openid_tray->addElement($openid_text);
		$openid_cbox_value = $xoopsUser->user_viewoid() ? 1 : 0;
		$openid_cbox = new XoopsFormCheckBox('', 'user_viewoid', $openid_cbox_value);
		$openid_cbox->addOption(1, _US_ALLOWVIEWEMAILOPENID);
		$openid_tray->addElement($openid_cbox);
		$form->addElement($openid_tray);
	}
	$url_text = new XoopsFormText(_US_WEBSITE, 'url', 30, 100, $xoopsUser->getVar('url', 'E'));
	$form->addElement($url_text);
	$timezone_select = new XoopsFormSelectTimezone(_US_TIMEZONE, 'timezone_offset', $xoopsUser->getVar('timezone_offset'));
	$icq_text = new XoopsFormText(_US_ICQ, 'user_icq', 15, 15, $xoopsUser->getVar('user_icq', 'E'));
	$aim_text = new XoopsFormText(_US_AIM, 'user_aim', 18, 18, $xoopsUser->getVar('user_aim', 'E'));
	$yim_text = new XoopsFormText(_US_YIM, 'user_yim', 25, 25, $xoopsUser->getVar('user_yim', 'E'));
	$msnm_text = new XoopsFormText(_US_MSNM, 'user_msnm', 30, 100, $xoopsUser->getVar('user_msnm', 'E'));
	$location_text = new XoopsFormText(_US_LOCATION, 'user_from', 30, 100, $xoopsUser->getVar('user_from', 'E'));
	$occupation_text = new XoopsFormText(_US_OCCUPATION, 'user_occ', 30, 100, $xoopsUser->getVar('user_occ', 'E'));
	$interest_text = new XoopsFormText(_US_INTEREST, 'user_intrest', 30, 150, $xoopsUser->getVar('user_intrest', 'E'));
	include_once 'include/xoopscodes.php';
	if($xoopsConfigUser['allwshow_sig'] == 1)
	{
		if($xoopsConfigUser['allow_htsig'] == 0)
		{
			$sig_tray = new XoopsFormElementTray(_US_SIGNATURE, '<br />');
			$sig_tarea = new XoopsFormTextArea('', 'user_sig', $xoopsUser->getVar('user_sig', 'E'));
			$sig_tray->addElement($sig_tarea);
			$sig_cbox_value = $xoopsUser->getVar('attachsig') ? 1 : 0;
			$sig_cbox = new XoopsFormCheckBox('', 'attachsig', $sig_cbox_value);
			$sig_cbox->addOption(1, _US_SHOWSIG);
			$sig_tray->addElement($sig_cbox);
		}
		else
		{
			$sig_tray = new XoopsFormElementTray(_US_SIGNATURE, '<br />');
			$sig_tarea = new XoopsFormDhtmlTextArea('', 'user_sig', $xoopsUser->getVar('user_sig', 'E'));
			$sig_tray->addElement($sig_tarea);
			$sig_cbox_value = $xoopsUser->getVar('attachsig') ? 1 : 0;
			$sig_cbox = new XoopsFormCheckBox('', 'attachsig', $sig_cbox_value);
			$sig_cbox->addOption(1, _US_SHOWSIG);
			$sig_tray->addElement($sig_cbox);
		}
	}
	$umode_select = new XoopsFormSelect(_US_CDISPLAYMODE, 'umode', $xoopsUser->getVar('umode'));
	$umode_select->addOptionArray(array('nest'=>_NESTED, 'flat'=>_FLAT, 'thread'=>_THREADED));
	$uorder_select = new XoopsFormSelect(_US_CSORTORDER, 'uorder', $xoopsUser->getVar('uorder'));
	$uorder_select->addOptionArray(array(XOOPS_COMMENT_OLD1ST => _OLDESTFIRST, XOOPS_COMMENT_NEW1ST => _NEWESTFIRST));
	$selected_theme = new XoopsFormSelect(_US_SELECT_THEME, 'theme_selected' , $xoopsUser->theme() );
	foreach($xoopsConfig['theme_set_allowed'] as $theme) {$selected_theme->addOption($theme, $theme);}
	$selected_language = new XoopsFormSelect(_US_SELECT_LANG, 'language_selected', $xoopsUser->language());
	include_once(ICMS_ROOT_PATH.'/class/xoopslists.php');
	foreach(XoopsLists::getLangList() as $language) {$selected_language->addOption($language, $language);}
	// RMV-NOTIFY
	// TODO: add this to admin user-edit functions...
	include_once ICMS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/notification.php';
	include_once ICMS_ROOT_PATH.'/include/notification_constants.php';
	$notify_method_select = new XoopsFormSelect(_NOT_NOTIFYMETHOD, 'notify_method', $xoopsUser->getVar('notify_method'));
	$notify_method_select->addOptionArray(array(XOOPS_NOTIFICATION_METHOD_DISABLE=>_NOT_METHOD_DISABLE, XOOPS_NOTIFICATION_METHOD_PM=>_NOT_METHOD_PM, XOOPS_NOTIFICATION_METHOD_EMAIL=>_NOT_METHOD_EMAIL));
	$notify_mode_select = new XoopsFormSelect(_NOT_NOTIFYMODE, 'notify_mode', $xoopsUser->getVar('notify_mode'));
	$notify_mode_select->addOptionArray(array(XOOPS_NOTIFICATION_MODE_SENDALWAYS=>_NOT_MODE_SENDALWAYS, XOOPS_NOTIFICATION_MODE_SENDONCETHENDELETE=>_NOT_MODE_SENDONCE, XOOPS_NOTIFICATION_MODE_SENDONCETHENWAIT=>_NOT_MODE_SENDONCEPERLOGIN));
	$bio_tarea = new XoopsFormTextArea(_US_EXTRAINFO, 'bio', $xoopsUser->getVar('bio', 'E'));
	$cookie_radio_value = empty($_COOKIE[$xoopsConfig['usercookie']]) ? 0 : 1;
	$cookie_radio = new XoopsFormRadioYN(_US_USECOOKIE, 'usecookie', $cookie_radio_value, _YES, _NO);
	$pwd_change_radio = new XoopsFormRadioYN(_US_CHANGE_PASSWORD, 'change_pass', 0, _YES, _NO);
	$pwd_change_radio->setExtra('onchange="initQualityMeter(this.value);"');
	$passConfig = $config_handler->getConfigsByCat(2);
	if($passConfig['pass_level'] <= 20) {$pwd_text = new XoopsFormPassword('', 'password', 10, 255);}
	else	{include_once ICMS_ROOT_PATH.'/include/passwordquality.php';}
	$pwd_text2 = new XoopsFormPassword('', 'vpass', 10, 255);
	$pwd_tray = new XoopsFormElementTray(_US_PASSWORD.'<br />'._US_TYPEPASSTWICE);
	$pwd_tray->addElement($pwd_text);
	$pwd_tray->addElement($pwd_text2);
	$mailok_radio = new XoopsFormRadioYN(_US_MAILOK, 'user_mailok', intval($xoopsUser->getVar('user_mailok')));
	$salt_hidden = new XoopsFormHidden('salt', $xoopsUser->getVar('salt'));
	$uid_hidden = new XoopsFormHidden('uid', intval($xoopsUser->getVar('uid')));
	$op_hidden = new XoopsFormHidden('op', 'saveuser');
	$submit_button = new XoopsFormButton('', 'submit', _US_SAVECHANGES, 'submit');
	
	$form->addElement($timezone_select);
	$form->addElement($icq_text);
	$form->addElement($aim_text);
	$form->addElement($yim_text);
	$form->addElement($msnm_text);
	$form->addElement($location_text);
	$form->addElement($occupation_text);
	$form->addElement($interest_text);
	$form->addElement($sig_tray);
	if(count($xoopsConfig['theme_set_allowed']) > 1) {$form->addElement($selected_theme);}
	if($im_multilanguageConfig['ml_enable']) {$form->addElement($selected_language);}
	$form->addElement($umode_select);
	$form->addElement($uorder_select);
	$form->addElement($notify_method_select);
	$form->addElement($notify_mode_select);
	$form->addElement($bio_tarea);
	$form->addElement($pwd_change_radio);
	$form->addElement($pwd_tray);
	$form->addElement($cookie_radio);
	$form->addElement($mailok_radio);
	$form->addElement($salt_hidden);
	$form->addElement($uid_hidden);
	$form->addElement($op_hidden);
	$form->addElement($token_hidden);
	$form->addElement($submit_button);
	if($xoopsConfigUser['allow_chgmail'] == 1) {$form->setRequired($email_text);}
	$form->display();
	include ICMS_ROOT_PATH.'/footer.php';
}

if($op == 'avatarform')
{
	include ICMS_ROOT_PATH.'/header.php';
	echo '<a href="userinfo.php?uid='.intval($xoopsUser->getVar('uid')).'">'._US_PROFILE.'</a>&nbsp;<span style="font-weight:bold;">&raquo;&raquo;</span>&nbsp;'._US_UPLOADMYAVATAR.'<br /><br />';
	$oldavatar = $xoopsUser->getVar('user_avatar');
	if(!empty($oldavatar) && $oldavatar != 'blank.gif')
	{
		echo '<div style="text-align:center;"><h4 style="color:#ff0000; font-weight:bold;">'._US_OLDDELETED.'</h4>';
		// Im-IPB Core edit - Avatar Fix
		if(preg_match("/http/", $oldavatar)) {echo "<img src=$oldavatar alt='' /></div>";}
		else {echo '<img src="'.ICMS_UPLOAD_URL.'/'.$oldavatar.'" alt="" /></div>';}
		// End of Im-IPB Core edit
	}
	if($xoopsConfigUser['avatar_allow_upload'] == 1 && $xoopsUser->getVar('posts') >= $xoopsConfigUser['avatar_minposts'])
	{
		include_once 'class/xoopsformloader.php';
		$form = new XoopsThemeForm(_US_UPLOADMYAVATAR, 'uploadavatar', 'edituser.php', 'post', true);
		$form->setExtra('enctype="multipart/form-data"');
		$form->addElement(new XoopsFormLabel(_US_MAXPIXEL, $xoopsConfigUser['avatar_width'].' x '.$xoopsConfigUser['avatar_height']));
		$form->addElement(new XoopsFormLabel(_US_MAXIMGSZ, $xoopsConfigUser['avatar_maxsize']));
		$form->addElement(new XoopsFormFile(_US_SELFILE, 'avatarfile', $xoopsConfigUser['avatar_maxsize']), true);
		$form->addElement(new XoopsFormHidden('op', 'avatarupload'));
		$form->addElement(new XoopsFormHidden('uid', intval($xoopsUser->getVar('uid'))));
		$form->addElement(new XoopsFormButton('', 'submit', _SUBMIT, 'submit'));
		$form->display();
	}
	$avatar_handler = xoops_gethandler('avatar');
	$form2 = new XoopsThemeForm(_US_CHOOSEAVT, 'uploadavatar', 'edituser.php', 'post', true);
	$avatar_select = new XoopsFormSelect('', 'user_avatar', $xoopsUser->getVar('user_avatar'));
	$avatar_select->addOptionArray($avatar_handler->getList('S'));
	$avatar_select->setExtra("onchange='showImgSelected(\"avatar\", \"user_avatar\", \"uploads\", \"\", \"".ICMS_URL."\")'");
	$avatar_tray = new XoopsFormElementTray(_US_AVATAR, '&nbsp;');
	$avatar_tray->addElement($avatar_select);
	
	// Im-IPB Core edit - Avatar Fix
	if(preg_match("/http/", $oldavatar))
	{
		$avatar_tray->addElement(new XoopsFormLabel('', "<img src='".$xoopsUser->getVar('user_avatar', 'E')."' name='avatar' id='avatar' alt='' /><a href=\"javascript:openWithSelfMain('".ICMS_URL."/misc.php?action=showpopups&amp;type=avatars','avatars',600,400);\">"._LIST."</a>"));
	}
	else
	{
		$avatar_tray->addElement(new XoopsFormLabel('', "<img src='".ICMS_UPLOAD_URL."/".$xoopsUser->getVar('user_avatar', 'E')."' name='avatar' id='avatar' alt='' /><a href=\"javascript:openWithSelfMain('".ICMS_URL."/misc.php?action=showpopups&amp;type=avatars','avatars',600,400);\">"._LIST."</a>"));
	}
	// End of Im-IPB Core edit
	
	if($xoopsConfigUser['avatar_allow_upload'] == 1 && $xoopsUser->getVar('posts') < $xoopsConfigUser['avatar_minposts'])
	{
		$form2->addElement(new XoopsFormLabel(sprintf(_US_POSTSNOTENOUGH,$xoopsConfigUser['avatar_minposts']),_US_UNCHOOSEAVT));
	}
	$form2->addElement($avatar_tray);
	$form2->addElement(new XoopsFormHidden('uid', intval($xoopsUser->getVar('uid'))));
	$form2->addElement(new XoopsFormHidden('op', 'avatarchoose'));
	$form2->addElement(new XoopsFormButton('', 'submit2', _SUBMIT, 'submit'));
	$form2->display();
	include ICMS_ROOT_PATH.'/footer.php';
}

if($op == 'avatarupload')
{
	if(!$GLOBALS['xoopsSecurity']->check())
	{
		redirect_header('index.php',3,_US_NOEDITRIGHT.'<br />'.implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
	}
	$xoops_upload_file = array();
	$uid = 0;
	if(!empty($_POST['xoops_upload_file']) && is_array($_POST['xoops_upload_file']))
	{
		$xoops_upload_file = $_POST['xoops_upload_file'];
	}
	if(!empty($_POST['uid'])) {$uid = intval($_POST['uid']);}
	if(empty($uid) || $xoopsUser->getVar('uid') != $uid) {redirect_header('index.php',3,_US_NOEDITRIGHT);}
	if($xoopsConfigUser['avatar_allow_upload'] == 1 && $xoopsUser->getVar('posts') >= $xoopsConfigUser['avatar_minposts'])
	{
		include_once ICMS_ROOT_PATH.'/class/uploader.php';
		$uploader = new XoopsMediaUploader(ICMS_UPLOAD_PATH, array('image/gif', 'image/jpeg', 'image/pjpeg', 'image/x-png', 'image/png'), $xoopsConfigUser['avatar_maxsize'], $xoopsConfigUser['avatar_width'], $xoopsConfigUser['avatar_height']);
		if($uploader->fetchMedia($_POST['xoops_upload_file'][0]))
		{
			$uploader->setPrefix('cavt');
			if($uploader->upload())
			{
				$avt_handler = xoops_gethandler('avatar');
				$avatar = $avt_handler->create();
				$avatar->setVar('avatar_file', $uploader->getSavedFileName());
				$avatar->setVar('avatar_name', $xoopsUser->getVar('uname'));
				$avatar->setVar('avatar_mimetype', $uploader->getMediaType());
				$avatar->setVar('avatar_display', 1);
				$avatar->setVar('avatar_type', 'C');
				if(!$avt_handler->insert($avatar)) {@unlink($uploader->getSavedDestination());}
				else
				{
					$oldavatar = $xoopsUser->getVar('user_avatar');
					if(!empty($oldavatar) && preg_match('/^cavt/', strtolower($oldavatar)))
					{
						$avatars = $avt_handler->getObjects(new Criteria('avatar_file', $oldavatar));
						if(!empty($avatars) && count($avatars) == 1 && is_object($avatars[0]))
						{
							$avt_handler->delete($avatars[0]);
							$oldavatar_path = str_replace('\\', '/', realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
							if(0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path))
							{
								unlink($oldavatar_path);
							}
						}
					}
					$sql = sprintf("UPDATE %s SET user_avatar = %s WHERE uid = '%u'", $xoopsDB->prefix('users'), $xoopsDB->quoteString($uploader->getSavedFileName()), intval($xoopsUser->getVar('uid')));
					$xoopsDB->query($sql);
					$avt_handler->addUser($avatar->getVar('avatar_id'), intval($xoopsUser->getVar('uid')));
					redirect_header('userinfo.php?t='.time().'&amp;uid='.intval($xoopsUser->getVar('uid')),0, _US_PROFUPDATED);
				}
			}
		}
		include ICMS_ROOT_PATH.'/header.php';
		echo $uploader->getErrors();
		include ICMS_ROOT_PATH.'/footer.php';
	}
}

if($op == 'avatarchoose')
{
	if(!$GLOBALS['xoopsSecurity']->check())
	{
		redirect_header('index.php',3,_US_NOEDITRIGHT.'<br />'.implode('<br />', $GLOBALS['xoopsSecurity']->getErrors()));
	}
	$myts = MyTextSanitizer::getInstance();
	$uid = 0;
	if(!empty($_POST['uid'])) {$uid = intval($_POST['uid']);}
	if(empty($uid) || $xoopsUser->getVar('uid') != $uid) {redirect_header('index.php', 3, _US_NOEDITRIGHT);}
	$user_avatar = '';
	$avt_handler = xoops_gethandler('avatar');
	if(!empty($_POST['user_avatar']))
	{
		$user_avatar = $myts->addSlashes(trim($_POST['user_avatar']));
		$criteria_avatar = new CriteriaCompo(new Criteria('avatar_file', $user_avatar));
		$criteria_avatar->add(new Criteria('avatar_type', "S"));
		$avatars = $avt_handler->getObjects($criteria_avatar);
		if(!is_array($avatars) || !count($avatars)) {$user_avatar = 'blank.gif';}
		unset($avatars, $criteria_avatar);
	}
	$user_avatarpath = str_replace('\\', '/', realpath(ICMS_UPLOAD_PATH.'/'.$user_avatar));
	if(0 === strpos($user_avatarpath, ICMS_UPLOAD_PATH) && is_file($user_avatarpath))
	{
		$oldavatar = $xoopsUser->getVar('user_avatar');
		$xoopsUser->setVar('user_avatar', $user_avatar);
		$member_handler =& xoops_gethandler('member');
		if(!$member_handler->insertUser($xoopsUser))
		{
			include ICMS_ROOT_PATH.'/header.php';
			echo $xoopsUser->getHtmlErrors();
			include ICMS_ROOT_PATH.'/footer.php';
			exit();
		}
		if($oldavatar && preg_match('/^cavt/', strtolower($oldavatar)))
		{
			$avatars = $avt_handler->getObjects(new Criteria('avatar_file', $oldavatar));
			if(!empty($avatars) && count($avatars) == 1 && is_object($avatars[0]))
			{
				$avt_handler->delete($avatars[0]);
				$oldavatar_path = str_replace('\\', '/', realpath(ICMS_UPLOAD_PATH.'/'.$oldavatar));
				if(0 === strpos($oldavatar_path, ICMS_UPLOAD_PATH) && is_file($oldavatar_path))
				{
					unlink($oldavatar_path);
				}
			}
		}
		if($user_avatar != 'blank.gif')
		{
			$avatars =& $avt_handler->getObjects(new Criteria('avatar_file', $user_avatar));
			if(is_object($avatars[0])) {$avt_handler->addUser($avatars[0]->getVar('avatar_id'), $xoopsUser->getVar('uid'));}
		}
	}
	redirect_header('userinfo.php?uid='.$uid, 0, _US_PROFUPDATED);
}
?>