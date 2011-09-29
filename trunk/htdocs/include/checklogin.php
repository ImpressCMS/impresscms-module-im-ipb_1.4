<?php
// $Id: checklogin.php 660 2006-08-23 20:21:15Z skalpa $
/*
// ------------------------------------------------------------------------
-+ Update: 12-SEP-2006
-+ Version: 1.4J CORE UPDATE XOOPS 2.0.15
-+ ========================================
-+ Be Modified by Koudanshi
-+ E-mail: koudanshi@gmx.net
-+ Homepage: bbpixel.com
-+ ========================================
-+ Any Problems please email me,
-+ 
-+ ========================================
\\ ------------------------------------------------------------------------
*/
//  ------------------------------------------------------------------------ //
//                XOOPS - PHP Content Management System                      //
//                    Copyright (c) 2000 XOOPS.org                           //
//                       <http://www.xoops.org/>                             //
//  ------------------------------------------------------------------------ //
//  This program is free software; you can redistribute it and/or modify     //
//  it under the terms of the GNU General Public License as published by     //
//  the Free Software Foundation; either version 2 of the License, or        //
//  (at your option) any later version.                                      //
//                                                                           //
//  You may not change or alter any portion of this comment or credits       //
//  of supporting developers from this source code or any supporting         //
//  source code which is considered copyrighted (c) material of the          //
//  original comment or credit authors.                                      //
//                                                                           //
//  This program is distributed in the hope that it will be useful,          //
//  but WITHOUT ANY WARRANTY; without even the implied warranty of           //
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the            //
//  GNU General Public License for more details.                             //
//                                                                           //
//  You should have received a copy of the GNU General Public License        //
//  along with this program; if not, write to the Free Software              //
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA //
//  ------------------------------------------------------------------------ //
// Author: Kazumi Ono (AKA onokazu)                                          //
// URL: http://www.xoops.org/ http://jp.xoops.org/  http://www.myweb.ne.jp/  //
// Project: The XOOPS Project (http://www.xoops.org/)                        //
// ------------------------------------------------------------------------- //

if (!defined('ICMS_ROOT_PATH')) {
    exit();
}
include_once ICMS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/user.php';

$member_handler =& xoops_gethandler('member');
$myts =& MyTextsanitizer::getInstance();

include_once ICMS_ROOT_PATH.'/class/auth/authfactory.php';
include_once ICMS_ROOT_PATH.'/language/'.$xoopsConfig['language'].'/auth.php';
$xoopsAuth =& XoopsAuthFactory::getAuthConnection($myts->addSlashes($uname));
//<<<---------------------------------------------
//-+- IPBM compatibility -- Koudanshi
//<<<---------------------------------------------

if(isset($_POST['Privacy'])){
	$Privacy=$_POST['Privacy'];
} else if (isset($_GET['Privacy'])){
	$Privacy=$_GET['Privacy'];
}
if (!empty($Privacy)){
   @setcookie('anonlogin' , 1, $exp_time, '/',  '', 0);
}

if (isset($_POST['uname']) && isset($_POST['pass'])) {
	$uname = trim($_POST['uname']);
	$pass  = trim($_POST['pass']);
	$user  = $xoopsAuth->authenticate($myts->addSlashes($uname), $myts->addSlashes($pass));
} else {
	$uname = trim($_GET['uname']);
	$pass  = trim($_GET['pass']);
	
	$user  = $xoopsAuth->authenticatemd5($myts->addslashes($uname), $myts->addslashes($pass));
}
//>>>---------------------------------------------

if ($uname == '' || $pass == '') {
	redirect_header(ICMS_URL.'/user.php', 1, _US_INCORRECTLOGIN);
	exit();
}

