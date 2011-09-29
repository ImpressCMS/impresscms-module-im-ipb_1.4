<?php
/*
// ------------------------------------------------------------------------
-+ Date: 7-Mar-2004
-+ Version: 1.3
-+ ========================================
-+ Be Modified by Koudanshi
-+ E-mail: koudanshi@gmx.net
-+ Homepage: koudanshi.net or bbpixel.com
-+ ========================================
-+ Any Problems please email me,
-+ Please! don't bother IPS INC.
-+ ========================================
\\ ------------------------------------------------------------------------
*/

/*
+--------------------------------------------------------------------------
|   Invision Power Board v1.3 Final
|   ========================================
|   by Matthew Mecham
|   (c) 2001 - 2003 Invision Power Services
|   http://www.invisionpower.com
|   ========================================
|   Web: http://www.invisionboard.com
|   Time: Wed, 21 Jan 2004 09:54:34 GMT
|   Release: 2c4ce01a2d8aa60f718f2246a5cd4a18
|   Email: matt@invisionpower.com
|   Licence Info: http://www.invisionboard.com/?license
+---------------------------------------------------------------------------
|
|   > UserCP functions library
|   > Module written by Matt Mecham
|   > Date started: 20th February 2002
|
|	> Module Version Number: 1.0.0
+--------------------------------------------------------------------------
*/


class usercp_functions {

	var $class;

	function usercp_functions($class) {

		$this->class = $class;
	}

	//----------------------------------------------------------------------
	//
	// HANDLE PHOTO OP'S
	//
	//----------------------------------------------------------------------

	function do_photo()
	{
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS, $HTTP_POST_FILES;

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}

				//-------------------------------------
        // Nawty, Nawty!
        //-------------------------------------

