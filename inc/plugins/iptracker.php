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
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `spieler` varchar(400) CHARACTER SET utf8 NOT NULL AFTER `replies`;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `day` varchar(10) NOT NULL AFTER `spieler`;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `month` varchar(10) NOT NULL AFTER `day`;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `year` varchar(10) NOT NULL AFTER `month`;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `ort` varchar(400) CHARACTER SET utf8 NOT NULL AFTER `year`;");
    $db->query("ALTER TABLE `".TABLE_PREFIX."threads` ADD `ip_time` varchar(400) CHARACTER SET utf8 NOT NULL AFTER `ort`;");
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
        'name' => 'inplay_id',
        'title' => 'Kategorien ID',
        'description' => 'Gib hier die ID deiner Inplaykategorie an.',
        'optionscode' => 'forumselectsingle ',
        'value' => '23',
        'disporder' => 1,
        "gid" => (int)$gid
    );
    $db->insert_query('settings', $setting_array);

    $setting_array = array(
        'name' => 'archiv_id',
        'title' => 'Archiv ID',
        'description' => 'Gib hier die ID deines Archivs an.',
        'optionscode' => 'forumselectsingle ',
        'value' => '2',
        'disporder' => 2,
        "gid" => (int)$gid
    );
    $db->insert_query('settings', $setting_array);
    rebuild_settings();}

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

    if($db->field_exists("day", "threads"))
    {
        $db->drop_column("threads", "day");
    }

    if($db->field_exists("month", "threads"))
    {
        $db->drop_column("threads", "month");
    }

    if($db->field_exists("year", "threads"))
    {
        $db->drop_column("threads", "year");
    }

    if($db->field_exists("ort", "threads"))
    {
        $db->drop_column("threads", "ort");
    }

    if($db->field_exists("ip_time", "threads"))
    {
        $db->drop_column("threads", "ip_time");
    }
    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='iptracker'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='inplay_id'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='archiv_id'");
}

function iptracker_activate()
{
    global $db;

}

function iptracker_deactivate()
{
    global $db;

}
//wenn neue Szene erstellt wird
function ip_newscene(){
    global $mybb, $forum, $templates, $day, $month, $year, $ort, $ip_time, $datum, $mitspieler, $db, $spieler, $post_errors, $thread ;

    //Zieht sich erstmal die Einstellung
    $ipforum = $mybb->settings['inplay_id'];
    //Usergruppen, die nicht beachtet werden sollen
    $usergruppe = array(7);
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist'])){
        if($mybb->input['previewpost'] || $post_errors)
        {
            $spieler = htmlspecialchars($mybb->get_input('spieler'));
            $day = $mybb->input['day'];
            $month = $mybb->input['month'];
            $year = $mybb->input['year'];
            $ip_time = $mybb->get_input('ip_time');
            $ort = htmlspecialchars($mybb->get_input('ort'));
        } else{
            $spieler = htmlspecialchars($thread['spieler']);
            $day = $thread['day'];
            $month = $thread['month'];
            $year = $thread['year'];
            $ip_time = $thread['ip_time'];
            $ort = htmlspecialchars($thread['ort']);
        }

        eval("\$mitspieler = \"".$templates->get("iptracker_mitspieler")."\";");
        eval("\$datum = \"".$templates->get("iptracker_datum")."\";");
        eval("\$ort = \"".$templates->get("iptracker_ort")."\";");
    }

}

//und jetzt mach, was du machen sollst
function ip_newscene_do(){
    global $db, $mybb, $templates, $tid, $forum;

    $ipforum = $mybb->settings['inplay_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist'])) {
        $usernames = explode(',', $mybb->input['spieler']);
        $usernames = array_map("trim", $usernames);
        $spieler = array();
        foreach ($usernames as $username) {
            $user = $db->query("SELECT username 
          FROM ".TABLE_PREFIX."users 
          WHERE username = '".$username."'
           ");
            $spielername = $db->fetch_field($user, "username");
            $spieler[] = $spielername;
        }

        $charakter = implode(", ", $spieler);
        $day = $_POST['day'];
        $month = $_POST['month'];
        $year = $_POST['year'];
        $ort = $_POST['ort'];
        $ip_time = $_POST['ip_time'];

        $new_array = array(
            "spieler" => $db->escape_string($charakter),
            "day" => $db->escape_string($day),
            "month" => $db->escape_string($month),
            "year" => $db->escape_string($year),
            "ip_time" => $db->escape_string($ip_time),
            "ort" => $db->escape_string($ort)
        );

        $db->update_query("threads", $new_array, "tid='{$tid}'");
    }
}

