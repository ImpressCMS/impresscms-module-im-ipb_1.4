<?php
/*
// ------------------------------------------------------------------------
-+ Date: 27-May-2004
-+ Version: 1.4e
-+ ========================================
-+ Made by Koudanshi
-+ E-mail: koudanshi@gmx.net
-+ Homepage: bbpixel.com
-+ ========================================
-+ Any Problems please email me,
-+ ========================================
\\ ------------------------------------------------------------------------
*/

if ( ! defined( 'IN_ACP' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded 'admin.php'.";
	exit();
}


$idx = new ad_xbb();

class ad_xbb {

	function ad_xbb() {
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP;

		//---------------------------------------
		// Kill globals - globals bad, Homer good.
		//---------------------------------------

		$tmp_in = array_merge( $_GET, $_POST, $_COOKIE );

		foreach ( $tmp_in as $k => $v )
		{
			unset($$k);
		}

		//---------------------------------------

		switch($IN['code'])
		{
			case 'xset':
				$this->xset();
				break;
			case 'doxset':
				$this->save_config( array ( 'xbbc_ucp', 'xbbc_reg', 'xbbc_wrap', 'xbbc_redirect','xbbc_popup', 'xbbc_uname_len','xbbc_charset', 'xbbc_ag' ) );
				break;
			//-------------------------
			default:
				exit();
				break;
		}

	}

	function xset()
	{
		global $IN, $INFO, $SKIN, $ADMIN, $DB, $isbb;
		$ADMIN->page_title = "Common Settings";

		$ADMIN->page_detail = "This section will allow you to choose which type of systems that you want to use such as UserCP, Registration... and other stuffs.";
		//+-------------------------------

		$ADMIN->html .= $SKIN->start_form( 
		                array( 1 => array( 'code'  , 'doxset'),
											  	 2 => array( 'act'  , 'xbb'    )
											) );

		//+-------------------------------

		$SKIN->td_header[] = array( "&nbsp;"  , "50%" );
		$SKIN->td_header[] = array( "&nbsp;"  , "50%" );

		//+-------------------------------

		$ADMIN->html .= $SKIN->start_table( "Settings" );
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Using UserCP System of XOOPS?</b><br>View/Edit account in XOOPS=Yes or IPBM=No." ,
										  $SKIN->form_yes_no( "xbbc_ucp", $INFO['xbbc_ucp']  )
								 )      );

		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Using Registration System of XOOPS?</b><br>If No, using IPBM's one.</b>" ,
										  $SKIN->form_yes_no( "xbbc_reg", $INFO['xbbc_reg']  )
								 )      );

		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Wrap XOOPS's header?</b><br>Header, footer, left blocks, right blocks...</b>" ,
										  $SKIN->form_yes_no( "xbbc_wrap", $INFO['xbbc_wrap']  )
								 )      );

		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Show Redirect Dialog Messages?</b><br>Something as \"Please wait while we transfer you..\".</b>" ,
										  $SKIN->form_yes_no( "xbbc_redirect", $INFO['xbbc_redirect']  )
								 )      );
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>View Recent/Top/Active blocks in new windows?</b><br>Popup new window when we read these topics.</b>" ,
										  $SKIN->form_yes_no( "xbbc_popup", $INFO['xbbc_popup']  )
								 )      );
    if (empty($INFO['xbbc_uname_len'])) {
      $INFO['xbbc_uname_len'] = "15";
    }
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Input the length of username?</b><br>Usernames which are displayed on forum/categories." ,
												  $SKIN->form_input("xbbc_uname_len", $INFO['xbbc_uname_len'])
									     )      );
    
    if (empty($INFO['xbbc_charset'])) {
      $INFO['xbbc_charset'] = "utf-8";
    }
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>What's CHARSET which to use?</b><br>Specification for your own language." ,
												  $SKIN->form_input("xbbc_charset", $INFO['xbbc_charset'])
									     )      );
									     
    // Autologin with group's permission				
    // Koudanshi
    					        
    $gname = array();
		$group_q = $DB->query("SELECT g_title, g_id FROM ibf_groups");
		
		while ($group = $DB->fetch_row($group_q)){
		 $gname[]=array( $group['g_id'], $group['g_title'] );
		}
		
		$xbbc_ag_dft = explode( ",", $INFO['xbbc_ag']);
		
		$ADMIN->html .= $SKIN->add_td_row( array( "<b>Which groups not have permited to use AutoLogin Function...</b><br>You may choose more than one. These groups will not stay logged in anymore." ,
												  $SKIN->form_multiselect("ag[]", $gname, $xbbc_ag_dft, 5)
									     )      );

		$ADMIN->html .= $SKIN->end_form("Submit changes");

		$ADMIN->html .= $SKIN->end_table();
		//+-------------------------------

		$ADMIN->output();

		exit();
	}

	//-------------------------------------------------------------
	//
	// Save config. Does the hard work, so you don't have to.
	//
	//--------------------------------------------------------------
	function save_config( $new )
	{
		global $IN, $INFO, $DB, $SKIN, $ADMIN, $std, $MEMBER, $GROUP, $HTTP_POST_VARS, $ipb_isactive, $ipb_mid;
    
    // Auto set permission for XOOPS's Guest if we use IPBM user system
    if (!$ipb_isactive and (!$HTTP_POST_VARS['xbbc_ucp'] or !$HTTP_POST_VARS['xbbc_reg']))
    {
     	$DB->query("INSERT INTO ".XOOPS_DB_PREFIX."_group_permission (gperm_id, gperm_groupid, gperm_itemid, gperm_modid, gperm_name) VALUES ('', 3, $ipb_mid, 1, 'module_read')");
    }
 
    $master = array();

		if ( is_array($new) )
		{
			if ( count($new) > 0 )
			{
				foreach( $new as $field )
				{

					// Handle special..

					if ($field == 'img_ext' or $field == 'avatar_ext' or $field == 'photo_ext')
					{
						$HTTP_POST_VARS[ $field ] = preg_replace( "/[\.\s]/", "" , $HTTP_POST_VARS[ $field ] );
						$HTTP_POST_VARS[ $field ] = str_replace('|', "&#124;", $HTTP_POST_VARS[ $field ]);
						$HTTP_POST_VARS[ $field ] = preg_replace( "/,/"     , '|', $HTTP_POST_VARS[ $field ] );
					}
					else if ($field == 'coppa_address')
					{
						$HTTP_POST_VARS[ $field ] = nl2br( $HTTP_POST_VARS[ $field ] );
					}

					if ( $field == 'gd_font' OR $field == 'html_dir' OR $field == 'upload_dir')
					{
						$HTTP_POST_VARS[ $field ] = preg_replace( "/'/", "&#39;", $HTTP_POST_VARS[ $field ] );
					}
					else
					{
					$HTTP_POST_VARS[ $field ] = preg_replace( "/'/", "&#39;", stripslashes($HTTP_POST_VARS[ $field ]) );
					}
          // Koudanshi hack autologin group permission
					if ($field == 'xbbc_ag')
					{
					  if ( is_array( $HTTP_POST_VARS['ag'] ) )
        		{
         			$ag = implode( ",", $HTTP_POST_VARS['ag'] );
        			$HTTP_POST_VARS[$field] = $ag;
        		}

					}
					// End hack
					$master[ $field ] = stripslashes($HTTP_POST_VARS[ $field ]);
				}

				$ADMIN->rebuild_config($master);
			}
		}

		$ADMIN->save_log("XBB Settings Updated, Back Up Written");

		$ADMIN->done_screen("Common Settings updated", "XBB Common Settings", "act=xbb&code=xset" );
	}




}
?>