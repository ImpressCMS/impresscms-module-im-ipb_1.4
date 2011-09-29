<?php
// $Id: session.php 2 2005-11-02 18:23:29Z skalpa $
/*
// ------------------------------------------------------------------------
-+ Date: 12-SEP-2006
-+ Version: 1.4J Core update for 2.0.15
-+ ========================================
-+ Be Modified by Koudanshi
-+ E-mail: koudanshi@gmx.net
-+ Homepage: koudanshi.net or bbpixel.com
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
// URL: http://www.myweb.ne.jp/, http://www.xoops.org/, http://jp.xoops.org/ //
// Project: The XOOPS Project                                                //
// ------------------------------------------------------------------------- //
/**
 * @package     kernel
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */


/**
 * Handler for a session
 * @package     kernel
 * 
 * @author	    Kazumi Ono	<onokazu@xoops.org>
 * @copyright	copyright (c) 2000-2003 XOOPS.org
 */
class XoopsSessionHandler
{

    /**
     * Database connection
     * 
     * @var	object
     * @access	private
     */
    var $db;

    /**
     * Constructor
     * 
     * @param	object  &$mf    reference to a XoopsManagerFactory
     * 
     */
    function XoopsSessionHandler(&$db)
    {
        $this->db =& $db;
    }

    /**
     * Open a session
     * 
     * @param	string  $save_path
     * @param	string  $session_name
     * 
     * @return	bool
     */
    function open($save_path, $session_name)
	{
        return true;
    }

    /**
     * Close a session
     * 
     * @return	bool
     */
    function close()
	{
        return true;
    }

    /**
     * Read a session from the database
     * 
     * @param	string  &sess_id    ID of the session
     * 
     * @return	array   Session data
     */
    function read($sess_id)
	{
        $sql = sprintf('SELECT sess_data FROM %s WHERE sess_id = %s', $this->db->prefix('session'), $this->db->quoteString($sess_id));
        if (false != $result = $this->db->query($sql)) {
            if (list($sess_data) = $this->db->fetchRow($result)) {
                return $sess_data;
            }
        }
        return '';
    }

    /**
     * Write a session to the database
     * 
     * @param   string  $sess_id
     * @param   string  $sess_data
     * 
     * @return  bool    
     **/
    function write($sess_id, $sess_data)
	{
      global $isbb, $sid_bb, $uid_bb, $uname_bb, $mgroup_bb, $lastact_bb, $xoopsConfig, $INFO;
  		$sess_id = $this->db->quoteString($sess_id);		
  		list($count) = $this->db->fetchRow($this->db->query("SELECT COUNT(*) FROM ".$this->db->prefix('session')." WHERE sess_id=".$sess_id));
      
      //<<<---------------------------------------------
      //-+- IPB Module session hack -- Koudanshi
      //<<<---------------------------------------------
      $anon = !empty($_COOKIE['anonlogin'])? 1: 0;

  		if ($isbb and (count($INFO)>100)) {
  			$INFO['session_expiration'] = $xoopsConfig['session_expire']*60 <= $INFO['session_expiration'] ? (time() - $xoopsConfig['session_expire']*60) : (time() - $INFO['session_expiration']) ;						
        if ( $count > 0 ) {
  				$sql = sprintf('UPDATE %s SET sess_updated = %u, sess_data = %s, member_id = %u, member_name = %s, member_group = %u, login_type=%u WHERE sess_id = %s', $this->db->prefix('session'), time(), $this->db->quoteString($sess_data), $uid_bb, $this->db->quoteString($uname_bb), $mgroup_bb, $anon, $sess_id );
        } else {     	
          $this->db->queryF("DELETE FROM ".$this->db->prefix('session')." WHERE sess_updated < '".$INFO['session_expiration']."'");
				  $this->db->queryF("DELETE FROM ".$this->db->prefix('ipb_validating')." WHERE member_id = $uid_bb AND lost_pass = 1");
	    		$sql = sprintf('INSERT INTO %s (sess_id, sess_updated, sess_ip, sess_data, member_id, member_name, member_group, browser) VALUES (%s, %u, %s, %s, %u, %s, %u, %s)', $this->db->prefix('session'), $sess_id, time(), $this->db->quoteString($_SERVER['REMOTE_ADDR']), $this->db->quoteString($sess_data), $uid_bb, $this->db->quoteString($uname_bb), $mgroup_bb, $this->db->quoteString(substr($_SERVER['HTTP_USER_AGENT'], 0, 50)));

  				if (time() - $lastact_bb > 300) {
  					@setcookie('topicsread', '', 0, $INFO['cookie_path'],  $INFO['cookie_domain'], 0);
  		    	$this->db->queryF("UPDATE ".$this->db->prefix('users')." SET last_visit = last_activity, last_activity = '".time()."' WHERE uid='".$uid_bb."' ");        	        											
  				}
        }
  		} else {
        if ( $count > 0 ) {
	    	  $sql = sprintf('UPDATE %s SET sess_updated = %u, sess_data = %s WHERE sess_id = %s', $this->db->prefix('session'), time(), $this->db->quoteString($sess_data), $sess_id);
        } else {
    		  $sql = sprintf('INSERT INTO %s (sess_id, sess_updated, sess_ip, sess_data) VALUES (%s, %u, %s, %s)', $this->db->prefix('session'), $sess_id, time(), $this->db->quoteString($_SERVER['REMOTE_ADDR']), $this->db->quoteString($sess_data));
        }
      }
      //>>>---------------------------------------------

		if (!$this->db->queryF($sql)) {
            return false;
        }
		return true;
    }

    /**
     * Destroy a session
     * 
     * @param   string  $sess_id
     * 
     * @return  bool
     **/
    function destroy($sess_id)
    {
    	global $isbb, $uid_bb;
    	
	    //<<<---------------------------------------------
		  //+- IPBM sessions -- Koudanshi 		
  		if ($isbb) 
  		{
         $this->db->queryF("UPDATE ".$this->db->prefix('users')." SET last_visit='".time()."', last_activity='".time()."' WHERE uid='".$uid_bb."' ");        	        							
  		}	
      //>>>---------------------------------------------

		$sql = sprintf('DELETE FROM %s WHERE sess_id = %s', $this->db->prefix('session'), $this->db->quoteString($sess_id));
        if ( !$result = $this->db->queryF($sql) ) {
            return false;
        }
        return true;
    }

    /**
     * Garbage Collector
     * 
     * @param   int $expire Time in seconds until a session expires
	 * @return  bool
     **/
    function gc($expire)
    {
        $mintime = time() - intval($expire);
		$sql = sprintf('DELETE FROM %s WHERE sess_updated < %u', $this->db->prefix('session'), $mintime);
        return $this->db->queryF($sql);
    }
}
?>