        if ($ibforums->input['auth_key'] != $this->class->md5_check )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}

		//------------------------------------
		// Did we press "remove"?
		//------------------------------------

		if ( $ibforums->input['remove'] )
		{
			$this->bash_uploaded_photos($ibforums->member['uid']);

			$DB->query("SELECT id FROM ibf_member_extra WHERE id={$ibforums->member['uid']}");

			if ( $DB->get_num_rows() )
			{
				$DB->query("UPDATE ibf_member_extra SET photo_location='', photo_type='', photo_dimensions='' WHERE id={$ibforums->member['uid']}");
			}
			else
			{
				$DB->query("INSERT INTO ibf_member_extra SET photo_location='', photo_type='', photo_dimensions='', id={$ibforums->member['uid']}");
			}

			$print->redirect_screen( $ibforums->lang['photo_c_up'], "act=UserCP&CODE=photo" );

		}

		//------------------------------------
		// NO? CARRY ON!!
		//------------------------------------

		list($p_max, $p_width, $p_height) = explode( ":", $ibforums->member['g_photo_max_vars'] );

		//-----------------------------------
		// Check to make sure we don't just have
		// http:// in the URL box..
		//------------------------------------

		if ( preg_match( "/^http:\/\/$/i", $ibforums->input['url_photo'] ) )
		{
			$ibforums->input['url_photo'] = "";
		}

		if ( empty($ibforums->input['url_photo']) )
		{
			//------------------------------------
			// Lets check for an uploaded photo..
			//------------------------------------

			if ($HTTP_POST_FILES['upload_photo']['name'] != "" and ($HTTP_POST_FILES['upload_photo']['name'] != "none") )
			{
				$FILE_NAME = $HTTP_POST_FILES['upload_photo']['name'];
				$FILE_SIZE = $HTTP_POST_FILES['upload_photo']['size'];
				$FILE_TYPE = $HTTP_POST_FILES['upload_photo']['type'];

				if ($HTTP_POST_FILES['upload_photo']['name'] == "")
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_name' ) );
				}

				// Naughty Opera adds the filename on the end of the
				// mime type - we don't want this.

				$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );

				//------------------------------------
				// Are we allowed to upload a photo?
				//------------------------------------

				if ( $p_max < 0 )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_upload' ) );
				}

				//-------------------------------------------------
				// Check the file size
				//-------------------------------------------------

				if ($FILE_SIZE > ($p_max * 1024))
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_to_big' ) );
				}

				$ext = '.gif';

				switch($FILE_TYPE)
				{
					case 'image/gif':
						$ext = '.gif';
						break;
					case 'image/jpeg':
						$ext = '.jpg';
						break;
					case 'image/pjpeg':
						$ext = '.jpg';
						break;
					case 'image/x-png':
						$ext = '.png';
						break;
					case 'image/png':
						$ext = '.png';
						break;
					default:
						$ext = '.gif';
						break;
				}

				$real_name = 'photo-'.$ibforums->member['uid'].$ext;

				//-------------------------------------------------
				// Remove any uploaded images..
				//-------------------------------------------------

				$this->bash_uploaded_photos($ibforums->member['uid']);

				//-------------------------------------------------
				// Copy the upload to the uploads directory
				//-------------------------------------------------

				if (! @move_uploaded_file( $HTTP_POST_FILES['upload_photo']['tmp_name'], $ibforums->vars['upload_dir']."/".$real_name) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_failed' ) );
				}
				else
				{
					@chmod( $ibforums->vars['upload_dir']."/".$real_name, 0777 );
				}

				//-------------------------------------------------
				// Check image size...
				//-------------------------------------------------

				$im = array();

				if ( ! $ibforums->vars['disable_ipbsize'] )
				{
					$img_size = GetImageSize( $ibforums->vars['upload_dir']."/".$real_name );

					$im = $std->scale_image( array(
													'max_width'  => $p_width,
													'max_height' => $p_height,
													'cur_width'  => $img_size[0],
													'cur_height' => $img_size[1]
										   )      );
				}
				else
				{
					$w = intval($ibforums->input['man_width'])  ? intval($ibforums->input['man_width'])  : $p_width;
					$h = intval($ibforums->input['man_height']) ? intval($ibforums->input['man_height']) : $p_height;
					$im['img_width']  = $w > $p_width  ? $p_width  : $w;
					$im['img_height'] = $h > $p_height ? $p_height : $h;
				}

				$final_location  = $real_name;
				$final_type      = 'upload';
				$final_dimension = $im['img_width'].','.$im['img_height'];
			}
			else
			{
				// URL field and upload field left blank.

				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_photo_selected' ) );

			}
		}
		else
		{
			//-------------------------------------------------
			// It's an entered URL 'ting man
			//-------------------------------------------------

			if ( empty($ibforums->vars['allow_dynamic_img']) )
			{
				if ( preg_match( "/[?&;]/", $ibforums->input['url_photo'] ) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'not_url_photo' ) );
				}
			}

			//-------------------------------------------------
			// Check extension
			//-------------------------------------------------

			$ext = explode ( "|", $ibforums->vars['photo_ext'] );
			$checked = 0;
			$av_ext = preg_replace( "/^.*\.(\S+)$/", "\\1", $ibforums->input['url_photo'] );

			foreach ($ext as $v )
			{
				if (strtolower($v) == strtolower($av_ext))
				{
					$checked = 1;
				}
			}

			if ($checked != 1)
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'photo_invalid_ext' ) );
			}

			//-------------------------------------------------
			// Check image size...
			//-------------------------------------------------

			$im = array();

			if ( ! $ibforums->vars['disable_ipbsize'] )
			{
				if ( ! $img_size = @GetImageSize( $ibforums->input['url_photo'] ) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'not_url_photo' ) );
				}

				$im = $std->scale_image( array(
												'max_width'  => $p_width,
												'max_height' => $p_height,
												'cur_width'  => $img_size[0],
												'cur_height' => $img_size[1]
									   )      );
			}
			else
			{
				$w = intval($ibforums->input['man_width'])  ? intval($ibforums->input['man_width'])  : $p_width;
				$h = intval($ibforums->input['man_height']) ? intval($ibforums->input['man_height']) : $p_height;
				$im['img_width']  = $w > $p_width  ? $p_width  : $w;
				$im['img_height'] = $h > $p_height ? $p_height : $h;
			}

			//-------------------------------------------------
			// Remove any uploaded images..
			//-------------------------------------------------

			$this->bash_uploaded_photos($ibforums->member['uid']);

			$final_location  = $ibforums->input['url_photo'];
			$final_type      = 'url';
			$final_dimension = $im['img_width'].','.$im['img_height'];
		}

		// Do we have an entry?

		$DB->query("SELECT id FROM ibf_member_extra WHERE id={$ibforums->member['uid']}");

		if ( $DB->get_num_rows() )
		{
			$DB->query("UPDATE ibf_member_extra SET photo_location='$final_location', photo_type='$final_type', photo_dimensions='$final_dimension' WHERE id={$ibforums->member['uid']}");
		}
		else
		{
			$DB->query("INSERT INTO ibf_member_extra SET photo_location='$final_location', photo_type='$final_type', photo_dimensions='$final_dimension', id={$ibforums->member['uid']}");
		}

		$print->redirect_screen( $ibforums->lang['photo_c_up'], "act=UserCP&CODE=photo" );

	}


	//----------------------------------------------------------------------
	//
	// REMOVE UPLOADED PICCIES
	//
	//----------------------------------------------------------------------

	function bash_uploaded_photos($id)
	{
		global $ibforums, $DB, $std, $print;

		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @file_exists( $ibforums->vars['upload_dir']."/photo-".$id.".".$ext ) )
			{
				@unlink( $ibforums->vars['upload_dir']."/photo-".$id.".".$ext );
			}
		}
	}

	function bash_uploaded_avatars($id,$name)
	{
		global $ibforums, $DB, $std, $print;

		$up_dir = ICMS_ROOT_PATH.'/uploads/';

		foreach( array( 'swf', 'jpg', 'jpeg', 'gif', 'png' ) as $ext )
		{
			if ( @file_exists( $up_dir."cavt-".$id."-".$name.".".$ext ) )
			{
				@unlink( $up_dir."cavt-".$id."-".$name.".".$ext );
			}
		}
	}

	//----------------------------------------------------------------------
	//
	// SAVE SKIN/LANG PREFS
	//
	//----------------------------------------------------------------------

	function do_skin_langs()
	{
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;

		// Check input for 1337 h/\x0r nonsense

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}

		//-------------------------------------
    // Nawty, Nawty!
    //-------------------------------------

    if ($ibforums->input['auth_key'] != $this->class->md5_check )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}

		//+----------------------------------------

		if ( preg_match( "/\.\./", $ibforums->input['u_skin'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//+----------------------------------------
		if ( preg_match( "/\.\./", $ibforums->input['u_language'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}

		//+----------------------------------------

		if ($ibforums->vars['allow_skins'] == 1)
		{

		$DB->query("SELECT sid FROM ibf_skins WHERE hidden <> 1 AND sid='".$ibforums->input['u_skin']."'");

		if (! $DB->get_num_rows() )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'skin_not_found' ) );
		}

		$db_string = $DB->compile_db_update_string(  array (
															  'language'    => $ibforums->input['u_language'],
															  'skin       ' => $ibforums->input['u_skin'],
												  )         );
		}
		else
		{
			$db_string = $DB->compile_db_update_string(  array (
																  'language'    => $ibforums->input['u_language'],
													  )         );
		}

		//+----------------------------------------



		$DB->query("UPDATE ibf_members SET $db_string WHERE uid='".$this->class->member['uid']."'");

		$print->redirect_screen( $ibforums->lang['set_updated'], "act=UserCP&CODE=06" );

	}


	function do_board_prefs() {
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;

		// Check the input for naughties :D

		//-------------------------------------
    // Nawty, Nawty!
    //-------------------------------------

    if ($ibforums->input['auth_key'] != $this->class->md5_check )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		//+----------------------------------------
		if ( ! preg_match( "/^[\-\d\.]+$/", $ibforums->input['u_timezone'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		
    //+----------------------------------------
    //| XOOPS user setting hack by Koudanshi		
 /*   //+----------------------------------------
		if ( ! preg_match( "/^\d+$/", $ibforums->input['COM_DISP'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//+----------------------------------------
		if ( ! preg_match( "/^\d+$/", $ibforums->input['COM_ORDER'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//+----------------------------------------
		if ( ! preg_match( "/^\d+$/", $ibforums->input['NOTIFY_METHOD'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//+----------------------------------------
		if ( ! preg_match( "/^\d+$/", $ibforums->input['NOTIFY_MODE'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		// End by Koudanshi
		//+----------------------------------------
  */  
		if ( ! preg_match( "/^\d+$/", $ibforums->input['VIEW_IMG'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//+----------------------------------------
		if ( ! preg_match( "/^\d+$/", $ibforums->input['VIEW_SIGS'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//+----------------------------------------
		if ( ! preg_match( "/^\d+$/", $ibforums->input['VIEW_AVS'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}
		//+----------------------------------------
		if ( ! preg_match( "/^\d+$/", $ibforums->input['DO_POPUP'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}

		/*if ( ! preg_match( "/^\d+$/", $ibforums->input['HIDE_SESS'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}*/

		if ( ! preg_match( "/^\d+$/", $ibforums->input['OPEN_QR'] ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'poss_hack_attempt' ) );
		}

		//+----------------------------------------

		if ($ibforums->vars['postpage_contents'] == "")
		{
			$ibforums->vars['postpage_contents'] = '5,10,15,20,25,30,35,40';
		}

		if ($ibforums->vars['topicpage_contents'] == "")
		{
			$ibforums->vars['topicpage_contents'] = '5,10,15,20,25,30,35,40';
		}

		$ibforums->vars['postpage_contents']  .= ",-1,";
		$ibforums->vars['topicpage_contents'] .= ",-1,";

		if (! preg_match( "/(^|,)".$ibforums->input['postpage'].",/", $ibforums->vars['postpage_contents'] ) )
		{
			$ibforums->input['postpage'] = '-1';
		}

		//+----------------------------------------

		if (! preg_match( "/(^|,)".$ibforums->input['topicpage'].",/", $ibforums->vars['topicpage_contents'] ) )
		{
			$ibforums->input['topicpage'] = '-1';
		}

		//+----------------------------------------

		$db_string = $DB->compile_db_update_string(  array (
    //+----------------------------------------
    //| XOOPS user setting hack by Koudanshi		
    //+----------------------------------------
															  'umode'           => $ibforums->input['COM_DISP'],
															  'uorder'          => $ibforums->input['COM_ORDER'],
															  'notify_method'   => $ibforums->input['NOTIFY_METHOD'],
															  'notify_mode'     => $ibforums->input['NOTIFY_MODE'],
    //+----------------------------------------		
															  'timezone_offset' => $ibforums->input['u_timezone'],
															  'view_avs'        => $ibforums->input['VIEW_AVS'],
															  'attachsig'       => $ibforums->input['VIEW_SIGS'],
															  'view_img'        => $ibforums->input['VIEW_IMG'],
															  'view_pop'        => $ibforums->input['DO_POPUP'],
															  'dst_in_use'      => $ibforums->input['DST'],
															  'view_prefs'      => $ibforums->input['postpage']."&".$ibforums->input['topicpage'],
												  )         );

		$DB->query("UPDATE ibf_members SET $db_string WHERE uid='".$this->class->member['uid']."'");

		/*if ($ibforums->input['HIDE_SESS'] == 1)
		{
			$std->my_setcookie('hide_sess', '1');
		}
		else
		{
			$std->my_setcookie('hide_sess', '0');
		}*/

		if ($ibforums->input['OPEN_QR'] == 1)
		{
			$std->my_setcookie('open_qr', '1');
		}
		else
		{
			$std->my_setcookie('open_qr', '0');
		}

		$print->redirect_screen( $ibforums->lang['set_updated'], "act=UserCP&CODE=04" );

	}



	function do_email_settings() {
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}

		//-------------------------------------
    // Nawty, Nawty!
    //-------------------------------------

    if ($ibforums->input['auth_key'] != $this->class->md5_check )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}

		//+----------------------------------------

		//check and set the rest of the info

		foreach ( array('hide_email','admin_send', 'send_full_msg', 'pm_reminder', 'auto_track') as $v )
		{
			$ibforums->input[ $v ] = $std->is_number( $ibforums->input[ $v ] );

			if ( $ibforums->input[ $v ] < 1 )
			{
				$ibforums->input[ $v ] = 0;
			}
		}

		//+----------------------------------------
		//| Modify to work with xoops - Koudanshi
		//+----------------------------------------
		if ($ibforums->input['hide_email'])
		{
			$ibforums->input['hide_email']=0;
		}
		else
		{
			$ibforums->input['hide_email']=1;
		}
		//+----------------------------------------

		$db_string = $DB->compile_db_update_string(  array (
															  'user_viewemail'     => $ibforums->input['hide_email'],
															  'email_full'         => $ibforums->input['send_full_msg'],
															  'email_pm'           => $ibforums->input['pm_reminder'],
															  'user_mailok'  => $ibforums->input['admin_send'],
															  'auto_track'         => $ibforums->input['auto_track'],
												  )         );

		$DB->query("UPDATE ibf_members SET $db_string WHERE uid='".$this->class->member['uid']."'");

		$print->redirect_screen( $ibforums->lang['email_c_up'], "act=UserCP&CODE=02" );

	}

	//-----------------------------------------------------------------------------

	function set_internal_avatar()
	{
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;

		$up_dir = ICMS_ROOT_PATH.'/uploads/';

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}

		//-------------------------------------
    // Nawty, Nawty!
    //-------------------------------------

    if ($ibforums->input['auth_key'] != $this->class->md5_check )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}

		//+----------------------------------------

		$real_choice = 'blank.gif';
		$real_dims   = '';
		$real_dir    = "";
		$save_dir    = "avatars/";

		//+----------------------------------------
		// Check incoming..
		//+----------------------------------------

		$current_folder  = preg_replace( "/[^\s\w_-]/"    , "", urldecode($ibforums->input['current_folder']) );
		$selected_avatar = preg_replace( "/[^\s\w\._\-\[\]\(\)]/"  , "", urldecode($ibforums->input['avatar']) );

		//+----------------------------------------
		// Are we in a folder?
		//+----------------------------------------

		if ($current_folder == 'root')
		{
			$current_folder = "";
		}

		if ($current_folder != "")
		{
			$real_dir = "/".$current_folder;
			$save_dir = "avatars/".$current_folder."/";
		}

		//+----------------------------------------
		// Check it out!
		//+----------------------------------------

		$avatar_gallery = array();

		$dh = opendir( $up_dir.'avatars'.$real_dir );

			while ( $file = readdir( $dh ) )
			{
				if ( !preg_match( "/^..?$|^index/i", $file ) )
				{
					$avatar_gallery[] = $file;
				}
			}
			closedir( $dh );

		if (!in_array( $selected_avatar, $avatar_gallery ) )
			{
				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_avatar_selected' ) );
			}

		$final_string = $save_dir.$selected_avatar;

		// Update the DB

		$DB->query("UPDATE ibf_members SET user_avatar='$final_string' WHERE uid='".$ibforums->member['uid']."'");

		$print->redirect_screen( $ibforums->lang['av_c_up'], "act=UserCP&CODE=24" );

	}


	//-----------------------------------------------------------------------------


	function do_avatar()
	{
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS, $HTTP_POST_FILES;

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}

		//-------------------------------------
    // Nawty, Nawty!
    //-------------------------------------

    if ($ibforums->input['auth_key'] != $this->class->md5_check )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}

		//------------------------------------
		// Did we press "remove"?
		//------------------------------------

		if ( $ibforums->input['remove'] )
		{
			$this->bash_uploaded_avatars($ibforums->member['uid'], $ibforums->member['uname']);

			$DB->query("UPDATE ibf_members SET user_avatar='blank.gif', avatar_size='' WHERE uid='".$this->class->member['uid']."'");

			$print->redirect_screen( $ibforums->lang['av_c_up'], "act=UserCP&CODE=24" );

		}

		//------------------------------------
		// NO? CARRY ON!!
		//------------------------------------

		list($p_width, $p_height) = explode( "x", $ibforums->vars['avatar_dims'] );

			//-----------------------------------
			// Check to make sure we don't just have
			// http:// in the URL box..
			//------------------------------------

			if ( preg_match( "/^http:\/\/$/i", $ibforums->input['url_avatar'] ) )
			{
				$ibforums->input['url_avatar'] = "";
			}

			if (empty($ibforums->input['url_avatar']) )
			{
				//------------------------------------
				// Lets check for an uploaded photo..
				//------------------------------------

				if ($HTTP_POST_FILES['upload_avatar']['name'] != "" and ($HTTP_POST_FILES['upload_avatar']['name'] != "none") )
				{

					$FILE_NAME = $HTTP_POST_FILES['upload_avatar']['name'];
					$FILE_SIZE = $HTTP_POST_FILES['upload_avatar']['size'];
					$FILE_TYPE = $HTTP_POST_FILES['upload_avatar']['type'];

					if ($HTTP_POST_FILES['upload_avatar']['name'] == "")
					{
						$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_name' ) );
					}

					//-------------------------------------------------
					// Naughty Opera adds the filename on the end of the
					// mime type - we don't want this.
					//-------------------------------------------------

					$FILE_TYPE = preg_replace( "/^(.+?);.*$/", "\\1", $FILE_TYPE );

					//-------------------------------------------------
					// Are we allowed to upload this avatar?
					//-------------------------------------------------

					if ( ($ibforums->member['g_avatar_upload'] != 1) or ($ibforums->vars['avup_size_max'] < 1) )
					{
						$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_upload' ) );
					}

					// Check to make sure it's the correct content type.
					// Naughty Nominell won't be able to use PNG :P

					require "./conf_mime_types.php";

					if ($mime_types[ $FILE_TYPE ][3] != 1)
					{
						$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_av_type' ) );
					}

					//-------------------------------------------------
					// Check the file size
					//-------------------------------------------------

					if ($FILE_SIZE > ($ibforums->vars['avup_size_max']*1024))
					{
						$std->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_to_big' ) );
					}

					$ext = '.gif';

					switch($FILE_TYPE)
					{
						case 'image/gif':
							$ext = '.gif';
							break;
						case 'image/jpeg':
							$ext = '.jpg';
							break;
						case 'image/pjpeg':
							$ext = '.jpg';
							break;
						case 'image/x-png':
							$ext = '.png';
							break;
					case 'image/png':
						$ext = '.png';
						break;
					case "application/x-shockwave-flash":
						if ( $ibforums->vars['allow_flash'] != 1 )
						{
							$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_flash_av' ) );
						}
						$ext = '.swf';
						break;
						default:
							$ext = '.gif';
							break;
					}

				$real_name = 'cavt-'.$ibforums->member['uid'].'-'.$ibforums->member['uname'].$ext;
				$up_dir = ICMS_ROOT_PATH.'/uploads/';

				//-------------------------------------------------
				// Remove any uploaded avatars..
				//-------------------------------------------------

				$this->bash_uploaded_avatars($ibforums->member['uid'], $ibforums->member['uname']);

				//-------------------------------------------------
				// Copy the upload to the uploads directory
				//-------------------------------------------------

				if (! @move_uploaded_file( $HTTP_POST_FILES['upload_avatar']['tmp_name'], $up_dir.$real_name) )
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'upload_failed' ) );
				}
				else
				{
					@chmod( $up_dir.$real_name, 0777 );
				}

				//-------------------------------------------------
				// Check image size...
				//-------------------------------------------------

				$im = array();

				if ( ! $ibforums->vars['disable_ipbsize'] )
				{
					$img_size = GetImageSize( $up_dir.$real_name );

					$im = $std->scale_image( array(
													'max_width'  => $p_width,
													'max_height' => $p_height,
													'cur_width'  => $img_size[0],
													'cur_height' => $img_size[1]
										   )      );
				}
				else
				{
					$w = intval($ibforums->input['man_width'])  ? intval($ibforums->input['man_width'])  : $p_width;
					$h = intval($ibforums->input['man_height']) ? intval($ibforums->input['man_height']) : $p_height;
					$im['img_width']  = $w > $p_width  ? $p_width  : $w;
					$im['img_height'] = $h > $p_height ? $p_height : $h;
					}

					// Set the "real" avatar..

					$real_choice = $real_name;
					$real_dims   = $im['img_width'].'x'.$im['img_height'];
				}
			else
			{
				// URL field and upload field left blank.

				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_avatar_selected' ) );

		    }
		}
		else
		{
    		//-------------------------------------------------
    		// It's an entered URL 'ting man
    		//-------------------------------------------------

    		$ibforums->input['url_avatar'] = trim($ibforums->input['url_avatar']);

    		if ( empty($ibforums->vars['allow_dynamic_img']) )
    		{
    			if ( preg_match( "/[?&;]/", $ibforums->input['url_avatar'] ) )
    			{
    				$std->Error( array( 'LEVEL' => 1, 'MSG' => 'avatar_invalid_url' ) );
    			}
    		}

    		//-------------------------------------------------
    		// Check extension
    		//-------------------------------------------------

    		$ext = explode ( "|", $ibforums->vars['avatar_ext'] );
    		$checked = 0;
    		$av_ext = preg_replace( "/^.*\.(\S+)$/", "\\1", $ibforums->input['url_avatar'] );

    		foreach ($ext as $v )
    		{
    			if (strtolower($v) == strtolower($av_ext))
    			{
					if ( ( $v == 'swf' ) AND ($ibforums->vars['allow_flash'] != 1) )
					{
						$std->Error( array( 'LEVEL' => 1, 'MSG' => 'no_flash_av' ) );
					}

					$checked = 1;
				}
    		}

    		if ($checked != 1)
    		{
    			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'avatar_invalid_ext' ) );
    		}

    		//-------------------------------------------------
    		// Check image size...
    		//-------------------------------------------------

    		$im = array();

    		if ( ! $ibforums->vars['disable_ipbsize'] )
    		{
    			if ( ! $img_size = @GetImageSize( $ibforums->input['url_avatar'] ) )
    			{
    				//$std->Error( array( 'LEVEL' => 1, 'MSG' => 'avatar_invalid_url' ) );

    				$img_size[0] = $p_width;
    				$img_size[1] = $p_height;
    			}

    			$im = $std->scale_image( array(
    											'max_width'  => $p_width,
    											'max_height' => $p_height,
    											'cur_width'  => $img_size[0],
    											'cur_height' => $img_size[1]
    								   )      );
    		}
    		else
    		{
    			$w = intval($ibforums->input['man_width'])  ? intval($ibforums->input['man_width'])  : $p_width;
    			$h = intval($ibforums->input['man_height']) ? intval($ibforums->input['man_height']) : $p_height;
    			$im['img_width']  = $w > $p_width  ? $p_width  : $w;
    			$im['img_height'] = $h > $p_height ? $p_height : $h;
    		}

    		//-------------------------------------------------
    		// Remove any uploaded images..
    		//-------------------------------------------------

    		$this->bash_uploaded_avatars($ibforums->member['uid'], $ibforums->member['uname']);

    		$real_choice = $ibforums->input['url_avatar'];
    		$real_dims   = $im['img_width'].'x'.$im['img_height'];
    	}

     	//-------------------------------------------------
    	// Update the DB
    	//-------------------------------------------------

    	$DB->query("UPDATE ibf_members SET user_avatar='$real_choice', avatar_size='$real_dims' WHERE uid='".$this->class->member['uid']."'");

    	$print->redirect_screen( $ibforums->lang['av_c_up'], "act=UserCP&CODE=24" );

    }


	function do_profile()
	{
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;

		//----------------------------------
		// Check for bad entry
		//----------------------------------

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}

				//-------------------------------------
        // Nawty, Nawty!
        //-------------------------------------

        if ($ibforums->input['auth_key'] != $this->class->md5_check )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post') );
		}

		//----------------------------------
		// Custom profile field stuff
		//----------------------------------

		$custom_fields = array();

		$DB->query("SELECT * from ibf_pfields_data WHERE fedit=1");

		while ( $row = $DB->fetch_row() )
		{
			if ($row['freq'] == 1)
			{
				if ($HTTP_POST_VARS[ 'field_'.$row['fid'] ] == "")
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
				}
			}

			if ($row['fmaxinput'] > 0)
			{
				if (strlen($HTTP_POST_VARS[ 'field_'.$row['fid'] ]) > $row['fmaxinput'])
				{
					$std->Error( array( 'LEVEL' => 1, 'MSG' => 'cf_to_long', 'EXTRA' => $row['ftitle'] ) );
				}
			}

			$custom_fields[ 'field_'.$row['fid'] ] = str_replace( '<br>', "\n", $ibforums->input[ 'field_'.$row['fid'] ] );
		}

		//+--------------------

		if ( (strlen($HTTP_POST_VARS['Interests']) > $ibforums->vars['max_interest_length']) and ($ibforums->vars['max_interest_length']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'int_too_long' ) );
		}
		//+--------------------
		if ( (strlen($HTTP_POST_VARS['Location']) > $ibforums->vars['max_location_length']) and ($ibforums->vars['max_location_length']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'loc_too_long' ) );
		}
		//+--------------------
		if (strlen($HTTP_POST_VARS['WebSite']) > 150)
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'web_too_long' ) );
		}
		//+--------------------
		if (strlen($HTTP_POST_VARS['Photo']) > 150)
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'photo_too_long' ) );
		}
		//+--------------------
		if ( ($HTTP_POST_VARS['ICQNumber']) && (!preg_match( "/^(?:\d+)$/", $HTTP_POST_VARS['ICQNumber'] ) ) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'not_icq_number' ) );
		}


		//----------------------------------
		// make sure that either we entered
		// all calendar fields, or we left them
		// all blank
		//----------------------------------

		$c_cnt = 0;

		foreach ( array('day','month','year') as $v )
		{
			if (!empty($ibforums->input[$v]))
			{
				$c_cnt++;
			}
		}

		if ( ($c_cnt > 0) and ($c_cnt != 3) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'calendar_not_all' ) );
		}

		if ( ! preg_match( "#^http://#", $ibforums->input['WebSite'] ) )
		{
			$ibforums->input['WebSite'] = 'http://'.$ibforums->input['WebSite'];
		}

		//----------------------------------
		// Start off our array
		//----------------------------------

		$set = array(  'url'         => $ibforums->input['WebSite'],
					   'user_icq'    => $ibforums->input['ICQNumber'],
					   'user_aim'    => $ibforums->input['AOLName'],
					   'user_yim'    => $ibforums->input['YahooName'],
					   'user_msnm'   => $ibforums->input['MSNName'],
					   'integ_msg'   => $ibforums->input['integ_msg'],
					   'user_from'   => $ibforums->input['Location'],
					   'user_intrest'=> $ibforums->input['Interests'],
					   'bday_day'    => $ibforums->input['day'],
					   'bday_month'  => $ibforums->input['month'],
					   'bday_year'   => $ibforums->input['year'],
					);

		//----------------------------------
		// check to see if we can enter a member title
		// and if one is entered, update it.
		//----------------------------------

		if ( (isset($ibforums->input['member_title'])) and ( isset($ibforums->vars['post_titlechange']) ) and ( $this->class->member['posts'] >= $ibforums->vars['post_titlechange']) )
		{
			$set['title'] = $ibforums->input['member_title'];
		}

		//----------------------------------
		// Update the DB
		//----------------------------------

		$set_string = $DB->compile_db_update_string($set);

		$DB->query("UPDATE ibf_members SET $set_string WHERE uid='".$this->class->member['uid']."'");

		//----------------------------------
		// Save the profile stuffy wuffy
		//----------------------------------

		if ( count($custom_fields) > 0 )
		{

			// Do we already have an entry in the content table?

			$DB->query("SELECT member_id FROM ibf_pfields_content WHERE member_id='".$ibforums->member['uid']."'");
			$test = $DB->fetch_row();

			if ( $test['member_id'] )
			{
				// We have it, so simply update

				$db_string = $DB->compile_db_update_string($custom_fields);

				$DB->query("UPDATE ibf_pfields_content SET $db_string WHERE member_id='".$ibforums->member['uid']."'");
			}
			else
			{
				$custom_fields['member_id'] = $ibforums->member['uid'];

				$db_string = $DB->compile_db_insert_string($custom_fields);

				$DB->query("INSERT INTO ibf_pfields_content (".$db_string['FIELD_NAMES'].") VALUES(".$db_string['FIELD_VALUES'].")");
			}

		}

		//--------------------------------------------
 		// Use sync module?
 		//--------------------------------------------

 		if ( USE_MODULES == 1 )
		{
			$set['id'] = $ibforums->member['uid'];
			$this->class->modules->register_class(&$this);
    		$this->class->modules->on_profile_update($set, $custom_fields);
   		}

		// Return us!

		$print->redirect_screen( $ibforums->lang['profile_edited'], "act=UserCP&CODE=01" );

	}

	function do_signature() {
		global $ibforums, $DB, $std, $print, $HTTP_POST_VARS;

		if ($HTTP_POST_VARS['act'] == "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'complete_form' ) );
		}
		//+----------------------------------------

		//----------------------------------
		// Check for bad entry
		//----------------------------------

		if ( (strlen($HTTP_POST_VARS['Post']) > $ibforums->vars['max_sig_length']) and ($ibforums->vars['max_sig_length']) )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'sig_too_long' ) );
		}

		if ( $HTTP_POST_VARS['key'] != $std->return_md5_check() )
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => 'del_post' ) );
		}

		//----------------------------------
		// Check for valid IB CODE
		//----------------------------------
		//
		// For efficiency, we convert the IBF code into HTML and store it in the DB
		// Otherwise we'll have to parse the siggies each time we view a post - that
		// gets boring after a while.
		//
		// We will adjust raw HTML on the fly, as some admins may allow it until it's abused
		// then switch it off. If we pre-compile HTML in siggies, we'd have to edit everyones
		// siggies to remove it. We don't want that.
		//
		// I'm going to stick my neck out again and say that most admins will allow IBF Code
		// in siggies, so it's not much of a bother.

		$ibforums->input['Post'] = $this->class->parser->convert(  array( 'TEXT'      => $ibforums->input['Post'],
																   'SMILIES'   => 0,
																   'CODE'      => $ibforums->vars['sig_allow_ibc'],
																   'HTML'      => $ibforums->vars['sig_allow_html'],
																   'SIGNATURE' => 1
														 )       );

		if ($this->class->parser->error != "")
		{
			$std->Error( array( 'LEVEL' => 1, 'MSG' => $this->class->parser->error) );
		}

		//Write it to the DB.

		$ibforums->input['Post'] = preg_replace( "/'/", "\\'", $ibforums->input['Post'] );

		$DB->query("UPDATE ibf_members SET user_sig='".$ibforums->input['Post']."' WHERE uid ='".$this->class->member['uid']."'");

		if ( USE_MODULES == 1 )
 		{
  			$this->class->modules->register_class(&$this);
     		$this->class->modules->on_signature_update($ibforums->member, $ibforums->input['Post']);
    	}

		// Buh BYE:

		$std->boink_it($this->class->base_url."act=UserCP&CODE=22");

		exit;
	}





}



?>