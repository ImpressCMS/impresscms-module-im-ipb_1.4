<?php

$root_path = "./";

error_reporting  (E_ERROR | E_WARNING | E_PARSE);

set_magic_quotes_runtime(0);

require $root_path."conf_global.php";

//--------------------------------
// Load the DB driver and such
//--------------------------------

$INFO['sql_driver'] = !$INFO['sql_driver'] ? 'mySQL' : $INFO['sql_driver'];
$to_require = $root_path."sources/Drivers/".$INFO['sql_driver'].".php";
require ($to_require);

$DB = new db_driver;

$DB->obj['sql_database']     = $INFO['sql_database'];
$DB->obj['sql_user']         = $INFO['sql_user'];
$DB->obj['sql_pass']         = $INFO['sql_pass'];
$DB->obj['sql_host']         = $INFO['sql_host'];
$DB->obj['sql_tbl_prefix']   = $INFO['sql_tbl_prefix'];

// Get a DB connection
$DB->connect();

// Switch off auto_error

$DB->return_die = 1;

//---------------------------------------
// Sort out what to do..
//---------------------------------------
switch($VARS['a'])
{
	case 'alter':
		do_alter();
		break;
	default:
		break;
}
if ($_POST['ok']=="ok"){
  do_alter();
  echo "<font color=blue><b><br>Update successful.</b><br></font><br><font color=red><b><br>Remember that remove this file for your security.</b><br></font>";
  exit();
}
echo "
<html>
<title>IPBM for XOOPS upgrading v1.3 to v1.4</title>
<body>
<form action=upgrade7.php method=post>
<input type=hidden name=ok value=ok>
IPBM for XOOPS upgrading v1.3 to v1.4.<br>
Click button below to do.<br>
<input type=submit value=ok name=doit >
</form>
</body>
</html>

";
function do_alter()
{
	global $std, $template, $root, $DB;
	run_sql('alter');
}

function sql_alter()
{
	$SQL = array();
	
	$SQL[] = "alter table ibf_members drop user_sig";
	$SQL[] = "alter table ibf_members CHANGE signature user_sig text default ''";
	$SQL[] = "alter table ibf_members drop allow_admin_mails";
	$SQL[] = "update ibf_members set uid=0 where uname='Guest';";

	return $SQL;
}
function run_sql($type)
{
	global $std, $template, $root, $DB;
	$DB->error = "";
  if ($type == 'alter')
	{
		$SQL = sql_alter();
	}
	foreach( $SQL as $q )
	{
		$DB->query($q);
		if ( $DB->error != "" )
		{
			echo mysql_error()."<Br>";			
		}
	}
	return TRUE;
}
?>