function ip_editscene(){
    global $mybb, $forum, $templates, $datum, $ort, $mitspieler, $db, $spieler, $post_errors, $thread, $day, $month, $year, $days, $edit_month, $edit_year, $ip_time,  $month_select  ;

//Zieht sich erstmal die Einstellung
    $ipforum = $mybb->settings['inplay_id'];
    $archiv_id = $mybb->settings['archiv_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {
        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);
        if ($thread['firstpost'] == $pid) {
            if($mybb->input['previewpost'] || $post_errors)
            {

                $spieler = htmlspecialchars($mybb->get_input('spieler'));
                $day= htmlspecialchars($mybb->get_input('day'));

                $month= htmlspecialchars($mybb->get_input('month'));


                $year = htmlspecialchars($mybb->get_input('year'));
                $ip_time = htmlspecialchars($mybb->get_input('ip_time'));
                $ort = htmlspecialchars($mybb->get_input('ort'));
            } else{
                $spieler = htmlspecialchars($thread['spieler']);
                $day = $thread['day'];
                $month = $thread['month'];
                $year = $thread['year'];
                $ip_time = $thread['ip_time'];
                $ort = htmlspecialchars($thread['ort']);
            }

            eval("\$mitspieler = \"" . $templates->get("iptracker_mitspieler_edit") . "\";");
            eval("\$datum = \"" . $templates->get("iptracker_datum") . "\";");
            eval("\$ort = \"" . $templates->get("iptracker_ort") . "\";");
        }
    }
}

