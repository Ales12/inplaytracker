<?php

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );

/*
 * Die hooks sind wie immer hier, wo auch sonst....
 */

//Newthread Hooks
$plugins->add_hook("newthread_start", "ip_newscene");
$plugins->add_hook("newthread_do_newthread_end", "ip_newscene_do");
//bei Antwort
$plugins->add_hook("newreply_do_newreply_end", "ip_reply_do");
//Edit Hooks
$plugins->add_hook("editpost_end", "ip_editscene");
$plugins->add_hook("editpost_do_editpost_end", "ip_editscene_do");
//eigene Seite
$plugins->add_hook('misc_start', 'ipscenes');
//Link oben Hook
$plugins->add_hook('global_start', 'ip_global');
//Profil
$plugins->add_hook('member_profile_end', 'ip_profile');
//Showthread
$plugins->add_hook('showthread_start', 'ip_showthread');
//Forumdisplay
$plugins->add_hook('forumdisplay_thread_end', 'ip_forumdisplay');
// Alerts
if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    $plugins->add_hook("global_start", "iptracker_alerts");
}



function iptracker_info()
{
    return array(
        "name"			=> "Inplayszenen Übersicht",
        "description"	=> "Erlaubt die Leichte Übersicht der Szenen",
        "website"		=> "",
        "author"		=> "Ales",
        "authorsite"	=> "",
        "version"		=> "1.0",
        "guid" 			=> "",
        "codename"		=> "",
        "compatibility" => "*"
    );
}

