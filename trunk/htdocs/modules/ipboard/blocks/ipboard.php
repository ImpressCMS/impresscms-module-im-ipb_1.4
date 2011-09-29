<?php
/*
// ------------------------------------------------------------------------
-+ Date: 02-May-2004
-+ Version: 1.4E
-+ ========================================
-+ Made by Koudanshi
-+ E-mail: webmaster@bbpixel.com
-+ Homepage: bbpixel.com
-+ ========================================
-+ Any Problems please email me,
-+
-+ ========================================
\\ ------------------------------------------------------------------------
*/
function ipboard_topics_show($options) {
	global $sid_bb, $skinid_bb, $std, $INFO, $member, $uid_bb;
	$db =& Database::getInstance();
	$myts =& MyTextSanitizer::getInstance();
    	$block = array();
	switch($options[2]) {
	case 'views':
		$order = 't.views';
		break;
	case 'replies':
		$order = 't.posts';
		break;
	case 'time':
	default:
		$order = 't.last_post';
		break;
	}
	// Load member's permission
	$member = $db->fetchArray($db->query("SELECT u.org_perm_id, g.g_perm_id FROM ".XOOPS_DB_PREFIX."_users u, ".XOOPS_DB_PREFIX."_ipb_groups g WHERE u.uid=".$uid_bb." AND u.mgroup=g.g_id"));

	//-----------------------
	// Load skin image dir
	//-----------------------

	$sql  = $db->query("SELECT img_dir FROM ".$db->prefix('ipb_skins')." WHERE sid=$skinid_bb");
	$skin = $db->fetchArray($sql);

	if (empty($skin['img_dir']) or $skin['img_dir']==""){
		$skin['img_dir'] = 1;
	}
	$query="SELECT t.tid, t.title, t.last_post, t.views, t.posts, t.last_poster_id, t.last_poster_name, t.icon_id, f.id as fid, f.name as fname, f.read_perms, f.password as fpass
		FROM ".$db->prefix("ipb_topics")." AS t 
		  INNER JOIN ".$db->prefix("ipb_forums")." AS f ON(f.id=t.forum_id)
    		WHERE t.approved = 1
    			ORDER BY ".$order." DESC";

	if (!$result = $db->query($query,$options[0],0)) {
		return false;
	}
	if ( $options[1] != 0 ) {
		$block['full_view'] = true;
	} else {
		$block['full_view'] = false;
	}
	if ($INFO['xbbc_popup']==1) 
	{
		$block['view_popup'] = true;
	} else {
		$block['view_popup'] = false;
	}
	$block['lang_forum']       = _MB_IPBOARD_FORUM;
	$block['lang_topic']       = _MB_IPBOARD_TOPIC;
	$block['lang_replies']     = _MB_IPBOARD_RPLS;
	$block['lang_views']       = _MB_IPBOARD_VIEWS;
	$block['lang_by']          = _MB_IPBOARD_BY;
	$block['lang_lastpost']    = _MB_IPBOARD_LPOST;
	$block['lang_visitforums'] = _MB_IPBOARD_VSTFRMS;
	while ($arr = $db->fetchArray($result)) {
	  if (check_perms($arr['read_perms']) and ($arr['fpass']=='NULL' or $arr['fpass']=='')) 
		{
			$topic['forum_id']       = $arr['fid'];
			$topic['forum_name']     = $myts->makeTboxData4Show($arr['fname']);
			$topic['id']             = $arr['tid'];
			$topic['title']          = $myts->makeTboxData4Show($arr['title']);
			$topic['replies']        = $arr['posts'];
			$topic['views']          = $arr['views'];
			$topic['time']           = get_date($arr['last_post'],1);
			$topic['last_post_name'] = $arr['last_poster_name'];
			$topic['last_post_id']   = $arr['last_poster_id'];
			$topic['pages']          = show_page($arr['posts'],$arr['fid'],$arr['tid']);
			$topic['link_topic']     = ICMS_URL."/modules/ipboard/index.php?showtopic=".$arr['tid']."&s=".$sid_bb;
			$topic['link_forum']     = ICMS_URL."/modules/ipboard/index.php?showforum=".$arr['fid']."&s=".$sid_bb;
			$topic['link_user']      = ICMS_URL."/modules/ipboard/index.php?showuser=".$arr['last_poster_id']."&s=".$sid_bb;
			$topic['link_board']     = ICMS_URL."/modules/ipboard/";
			
			if (!$arr['icon_id']) {
			  $arr['icon_id'] = 1;
			}
			$topic['img_smile']    = "<img src=\"".ICMS_URL."/modules/ipboard/style_images/".$skin['img_dir']."/icon".$arr['icon_id'].".gif\" alt=\"".$topic['forum_name']."\">";
			$block['topics'][]     = &$topic;
			unset($topic);
		}
	}
 	return $block;
}