if (false != $user) {
    if (0 == $user->getVar('level')) {
        redirect_header(ICMS_URL.'/index.php', 5, _US_NOACTTPADM);
        exit();
    }
    if ($xoopsConfig['closesite'] == 1) {
        $allowed = false;
        foreach ($user->getGroups() as $group) {
            if (in_array($group, $xoopsConfig['closesite_okgrp']) || XOOPS_GROUP_ADMIN == $group) {
                $allowed = true;
                break;
            }
        }
        if (!$allowed) {
            redirect_header(ICMS_URL.'/index.php', 1, _NOPERM);
            exit();
        }
    }
    $user->setVar('last_login', time());
    if (!$member_handler->insertUser($user)) {
    }
    $_SESSION = array();
    $_SESSION['xoopsUserId'] = $user->getVar('uid');
    $_SESSION['xoopsUserGroups'] = $user->getGroups();
    if ($xoopsConfig['use_mysession'] && $xoopsConfig['session_name'] != '') {
        setcookie($xoopsConfig['session_name'], session_id(), time()+(60 * $xoopsConfig['session_expire']), '/',  '', 0);
    }

  //+-----------------------------------------------
  //| AutoLogin + others stuff cookies
  //+-----------------------------------------------	
  if(isset($_POST["AutoLogin"]) and $_POST["AutoLogin"] == "On")
	{
		setcookie('mgroupid'    , $user->getVar('mgroup')          , $exp_time, '/',  '', 0);
		setcookie('member_id'   , $_SESSION['xoopsUserId']         , $exp_time, '/',  '', 0);
		setcookie('xoopsib_pass', $user->getVar('pass')			       , $exp_time, '/',  '', 0);
		setcookie('pass_hash'   , $user->getVar('pass')			       , $exp_time, '/',  '', 0);		
	}
  //+-----------------------------------------------	
    $user_theme = $user->getVar('theme');
    if (in_array($user_theme, $xoopsConfig['theme_set_allowed'])) {
        $_SESSION['xoopsUserTheme'] = $user_theme;
    }
    if (!empty($_POST['xoops_redirect']) && !strpos($_POST['xoops_redirect'], 'register')) {
		$_POST['xoops_redirect'] = trim( $_POST['xoops_redirect'] );
        $parsed = parse_url(ICMS_URL);
        $url = isset($parsed['scheme']) ? $parsed['scheme'].'://' : 'http://';
        if ( isset( $parsed['host'] ) ) {
        	$url .= $parsed['host'];
			if ( isset( $parsed['port'] ) ) {
				$url .= ':' . $parsed['port'];
			}
        } else {
        	$url .= $_SERVER['HTTP_HOST'];
        }
        if ( @$parsed['path'] ) {
        	if ( strncmp( $parsed['path'], $_POST['xoops_redirect'], strlen( $parsed['path'] ) ) ) {
	        	$url .= $parsed['path'];
        	}
        }
	//<<<---------------------------------------------
	//-+- IPB Module AUTO REDIRECT -- Koudanshi
	//<<<---------------------------------------------
	if (preg_match("/ipboard/",$_SERVER['HTTP_REFERER'])) 
	{
   	$url = $_SERVER['HTTP_REFERER'];  //Kou added
		$url = str_replace ("act=Reg&CODE=00", "",$url);
		$url = str_replace ("act=Reg&CODE=10", "",$url);
		$url = str_replace ("act=Reg&CODE=reval", "",$url);
		$url = str_replace ("act=Login&CODE=00", "",$url);
	}
	if (empty($url) or !isset($url)) {
	  $url = $_SERVER['HTTP_REFERER'];  //Kou added
		$url = str_replace ("user", "index",$url);
	}
  setcookie('open_qr', 1, time()+(60*60*24*365), '/',  '', 0);			
	//>>>---------------------------------------------	
   } else {
        $url = ICMS_URL.'/index.php';
    }

    // RMV-NOTIFY
    // Perform some maintenance of notification records
    $notification_handler =& xoops_gethandler('notification');
    $notification_handler->doLoginMaintenance($user->getVar('uid'));

    redirect_header($url, 1, sprintf(_US_LOGGINGU, $user->getVar('uname')), false);
}elseif(empty($_POST['xoops_redirect'])){
	redirect_header(ICMS_URL.'/user.php', 5, $xoopsAuth->getHtmlErrors());
}else{
	redirect_header(ICMS_URL.'/user.php?xoops_redirect='.urlencode(trim($_POST['xoops_redirect'])), 5, $xoopsAuth->getHtmlErrors(), false);
}
exit();

?>