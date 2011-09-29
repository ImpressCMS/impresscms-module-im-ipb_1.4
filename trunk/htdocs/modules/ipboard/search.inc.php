<?php
// $Id: search.inc.php,v 1.1 2003/01/02 18:38:28 w4z004 Exp $
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

function ipboard_search($queryarray, $andor, $limit, $offset, $userid){
	global $xoopsDB,$sid_bb;
	$sql = "SELECT p.post, p.author_name, p.post_title, p.post_date, p.author_id, t.tid, t.title, t.description, t.forum_id
	FROM ".$xoopsDB->prefix("ipb_posts")." p, ".$xoopsDB->prefix("ipb_topics")." t 
	WHERE t.tid = p.topic_id ";
	if ( $userid != 0 ) {
		$sql .= " AND author_id = ".$userid." ";
	} 


	// because count() returns 1 even if a supplied variable
	// is not an array, we must check if $querryarray is really an array

	if ( is_array($queryarray) && $count = count($queryarray) ) {
		$sql .= " AND ((post LIKE '%$queryarray[0]%' OR author_name LIKE '%$queryarray[0]%' OR post_title LIKE '%$queryarray[0]%' OR title LIKE '%$queryarray[0]%' OR description LIKE '%$queryarray[0]%')";
		for($i=1;$i<$count;$i++){
			$sql .= " $andor ";
			$sql .= "(post LIKE '%$queryarray[$i]%' OR author_name LIKE '%$queryarray[$i]%' OR post_title LIKE '%$queryarray[$i]%' OR title LIKE '%$queryarray[$i]%' OR description LIKE '%$queryarray[$i]%')";
		}
		$sql .= ") ";
	}

	$sql .= "ORDER BY post_date DESC";
	$result = $xoopsDB->query($sql,$limit,$offset);
	$ret = array();
	$i = 0;

 	while($myrow = $xoopsDB->fetchArray($result)){
		$ret[$i]['image'] = "html/sys-img/search.gif";
		$ret[$i]['link'] = "index.php?act=ST&f=".$myrow['forum_id']."&t=".$myrow['tid']."&s=$sid_bb ";
		$ret[$i]['title'] = $myrow['title'];
		$ret[$i]['time'] = $myrow['post_date'];
		$ret[$i]['uid'] = $myrow['author_id'];
		$i++;

	}
	return $ret;
}
?>