//und jetzt mach, was du machen sollst
function ip_editscene_do(){
    global $db, $mybb, $templates, $tid, $forum, $thread;

    $ipforum = $mybb->settings['inplay_id'];
    $archiv_id = $mybb->settings['archiv_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {
        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);
        if ($thread['firstpost'] == $pid) {


            $charakter = $mybb->input['spieler'];

            $day = $mybb->input['day'];
            $month = $mybb->input['month'];
            $year = $mybb->input['year'];
            $ip_time = $mybb->input['ip_time'];
            $ort = $mybb->input['ort'];


            $new_array = array(
                "spieler" => $db->escape_string($charakter),
                "day" => $db->escape_string($day),
                "month" => $db->escape_string($month),
                "year" => $db->escape_string($year),
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
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $db, $page,$szene, $spieler, $szenen_bit, $lastpost, $postdate,  $status, $charaszenen, $charaoffenszenen, $alleoffeneszenen, $alleszenen, $szene_link ;

    if ($mybb->get_input ('action') == 'ipszenen') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb('Deine Inplayszenen', "misc.php?action=ipszenen");
        if ($mybb->usergroup['gid'] == '1') {

            error_no_permission();
        } else {
            //Unser  ipforum
            $ipforum = $mybb->settings['inplay_id'];

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
                $character = format_name($spieler, $row['usergroup'], $row['displaygroup']);

                $charaoffenszenen = 0;
                $charaszenen = 0;

                //jetzt ziehen wir uns noch die Szenena
                $select2 = $db->query("SELECT *, t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.subject, t.ort, t.lastposteruid, p.pid
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
                        if ($next == $spieler) {
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
                    $szenen['datum'] = $szenen['day'] . "." . $szenen['month'] . "." . $szenen['year'];

                    $postdate = my_date("relative", $szenen['lastpost']);



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
    global $mybb, $db, $templates, $iptracker, $alleszenen, $alleoffeneszenen, $iptracker2;

    //Unser  ipforum
    $ipforum = $mybb->settings['inplay_id'];

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
        $select2 = $db->query ("SELECT t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.subject, t.ort, t.lastposteruid, tp.prefix
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

                if ($next == $spieler) {
                    $alleoffeneszenen++;
                }
            }

        }
    }

    eval("\$iptracker = \"" . $templates->get ("iptracker_global") . "\";");
}

function ip_profile(){
    global $db, $mybb, $templates, $tracker_profile, $archiv, $inplay, $memprofile;

    //Forenids
    $inplay_id = $mybb->settings['inplay_id'];
    $archiv_id = $mybb->settings['archiv_id'];

    //Userid
    $charakter = $db->escape_string($memprofile['username']);
    $charakterid = $memprofile['uid'];


    $aktive = 0;
    $beendete = 0;
    //jetzt ziehen wir uns noch die Inplay Szenen
    $select = $db->query ("SELECT t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.ip_time, t.subject, t.ort, t.lastposteruid, t.tid, any_value(p.pid), displaystyle
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.tid = p.tid 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
            LEFT JOIN ".TABLE_PREFIX."threadprefixes tp 
    ON (tp.pid=t.prefix) 
        WHERE f.parentlist LIKE '$inplay_id,%'
        AND t.spieler like '%$charakter%'

        AND t.visible='1'
		AND t.spieler != ''
        GROUP BY t.tid
        ORDER BY t.year desc, t.month desc, t.day desc, t.subject asc
       ");


    while ($szenen = $db->fetch_array ($select)) {
        $aktive++;
        $szenen['datum'] = $szenen['day'] . "." . $szenen['month'] . "." . $szenen['year'];

        if(!empty($szenen['ip_time'])){
            $szenen['ip_time'] = "(um ".$szenen['ip_time'].")";
        }

        $prefix = $szenen['displaystyle'];

        $szenen['subject'] = "{$prefix} <a href=\"showthread.php?tid={$szenen['tid']}\" target=\"blank\">{$szenen['subject']}</a>";
        eval("\$inplay .= \"" . $templates->get ("iptracker_profile_bit") . "\";");

    }


    //jetzt ziehen wir uns noch die Archiv Szenen
    $select2 = $db->query ("SELECT t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.ip_time ,t.subject, t.ort, t.lastposteruid, t.tid, any_value(p.pid), displaystyle
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
        ORDER BY t.year desc, t.month desc, t.day desc, t.subject asc
       ");


    while ($szenen = $db->fetch_array ($select2)) {
        $beendete++;
        $szenen['datum'] = $szenen['day'] . "." . $szenen['month'] . "." . $szenen['year'];
        if(!empty($szenen['ip_time'])){
            $szenen['ip_time'] = "(um ".$szenen['ip_time'].")";
        }

        $prefix = $szenen['displaystyle'];

        $szenen['subject'] = "{$prefix} <a href=\"showthread.php?tid={$szenen['tid']}\" target=\"blank\">{$szenen['subject']}</a>";
        eval("\$archiv .= \"" . $templates->get ("iptracker_profile_bit") . "\";");

    }

    eval("\$tracker_profile = \"" . $templates->get ("iptracker_profile") . "\";");
}

function ip_showthread(){
    global $db, $mybb, $templates, $forum, $thread, $tracker_thread;

    $inplay_id = $mybb->settings['inplay_id'];
    $archiv_id = $mybb->settings['archiv_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";

    if(preg_match("/,$inplay_id,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {
        if($thread['spieler'] != ''){
            $thread['spieler'] = "<i class=\"fa fa-group\" aria-hidden=\"true\"></i> ".$thread['spieler'];
        }
        if($thread['day'] != ''){
            $thread['datum'] = $thread['day'] . "." . $thread['month'] . "." . $thread['year'];
            $thread['datum'] = "<i class=\"fa fa-calendar\" aria-hidden=\"true\"></i> ".$thread['datum'];
        }
        if($thread['ort'] != ''){
            $thread['ort'] = "<i class=\"fa fa-map-signs\" aria-hidden=\"true\"></i> ".$thread['ort'];
        }

        if($thread['ip_time'] != ''){
            $thread['ip_time'] = "<i class=\"fas fa-clock\"></i> {$thread['ip_time']}";
        }
        eval("\$tracker_thread = \"" . $templates->get ("iptracker_showthread") . "\";");
    }

}

function ip_forumdisplay(&$thread){
    global $db, $mybb, $templates, $forum, $thread, $tracker_forumdisplay,  $foruminfo;
    $inplay_id = $mybb->settings['inplay_id'];
    $archiv_id = $mybb->settings['archiv_id'];
    $foruminfo['parentlist'] = ",".$foruminfo['parentlist'].",";

    if(preg_match("/,$inplay_id,/i", $foruminfo['parentlist']) OR preg_match("/,$archiv_id,/i", $foruminfo['parentlist']) ) {
        if($thread['spieler'] != ''){
            $thread['spieler'] = "<i class=\"fa fa-group\" aria-hidden=\"true\"></i> ".$thread['spieler'];
        }
        if($thread['day'] != ''){
            $thread['datum'] = $thread['day'] . "." . $thread['month'] . "." . $thread['year'];
            $thread['datum'] = "<i class=\"fa fa-calendar\" aria-hidden=\"true\"></i> ".$thread['datum'];
        }
        if($thread['ort'] != ''){
            $thread['ort'] = "<i class=\"fa fa-map-signs\" aria-hidden=\"true\"></i> ".$thread['ort'];
        }

        if(!empty($thread['ip_time'])){
            $thread['ip_time'] ="<i class=\"fas fa-clock\"></i> {$thread['ip_time']}";
        }

        eval("\$tracker_forumdisplay = \"" . $templates->get ("iptracker_forumdisplay") . "\";");
        return $thread;
    }

}

//wer ist wo
$plugins->add_hook('fetch_wol_activity_end', 'ip_user_activity');
$plugins->add_hook('build_friendly_wol_location_end', 'ip_location_activity');
function ip_user_activity($user_activity){
    global $user;

    if(my_strpos($user['location'], "misc.php?action=ipszenen") !== false) {
        $user_activity['activity'] = "ipszenen";
    }

    return $user_activity;
}

function ip_location_activity($plugin_array) {
    global $db, $mybb, $lang;

    if($plugin_array['user_activity']['activity'] == "ipszenen")
    {
        $plugin_array['location_name'] = "Übersicht der eigenen aktiven Inplayszenen.";
    }

    return $plugin_array;
}