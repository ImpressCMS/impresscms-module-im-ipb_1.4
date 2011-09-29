<?php
//+---------------------------------------------------------------
//- Dadabase transfer from IPB origin 1.3 to IPB module 1.4 XOOPS
//- +============================================================+
//- Made by : Koudanshi 										
//- Homepage: http://Koudanshi.net						
//- Date	  : 10-Mar-2003		
//- Version : 1.1
//- +============================================================+
//- This script only works in small database, maybe <= 5MB						
//- +============================================================+
//- Need    : 													
//-			  IPB 1.3 origin from www.Invisionboard.com			
//- 			IPB Module for Xoops 1.4 from www.BBpixel.com			
//- +============================================================+
//- Notice  :
//- 	Run it with your own risk, I don't have any responsible  
//-		when you encounter any problem with this converter		
//-		Let's BACKUP your XOOPS database before run it.			 
//+---------------------------------------------------------------


include ("./../../mainfile.php");

$bbm_tbl 	= XOOPS_DB_PREFIX."_ipb_";
$xoops_tbl  = XOOPS_DB_PREFIX."_";
$new_url=ICMS_URL."/uploads";
// BB origin database connect
function bb_connect()
{
	global $bb_connect_id;

	if (!$bb_connect_id = mysql_connect($_POST['bb_host'], $_POST['bb_uname'], $_POST['bb_pass']))
	{
		die("Could not connect to BB Origin MySQL");
	}
	else
	{
		print ("<b>BB origin</b> MySql DataBase <font color=green><b>Connected</b> </font><br>");
	}
	if (!mysql_select_db($_POST['bb_db'],$bb_connect_id))
	{
		die("Could not select BB Origin DB");
	}
}

function bb_query($query)
{
	global $bb_connect_id;

	$sql_query = mysql_query($query,$bb_connect_id);

    if (!$sql_query )
    {
	   	print "<tr><td colspan=3>
	   	<font color=red><br>Error... Found on BB Origin DB</font> <br> ".$query." <br> <font color=green>".mysql_error($bb_connect_id)."</font>
		</td></tr>
	   	";
	   	exit;
    }
	return $sql_query;
}
// End BB Origin connect

// Begin BB module connect
function bbm_connect()
{
	global $bbm_connect_id;

	if (!$bbm_connect_id = mysql_pconnect(XOOPS_DB_HOST, XOOPS_DB_USER, XOOPS_DB_PASS))
	{
		die("Could not connect to BB Module MySQL");
	}
	else
	{
		print ("<b>BB module</b> MySql DataBase<font color=green> <b>Connected</b></font><br>");
	}
	if (!mysql_select_db (XOOPS_DB_NAME,$bbm_connect_id))
	{
		die("Could not select BB Module DB");
	}
}

function bbm_query($query)
{
	global $bbm_connect_id;

	$sql_query = mysql_query($query,$bbm_connect_id);

    if (!$sql_query )
    {
	   	print "<tr><td colspan=3>
	   	<font color=red><br>Error... Found on BB Module DB</font> <br> ".$query." <br> <font color=green>".mysql_error($bbm_connect_id)."</font>
	   	</td></tr>
	   	";
	   	exit;
    }
	return $sql_query;
}
// End BB module connect

function bb_disconnect()
{
	return mysql_close();
}

function bb_fetcharray($query)
{
	return ($query) ? mysql_fetch_array($query, MYSQL_ASSOC) : false;
}

function insert($data)
{
	foreach($data as $n => $v)
	{
		$columns[]     = $n;
		$rows[]        = "'$v'";
	}
	return array ('col' => implode(",", $columns),
				  'val' => implode(",", $rows),
				);
}

function bb_print($tbl1='',$tbl2='',$del='')
{
	if ((!empty($tbl1) or !empty($tbl2)) && !empty($del))
	{
		echo "<tr><table width='425' border=0 cellpadding=2 cellspacing=0>
		<td nowrap width=95%><font color=red>Deleting</font> <b>BB module</b> <font color=green><b><i>".$tbl2."</font></b></i></td>
		<td width=5%> table...";
	}
	else if (!empty($tbl1))
	{
		echo "<tr><table width='740' border=0 cellpadding=2 cellspacing=0>
		<td nowrap width=45%><font color=blue>Transfering</font> from <b>BB origin</b> <font color=green><b><i>".$tbl1."</font></b></i></td>
		<td nowrap width=41%>table to <b>BB module</b> <font color=green><b><i>".$tbl2."</font></b></i>
		<td width=5%> table...";
	}
	else
	{
		echo "<font color=blue size=3>Done.</font></td></table></tr>";
	}
}

function bb_maxcount ($id,$tbl,$z=0,$who=0)
{
	if(!$z) {
		if (!$who) {
			$mid = bb_fetcharray(bb_query("SELECT MAX(".$id.") as mid FROM ".$tbl." "));
		}
		else {
			$mid = bb_fetcharray(bbm_query("SELECT MAX(".$id.") as mid FROM ".$tbl." "));
		}
	} else {
		if (!$who)
		{
			$mid = bb_fetcharray(bb_query("SELECT COUNT(".$id.") as mid FROM ".$tbl." "));
		} else {
			$mid = bb_fetcharray(bbm_query("SELECT COUNT(".$id.") as mid FROM ".$tbl." "));
		}
	}
	return $mid['mid'];
}

function username($uid=0,$Guest='')
{
	global $xoops_tbl;
	if ($uid < 0) $uid=0;
	$uname=bb_fetcharray(bbm_query("SELECT uname FROM ".$xoops_tbl."users WHERE uid=".intval($uid).""));
	if (isset($uname['uname'])) return $uname['uname'];
	elseif (!empty($Guest)) return $Guest;
	else return 'Guest';
}
	//------------------------------
	//Start
	//------------------------------

