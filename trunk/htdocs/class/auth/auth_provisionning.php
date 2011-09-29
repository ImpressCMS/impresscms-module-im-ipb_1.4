<?php
// $Id: auth_provisionning.php 512 2006-05-27 01:16:37Z skalpa $
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
/**
 * @package     kernel
 * @subpackage  auth
 * @description	Authentification provisionning class. This class is responsible to
 * provide synchronisation method to Xoops User Database
 * 
 * @author	    Pierre-Eric MENUET	<pemphp@free.fr>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsAuthProvisionning {

	
	function &getInstance()
	{
		static $provis_instance;		
		if (!isset($provis_instance)) {
			$provis_instance = new XoopsAuthProvisionning();
		}
		return $provis_instance;
	}

    /**
	 * Authentication Service constructor
	 */
    function XoopsAuthProvisionning () {
        $config_handler =& xoops_gethandler('config');    
        $config =& $config_handler->getConfigsByCat(XOOPS_CONF_AUTH);
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }
    }

    /**
	 *  Return a Xoops User Object 
	 *
	 * @return XoopsUser or false
	 */	
	function getXoopsUser($uname) {
		$member_handler =& xoops_gethandler('member');
		$criteria = new Criteria('uname', $uname);
		$getuser = $member_handler->getUsers($criteria);
		if (count($getuser) == 1)
			return $getuser[0];
		else return false;		
	}
	
    /**
	 *  Launch the synchronisation process 
	 *
	 * @return bool
	 */		
	function sync($datas, $uname, $pwd = null) {
		$ret = false;
		$member_handler =& xoops_gethandler('member');
        // Create XOOPS Database User
		$newuser = $member_handler->createUser();
        $newuser->setVar('uname', $uname);
        $newuser->setVar('name', utf8_decode($datas[$this->ldap_givenname_attr][0]) . ' ' . utf8_decode($datas[$this->ldap_surname_attr][0]));
        $newuser->setVar('email', $datas[$this->ldap_mail_attr][0]);
        $newuser->setVar('pass', md5(stripslashes($pwd)));
        $newuser->setVar('rank', 0);
        $newuser->setVar('level', 1);
        if ($member_handler->insertUser($newuser)) {
        	foreach ($this->ldap_provisionning_group as $groupid)
        		$member_handler->addUserToGroup($groupid, $newuser->getVar('uid'));
        	$newuser->unsetNew();
        	return $newuser;
        } else redirect_header(ICMS_URL.'/user.php', 5, $newuser->getHtmlErrors());         
    	return $ret;
	}

// ------ added by m0nty for X-IPB 1.4J
    /**
	 *  Launch the synchronisationmd5 process 
	 *
	 * @return bool
	 */		
	function syncmd5($datas, $uname, $md5pwd = null) {
		$ret = false;
		$member_handler =& xoops_gethandler('member');
        // Create XOOPS Database User
		$newuser = $member_handler->createUser();
        $newuser->setVar('uname', $uname);
        $newuser->setVar('name', utf8_decode($datas[$this->ldap_givenname_attr][0]) . ' ' . utf8_decode($datas[$this->ldap_surname_attr][0]));
        $newuser->setVar('email', $datas[$this->ldap_mail_attr][0]);
        $newuser->setVar('pass', stripslashes($md5pwd));
        $newuser->setVar('rank', 0);
        $newuser->setVar('level', 1);
        if ($member_handler->insertUser($newuser)) {
        	foreach ($this->ldap_provisionning_group as $groupid)
        		$member_handler->addUserToGroup($groupid, $newuser->getVar('uid'));
        	$newuser->unsetNew();
        	return $newuser;
        } else redirect_header(ICMS_URL.'/user.php', 5, $newuser->getHtmlErrors());         
    	return $ret;
	}
// ------ end X-IPB 1.4J

    /**
	 *  Add a new user to the system
	 *
	 * @return bool
	 */		
	function add() {
	}
	
    /**
	 *  Add a new user to the system
	 *
	 * @return bool
	 */		
	function change() {
	}

    /**
	 *  Modify a user
	 *
	 * @return bool
	 */		
	function delete() {
	}

    /**
	 *  Suspend a user
	 *
	 * @return bool
	 */		
	function suspend() {
	}

    /**
	 *  Restore a user
	 *
	 * @return bool
	 */		
	function restore() {
	}

    /**
	 *  Add a new user to the system
	 *
	 * @return bool
	 */		
	function resetpwd() {
	}
	
    
} // end class
 
?>
