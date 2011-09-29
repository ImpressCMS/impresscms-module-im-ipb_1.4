ALTER TABLE smiles
	DROP  clickable
;

ALTER TABLE session
	DROP    member_name ,
	DROP    member_id ,
	DROP    browser ,
	DROP    login_type ,
	DROP    location ,
	DROP    member_group ,
	DROP    in_forum ,
	DROP    in_topic ,
	DROP    INDEX in_topic ,
	DROP    INDEX in_forum
;
DELETE FROM users WHERE uname='Guest' ;

ALTER TABLE users
	DROP   mgroup ,
	DROP   ip_address ,
	DROP   avatar_size ,
	DROP   title ,
	DROP   email_pm ,
	DROP   email_full ,
	DROP   skin ,
	DROP   warn_level ,
	DROP   warn_lastwarn ,
	DROP   language ,
	DROP   last_post ,
	DROP   restrict_post ,
	DROP   view_img ,
	DROP   view_avs ,
	DROP   view_pop ,
	DROP   bday_day ,
	DROP   bday_month ,
	DROP   bday_year,
	DROP   new_msg ,
	DROP   msg_from_id ,
	DROP   msg_msg_id ,
	DROP   msg_total ,
	DROP   vdirs ,
	DROP   show_popup ,
	DROP   misc ,
	DROP   last_visit,
	DROP   last_activity ,
	DROP   dst_in_use ,
	DROP   view_prefs ,
	DROP   coppa_user ,
	DROP   mod_posts ,
	DROP   auto_track ,
	DROP   org_perm_id ,
	DROP   org_supmod ,
	DROP   integ_msg ,
	DROP   temp_ban ,
	DROP   sub_end,
	DROP   INDEX mgroup ,
	DROP   INDEX bday_day,
	DROP   INDEX bday_month
;
