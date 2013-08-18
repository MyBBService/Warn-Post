<?php
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook("warnings_do_warn_start", "warnpost_doAction");
$plugins->add_hook("redirect", "warnpost_save");

function warnpost_info()
{
    return array(
		"name"			=> "Warn Post",
		"description"	=> "Erstellt einen Thread beim Verwarnungen von Users<br /><i>Based on Warn Post by thebod</i>",
		"website"		=> "http://mybbservice.de/",
		"author"		=> "MyBBService",
		"authorsite"	=> "http://mybbservice.de/",
		"version"		=> "1.0.1",
		"guid"			=> "",
		"compatibility"	=> "16*",
		"dlcid"			=> "22"
    );
}

function warnpost_activate()
{
	global $db;

    $group = array(
        "name" => "warnpost",
        "title" => "Warn Post",
        "description" => "",
        "disporder" => "1",
        "isdefault" => "0",
        );
    $gid = $db->insert_query("settinggroups", $group);

    $setting = array(
        "name" => "warnpost_fid",
        "title" => "In welchem Forum soll der Beitrag geschrieben werden?",
        "optionscode" => "text",
        "value" => "2",
        "disporder" => "1",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);

    $setting = array(
        "name" => "warnpost_text",
        "title" => "Die eigentliche Nachricht, die geschrieben werden soll",
        "description" => "Folgende Ersetzungen werden durchgef&uuml;ht:<br />{user} -> Name des verwarnten User<br />{notes} -> Admin Notizen der Verwarnung
		<br />{points} -> Anzahl der Punkte die durch die Verwarnung hinzugef&uuml;gt werden",
        "optionscode" => "textarea",
        "value" => "Verwarnung von User {user}\n".
                    "Notiz:[quote]{notes}[/QUOTE]\n".
                    "Punkte: {points}",
        "disporder" => "2",
        "gid" => (int)$gid,
        );
    $db->insert_query("settings", $setting);

    rebuild_settings();
}

function warnpost_deactivate()
{
    global $db;
    $query = $db->simple_select("settinggroups", "gid", "name='warnpost'");
    $g = $db->fetch_array($query);
    $db->delete_query("settinggroups", "gid='".$g['gid']."'");
    $db->delete_query("settings", "gid='".$g['gid']."'");
    rebuild_settings();	
}

function warnpost_doAction()
{
    global $mybb, $user, $group_permissions, $warnpost_new_thread;
    
    $pmNotice = false;
    if($mybb->input['send_pm'] == 1 && $group_permissions['canusepms']  != 0 && $user['receivepms'] != 0 && $mybb->settings['enablepms'] != 0)
        $pmNotice = true;
    
    $message = str_replace("{user}"		, $user['username']		, $mybb->settings['warnpost_text']);
    $message = str_replace("{notes}"	, $mybb->input['notes']	, $message);
    
    $warnpost_new_thread = array(
        "fid" => $mybb->settings['warnpost_fid'],
        "subject" => "Verwarnung User " . $user['username'],
        "prefix" => '',
        "icon" => 0,
        "uid" => $mybb->user['uid'],
        "username" => $mybb->user['username'],
        "message" =>$message.($pmNotice ? "\nDer User wurde per PM benachrichtigt." : ''),
        "ipaddress" => get_ip(),
        "posthash" => $mybb->input['posthash']
    );
}

function warnpost_save($args)
{
	global $warnpost_new_thread, $new_warning;
	
	if(isset($warnpost_new_thread)) {
	    require_once MYBB_ROOT."inc/datahandlers/post.php";
	    $posthandler = new PostDataHandler("insert");
	    $posthandler->action = "thread";
	
		$warnpost_new_thread['message'] = str_replace("{points}", $new_warning['points'], $warnpost_new_thread['message']);
	
	    $posthandler->set_data($warnpost_new_thread);
	
	    $posthandler->validate_thread();
	
	    $posthandler->insert_thread();
	}
}