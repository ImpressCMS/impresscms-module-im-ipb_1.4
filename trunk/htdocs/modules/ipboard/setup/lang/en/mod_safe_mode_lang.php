<?php

$lang['import_title'] = "Import Skin Archive Manager in Safe Mode";
$lang['import_detail']  = "You can select which archives to import onto your board in this section. All archives must be uploaded into the 'archive_in' directory<br>";
$lang['import_detail'] .= "But remember: Your web hoster has switched on SAFE MODE. So our script cannot create files and directories itself. You have to help.<br>";
$lang['import_detail'] .= "Untar the archive manually into a directory here in archive_in and name it set-xxx where xxx is your skin name  (chmod to 444).";
$lang['list_uploaded'] = "Current Archives Uploaded";
$lang['name'] = "Name";
$lang['type'] = "Type";
$lang['dir_name'] = "Dir Name";
$lang['import'] = "Import";
$lang['set_import_info'] = "You have to create two directories manually:<br><br>".
                           "Copy <b>%set_dir%images</b> to <b>%images_dir%</b> (chmod to 444 or 666)<br>".
                           "Finally create <b>%skin_dir%</b> (chmod to 444 or 666)<br><br>".
                           "If you have any mod files to copy in a skin directory then execute the next steps.<br><br>".
                           "Copy your mod files in this new directory <b>%skin_dir%</b>.<br>".
                           "Resynchronize your templates in <b>ACP &rArr; Skins&Templates &rArr; Manage HTML Templates</b><br>";
$lang['set_import_title'] = "Skin Import in Safe Mode";
$lang['set_import_detail'] = "The action was executed but there remains some steps to be executed by yourself";
$lang['finalize_info'] = "Instructions to finalize skin import";
$lang['link_rebuild'] = "Go to: Resynchronize Templates";
$lang['link_skin_sets'] = "Go to: Manage Skin sets";
$lang['lang_import_detail'] = "The tar-chive that you wish to import must reside in 'archive_in' and be a valid tar-chive uploaded in binary format.";
$lang['lang_import_detail'] .= "But remember: Your web hoster has switched on SAFE MODE. So our script cannot create files and directories itself. You have to help.<br>";
$lang['lang_import_detail'] .= "Untar the archive manually into a directory here in archive_in and name it lang-xxx where xxx is your language name  (chmod to 444).";
$lang['lang_import_title'] = "Language Pack Import";
$lang['lang_list_info'] = "Choose a language directory to import";
$lang['lang_list'] = "<b>Language directory to use ...</b>";
$lang['link_lang_sets'] = "Goto: Manage Language Sets";
$lang['lang_finalize_info'] = "Instruction to finalize language import";
$lang['lang_imported_info'] = "You have to create one directory manually:<br><br>".
                           "Copy <b>%lang_source_dir%</b> to <b>%lang_dest_dir%</b> (chmod to 666)<br>";
$lang['lang_imported_title'] = "Language Import in Safe Mode";
$lang['lang_imported_detail'] = "Import was executed but one step is left to be executed by yourself.";
?>