function iptracker_install()
{
    global $db;
//Threadtabelle
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `spieler` varchar(400) CHARACTER SET utf8 NOT NULL;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `date` varchar(400)  NOT NULL;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `ort` varchar(400) CHARACTER SET utf8 NOT NULL ;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `ip_time` varchar(10) NOT NULL;");


    //Wann wurde die Meldung für einen bestimmten Charakter ausgeblendet? (0 Meldung wird angezeigt, 1 Meldung nicht anzeigen.)
    $db->add_column("users", "iptracker_pn", "INT(10) DEFAULT NULL");


    //Einstellungen
    /*
   * nun kommen die Einstellungen
   */
    $setting_group = array(
        'name' => 'iptracker',
        'title' => 'Inplayszenen Übersicht',
        'description' => 'Einstellungen für die Übersicht',
        'disporder' => 2,
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);


    $setting_array = array(
        'name' => 'ip_inplay_id',
        'title' => 'Kategorien ID',
        'description' => 'Gib hier die ID deiner Inplaykategorie an.',
        'optionscode' => 'forumselectsingle ',
        'value' => '23',
        'disporder' => 1,
        "gid" => (int)$gid
    );
    $db->insert_query('settings', $setting_array);

    $setting_array = array(
        'name' => 'ip_archive_id',
        'title' => 'Archiv ID',
        'description' => 'Gib hier die ID deines Archivs an.',
        'optionscode' => 'forumselectsingle ',
        'value' => '2',
        'disporder' => 2,
        "gid" => (int)$gid
    );
    $db->insert_query('settings', $setting_array);
    rebuild_settings();

    $insert_array = array(
        'title' => 'iptracker_bit_misc',
        'template' => $db->escape_string('<tr><td class="trow1" align="center">{$status}</td>
	<td class="trow1" align="center"><i class="fa fa-users" aria-hidden="true"></i> {$szenen[\'spieler\']} <br />
		<i class="fa fa-calendar" aria-hidden="true"></i> {$szenen[\'datum\']} {$szenen[\'ip_time\']}<br />
		<i class="fa fa-map-signs" aria-hidden="true"></i> {$szenen[\'ort\']}</td>
	<td class="trow1" align="center">&raquo; <a href="showthread.php?tid={$szenen[\'tid\']}&pid={$lastpost}#pid{$lastpost}" target="blank">{$szenen[\'subject\']}</a><br />
		<i class="fa fa-user" aria-hidden="true"></i> {$szenen[\'lastposter\']}<br/>
		<i class="fa fa-clock-o" aria-hidden="true"></i> {$postdate}</td></tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);


    $insert_array = array(
        'title' => 'iptracker_forumdisplay',
        'template' => $db->escape_string('<div class="smalltext"> {$thread[\'spieler\']} {$thread[\'datum\']} {$thread[\'ort\']}{$thread[\'ip_time\']}
				</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_global',
        'template' => $db->escape_string('<a href="misc.php?action=ipszenen">{$lang->iptracker_global}</a> ({$alleoffeneszenen}|{$alleszenen})'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_misc',
        'template' => $db->escape_string('<<html>
<head>
<title>{$lang->iptracker}</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="trow2"><h1>{$lang->iptracker_misc} (<i class="fa fa-folder-open" aria-hidden="true"></i> {$alleoffeneszenen} | <i class="fa fa-folder" aria-hidden="true"></i> {$alleszenen})</h1></td>
</tr>
<tr>
<td class="trow1" align="center">
{$szenen_bit}
</td>
</tr>
</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_misc_bit',
        'template' => $db->escape_string('<table width="100%"><tr><td colspan="3" class="trow1"><h2>{$character} (<i class="fa fa-folder-open" aria-hidden="true"></i> {$charaoffenszenen} | <i class="fa fa-folder" aria-hidden="true"></i> {$charaszenen})</h2></td> </tr>
	<tr><td class="trow1" width="20%"><strong>{$lang->iptracker_misc_status}</strong></td>
		<td class="trow1" width="40%"><strong>{$lang->iptracker_misc_infos}</strong></td>
		<td class="trow1" width="40%"><strong>{$lang->iptracker_misc_lastpost}</strong></td>
	</tr>
{$szene}
</table>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_mitspieler',
        'template' => $db->escape_string('<tr>
<td class="trow1" width="20%"><strong>{$lang->iptracker_partner}</strong></td>
<td class="trow1"><span class="smalltext"><input type="text" class="textbox" name="spieler" id="spieler" size="40" maxlength="1155" value="{$spieler}" style="min-width: 347px; max-width: 100%;" /> </span> </td>
</tr>

<link rel="stylesheet" href="{$mybb->asset_url}/jscripts/select2/select2.css?ver=1807">
<script type="text/javascript" src="{$mybb->asset_url}/jscripts/select2/select2.min.js?ver=1806"></script>
<script type="text/javascript">
<!--
if(use_xmlhttprequest == "1")
{
    MyBB.select2();
    $("#spieler").select2({
        placeholder: "{$lang->search_user}",
        minimumInputLength: 2,
        maximumSelectionSize: \'\',
        multiple: true,
        ajax: { // instead of writing the function to execute the request we use Select2\'s convenient helper
            url: "xmlhttp.php?action=get_users",
            dataType: \'json\',
            data: function (term, page) {
                return {
                    query: term, // search term
                };
            },
            results: function (data, page) { // parse the results into the format expected by Select2.
                // since we are using custom formatting functions we do not need to alter remote JSON data
                return {results: data};
            }
        },
        initSelection: function(element, callback) {
            var query = $(element).val();
            if (query !== "") {
                var newqueries = [];
                exp_queries = query.split(",");
                $.each(exp_queries, function(index, value ){
                    if(value.replace(/\s/g, \'\') != "")
                    {
                        var newquery = {
                            id: value.replace(/,\s?/g, ","),
                            text: value.replace(/,\s?/g, ",")
                        };
                        newqueries.push(newquery);
                    }
                });
                callback(newqueries);
            }
        }
    })
}
// -->
</script>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_mitspieler_edit',
        'template' => $db->escape_string('<tr>
<td class="trow1" width="20%"><strong>{$lang->iptracker_partner}</strong></td>
<td class="trow1"><span class="smalltext"> <input type="text" class="textbox" name="spieler" size="40" maxlength="1155" value="{$spieler}" /> <br />{$lang->iptracker_partner_edit}</span> </td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_infos',
        'template' => $db->escape_string('{$mitspieler}
<tr>
<td class="trow1" width="20%"><strong>Datum &amp; Uhrzeit:</strong></td>
    <td class="trow1">
        <table><tr>
<td align="center">
<input type="date" class="textbox" name="date" value="{$date}" >
   </td>
		<td align="center">  <input type="time" name="ip_time" value="{$ip_time}" class="textbox">
			</td>
</tr></table></td>
</tr>
<tr>
<td class="trow1" width="20%"><strong>Ort:</strong></td>
<td class="trow1"> <input type="text" class="textbox" name="ort" size="40" maxlength="1155" value="{$ort}" />
	<div class="smalltext">Gib hier den Ort an, an dem die Szene spielt.</div></td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_profile',
        'template' => $db->escape_string('<table width="100%">
<tr><td width="50%" class="thead"><h1>{$lang->iptracker_profile_active} ({$aktive})</h1></td>
<td width="50%" class="thead"><h1>{$lang->iptracker_profile_end} ({$beendete})</h1></td></tr>
<tr><td><div class="ingamescene"><table width="100%">{$inplay}</table></div></td> <td><div class="ingamescene"><table width="100%">{$archiv}</table></div></td></tr>
</table>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_profile_bit',
        'template' => $db->escape_string('<<tr>
<td class="trow1" align="center"><span class="ingamelink">{$szenen[\'threadprefix\']} {$szenen[\'subject\']}</span><br />
<i class="fa fa-users" aria-hidden="true"></i> {$szenen[\'spieler\']} <br />
		<i class="fa fa-calendar" aria-hidden="true"></i> {$szenen[\'datum\']}  {$szenen[\'ip_time\']} 
		<i class="fa fa-map-signs" aria-hidden="true"></i> {$szenen[\'ort\']}</td></tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_showthread',
        'template' => $db->escape_string('<tr><td class="trow1" align="center">
				<div> {$thread[\'spieler\']}  {$thread[\'datum\']} {$thread[\'ort\']}
				</div>
	</td></tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'iptracker_pn_usercp',
        'template' => $db->escape_string('<tr>
<td valign="top"><input type="checkbox" class="checkbox" name="iptracker_pn" id="iptracker_pn" value="1" {$pn_check} /></td>
<td><span class="smalltext"><label for="iptracker_pn">{$lang->iptracker_pn_usercp}</label></span></td>
</tr>
<tr>
<td valign="top"><input type="checkbox" class="checkbox" name="iptracker_pn_all" id="iptracker_pn_all" value="1" /></td>
<td><span class="smalltext"><label for="iptracker_pn_all">{$lang->iptracker_pn_usercp_all}</label></span></td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

}

function iptracker_is_installed()
{
    global $db;
    if($db->field_exists("spieler", "threads"))
    {
        return true;
    }
    return false;
}

function iptracker_uninstall()
{
    global $db;

    //threadstabelle
    if($db->field_exists("spieler", "threads"))
    {
        $db->drop_column("threads", "spieler");
    }

    if($db->field_exists("date", "threads"))
    {
        $db->drop_column("threads", "date");
    }

    if($db->field_exists("ort", "threads"))
    {
        $db->drop_column("threads", "ort");
    }

    if($db->field_exists("ip_time", "threads"))
    {
        $db->drop_column("threads", "ip_time");
    }


    if($db->field_exists("iptracker_pn", "users"))
    {
        $db->drop_column("users", "iptracker_pn");
    }

    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='iptracker'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ip_inplay_id'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='ip_archive_id'");

    $db->delete_query("templates", "title LIKE '%iptracker%'");
}

function iptracker_activate()
{
    global $db, $cache;

    if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('iptracker_newscene'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('iptracker_newreply'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);
    }
    
    require MYBB_ROOT."/inc/adminfunctions_templates.php";

}

function iptracker_deactivate()
{
    global $db, $cache;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertTypeManager->deleteByCode('iptracker_newscene');
        $alertTypeManager->deleteByCode('iptracker_newreply');
    }


    require MYBB_ROOT."/inc/adminfunctions_templates.php";

}

//wenn neue Szene erstellt wird
function ip_newscene(){
    global $mybb, $forum, $templates, $date, $month, $year, $ort, $ip_time, $datum, $mitspieler, $db, $spieler, $post_errors, $thread, $ip_infos, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');
    //Zieht sich erstmal die Einstellung
    $ipforum = $mybb->settings['ip_inplay_id'];

    //Usergruppen, die nicht beachtet werden sollen
    $usergruppe = array(7);

    $thread['spieler'] = $mybb->user['username'].$mybb->get_input('spieler');
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist'])){
        if($mybb->input['previewpost'] || $post_errors)
        {
            $spieler = htmlspecialchars($mybb->input['spieler']);
            $date = $mybb->input['date'];
            $ip_time = $mybb->get_input('ip_time');
            $ort = htmlspecialchars($mybb->get_input('ort'));
        } else{
            $spieler = htmlspecialchars($thread['spieler']);
            $date = $thread['date'];
            $ip_time = $thread['ip_time'];
            $ort = htmlspecialchars($thread['ort']);
        }

        eval("\$mitspieler = \"".$templates->get("iptracker_mitspieler")."\";");
        eval("\$ip_infos = \"".$templates->get("iptracker_infos")."\";");
    }

}

//und jetzt mach, was du machen sollst
function ip_newscene_do(){
    global $db, $mybb, $templates, $tid, $forum;
    $ipforum = $mybb->settings['ip_inplay_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist'])) {
        $usernames = explode(',', $mybb->input['spieler']);
        $usernames = array_map("trim", $usernames);
        $spieler = array();
        foreach ($usernames as $username) {

            $username = $db->escape_string($username);
            $user = $db->query("SELECT username 
          FROM ".TABLE_PREFIX."users 
          WHERE username = '".$username."'
           ");
            $spielername = $db->fetch_field($user, "username");
            $spieler[] = $spielername;

            //Wenn Inplaytracker PN aktiv ist.

            $uid_query = $db->query("SELECT uid, username
          FROM ".TABLE_PREFIX."users 
          WHERE username = '".$username."'
           ");
            $row = $db->fetch_array($uid_query);

            $charaname = $row['username'];
            $uid = $row['uid'];
            $from_uid = $mybb->user['uid'];
            if(class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('iptracker_newscene');
                if ($alertType != NULL && $alertType->getEnabled() && $from_uid != $uid) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$uid, $alertType, (int)$tid);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }
        }

        $charakter = implode(", ", $spieler);
        $charakter = $charakter;
        $date = $_POST['date'];
        $ort = $_POST['ort'];
        $ip_time = $_POST['ip_time'];

        $new_array = array(
            "spieler" => $db->escape_string($charakter),
            "date" => $db->escape_string($date),
            "ip_time" => $db->escape_string($ip_time),
            "ort" => $db->escape_string($ort)
        );

        $db->update_query("threads", $new_array, "tid='{$tid}'");
    }
}

function ip_reply_do(){
    global $mybb, $forum, $templates, $db, $tid, $thread, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');

    $ipforum = $mybb->settings['ip_inplay_id'];

    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist'])){
        $charas = explode(", ", $thread['spieler']);
        $subject = $thread['subject'];


        foreach ($charas as $chara) {
            $chara = $db->escape_string($chara);
            $uid_query = $db->query("SELECT uid, username
          FROM ".TABLE_PREFIX."users 
          WHERE username = '".$chara."'
           ");
            $row = $db->fetch_array($uid_query);

            $charaname = $row['username'];
            $uid = $row['uid'];
            $from_uid = $mybb->user['uid'];
            if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $last_post = $db->fetch_field($db->query("SELECT pid FROM ".TABLE_PREFIX."posts WHERE tid = '$thread[tid]' ORDER BY pid DESC LIMIT 1"), "pid");

                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('iptracker_newreply');
                if ($alertType != NULL && $alertType->getEnabled() && $from_uid != $uid) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int)$uid, $alertType, (int)$thread['tid']);
                    $alert->setExtraDetails([
                        'subject' => $subject,
                        'lastpost' => $last_post
                    ]);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);

                }
            }
        }
    }
}

function ip_editscene(){
    global $mybb, $forum, $templates, $ip_infos, $ort, $mitspieler, $db, $spieler, $post_errors, $thread, $date, $month, $year, $dates, $edit_month, $edit_year, $ip_time,  $month_select, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');

//Zieht sich erstmal die Einstellung
    $ipforum = $mybb->settings['ip_inplay_id'];
    $archiv_id = $mybb->settings['ip_archive_id'];

    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {
        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);

        if ($thread['firstpost'] == $pid) {
            if($mybb->input['previewpost'] || $post_errors)
            {

                $spieler = htmlspecialchars($mybb->get_input('spieler'));
                $date= htmlspecialchars($mybb->get_input('date'));
                $ip_time = htmlspecialchars($mybb->get_input('ip_time'));
                $ort = htmlspecialchars($mybb->get_input('ort'));
            } else{
                $spieler = htmlspecialchars($thread['spieler']);
                $date = $thread['date'];
                $ip_time = $thread['ip_time'];
                $ort = htmlspecialchars($thread['ort']);
            }

            eval("\$mitspieler = \"".$templates->get("iptracker_mitspieler_edit")."\";");
            eval("\$ip_infos = \"".$templates->get("iptracker_infos")."\";");
        }
    }
}

//und jetzt mach, was du machen sollst
function ip_editscene_do(){
    global $db, $mybb, $templates, $tid, $forum, $thread;

    $ipforum = $mybb->settings['ip_inplay_id'];
    $archiv_id = $mybb->settings['ip_archive_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {
        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);

        if ($thread['firstpost'] == $pid) {
            $charakter = htmlspecialchars($mybb->input['spieler']);
            $date = $mybb->input['date'];
            $ip_time = $mybb->input['ip_time'];
            $ort = $mybb->input['ort'];


            $new_array = array(
                "spieler" => $db->escape_string($charakter),
                "date" => $db->escape_string($date),
                "ip_time" => $db->escape_string($ip_time),
                "ort" => $db->escape_string($ort)
            );

            $db->update_query("threads", $new_array, "tid='{$tid}'");
        }
    }
}

//Übersicht der Inplayszenen
function ipscenes()
{
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $character, $db, $page,$szene, $spieler, $szenen_bit, $lastpost, $postdate,  $status, $charaszenen, $charaoffenszenen, $alleoffeneszenen, $alleszenen, $szene_link, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');

    if ($mybb->get_input ('action') == 'ipszenen') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb('Deine Inplayszenen', "misc.php?action=ipszenen");
        if ($mybb->usergroup['gid'] == '1') {

            error_no_permission();
        } else {
            //Unser  ipforum
            $ipforum = $mybb->settings['ip_inplay_id'];

            //Zähler für die Szenen
            $alleszenen = 0;
            $alleoffeneszenen = 0;

//welcher user ist online
            $this_user = intval($mybb->user['uid']);

//für den fall nicht mit hauptaccount online
            $as_uid = intval($mybb->user['as_uid']);

// suche alle angehangenen accounts
            if ($as_uid == 0) {
                $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $this_user) OR (uid = $this_user) ORDER BY username ASC");
            } else if ($as_uid != 0) {
//id des users holen wo alle angehangen sind
                $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid) ORDER BY username ASC");
            }
            while ($row = $db->fetch_array($select)) {
                $szene = "";
                $spieler = $db->escape_string($row['username']);
                $character = format_name($row['username'], $row['usergroup'], $row['displaygroup']);

                $charaoffenszenen = 0;
                $charaszenen = 0;

                //jetzt ziehen wir uns noch die Szenena
                $select2 = $db->query("SELECT *, t.lastposter, t.lastpost, t.date, t.spieler, t.subject, t.ort, t.lastposteruid, p.pid
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.lastpost = p.dateline 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
            LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
        WHERE f.parentlist LIKE '$ipforum,%'
        AND t.spieler like '%$spieler%'
		AND t.visible = '1'
        ORDER BY t.lastpost DESC
       ");


                while ($szenen = $db->fetch_array($select2)) {

                    $alleszenen++;

                    $tagged = explode(", ", $szenen['spieler']);
                    $prefix = $szenen['displaystyle'];

                    $key = array_search($szenen['lastposter'], $tagged);
                    $key = $key + 1;
                    $next = $tagged[$key];

                    if (!$tagged[$key]) {
                        $next = $tagged[0];
                    }



                    if(empty($prefix)) {
                        if ($next == $row['username']) {
                            $status = "<center><i class=\"fa fa-star\" aria-hidden=\"true\"></i> <span style=\"text-transform: uppercase; font-size: 12px; font-weight: bold;\">DU BIST DRAN!</span></center>";
                            $alleoffeneszenen++;
                            $charaoffenszenen++;

                        } else {
                            $status = "<center><i class=\"fa fa-star-o\" aria-hidden=\"true\"></i> <span style=\"text-transform: uppercase; font-size: 12px; font-style: italic;\">$next</span></center>";
                        }
                    }else{
                        $status = "<div align='center'><i class=\"fa fa-star\" aria-hidden=\"true\"></i>  <span  style=\"text-transform: uppercase; font-size: 12px; font-style: italic;\">{$szenen['prefix']}</span></div>";
                    }

                    $charaszenen++;

                    $szenen['date'] = strtotime($szenen['date']);
                    $szenen['datum'] = date("d.m.Y", $szenen['date']);
                    if(!empty($szenen['ip_time'])){
                        $szenen['ip_time'] = ", ".$szenen['ip_time'];
                    }

                    $postdate = my_date("relative", $szenen['lastpost']);

                    $charas = explode(", ", $szenen['spieler']);
                    $charalist = array();
                    foreach ($charas as $charaname) {
                        $charaname = $db->escape_string($charaname);
                        $chara_query = $db->simple_select("users", "*", "username ='$charaname'");
                        $charaktername = $db->fetch_array($chara_query);
                        if (!empty($charaktername)) {
                            $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                            $chara = build_profile_link($username, $charaktername['uid']);
                        } else {
                            $chara = $charaname;
                        }
                        array_push($charalist, $chara);
                    }

                    //lasst uns die Charas wieder zusammenkleben :D
                    $szenen['spieler']= implode(", ", $charalist);

                    $szenen['lastposter'] = build_profile_link($szenen['lastposter'], $szenen['lastposteruid']);

                    if (my_strlen($szenen['subject']) > 35) {
                        $szenen['subject'] = my_substr($szenen['subject'], 0, 35) . "...";
                    }

                    $lastpost = $szenen['pid'];

                    $szene_link = "<a href=\"showthread.php?tid={$szenen['tid']}&pid={$lastpost}#pid{$lastpost}\" target=\"blank\">{$szenen['subject']}</a>";

                    eval("\$szene .= \"" . $templates->get("iptracker_bit_misc") . "\";");
                }

                eval("\$szenen_bit .= \"" . $templates->get("iptracker_misc_bit") . "\";");
            }

            eval("\$page = \"" . $templates->get("iptracker_misc") . "\";");
            output_page($page);
        }
    }
}

function ip_global(){
    global $mybb, $db, $templates, $iptracker, $alleszenen, $alleoffeneszenen, $iptracker2, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');
    //Unser  ipforum
    $ipforum = $mybb->settings['ip_inplay_id'];

    //Zähler für die Szenen
    $alleszenen = 0;
    $alleoffeneszenen = 0;

//welcher user ist online
    $this_user = intval ($mybb->user['uid']);

//für den fall nicht mit hauptaccount online
    $as_uid = intval ($mybb->user['as_uid']);

// suche alle angehangenen accounts
    if ($as_uid == 0) {
        $select = $db->query ("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $this_user) OR (uid = $this_user) ORDER BY username ASC");
    } else if ($as_uid != 0) {
//id des users holen wo alle angehangen sind
        $select = $db->query ("SELECT * FROM " . TABLE_PREFIX . "users WHERE (as_uid = $as_uid) OR (uid = $this_user) OR (uid = $as_uid) ORDER BY username ASC");
    }
    while ($row = $db->fetch_array ($select)) {
        $szene = "";
        $spieler = $db->escape_string ($row['username']);


        //jetzt ziehen wir uns noch die Szenen
        $select2 = $db->query ("SELECT t.lastposter, t.lastpost, t.date, t.spieler, t.subject, t.ort, t.lastposteruid, tp.prefix
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.lastpost = p.dateline 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
                  LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
        WHERE f.parentlist LIKE '$ipforum,%'
        AND t.spieler like '%$spieler%'
		  AND t.visible='1'
       ");


        while ($szenen = $db->fetch_array ($select2)) {

            $alleszenen++;

            $tagged = explode(", ", $szenen['spieler']);

            $key = array_search($szenen['lastposter'], $tagged);
            $key = $key + 1;
            $next = $tagged[$key];

            if(!$tagged[$key]) {
                $next = $tagged[0];
            }

            if(empty($szenen['prefix'])) {

                if ($next == $row['username']) {
                    $alleoffeneszenen++;
                }
            }

        }
    }

    eval("\$iptracker = \"" . $templates->get ("iptracker_global") . "\";");
}

function ip_profile(){
    global $db, $mybb, $templates, $tracker_profile, $archiv, $inplay, $memprofile, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');
    //Forenids
    $ip_inplay_id = $mybb->settings['ip_inplay_id'];
    $archiv_id = $mybb->settings['ip_archive_id'];

    //Userid
    $charakter = $db->escape_string($memprofile['username']);
    $charakterid = $memprofile['uid'];



    $aktive = 0;
    $beendete = 0;
    //jetzt ziehen wir uns noch die Inplay Szenen
    $select = $db->query ("SELECT t.lastposter, t.lastpost, t.date, t.spieler, t.ip_time, t.subject, t.ort, t.lastposteruid, t.tid, p.pid, displaystyle
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.tid = p.tid 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
            LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
        WHERE f.parentlist LIKE '$ip_inplay_id,%'
        AND t.spieler like '%$charakter%'

        AND t.visible='1'
		AND t.spieler != ''
        GROUP BY t.tid
        ORDER BY t.date desc, t.subject asc
       ");


    while ($szenen = $db->fetch_array ($select)) {
        $aktive++;
        $szenen['date'] = strtotime($szenen['date']);
        $szenen['datum'] = date("d.m.Y", $szenen['date']);

        if($szenen['ip_time'] != '00:00:00'){
            $szenen['ip_time'] = "(".$szenen['ip_time'].")";
        }

        $prefix = $szenen['displaystyle'];

        $charas = explode(", ", $szenen['spieler']);
        $charalist = array();
        foreach ($charas as $charaname) {
            $charaname = $db->escape_string($charaname);
            $chara_query = $db->simple_select("users", "*", "username ='$charaname'");
            $charaktername = $db->fetch_array($chara_query);
            if (!empty($charaktername)) {
                $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                $chara = build_profile_link($username, $charaktername['uid']);
            } else {
                $chara = $charaname;
            }
            array_push($charalist, $chara);
        }

        //lasst uns die Charas wieder zusammenkleben :D
        $szenen['spieler']= implode(", ", $charalist);
        $szenen['subject'] = "{$prefix} <a href=\"showthread.php?tid={$szenen['tid']}\" target=\"blank\">{$szenen['subject']}</a>";
        eval("\$inplay .= \"" . $templates->get ("iptracker_profile_bit") . "\";");

    }


    //jetzt ziehen wir uns noch die Archiv Szenen
    $select2 = $db->query ("SELECT t.lastposter, t.lastpost, t.date, t.spieler, t.ip_time ,t.subject, t.ort, t.lastposteruid, t.tid, p.pid, displaystyle
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.tid = p.tid 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
            LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
        WHERE p.username = '$charakter'
		AND t.spieler like '%$charakter%'
	    AND concat(',',f.parentlist,',') LIKE '%,".$archiv_id.",%' 
        AND t.visible='1'
		AND t.spieler != ''
        GROUP BY t.tid
        ORDER BY t.date desc, t.subject asc
       ");


    while ($szenen = $db->fetch_array ($select2)) {
        $beendete++;
        $szenen['date'] = strtotime($szenen['date']);
        $szenen['datum'] = date("d.m.Y", $szenen['date']);
        if($szenen['ip_time'] != '00:00:00'){
            $szenen['ip_time'] = "(um ".$szenen['ip_time'].")";
        }

        $prefix = $szenen['displaystyle'];

        $charas = explode(", ", $szenen['spieler']);
        $charalist = array();
        foreach ($charas as $charaname) {
            $charaname = $db->escape_string($charaname);
            $chara_query = $db->simple_select("users", "*", "username ='$charaname'");
            $charaktername = $db->fetch_array($chara_query);
            if (!empty($charaktername)) {
                $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                $chara = build_profile_link($username, $charaktername['uid']);
            } else {
                $chara = $charaname;
            }
            array_push($charalist, $chara);
        }

        //lasst uns die Charas wieder zusammenkleben :D
        $szenen['spieler']= implode(", ", $charalist);

        $szenen['subject'] = "{$prefix} <a href=\"showthread.php?tid={$szenen['tid']}\" target=\"blank\">{$szenen['subject']}</a>";
        eval("\$archiv .= \"" . $templates->get ("iptracker_profile_bit") . "\";");

    }

    eval("\$tracker_profile = \"" . $templates->get ("iptracker_profile") . "\";");
}

function ip_showthread(){
    global $db, $mybb, $templates, $forum, $thread, $tracker_thread, $lang,   $charas;
    //Die Sprachdatei
    $lang->load ('iptracker');

    $ip_inplay_id = $mybb->settings['ip_inplay_id'];
    $archiv_id = $mybb->settings['ip_archive_id'];

    $forum['parentlist'] = ",".$forum['parentlist'].",";

    if(preg_match("/,$ip_inplay_id,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {
        if($thread['spieler'] != ''){
            // alle Charas wieder auseinander nehmen
            $charas = explode(", ", $thread['spieler']);
            $charalist = array();
            foreach ($charas as $charaname) {
                $charaname = $db->escape_string($charaname);
                $chara_query = $db->simple_select("users", "*", "username ='$charaname'");
                $charaktername = $db->fetch_array($chara_query);
                if (!empty($charaktername)) {
                    $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                    $chara = build_profile_link($username, $charaktername['uid']);
                } else {
                    $chara = $charaname;
                }
                array_push($charalist, $chara);
            }

            //lasst uns die Charas wieder zusammenkleben :D
            $charas = implode(", ", $charalist);


            if($thread['date'] != ''){
                $thread['date'] = strtotime($thread['date']);
                $thread['datum'] = date("d.m.Y", $thread['date']);
            }
            if($thread['ort'] != ''){
                $thread['ort'] = $thread['ort'];
            }

            if($thread['ip_time'] != ''){
                $thread['ip_time'] = $thread['ip_time'];
            }
            eval("\$tracker_thread = \"" . $templates->get ("iptracker_showthread") . "\";");
        }
    }
}

function ip_forumdisplay(&$thread){
    global $db, $mybb, $templates, $forum, $thread, $tracker_forumdisplay,  $foruminfo, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');
    $ip_inplay_id = $mybb->settings['ip_inplay_id'];
    $archiv_id = $mybb->settings['ip_archive_id'];
    $foruminfo['parentlist'] = ",".$foruminfo['parentlist'].",";

    $tracker_forumdisplay = "";
    if(preg_match("/,$ip_inplay_id,/i", $foruminfo['parentlist']) OR preg_match("/,$archiv_id,/i", $foruminfo['parentlist']) ) {
        if($thread['spieler'] != ''){
            $charas = explode(", ", $thread['spieler']);
            $charalist = array();
            foreach ($charas as $charaname) {
                $charaname = $db->escape_string($charaname);
                $chara_query = $db->simple_select("users", "*", "username ='$charaname'");
                $charaktername = $db->fetch_array($chara_query);
                if (!empty($charaktername)) {
                    $username = format_name($charaktername['username'], $charaktername['usergroup'], $charaktername['displaygroup']);
                    $chara = build_profile_link($username, $charaktername['uid']);
                } else {
                    $chara = $charaname;
                }
                array_push($charalist, $chara);
            }

            //lasst uns die Charas wieder zusammenkleben :D
            $thread['spieler']= implode(", ", $charalist);
            $thread['spieler'] = $thread['spieler'];

            if($thread['date'] != ''){
                $thread['date'] = strtotime($thread['date']);
                $thread['datum'] = date("d.m.Y", $thread['date']);
                $thread['datum'] = " am ".$thread['datum'];
            }
            if($thread['ort'] != ''){
                $thread['ort'] = $thread['ort'];
            }

            if(!empty($thread['ip_time'])){
                $thread['ip_time'] ="({$thread['ip_time']})";
            }

            eval("\$tracker_forumdisplay = \"" . $templates->get ("iptracker_forumdisplay") . "\";");
            return $thread;
        }
    }
}

//wer ist wo
$plugins->add_hook('fetch_wol_activity_end', 'ip_user_activity');
$plugins->add_hook('build_friendly_wol_location_end', 'ip_location_activity');
function ip_user_activity($user_activity){
    global $user, $lang ;

    if(my_strpos($user['location'], "misc.php?action=ipszenen") !== false) {
        $user_activity['activity'] = "ipszenen";
    }

    return $user_activity;
}

function ip_location_activity($plugin_array) {
    global $db, $mybb, $lang ;
    //Die Sprachdatei
    $lang->load ('iptracker');

    if($plugin_array['user_activity']['activity'] == "ipszenen")
    {
        $plugin_array['location_name'] = "Übersicht der eigenen aktiven Inplayszenen.";
    }

    return $plugin_array;
}



function iptracker_alerts() {
    global $mybb, $lang;
    $lang->load('iptracker');

     /**
     * Alert formatter for my custom alert type.
     */
    class MybbStuff_MyAlerts_Formatter_InplaytrackerNewsceneFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->lang->sprintf(
                $this->lang->iptracker_newscene,
                $outputAlert['from_user'],
                $outputAlert['dateline']
            );
        }


        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/' . get_post_link((int) $alertContent['lastpost'], (int) $alert->getObjectId());
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_InplaytrackerNewsceneFormatter($mybb, $lang, 'iptracker_newscene')
        );
    }

    /**
     * Alert formatter for my custom alert type.
     */
    class MybbStuff_MyAlerts_Formatter_InplaytrackerNewreplyFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->lang->sprintf(
                $this->lang->iptracker_newreply,
                $outputAlert['from_user'],
                $alertContent['subject'],
                $outputAlert['dateline']
            );
        }


        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->mybb->settings['bburl'] . '/' . get_post_link((int) $alertContent['lastpost'], (int) $alert->getObjectId()) . '#pid' . $alertContent['lastpost'];
        }
    }

    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_InplaytrackerNewreplyFormatter($mybb, $lang, 'iptracker_newreply')
        );
    }

}