if(isset($_POST['letsgo']))
{
	if($_POST['bb_host'] == "" or $_POST['bb_uname'] == "" or $_POST['bb_db'] == "")
	{
		die("<font color=red>Error...<br></font> <font color = blue>Please go back and fill in all the information.</font>");
	}

	if($_POST['bb_tbl'] == "")
	{
		$bb_tbl = "ibf_";
	}else{
		$bb_tbl = $_POST['bb_tbl'];
	}
	//------------------------------
	// Connect Now
	//------------------------------

	bb_connect();
	bbm_connect();

 	$bbm_uid_max = bb_maxcount (uid, $xoops_tbl."users",0,1);

	//------------------------------
	// Delete tables
	//------------------------------

	$del_tbls = array(badwords, calendar_events, categories, contacts, 
					  faq, forum_perms, forum_tracker, forums, groups, languages, member_extra, messages, 
					  moderators, pfields_content, pfields_data, polls, posts, reg_antispam, 
					  stats,subscription_currency,subscription_extra,subscription_methods,subscription_trans,subscriptions, 
					  titles,topic_mmod,topics,tracker,validating,voters
					);

	foreach ($del_tbls as $id)
	{
		$tbl = $bbm_tbl.$id;
		bb_print(0,$tbl,del);
		bbm_query("TRUNCATE TABLE ".$tbl." ");
		bb_print();
	}

	//------------------------------
	// Transfer Badwords Tables
	//------------------------------
	$badw_query = bb_query("SELECT * FROM ".$bb_tbl."badwords");
	bb_print($bb_tbl."badwords",$bbm_tbl."badwords");

	while ($badw_arr = bb_fetcharray($badw_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."badwords ( `wid` , `type` , `swop` , `m_exact` )
	    		   VALUES('".$badw_arr['wid']."', '".addslashes($badw_arr['type'])."', '".addslashes($badw_arr['swop'])."', '".$badw_arr['m_exact']."')
				  ");
	}
	bb_print();
/*
	//------------------------------
	// Transfer cache_store Tables
	//------------------------------
	$cstr_query = bb_query("SELECT * FROM ".$bb_tbl."cache_store");
	bb_print($bb_tbl."cache_store",$bbm_tbl."cache_store");

	while ($cstr_arr = bb_fetcharray($cstr_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."cache_store ( `cs_key` , `cs_value` , `cs_extra` ) 
	    		   VALUES('".addslashes($cstr_arr['cs_key'])."', '".addslashes($cstr_arr['cs_value'])."','".addslashes($cstr_arr['cs_extra'])."')
				  ");
	}
	bb_print();
*/
	//------------------------------
	// Transfer Categories Tables
	//------------------------------
	$cat_query = bb_query("SELECT * FROM ".$bb_tbl."categories");
	bb_print($bb_tbl."categories",$bbm_tbl."categories");

	while ($cat_arr = bb_fetcharray($cat_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."categories (`id`, `position`, `state`, `name`, `description`, `image`, `url`) 
	    		   VALUES('".$cat_arr['id']."', '".$cat_arr['position']."', '".$cat_arr['state']."', '".addslashes($cat_arr['name'])."', '".addslashes($cat_arr['description'])."', '".addslashes($cat_arr['image'])."', '".addslashes($cat_arr['url'])."')
				  ");
	}
	bb_print();

/*
	//------------------------------
	// Transfer css Tables
	//------------------------------
	$css_query = bb_query("SELECT * FROM ".$bb_tbl."css");
	bb_print($bb_tbl."css",$bbm_tbl."css");

	while ($css_arr = bb_fetcharray($css_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."css (`cssid`, `css_name`, `css_text`, `css_comments`, `updated`)
	    		   VALUES('".$css_arr['cssid']."', '".addslashes($css_arr['css_name'])."', '".addslashes($css_arr['css_text'])."', '".addslashes($css_arr['css_comments'])."', '".$css_arr['updated']."')
				  ");
	}
	bb_print();
*/
	//------------------------------
	// Transfer emoticons Tables
	//------------------------------
	$cat_query = bb_query("SELECT * FROM ".$bb_tbl."emoticons");
	bb_print($bb_tbl."emoticons",$xoops_tbl."smiles");
	$iconid=bb_maxcount(id,$xoops_tbl."smiles",0,1);
	while ($cat_arr = bb_fetcharray($cat_query))
	{
		++$iconid;
		bbm_query("INSERT INTO ".$xoops_tbl."smiles (`id`, `code`, `smile_url`, `clickable`)
	    		   VALUES($iconid, '".addslashes($cat_arr['typed'])."', '".addslashes($cat_arr['image'])."', '".$cat_arr['clickable']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer faq Tables
	//------------------------------
	$faq_query = bb_query("SELECT * FROM ".$bb_tbl."faq");
	bb_print($bb_tbl."faq",$bbm_tbl."faq");

	while ($faq_arr = bb_fetcharray($faq_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."faq (`id`, `title`, `text`, `description`)
	    		   VALUES('".$faq_arr['id']."', '".addslashes($faq_arr['title'])."', '".addslashes($faq_arr['text'])."', '".addslashes($faq_arr['description'])."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer forum_perms Tables
	//------------------------------
	$fperm_query = bb_query("SELECT * FROM ".$bb_tbl."forum_perms");
	bb_print($bb_tbl."forum_perms",$bbm_tbl."forum_perms");

	while ($fperm_arr = bb_fetcharray($fperm_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."forum_perms (`perm_id`, `perm_name`) 
	    		   VALUES('".$fperm_arr['perm_id']."', '".addslashes($fperm_arr['perm_name'])."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer groups Tables
	//------------------------------

	$grp_query = bb_query("SELECT * FROM ".$bb_tbl."groups ");
	bb_print($bb_tbl."groups",$bbm_tbl."groups");

	while ($grp_arr = bb_fetcharray($grp_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."groups (`g_id`, `g_view_board`, `g_mem_info`, `g_other_topics`, `g_use_search`, `g_email_friend`, `g_invite_friend`, `g_edit_profile`, `g_post_new_topics`, `g_reply_own_topics`, `g_reply_other_topics`, `g_edit_posts`, `g_delete_own_posts`, `g_open_close_posts`, `g_delete_own_topics`, `g_post_polls`, `g_vote_polls`, `g_use_pm`, `g_is_supmod`, `g_access_cp`, `g_title`, `g_can_remove`, `g_append_edit`, `g_access_offline`, `g_avoid_q`, `g_avoid_flood`, `g_icon`, `g_attach_max`, `g_avatar_upload`, `g_calendar_post`, `prefix`, `suffix`, `g_max_messages`, `g_max_mass_pm`, `g_search_flood`, `g_edit_cutoff`, `g_promotion`, `g_hide_from_list`, `g_post_closed`, `g_perm_id`, `g_photo_max_vars`, `g_dohtml`, `g_edit_topic`, `g_email_limit`) 
				   VALUES ('".$grp_arr['g_id']."','".$grp_arr['g_view_board']."','".$grp_arr['g_mem_info']."','".$grp_arr['g_other_topics']."','".$grp_arr['g_use_search']."','".$grp_arr['g_email_friend']."','".$grp_arr['g_invite_friend']."','".$grp_arr['g_edit_profile']."','".$grp_arr['g_post_new_topics']."','".$grp_arr['g_reply_own_topics']."','".$grp_arr['g_reply_other_topics']."','".$grp_arr['g_edit_posts']."','".$grp_arr['g_delete_own_posts']."','".$grp_arr['g_open_close_posts']."','".$grp_arr['g_delete_own_topics']."','".$grp_arr['g_post_polls']."','".$grp_arr['g_vote_polls']."','".$grp_arr['g_use_pm']."','".addslashes($grp_arr['g_is_supmod'])."','".$grp_arr['g_access_cp']."','".addslashes($grp_arr['g_title'])."','".$grp_arr['g_can_remove']."','".$grp_arr['g_append_edit']."','".$grp_arr['g_access_offline']."',
				   		   '".$grp_arr['g_avoid_q']."','".$grp_arr['g_avoid_flood']."','".addslashes($grp_arr['g_icon'])."','".$grp_arr['g_attach_max']."','".$grp_arr['g_avatar_upload']."','".$grp_arr['g_calendar_post']."','".addslashes($grp_arr['prefix'])."','".addslashes($grp_arr['suffix'])."','".$grp_arr['g_max_messages']."','".$grp_arr['g_max_mass_pm']."','".$grp_arr['g_search_flood']."','".$grp_arr['g_edit_cutoff']."','".$grp_arr['g_promotion']."','".$grp_arr['g_hide_from_list']."','".$grp_arr['g_post_closed']."','".$grp_arr['g_perm_id']."','".$grp_arr['g_photo_max_vars']."','".$grp_arr['g_dohtml']."','".$grp_arr['g_edit_topic']."','".$grp_arr['g_email_limit']."')				   																																																																																												
				 ");
	}
	bb_print();
/*
	//------------------------------
	// Transfer languages Tables
	//------------------------------
	$lang_query = bb_query("SELECT * FROM ".$bb_tbl."languages");
	bb_print($bb_tbl."languages",$bbm_tbl."languages");

	while ($lang_arr = bb_fetcharray($lang_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."languages (`lid`, `ldir`, `lname`, `lauthor`, `lemail`) 
	    		   VALUES('".$lang_arr['lid']."', '".$lang_arr['ldir']."', '".addslashes($lang_arr['lname'])."', '".addslashes($lang_arr['lauthor'])."', '".addslashes($lang_arr['lemail'])."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer macro Tables
	//------------------------------
	$macro_query = bb_query("SELECT * FROM ".$bb_tbl."macro");
	bb_print($bb_tbl."macro",$bbm_tbl."macro");

	while ($macro_arr = bb_fetcharray($macro_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."macro (`macro_id`, `macro_value`, `macro_replace`, `can_remove`, `macro_set`)
	    		   VALUES('".$macro_arr['macro_id']."', '".addslashes($macro_arr['macro_value'])."', '".addslashes($macro_arr['macro_replace'])."', '".$macro_arr['can_remove']."', '".$macro_arr['macro_set']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer macro name Tables
	//------------------------------
	$macro_query = bb_query("SELECT * FROM ".$bb_tbl."macro_name");
	bb_print($bb_tbl."macro_name",$bbm_tbl."macro_name");

	while ($macro_arr = bb_fetcharray($macro_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."macro_name (`set_id`, `set_name`)
	    		   VALUES('".$macro_arr['set_id']."', '".addslashes($macro_arr['set_name'])."')
				  ");
	}
	bb_print();
*/
	//------------------------------
	// Transfer pfields_data Tables
	//------------------------------
	$pfdata_query = bb_query("SELECT * FROM ".$bb_tbl."pfields_data");
	bb_print($bb_tbl."pfields_data",$bbm_tbl."pfields_data");

	while ($pfdata_arr = bb_fetcharray($pfdata_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."pfields_data ( `fid` , `ftitle` , `fdesc` , `fcontent` , `ftype` , `freq` , `fhide` , `fmaxinput` , `fedit` , `forder` , `fshowreg` )
	    		   VALUES('".$pfdata_arr['fid']."','".addslashes($pfdata_arr['ftitle'])."','".addslashes($pfdata_arr['fdesc'])."', '".addslashes($pfdata_arr['fcontent'])."','".addslashes($pfdata_arr['ftype'])."','".$pfdata_arr['freq']."','".$pfdata_arr['fhide']."','".$pfdata_arr['fmaxinput']."','".$pfdata_arr['fedit']."','".$pfdata_arr['forder']."','".$pfdata_arr['fshowreg']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer reg_antispam Tables
	//------------------------------
	$reganti_query = bb_query("SELECT * FROM ".$bb_tbl."reg_antispam");
	bb_print($bb_tbl."reg_antispam",$bbm_tbl."reg_antispam");

	while ($reganti_arr = bb_fetcharray($reganti_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."reg_antispam ( `regid` , `regcode` , `ip_address` , `ctime` ) 
	    		   VALUES('".$reganti_arr['regid']."','".addslashes($reganti_arr['regcode'])."','".$reganti_arr['ip_address']."', '".$reganti_arr['ctime']."')
				  ");
	}
	bb_print();

/*
	//------------------------------
	// Transfer skin_templates Tables
	//------------------------------
	$skintpl_num = bb_maxcount (suid, $bb_tbl."skin_templates",count,0);
	$skintpl_st = 0;
	$skintpl_offset = 250;

	bb_print($bb_tbl."skin_templates",$bbm_tbl."skin_templates");
	while ($skintpl_st <= $skintpl_num)
	{
		$skintpl_query = bb_query("SELECT * FROM ".$bb_tbl."skin_templates ORDER BY suid ASC LIMIT ".$skintpl_st.",".$skintpl_offset."");
		while ($skintpl_arr = bb_fetcharray($skintpl_query))
		{
			bbm_query("INSERT INTO ".$bbm_tbl."skin_templates (`suid`, `set_id`, `group_name`, `section_content`, `func_name`, `func_data`, `updated`, `can_remove`)
		    		   VALUES('".$skintpl_arr['suid']."','".$skintpl_arr['set_id']."','".addslashes($skintpl_arr['group_name'])."', '".addslashes($skintpl_arr['section_content'])."','".addslashes($skintpl_arr['func_name'])."','".addslashes($skintpl_arr['func_data'])."','".$skintpl_arr['updated']."','".$skintpl_arr['can_remove']."')
					  ");
		}
		$skintpl_st += $skintpl_offset;
	}		
	bb_print();

	//------------------------------
	// Transfer skins Tables
	//------------------------------
	$skin_query = bb_query("SELECT * FROM ".$bb_tbl."skins");
	bb_print($bb_tbl."skins",$bbm_tbl."skins");

	while ($skin_arr = bb_fetcharray($skin_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."skins (`uid`, `sname`, `sid`, `set_id`, `tmpl_id`, `macro_id`, `css_id`, `img_dir`, `tbl_width`, `tbl_border`, `hidden`, `default_set`, `css_method`)
	    		   VALUES('".$skin_arr['uid']."', '".addslashes($skin_arr['sname'])."', '".$skin_arr['sid']."','".$skin_arr['set_id']."','".$skin_arr['tmpl_id']."','".$skin_arr['macro_id']."','".$skin_arr['css_id']."','".addslashes($skin_arr['img_dir'])."','".addslashes($skin_arr['tbl_width'])."','".addslashes($skin_arr['tbl_border'])."','".$skin_arr['hidden']."','".$skin_arr['default_set']."','".addslashes($skin_arr['css_method'])."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer templates Tables
	//------------------------------
	$tpl_query = bb_query("SELECT * FROM ".$bb_tbl."templates");
	bb_print($bb_tbl."templates",$bbm_tbl."templates");

	while ($tpl_arr = bb_fetcharray($tpl_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."templates (`tmid`, `template`, `name`) 
	    		   VALUES('".$tpl_arr['tmid']."', '".addslashes($tpl_arr['template'])."', '".addslashes($tpl_arr['name'])."')
				  ");
	}
	bb_print();
*/
	//------------------------------
	// Transfer titles Tables
	//------------------------------
	$title_query = bb_query("SELECT * FROM ".$bb_tbl."titles");
	bb_print($bb_tbl."titles",$bbm_tbl."titles");

	while ($title_arr = bb_fetcharray($title_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."titles (`id`, `posts`, `title`, `pips`)
	    		   VALUES('".$title_arr['id']."', '".$title_arr['posts']."', '".addslashes($title_arr['title'])."','".$title_arr['pips']."')
				  ");
	}
	bb_print();
/*
	//------------------------------
	// Transfer tmpl_names Tables
	//------------------------------
	$tpln_query = bb_query("SELECT * FROM ".$bb_tbl."tmpl_names");
	bb_print($bb_tbl."tmpl_names",$bbm_tbl."tmpl_names");

	while ($tpln_arr = bb_fetcharray($tpln_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."tmpl_names (`skid`, `skname`, `author`, `email`, `url`) 
	    		   VALUES('".$tpln_arr['skid']."', '".addslashes($tpln_arr['skname'])."', '".addslashes($tpln_arr['author'])."','".addslashes($tpln_arr['email'])."','".addslashes($tpln_arr['url'])."')
				  ");
	}
	bb_print();
*/
	//------------------------------
	// Transfer topic_mmod Tables
	//------------------------------
	$topicm_query = bb_query("SELECT * FROM ".$bb_tbl."topic_mmod");
	bb_print($bb_tbl."topic_mmod",$bbm_tbl."topic_mmod");

	while ($topicm_arr = bb_fetcharray($topicm_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."topic_mmod ( `mm_id` , `mm_title` , `mm_enabled` , `topic_state` , `topic_pin` , `topic_move` , `topic_move_link` , `topic_title_st` , `topic_title_end` , `topic_reply` , `topic_reply_content` , `topic_reply_postcount` ) 
	    		   VALUES('".$topicm_arr['mm_id']."', '".addslashes($topicm_arr['mm_title'])."', '".$topicm_arr['mm_enabled']."','".$topicm_arr['topic_state']."','".$topicm_arr['topic_pin']."','".$topicm_arr['topic_move']."','".addslashes($topicm_arr['topic_move_link'])."','".addslashes($topicm_arr['topic_title_st'])."','".addslashes($topicm_arr['topic_title_end'])."','".$topicm_arr['topic_reply']."','".addslashes($topicm_arr['topic_reply_content'])."','".$topicm_arr['topic_reply_postcount']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer users Tables
	//------------------------------
	$userid=array();
	$userid[0]=0;
	$userid_cache=array();
	$userid_cache[0]=0;
	$uid_tmp = $bbm_uid_max;
	
	bb_print($bb_tbl."members",$xoops_tbl."users");
	
	$mem_num = bb_maxcount (id, $bb_tbl."members",count,0);
	$mem_offset = 150;
	$mem_st = 0;

	while ($mem_st <= $mem_num)
	{
		$user_query = bb_query("SELECT * FROM ".$bb_tbl."members WHERE id<>0 ORDER BY id ASC LIMIT ".$mem_st.",".$mem_offset." ");
		while ($user_arr = bb_fetcharray($user_query))
		{
			$bbm_user_query = bbm_query("SELECT uid, posts FROM ".$xoops_tbl."users WHERE uid<>0 AND lower(uname)='".stripslashes(strtolower($user_arr['name']))."'");
			$bbm_user_arr   = bb_fetcharray($bbm_user_query);

			if ($bbm_user_arr['uid'])
			{
				$posts = $bbm_user_arr['posts'] + $user_arr['posts'];
				$userid[$user_arr[id]] = $bbm_user_arr['uid'];
				$userid_cache[$user_arr[id]] = $bbm_user_arr['uid'];
				bbm_query("UPDATE ".$xoops_tbl."users SET posts = ".$posts." WHERE uid = ".$bbm_user_arr['uid']." ");
			}
			else {
				++$uid_tmp;
				$userid[$user_arr[id]] = $uid_tmp ;
			
				bbm_query("INSERT INTO ".$xoops_tbl."users (`uid`, `uname`, `email`, `url`, `user_avatar`, `user_regdate`, `user_icq`, `user_from`, `user_sig`, `user_viewemail`, 
				                                            `user_aim`, `user_yim`, `user_msnm`, `pass`, `posts`, `attachsig`, `timezone_offset`, `user_intrest`, `user_mailok`, `mgroup`, 
				                                            `ip_address`, `avatar_size`, `title`, `email_pm`, `email_full`, `skin`, `warn_level`, `warn_lastwarn`, `language`, `last_post`, 
				                                            `restrict_post`, `view_img`, `view_avs`, `view_pop`, `bday_day`, `bday_month`, `bday_year`, `new_msg`, `msg_from_id`, `msg_msg_id`, 
				                                            `msg_total`, `vdirs`, `show_popup`, `misc`, `last_visit`, `last_activity`, `dst_in_use`, `view_prefs`, `coppa_user`, `mod_posts`, 
				                                            `auto_track`, `org_perm_id`, `org_supmod`, `integ_msg`, `temp_ban`, sub_end)
			    		   VALUES($uid_tmp,'".addslashes($user_arr['name'])."','".addslashes($user_arr['email'])."', '".addslashes($user_arr['website'])."','".addslashes($user_arr['avatar'])."','".$user_arr['joined']."','".$user_arr['icq_number']."','".addslashes($user_arr['location'])."','".addslashes($user_arr['signature'])."',replace('".$user_arr['hide_email']."','1','0'),
			    		          '".addslashes($user_arr['aim_name'])."','".addslashes($user_arr['yahoo'])."','".addslashes($user_arr['msnname'])."','".$user_arr['password']."','".$user_arr['posts']."','".$user_arr['view_sigs']."','".$user_arr['time_offset']."','".addslashes($user_arr['interests'])."','".$user_arr['allow_admin_mails']."','".$user_arr['mgroup']."',
			    		          '".$user_arr['ip_address']."','".$user_arr['avatar_size']."','".addslashes($user_arr['title'])."','".$user_arr['email_pm']."','".$user_arr['email_full']."','".$user_arr['skin']."','".$user_arr['warn_level']."','".$user_arr['warn_lastwarn']."','".addslashes($user_arr['language'])."','".$user_arr['last_post']."',
			    		          '".addslashes($user_arr['restrict_post'])."','".$user_arr['view_img']."','".$user_arr['view_avs']."','".$user_arr['view_pop']."','".$user_arr['bday_day']."','".$user_arr['bday_month']."','".$user_arr['bday_year']."','".$user_arr['new_msg']."','".$user_arr['msg_from_id']."','".$user_arr['msg_msg_id']."',
			    		          '".$user_arr['msg_total']."','".addslashes($user_arr['vdirs'])."','".$user_arr['show_popup']."','".addslashes($user_arr['misc'])."','".$user_arr['last_visit']."','".$user_arr['last_activity']."','".$user_arr['dst_in_use']."','".addslashes($user_arr['view_prefs'])."','".$user_arr['coppa_user']."','".addslashes($user_arr['mod_posts'])."',
			    		          '".$user_arr['auto_track']."','".addslashes($user_arr['org_perm_id'])."','".addslashes($user_arr['org_supmod'])."','".addslashes($user_arr['integ_msg'])."','".addslashes($user_arr['temp_ban'])."','".$user_arr['sub_end']."')
						  ");
				bbm_query("INSERT INTO ".$xoops_tbl."groups_users_link (groupid,uid) VALUES (2,$uid_tmp)");
				if ($user_arr['mgroup']==4) {
					bbm_query("INSERT INTO ".$xoops_tbl."groups_users_link (groupid,uid) VALUES (1,$uid_tmp)");				
				}
			}
		}
		$mem_st += $mem_offset;
	}
	bb_print();

	//------------------------------
	// Transfer calendar_events Tables
	//------------------------------
	$cale_query = bb_query("SELECT * FROM ".$bb_tbl."calendar_events");
	bb_print($bb_tbl."calendar_events",$bbm_tbl."calendar_events");

	while ($cale_arr = bb_fetcharray($cale_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."calendar_events ( `eventid` , `userid` , `year` , `month` , `mday` , `title` , `event_text` , `read_perms` , `unix_stamp` , `priv_event` , `show_emoticons` , `rating` , `event_ranged` , `event_repeat` , `repeat_unit` , `end_day` , `end_month` , `end_year` , `end_unix_stamp` , `event_bgcolor` , `event_color` ) 
	    		   VALUES('".$cale_arr['eventid']."', '".$userid[$cale_arr['userid']]."', '".$cale_arr['year']."', '".$cale_arr['month']."', '".$cale_arr['mday']."', '".addslashes($cale_arr['title'])."', '".addslashes($cale_arr['event_text'])."', '".$cale_arr['read_perms']."', '".$cale_arr['unix_stamp']."', '".$cale_arr['priv_event']."', '".$cale_arr['show_emoticons']."',
	    		   		  '".$cale_arr['rating']."', '".$cale_arr['event_ranged']."', '".$cale_arr['event_repeat']."', '".$cale_arr['repeat_unit']."', '".$cale_arr['end_day']."', '".$cale_arr['end_month']."', '".$cale_arr['end_year']."', '".$cale_arr['end_unix_stamp']."', '".addslashes($cale_arr['event_bgcolor'])."', '".addslashes($cale_arr['event_color'])."' )
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer contacts Tables
	//------------------------------
	$cnt_num = bb_maxcount (id, $bb_tbl."contacts",count,0);
	$cnt_st = 0;
	$cnt_offset = 150;

	bb_print($bb_tbl."contacts",$bbm_tbl."contacts");
	while ($cnt_st <= $cnt_num)
	{
		$cnt_query = bb_query("SELECT * FROM ".$bb_tbl."contacts ORDER BY id ASC LIMIT ".$cnt_st.",".$cnt_offset."");	
		while ($cnt_arr = bb_fetcharray($cnt_query))
		{
			bbm_query("INSERT INTO ".$bbm_tbl."contacts ( `id` , `contact_id` , `member_id` , `contact_name` , `allow_msg` , `contact_desc` )
		    		   VALUES('".$cnt_arr['id']."', '".$userid[$cnt_arr['contact_id']]."', '".$userid[$cnt_arr['member_id']]."', '".addslashes(username($userid[$cnt_arr['contact_id']]))."', '".$cnt_arr['allow_msg']."', '".addslashes($cnt_arr['contact_desc'])."')
					  ");
		}
		$cnt_st += $cnt_offset;
	}						
	bb_print();

	//------------------------------
	// Transfer forum_tracker Tables
	//------------------------------
	$ftrack_num = bb_maxcount (frid, $bb_tbl."forum_tracker",count,0);
	$ftrack_st = 0;
	$ftrack_offset = 150;

	bb_print($bb_tbl."forum_tracker",$bbm_tbl."forum_tracker");
	while ($ftrack_st <= $ftrack_num)
	{
		$ftrack_query = bb_query("SELECT * FROM ".$bb_tbl."forum_tracker ORDER BY frid ASC LIMIT ".$ftrack_st.",".$ftrack_offset."");	
		while ($ftrack_arr = bb_fetcharray($ftrack_query))
		{
			bbm_query("INSERT INTO ".$bbm_tbl."forum_tracker ( `frid` , `member_id` , `forum_id` , `start_date` , `last_sent` ) 
		    		   VALUES('".$ftrack_arr['frid']."', '".$userid[$ftrack_arr['member_id']]."', '".$ftrack_arr['forum_id']."', '".$ftrack_arr['start_date']."', '".$ftrack_arr['last_sent']."')
					  ");
		}
		$ftrack_st += $ftrack_offset;
	}				
	bb_print();

	//------------------------------
	// Transfer Forum Tables
	//------------------------------
	$fr_query = bb_query("SELECT * FROM ".$bb_tbl."forums ");
	bb_print($bb_tbl."forums",$bbm_tbl."forums");

	while ($fr_arr = bb_fetcharray($fr_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."forums (`id`, `topics`, `posts`, `last_post`, `last_poster_id`, `last_poster_name`, `name`, `description`, `position`, `use_ibc`, `use_html`, `status`, `start_perms`, `reply_perms`, `read_perms`, `password`, `category`, `last_title`, `last_id`, `sort_key`, `sort_order`, `prune`, `show_rules`, `upload_perms`, `preview_posts`, `allow_poll`, `allow_pollbump`, `inc_postcount`, `skin_id`, `parent_id`, `subwrap`, `sub_can_post`, `quick_reply`, `redirect_url`, `redirect_on`, `redirect_hits`, `redirect_loc`, `rules_title`, `rules_text`, `has_mod_posts`, `topic_mm_id`, notify_modq_emails )		
				   VALUES ('".$fr_arr['id']."','".$fr_arr['topics']."','".$fr_arr['posts']."','".$fr_arr['last_post']."','".$userid[$fr_arr['last_poster_id']]."','".addslashes(username($userid[$fr_arr['last_poster_id']],$fr_arr['last_poster_name']))."','".addslashes($fr_arr['name'])."','".addslashes($fr_arr['description'])."','".$fr_arr['position']."','".$fr_arr['use_ibc']."','".$fr_arr['use_html']."','".$fr_arr['status']."','".$fr_arr['start_perms']."','".$fr_arr['reply_perms']."','".$fr_arr['read_perms']."','".$fr_arr['password']."','".$fr_arr['category']."','".addslashes($fr_arr['last_title'])."','".$fr_arr['last_id']."','".$fr_arr['sort_key']."','".$fr_arr['sort_order']."',
				   		   '".$fr_arr['prune']."','".$fr_arr['show_rules']."','".$fr_arr['upload_perms']."','".$fr_arr['preview_posts']."','".$fr_arr['allow_poll']."','".$fr_arr['allow_pollbump']."','".$fr_arr['inc_postcount']."','".$fr_arr['skin_id']."','".$fr_arr['parent_id']."','".$fr_arr['subwrap']."','".$fr_arr['sub_can_post']."','".$fr_arr['quick_reply']."','".$fr_arr['redirect_url']."','".$fr_arr['redirect_on']."','".$fr_arr['redirect_hits']."','".addslashes($fr_arr['redirect_loc'])."','".addslashes($fr_arr['rules_title'])."','".addslashes($fr_arr['rules_text'])."','".$fr_arr['has_mod_posts']."','".addslashes($fr_arr['topic_mm_id'])."','".addslashes($fr_arr['notify_modq_emails'])."')				   																																																																																												
				 ");
	}
	bb_print();

	//------------------------------
	// Transfer member_extra Tables
	//------------------------------
	$mext_query = bb_query("SELECT * FROM ".$bb_tbl."member_extra");
	bb_print($bb_tbl."member_extra",$bbm_tbl."member_extra");

	while ($mext_arr = bb_fetcharray($mext_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."member_extra ( `id` , `notes` , `links` , `bio` , `ta_size` , `photo_type` , `photo_location` , `photo_dimensions` ) 
	    		   VALUES('".$userid[$mext_arr['id']]."', '".addslashes($mext_arr['notes'])."', '".addslashes($mext_arr['links'])."','".addslashes($mext_arr['bio'])."','".addslashes($mext_arr['ta_size'])."','".addslashes($mext_arr['photo_type'])."','".addslashes($mext_arr['photo_location'])."','".addslashes($mext_arr['photo_dimensions'])."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer messages Tables
	//------------------------------
	$msg_num = bb_maxcount (msg_id, $bb_tbl."messages",count,0);
	$msg_st = 0;
	$msg_offset = 150;

	bb_print($bb_tbl."messages",$bbm_tbl."messages");
	while ($msg_st <= $msg_num)
	{
		$msg_query = bb_query("SELECT * FROM ".$bb_tbl."messages ORDER BY msg_id ASC LIMIT ".$msg_st.",".$msg_offset."");

		while ($msg_arr = bb_fetcharray($msg_query))
		{
			bbm_query("INSERT INTO ".$bbm_tbl."messages (`msg_id`, `msg_date`, `read_state`, `title`, `message`, `from_id`, `vid`, `member_id`, `recipient_id`, `attach_type`, `attach_file`, `cc_users`, `tracking`, `read_date`)
		    		   VALUES('".$msg_arr['msg_id']."','".$msg_arr['msg_date']."','".$msg_arr['read_state']."', '".addslashes($msg_arr['title'])."','".addslashes($msg_arr['message'])."','".$userid[$msg_arr['from_id']]."','".addslashes($msg_arr['vid'])."','".$userid[$msg_arr['member_id']]."','".$userid[$msg_arr['recipient_id']]."','".$msg_arr['attach_type']."','".$msg_arr['attach_file']."','".addslashes($msg_arr['cc_users'])."','".$msg_arr['tracking']."','".$msg_arr['read_date']."')
					  ");
		}
		$msg_st += $msg_offset;
	}		
	bb_print();

	//------------------------------
	// Transfer moderators Tables
	//------------------------------
	$mod_query = bb_query("SELECT * FROM ".$bb_tbl."moderators");
	bb_print($bb_tbl."moderators",$bbm_tbl."moderators");

	while ($mod_arr = bb_fetcharray($mod_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."moderators (`mid`, `forum_id`, `member_name`, `member_id`, `edit_post`, `edit_topic`, `delete_post`, `delete_topic`, `view_ip`, `open_topic`, `close_topic`, `mass_move`, `mass_prune`, `move_topic`, `pin_topic`, `unpin_topic`, `post_q`, `topic_q`, `allow_warn`, `edit_user`, `is_group`, `group_id`, `group_name`, `split_merge`, `can_mm`)
	    		   VALUES('".$mod_arr['mid']."','".$mod_arr['forum_id']."','".addslashes(username($userid[$mod_arr['member_id']]))."', '".$userid[$mod_arr['member_id']]."','".$mod_arr['edit_post']."','".$mod_arr['edit_topic']."','".$mod_arr['delete_post']."','".$mod_arr['delete_topic']."','".$mod_arr['view_ip']."','".$mod_arr['open_topic']."','".$mod_arr['close_topic']."','".$mod_arr['mass_move']."',
	    		   		  '".$mod_arr['mass_prune']."','".$mod_arr['move_topic']."','".$mod_arr['pin_topic']."','".$mod_arr['unpin_topic']."','".$mod_arr['post_q']."','".$mod_arr['topic_q']."','".$mod_arr['allow_warn']."','".$mod_arr['edit_user']."','".$mod_arr['is_group']."','".$mod_arr['group_id']."','".addslashes($mod_arr['group_name'])."','".$mod_arr['split_merge']."','".$mod_arr['can_mm']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer pfields_content Tables
	//------------------------------
	$pfct_query = bb_query("SELECT * FROM ".$bb_tbl."pfields_content");
	bb_print($bb_tbl."pfields_content",$bbm_tbl."pfields_content");

	while ($pfct_arr = bb_fetcharray($pfct_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."pfields_content ( `member_id` , `updated` )  
	    		   VALUES('".$userid[$pfct_arr['member_id']]."','".$pfct_arr['updated']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer polls Tables
	//------------------------------
	$poll_query = bb_query("SELECT * FROM ".$bb_tbl."polls");
	bb_print($bb_tbl."polls",$bbm_tbl."polls");

	while ($poll_arr = bb_fetcharray($poll_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."polls ( `pid` , `tid` , `start_date` , `choices` , `starter_id` , `votes` , `forum_id` , `poll_question` ) 
	    		   VALUES('".$poll_arr['pid']."','".$poll_arr['tid']."','".$poll_arr['start_date']."', '".addslashes($poll_arr['choices'])."','".$userid[$poll_arr['starter_id']]."','".$poll_arr['votes']."','".$poll_arr['forum_id']."','".addslashes($poll_arr['poll_question'])."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer posts Tables
	//------------------------------
	$post_num = bb_maxcount (pid, $bb_tbl."posts",count,0);
	$post_st = 0;
	$post_offset = 150;

	bb_print($bb_tbl."posts",$bbm_tbl."posts");
	while ($post_st <= $post_num)
	{
		$post_query = bb_query("SELECT * FROM ".$bb_tbl."posts ORDER BY pid ASC LIMIT ".$post_st.",".$post_offset."");	
		while ($post_arr = bb_fetcharray($post_query))
		{
//			$post_arr['posts']=str_replace($old_url, $new_url, $post_arr['posts'])
			bbm_query("INSERT INTO ".$bbm_tbl."posts (`pid`, `append_edit`, `edit_time`, `author_id`, `author_name`, `use_sig`, `use_emo`, `ip_address`, `post_date`, `icon_id`, `post`, `queued`, `topic_id`, `forum_id`, `attach_id`, `attach_hits`, `attach_type`, `attach_file`, `post_title`, `new_topic`, `edit_name`) 
		    		   VALUES('".$post_arr['pid']."','".$post_arr['append_edit']."','".$post_arr['edit_time']."', '".$userid[$post_arr['author_id']]."','".addslashes(username($userid[$post_arr['author_id']],$post_arr['author_name']))."','".$post_arr['use_sig']."','".$post_arr['use_emo']."','".$post_arr['ip_address']."','".$post_arr['post_date']."','".$post_arr['icon_id']."','".addslashes($post_arr['post'])."',
		    		   		  '".$post_arr['queued']."','".$post_arr['topic_id']."','".$post_arr['forum_id']."','".addslashes($post_arr['attach_id'])."','".$post_arr['attach_hits']."','".addslashes($post_arr['attach_type'])."','".addslashes($post_arr['attach_file'])."','".addslashes($post_arr['post_title'])."','".$post_arr['new_topic']."','".addslashes($post_arr['edit_name'])."')
					  ");
		}
		$post_st += $post_offset;
	}				
	bb_print();

	//------------------------------
	// Transfer stats Tables
	//------------------------------
	$stat_query = bb_query("SELECT * FROM ".$bb_tbl."stats");
	bb_print($bb_tbl."stats",$bbm_tbl."stats");

	while ($stat_arr = bb_fetcharray($stat_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."stats (`TOTAL_REPLIES`, `TOTAL_TOPICS`, `LAST_MEM_NAME`, `LAST_MEM_ID`, `MOST_DATE`, `MOST_COUNT`, `MEM_COUNT`)
	    		   VALUES('".$stat_arr['TOTAL_REPLIES']."', '".$stat_arr['TOTAL_TOPICS']."', '".addslashes(username($userid[$stat_arr['LAST_MEM_ID']],$stat_arr['LAST_MEM_NAME']))."','".$userid[$stat_arr['LAST_MEM_ID']]."','".$stat_arr['MOST_DATE']."','".$stat_arr['MOST_COUNT']."','".$stat_arr['MEM_COUNT']."')
				  ");
	}
	bb_print();

	//+-----------------------------------------------
	//| Transfer subscription_currency Tables
	//+-----------------------------------------------
	$subcur_qr = bb_query("SELECT * FROM ".$bb_tbl."subscription_currency");
	bb_print($bb_tbl."subscription_currency",$bbm_tbl."subscription_currency");

	while ($subcur_ar = bb_fetcharray($subcur_qr))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."subscription_currency (subcurrency_code, subcurrency_desc, subcurrency_exchange, subcurrency_default)
	    		   VALUES('".addslashes($subcur_ar['subcurrency_code'])."',
	    		          '".addslashes($subcur_ar['subcurrency_desc'])."', 
	    		          '".$subcur_ar['subcurrency_exchange']."',
	    		          '".$subcur_ar['subcurrency_default']."'
	    		          )
				  ");
	}
	bb_print();
	
	//+-----------------------------------------------
	//| Transfer subscription_extra Tables
	//+-----------------------------------------------
	$subext_qr = bb_query("SELECT * FROM ".$bb_tbl."subscription_extra");
	bb_print($bb_tbl."subscription_extra",$bbm_tbl."subscription_extra");

	while ($subext_ar = bb_fetcharray($subext_qr))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."subscription_extra (subextra_id, subextra_sub_id, subextra_method_id, subextra_product_id, subextra_can_upgrade, subextra_recurring, subextra_custom_1, subextra_custom_2, subextra_custom_3, subextra_custom_4, subextra_custom_5)
	    		   VALUES('".$subext_ar['subextra_id']."',
	    		          '".$subext_ar['subextra_sub_id']."', 
	    		          '".$subext_ar['subextra_method_id']."',
	    		          '".addslashes($subext_ar['subpgrade']."',
	    		          '".$subext_ar['subextra_recuextra_product_id'])."',
	    		          '".$subext_ar['subextra_can_urring']."',
	    		          '".addslashes($subext_ar['subextra_custom_1'])."',
	    		          '".addslashes($subext_ar['subextra_custom_2'])."',
	    		          '".addslashes($subext_ar['subextra_custom_3'])."',
	    		          '".addslashes($subext_ar['subextra_custom_4'])."',
	    		          '".addslashes($subext_ar['subextra_custom_5'])."'
	    		          )
				  ");
	}
	bb_print();
	
	//+-----------------------------------------------
	//| Transfer subscription_methods Tables
	//+-----------------------------------------------
	$submt_qr = bb_query("SELECT * FROM ".$bb_tbl."subscription_methods");
	bb_print($bb_tbl."subscription_methods",$bbm_tbl."subscription_methods");

	while ($submt_ar = bb_fetcharray($submt_qr))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."subscription_methods (submethod_id, submethod_title, submethod_name, submethod_email, submethod_sid, submethod_custom_1, submethod_custom_2, submethod_custom_3, submethod_custom_4, submethod_custom_5, submethod_is_cc, submethod_is_auto, submethod_desc, submethod_logo, submethod_active, submethod_use_currency)
	    		   VALUES('".$submt_ar['submethod_id']."',
	    		          '".addslashes($submt_ar['submethod_title']   )."', 
	    		          '".addslashes($submt_ar['submethod_name']    )."',
	    		          '".addslashes($submt_ar['submethod_email']   )."',
	    		          '".addslashes($submt_ar['submethod_sid']     )."',
	    		          '".addslashes($submt_ar['submethod_custom_1'])."',
	    		          '".addslashes($submt_ar['submethod_custom_2'])."',
	    		          '".addslashes($submt_ar['submethod_custom_3'])."',
	    		          '".addslashes($submt_ar['submethod_custom_4'])."',
	    		          '".addslashes($submt_ar['submethod_custom_5'])."',
	    		          '".$submt_ar['submethod_is_cc']."',
	    		          '".$submt_ar['submethod_is_auto']."',
	    		          '".addslashes($submt_ar['submethod_desc'])."',
	    		          '".addslashes($submt_ar['submethod_logo'])."',
	    		          '".$submt_ar['submethod_active']."',
	    		          '".addslashes($submt_ar['submethod_use_currency'])."'  		          
	    		          )
				  ");
	}
	bb_print();
	
	//+-----------------------------------------------
	//| Transfer subscription_trans Tables
	//+-----------------------------------------------
	$subtrans_qr = bb_query("SELECT * FROM ".$bb_tbl."subscription_trans");
	bb_print($bb_tbl."subscription_trans",$bbm_tbl."subscription_trans");

	while ($subtrans_ar = bb_fetcharray($subtrans_qr))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."subscription_trans (subtrans_id, subtrans_sub_id, subtrans_member_id, subtrans_old_group, subtrans_paid, subtrans_cumulative, subtrans_method, subtrans_start_date, subtrans_end_date, subtrans_state, subtrans_trxid, subtrans_subscrid, subtrans_currency)
	    		   VALUES('".$subtrans_ar['subtrans_id']."',
	    		          '".$subtrans_ar['subtrans_sub_id']."', 
	    		          '".$subtrans_ar['subtrans_member_id']."',
	    		          '".$subtrans_ar['subtrans_old_group']."',
	    		          '".$subtrans_ar['subtrans_paid']."',
	    		          '".$subtrans_ar['subtrans_cumulative']."',
	    		          '".addslashes($subtrans_ar['subtrans_method']  )."',
	    		          '".$subtrans_ar['subtrans_start_date']."',
	    		          '".$subtrans_ar['subtrans_end_date']."',
	    		          '".addslashes($subtrans_ar['subtrans_state']   )."',
	    		          '".addslashes($subtrans_ar['subtrans_trxid']   )."',
	    		          '".addslashes($subtrans_ar['subtrans_subscrid'])."',
	    		          '".addslashes($subtrans_ar['subtrans_currency'])."',
	    		          )
				  ");
	}
	bb_print();
	
	//+-----------------------------------------------
	//| Transfer subscriptions Tables
	//+-----------------------------------------------
	$subcript_qr = bb_query("SELECT * FROM ".$bb_tbl."subscriptions");
	bb_print($bb_tbl."subscriptions",$bbm_tbl."subscriptions");

	while ($subcript_ar = bb_fetcharray($subcript_qr))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."subscriptions (sub_id, sub_title, sub_desc, sub_new_group, sub_length, sub_unit, sub_cost, sub_run_module) 
	    		   VALUES('".$subcript_ar['sub_id']."',
	    		          '".addslashes($subcript_ar['sub_title'])."', 
	    		          '".addslashes($subcript_ar['sub_desc'] )."',
	    		          '".$subcript_ar['sub_new_group']."',
	    		          '".$subcript_ar['sub_length']."',
	    		          '".addslashes($subcript_ar['sub_unit']      )."',
	    		          '".$subcript_ar['sub_cost']."',
	    		          '".addslashes($subcript_ar['sub_run_module'])."'
	    		          )
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer topics Tables
	//------------------------------
	$topic_num = bb_maxcount (tid, $bb_tbl."topics",count,0);
	$topic_st = 0;
	$topic_offset =150;

	bb_print($bb_tbl."topics",$bbm_tbl."topics");
	while ($topic_st <= $topic_num)
	{
		$topic_query = bb_query("SELECT * FROM ".$bb_tbl."topics ORDER BY tid ASC LIMIT ".$topic_st.",".$topic_offset."");	
		while ($topic_arr = bb_fetcharray($topic_query))
		{
			bbm_query("INSERT INTO ".$bbm_tbl."topics (`tid`, `title`, `description`, `state`, `posts`, `starter_id`, `start_date`, `last_poster_id`, `last_post`, `icon_id`, `starter_name`, `last_poster_name`, `poll_state`, `last_vote`, `views`, `forum_id`, `approved`, `author_mode`, `pinned`, `moved_to`, `rating`, `total_votes`)
		    		   VALUES('".$topic_arr['tid']."', '".addslashes($topic_arr['title'])."', '".addslashes($topic_arr['description'])."','".$topic_arr['state']."','".$topic_arr['posts']."','".$userid[$topic_arr['starter_id']]."','".$topic_arr['start_date']."','".$userid[$topic_arr['last_poster_id']]."','".$topic_arr['last_post']."','".$topic_arr['icon_id']."','".addslashes(username($userid[$topic_arr['starter_id']],$topic_arr['starter_name']))."',
		    		   		  '".addslashes(username($userid[$topic_arr['last_poster_id']],$topic_arr['last_poster_name']))."','".$topic_arr['poll_state']."','".$topic_arr['last_vote']."','".$topic_arr['views']."','".$topic_arr['forum_id']."','".$topic_arr['approved']."','".$topic_arr['author_mode']."','".$topic_arr['pinned']."','".$topic_arr['moved_to']."','".addslashes($topic_arr['rating'])."','".$topic_arr['total_votes']."')
					  ");
		}
		$topic_st += $topic_offset;
	}						
	bb_print();

	//------------------------------
	// Transfer tracker Tables
	//------------------------------
	$tracker_query = bb_query("SELECT * FROM ".$bb_tbl."tracker");
	bb_print($bb_tbl."tracker",$bbm_tbl."tracker");

	while ($tracker_arr = bb_fetcharray($tracker_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."tracker ( `trid` , `member_id` , `topic_id` , `start_date` , `last_sent` ) 
	    		   VALUES('".$tracker_arr['trid']."', '".$userid[$tracker_arr['member_id']]."', '".$tracker_arr['topic_id']."','".$tracker_arr['start_date']."','".$tracker_arr['last_sent']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer validating Tables
	//------------------------------
	$vdate_query = bb_query("SELECT * FROM ".$bb_tbl."validating");
	bb_print($bb_tbl."validating",$bbm_tbl."validating");

	while ($vdate_arr = bb_fetcharray($vdate_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."validating ( `vid` , `member_id` , `real_group` , `temp_group` , `entry_date` , `coppa_user` , `lost_pass` , `new_reg` , `email_chg` , `ip_address` ) 
	    		   VALUES('".$vdate_arr['vid']."', '".$userid[$vdate_arr['member_id']]."', '".$vdate_arr['real_group']."','".$vdate_arr['temp_group']."','".$vdate_arr['entry_date']."','".$vdate_arr['coppa_user']."','".$vdate_arr['lost_pass']."','".$vdate_arr['new_reg']."','".$vdate_arr['email_chg']."','".$vdate_arr['ip_address']."')
				  ");
	}
	bb_print();

	//------------------------------
	// Transfer voters Tables
	//------------------------------
	$voter_query = bb_query("SELECT * FROM ".$bb_tbl."voters");
	bb_print($bb_tbl."voters",$bbm_tbl."voters");

	while ($voter_arr = bb_fetcharray($voter_query))
	{
		bbm_query("INSERT INTO ".$bbm_tbl."voters ( `vid` , `ip_address` , `vote_date` , `tid` , `member_id` , `forum_id` ) 
	    		   VALUES('".$voter_arr['vid']."', '".$voter_arr['ip_address']."', '".$voter_arr['vote_date']."','".$voter_arr['tid']."','".$userid[$voter_arr['member_id']]."','".$voter_arr['forum_id']."')
				  ");
	}
	bb_print();

	print "<font color=pink><b>Transfering successful</b></font>. <br>As long as everything went satisfactory, Please close this file and Delete it for your security!";

}else{

print "<center><font size='5' color='blue'>IPB Origin v1.3 to IPB Module v1.4 for XOOPS Transfer Data</font><br><br></center>
Please enter the information for your MySQL database, that is used with your <font color=blue>IPB Original</font>.<br>
This information will only be used during this transfer and the information will not be saved anywhere.<br>
You may also delete this file once this hack is sucessfully installed.<br><br>
<b><u>Notice:</u></b><br>
1. Run this Converter will <font color=red>DELETE</font> your current <font color=blue>IPB module Database</font><font color=red>(xoops_ipb_ prefix).</font>(It will not DELETE your IPB Origin and XOOPS users Database)<br>
2. Please <font color=blue>Backup</font> your Database before run this Converter.<br>
3. I don't have any <font color=blue>responsible</font> for damage from this Converter.<br>
4. Run this converter <font color=red>at once</font>. (Run with the second time will occur error)<Br>
5. This converter only tests with small database and it worked fine. (Because I don't have big database for tesing).<Br>

<form action=\"ipb2ipbmx14.php\" method=\"POST\">
<table width=\"100%\" cellspacing='2' cellpadding='3' align='center' border='0'>
  <tr>
   <td width=\"40%\"><b>IPB Origin SQL host</b><br>(localhost is usually sufficient)	</td>
   <td width='60%'><input type=\"text\" size=\"32\" name=\"bb_host\" value=\"localhost\"></input></td>
  </tr>
  <tr>

   <td width=\"40%\"><b>IPB Origin SQL Database Name</b></td>
   	<td width='60%'><input type=\"text\" size=\"32\" name=\"bb_db\" value=\"\"></input>
   </td>
  </tr>
  <tr>
   <td width=\"40%\"><b>IPB Origin SQL Username</b></td>
   	<td width='60%'><input type=\"text\" size=\"32\" name=\"bb_uname\" value=\"\"></input>
   </td>
  </tr>
  <tr>
   <td width=\"40%\"><b>IPB Origin SQL Password</b></td>
   	<td width='60%'><input type=\"text\" size=\"32\" name=\"bb_pass\" value=\"\"></input>
   </td>
  </tr>
  <tr>
   <td width=\"40%\"><b>IPB Origin SQL Table Prefix</b><br> (You can leave this blank)</td>
   	<td width='60%'><input type=\"text\" size=\"32\" name=\"bb_tbl\" value=\"ibf_\"></input>
   </td>
  </tr>
    <tr>
   <td width=\"40%\">
   	<td width='60%'><input type=\"submit\" name=\"letsgo\" value=\"Convert at ONCE\"></input>
   </td>
  </tr>

</table>

</form>";
print "<center>Made by <a target=\"_blank\" href='http://bbpixel.com'>Koudanshi</a> &copy 2004 Bulettin Board Modules Development.</center>";

}
?>