function check_perms($forum_perm="")
{
	global $uid_bb, $member;
	
  if ($uid_bb) {
    $perm_id = ( $member['org_perm_id'] ) ? $member['org_perm_id'] : $member['g_perm_id'];
  } else {
    $perm_id = 2;
  }
  $perm_id_array = explode( ",", $perm_id );

	if ( $forum_perm == "" )
	{
		return FALSE;
	}
	else if ( $forum_perm == '*' )
	{
		return TRUE;
	}
	else
	{
		$forum_perm_array = explode( ",", $forum_perm );

		foreach( $perm_id_array as $u_id )
		{
			if ( in_array( $u_id, $forum_perm_array ) )
			{
				return TRUE;
			}
		}

		// Still here? Not a match then.

		return FALSE;
	}
}


function ipboard_topics_edit($options) {
	$inputtag = "<input type='text' name='options[0]' value='".$options[0]."' />";
	$form = sprintf(_MB_IPBOARD_DISPLAY,$inputtag);
	$form .= "<br />"._MB_IPBOARD_DISPLAYF."&nbsp;<input type='radio' name='options[1]' value='1'";
	if ( $options[1] == 1 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._YES."<input type='radio' name='options[1]' value='0'";
	if ( $options[1] == 0 ) {
		$form .= " checked='checked'";
	}
	$form .= " />&nbsp;"._NO;
	$form .= '<input type="hidden" name="options[2]" value="'.$options[2].'">';
	return $form;
}

function show_page ($data,$f,$t)
{
	global $sid_bb, $INFO;
	$pages = 1;

	if ( (($data + 1) % $INFO['display_max_posts']) == 0 )
	{
		$pages = ($data + 1) / $INFO['display_max_posts'];
	}
	else
	{
		$number = ( ($data + 1) / $INFO['display_max_posts'] );
		$pages = ceil( $number);
	}
	$pages_link = '';
	
	if ($INFO['xbbc_popup']==1)
	{
	  $popup="target=\"_blank\"";
	}
	else
	{
	  $popup="";
	}
	if ($pages > 1) {
		$pages_link = "<span style='font-size:11px; font-weight:bold; font-family:verdana,tahoma;'>("._MB_IPBOARD_PAGES." ";
		for ($i = 0 ; $i < $pages ; ++$i ) {
			$real_no = $i * $INFO['display_max_posts'];
			$page_no = $i + 1;
			if ($page_no == 4) {
				$pages_link .= "<a ".$popup." href='".ICMS_URL."/modules/ipboard/index.php?s=$sid_bb&act=ST&f=$f&t=$t&st=" . ($pages - 1) * $INFO['display_max_posts'] . "'>...$pages </a>";
				break;
			} else {
				$pages_link .= "<a ".$popup." href='".ICMS_URL."/modules/ipboard/index.php?s=$sid_bb&act=ST&f=$f&t=$t&st=$real_no'>$page_no </a>";
			}
		}
		$pages_link .= ")</span>";
	}
	return $pages_link;
}

function ipboard_bday_show($options)
{
  global $timeoffset_bb,$INFO;
  $db =& Database::getInstance();
  $myts =& MyTextSanitizer::getInstance();
  $block = array();

  switch($options[2]) {
    case 'ages':
          $order = 'bday_year';
          break;
    case 'name':
    default:
          $order = 'uname';
          break;
  }

	$user_time = time() + ($timeoffset_bb * 3600 );
	$date      = getdate($user_time);
	$day       = $date['mday'];
	$month     = $date['mon'];
	$year      = $date['year'];

	$query     = "SELECT user_avatar, uid, uname, bday_day as DAY, bday_month as MONTH, bday_year as YEAR FROM ".$db->prefix("users")."
          				WHERE bday_day = $day AND bday_month = $month ORDER BY ".$order." DESC";

	if (!$result = $db->query($query,$options[0],0)) {
    return false;
	}
	if ($INFO['xbbc_popup']==1) 
	{
		$block['view_popup'] = true;
	} else {
		$block['view_popup'] = false;
	}

	if ( $options[1] != 0 ) {
    $block['avatar'] = true;
	} else {
    $block['avatar'] = false;
	}
	$block['lang_mem']  = _MB_IPBOARD_BDAY_MEM;
	$block['lang_ages'] = _MB_IPBOARD_BDAY_AGES;
	$count = 0;

  while ($arr = $db->fetchArray($result)) {
		$pyear               = $year - $arr['YEAR'];  // $year = 2002 and $user['YEAR'] = 1976
    $bday['name']        = strlen($arr['uname'])>10 ? substr($arr['uname'],0,10) : $arr['uname'];
    $bday['sess_id']     = $sid_bb;
    $bday['user_link']   = ICMS_URL."/userinfo.php?uid=".$arr['uid'];
    $bday['avatar']      = ICMS_URL."/uploads/".$arr['user_avatar'];
    $bday['ages']        = $pyear;
    $block['bday'][]     = &$bday;
    unset($bday);
    $count++;
  }
  if ($count == 0) {
		$block['no_bday']      = true;
		$block['lang_no_bday'] = _MB_IPBOARD_BDAY_NONE;
	}
  return $block;
}

function ipboard_bday_edit($options)
{
  $inputtag= "<input type='text' name='options[0]' value='".$options[0]."' />";
  $form    = sprintf(_MB_IPBOARD_BDAY_DISP,$inputtag);
  //Option 2
  $form   .= "<br />"._MB_IPBOARD_DISPLAY_AVT."&nbsp;<input type='radio' name='options[1]' value='1'";
  if ( $options[1] == 1 ) {
    $form .= " checked='checked'";
  }
  $form   .= " />&nbsp;"._YES."&nbsp;<input type='radio' name='options[1]' value='0'";
  if ( $options[1] == 0 ) {
    $form .= " checked='checked'";
  }
  $form   .= " />&nbsp;"._NO;
  //Option 3
  $form   .= "<br />"._MB_IPBOARD_BDAY_ORDER."&nbsp;<input type='radio' name='options[2]' value='name'";
  if ( $options[2] == "name" ) {
          $form .= " checked='checked'";
  }
  $form   .= " />&nbsp;"._MB_IPBOARD_BDAY_ORDER_NAME."<input type='radio' name='options[2]' value='ages'";
  if ( $options[2] == "ages" ) {
    $form .= " checked='checked'";
  }
  return $form  .= " />&nbsp;"._MB_IPBOARD_BDAY_ORDER_AGES;
}

$offset_set = 0;
$offset = "";

function get_date($date, $type=0) {
  global $offset_set, $offset, $timeoffset_bb, $dstinuse_bb, $INFO;

  if (!$date)
  {
      return '--';
  }

  if ($offset_set == 0)
  {
  // Save redoing this code for each call, only do once per page load
	$offset = (($timeoffset_bb != "") ? $timeoffset_bb : $INFO['time_offset']) * 3600;

	if (isset($INFO['time_adjust']) and $INFO['time_adjust'] != 0)
	{
		$offset += ($INFO['time_adjust'] * 60);
	}

	if ($dstinuse_bb)
	{
		$offset += 3600;
	}

	$offset_set = 1;
  }
  if ($type)
  {
  	$method = "d-M-y, H:i";
  }
  else
  {
  	$method = "j M, H:i";
  }
  return gmdate($method, ($date + $offset) );
}

function ipboard_welcome_show($options) {
	global $uid_bb, $sid_bb, $avatar_bb, $uname_bb, $lastvisit_bb, $INFO;
  $db =& Database::getInstance();
  $myts =& MyTextSanitizer::getInstance();

  //Topics
	$top_topic = $db->fetchArray($db->query("SELECT `starter_id` AS id, `starter_name` AS name, COUNT(*) AS `num`FROM ".$db->prefix("ipb_topics")."
	                                          WHERE starter_id > 0 GROUP BY id ORDER BY num DESC LIMIT 1"));

	$top_post  = $db->fetchArray($db->query("SELECT `uid`,`uname`,`posts` FROM ".$db->prefix("users")."
	                                          WHERE 1 AND uid > 0 ORDER BY `posts` DESC LIMIT 1"));

  //Last visit
	$since     = $db->fetchArray($db->query("SELECT COUNT(DISTINCT(t.tid)) as topics, COUNT(DISTINCT(p.pid)) as posts FROM ".$db->prefix("ipb_posts")." p, ".$db->prefix("ipb_topics")." t
                                      		  WHERE p.post_date < ".time()." AND p.post_date > ".$lastvisit_bb." AND p.topic_id=t.tid"));

  //Posts + time
	$stats       = $db->fetchArray($db->query("SELECT * FROM ".$db->prefix("ipb_stats")." LIMIT 1"));
	$most_online = $stats['MOST_COUNT'];
	$total_posts = $stats['TOTAL_TOPICS'] + $stats['TOTAL_REPLIES'];

	$ctime       = time();
	$time        = get_date ($ctime,1);
	$time_s      = get_date ($ctime,0);
	$lastvisit   = get_date ($lastvisit_bb,1);
	$lastvisit_s = get_date ($lastvisit_bb,0);
	$most_time   = get_date ($stats['MOST_DATE'],1);

	if ($INFO['xbbc_popup']==1) 
	{
		$block['view_popup'] = true;
	} else {
		$block['view_popup'] = false;
	}

	if ( $options[0] != 0 ) {
        $block['is_avatar'] = true;
	} else {
        $block['is_avatar'] = false;
	}

	if ( $options[1] != 0 ) {
		$block['full_view']          = true;
		$block['lang_now'] 		 	     = sprintf(_MB_IPBOARD_WCOME_NOW, $time);
		$block['lang_lastvisit'] 	   = sprintf(_MB_IPBOARD_WCOME_LASTVISIT, $lastvisit);
		$block['lang_new_user']	 	   = _MB_IPBOARD_WCOME_NEW_USER;
	} else {
		$block['full_view']          = false;
		$block['lang_now'] 			     = sprintf(_MB_IPBOARD_WCOME_NOW_S, $time_s);
		$block['lang_lastvisit'] 	   = sprintf(_MB_IPBOARD_WCOME_LASTVISIT_S, $lastvisit_s);
		$block['lang_new_user']	 	   = _MB_IPBOARD_WCOME_NEW_USER_S;
	}
  // Language
	$block['lang_welcome']   	     = sprintf(_MB_IPBOARD_WCOME_HELLO, "&nbsp;".$uname_bb);
	$block['lang_sum'] 		 	       = sprintf(_MB_IPBOARD_WCOME_SUM, "<b>".$since['posts']."</b>", "<b>".$since['topics']."</b>");
	$block['lang_most_online']     = sprintf(_MB_IPBOARD_WCOME_ONLINE, "<b>".$most_online."</b>", $most_time);
  //user lang
	$block['lang_users'] 	 	       = sprintf(_MB_IPBOARD_WCOME_USERS, "&nbsp;::&nbsp;<b>".$stats['MEM_COUNT']."</b>");
	$block['lang_new_uname'] 	     = $stats['LAST_MEM_NAME'];
  //topic lang
	$block['lang_top_topic'] 	     = _MB_IPBOARD_WCOME_TOP_TOPIC;
	$block['lang_topics']	 	       = sprintf(_MB_IPBOARD_WCOME_TOPICS, "&nbsp;&nbsp;&nbsp;&nbsp;::&nbsp;<b>".$stats['TOTAL_TOPICS']."</b>");
	$block['lang_top_topic_uname'] = !empty($top_topic['name'])? $top_topic['name'] : "NA";
	$block['lang_view_topics'] 	   = _MB_IPBOARD_WCOME_VIEW_TOPICS;
	$block['lang_new_topics'] 	   = _MB_IPBOARD_WCOME_NEW_TOPICS;
  //post lang
	$block['lang_top_post']	 	     = _MB_IPBOARD_WCOME_TOP_POST;
	$block['lang_posts'] 	 	       = sprintf(_MB_IPBOARD_WCOME_POSTS, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;::&nbsp;<b>".$total_posts."</b>");
	$block['lang_top_post_uname']  = !empty($top_post['uname']) ? $top_post['uname'] : "NA";
	$block['lang_view_posts'] 	   = _MB_IPBOARD_WCOME_VIEW_POSTS;
	$block['lang_new_posts'] 	     = _MB_IPBOARD_WCOME_NEW_POSTS;
  //replies lang
	$block['lang_replies']	 	     = sprintf(_MB_IPBOARD_WCOME_REPLIES, "&nbsp;&nbsp;&nbsp;&nbsp;::&nbsp;<b>".$stats['TOTAL_REPLIES']."</b>");

  // Others
  $block['vposts_link'] 	       = ICMS_URL."/modules/ipboard/index.php?act=Search&CODE=getnew";
  $block['vtopics_link'] 		     = ICMS_URL."/modules/ipboard/index.php?act=Search&CODE=getactive";

  //IPBM profile link
  $top_topic['id']               = !empty($top_topic['id'])? $top_topic['id'] : 1;
  $top_post['uid']               = !empty($top_post['uid'])? $top_post['uid'] : 1;
  $block['nuser_link'] 		       = ICMS_URL."/modules/ipboard/index.php?showuser=".$stats['LAST_MEM_ID'];
  $block['top_topic_id_link']	   = ICMS_URL."/modules/ipboard/index.php?showuser=".$top_topic['id'];
  $block['top_post_id_link']	   = ICMS_URL."/modules/ipboard/index.php?showuser=".$top_post['uid'];

  $block['new_topics']		       = $since['topics'];
  $block['new_posts']			       = $since['posts'];

  $block['top_topic']			       = "<b>".$top_topic['num']."</b>";
  $block['top_post']			       = "<b>".$top_post['posts']."</b>";

  if ($uid_bb)
  {
  	$block['user_link']          = ICMS_URL."/modules/ipboard/index.php?showuser=".$uid_bb;
  	$block['avatar']             = ICMS_URL."/uploads/".$avatar_bb;
  }
  else
  {
  	$block['user_link']          = ICMS_URL."/modules/ipboard/index.php";
  	$block['avatar']             = ICMS_URL."/uploads/blank.gif";
  }
  return $block;
}

function ipboard_welcome_edit($options) {
  //Option 1
  $form = ""._MB_IPBOARD_DISPLAY_AVT."&nbsp;<input type='radio' name='options[0]' value='1'";

  if ( $options[0] == 1 ) $form .= " checked='checked'";

  $form .= " />&nbsp;"._YES."&nbsp;<input type='radio' name='options[0]' value='0'";

  if ( $options[0] == 0 ) $form .= " checked='checked'";

  $form .= " />&nbsp;"._NO;
  //Option 2
  $form .= "<br />"._MB_IPBOARD_DISPLAYF."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type='radio' name='options[1]' value='1'";

  if ( $options[1] == 1 ) $form .= " checked='checked'";

  $form .= " />&nbsp;"._YES."&nbsp;<input type='radio' name='options[1]' value='0'";

  if ( $options[1] == 0 )  $form .= " checked='checked'";

  $form .= " />&nbsp;"._NO;

  return $form;
}
?>