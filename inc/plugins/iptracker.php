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

    if($db->field_exists("iptracker_pn", "users"))
    {
        $db->drop_column("users", "iptracker_pn");
    }

    $db->query("DELETE FROM ".TABLE_PREFIX."settinggroups WHERE name='iptracker'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='inplay_id'");
    $db->query("DELETE FROM ".TABLE_PREFIX."settings WHERE name='archiv_id'");
}

function iptracker_activate()
{
    global $db;
    $insert_array = array(
        'title' => 'iptracker_bit_misc',
        'template' => $db->escape_string('<tr><td class="trow1" align="center">{$status}</td>
	<td class="trow1" align="center"><i class="fa fa-users" aria-hidden="true"></i> {$szenen[\'spieler\']} <br />
		<i class="fa fa-calendar" aria-hidden="true"></i> {$szenen[\'datum\']} <br />
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
        'title' => 'iptracker_datum',
        'template' => $db->escape_string('<tr>
<td class="trow1" width="20%"><strong>Datum &amp; Uhrzeit:</strong></td>
    <td class="trow1">
        <table><tr>
            <td width="50px" align="center">
       	<input type="number" class="textbox" name="day" value="{$day}" width="10" placeholder="00"></td>
        <td width="50px" align="center">
			<input type="number" class="textbox" name="month" value="{$month}" width="10" placeholder="00"></td>
<td width="70px" align="center">
<input type="number" class="textbox" name="year" value="{$year}" width="10" placeholder="0000">
   </td>
		<td align="center">  <input type="time" name="ip_time" value="{$ip_time}">
			</td>
</tr></table></td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_forumdisplay',
        'template' => $db->escape_string('<div class="smalltext"> {$thread[\'spieler\']} {$thread[\'datum\']} {$thread[\'ort\']}
				</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_global',
        'template' => $db->escape_string('<a href="misc.php?action=ipszenen">Deine Szenen</a> ({$alleoffeneszenen}|{$alleszenen})'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_misc',
        'template' => $db->escape_string('<html>
<head>
<title>Deine Inplayszenen</title>
{$headerinclude}
</head>
<body>
{$header}
<table border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}" class="tborder">
<tr>
<td class="trow2"><h1>Deine Inplayszenen (<i class="fa fa-folder-open" aria-hidden="true"></i> {$alleoffeneszenen} | <i class="fa fa-folder" aria-hidden="true"></i> {$alleszenen})</h1></td>
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
        'template' => $db->escape_string('<table width="100%"><tr><td colspan="3" class="trow1"><h2>{$row[\'username\']} (<i class="fa fa-folder-open" aria-hidden="true"></i> {$charaoffenszenen} | <i class="fa fa-folder" aria-hidden="true"></i> {$charaszenen})</h2></td> </tr>
	<tr><td class="trow1" width="20%"><strong>Status</strong></td>
		<td class="trow1" width="40%"><strong>Szeneninformationen</strong></td>
		<td class="trow1" width="40%"><strong>Letzter Post</strong></td>
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
<td class="trow1" width="20%"><strong>Mitspieler:</strong></td>
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
<td class="trow1" width="20%"><strong>Spieler:</strong></td>
<td class="trow1"><span class="smalltext"> <input type="text" class="textbox" name="spieler" size="40" maxlength="1155" value="{$spieler}" /> <br /> Trägst du Charaktere nach oder zusätzlich dazu, füge sie so ein <b>, Username</b>. Achte aber darauf, dass es korrekt geschrieben wurde.</span> </td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_ort',
        'template' => $db->escape_string('<tr>
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
<tr><td width="50%" class="thead"><h1>Aktive Szenen ({$aktive})</h1></td>
<td width="50%" class="thead"><h1>Beendete Szenen({$beendete})</h1></td></tr>
<tr><td><div class="ingamescene"><table width="100%">{$inplay}</table></div></td> <td><div class="ingamescene"><table width="100%">{$archiv}</table></div></td></tr>
</table>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);
    $insert_array = array(
        'title' => 'iptracker_profile_bit',
        'template' => $db->escape_string('<tr>
<td class="trow1" align="center"><span class="ingamelink">{$szenen[\'threadprefix\']} {$szenen[\'subject\']}</span><br />
<i class="fa fa-users" aria-hidden="true"></i> {$szenen[\'spieler\']} <br />
		<i class="fa fa-calendar" aria-hidden="true"></i> {$szenen[\'datum\']} 
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
<td><span class="smalltext"><label for="iptracker_pn">Eine PN bei neuen Post?</label></span></td>
</tr>
<tr>
<td valign="top"><input type="checkbox" class="checkbox" name="iptracker_pn_all" id="iptracker_pn_all" value="1" /></td>
<td><span class="smalltext"><label for="iptracker_pn_all">Pns für alle Charaktere aktiveren/deaktivieren?</label></span></td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header_welcomeblock_member", "#".preg_quote('<div class="wrapper">')."#i", '	<div class="wrapper"> {$iptracker}');
    find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$thread[\'profilelink\']}')."#i", '{$tracker_forumdisplay} {$thread[\'profilelink\']}');
    find_replace_templatesets("showthread", "#".preg_quote('<tr><td id="posts_container">')."#i", '{$tracker_thread}
		<tr><td id="posts_container">');
    find_replace_templatesets("member_profile", "#".preg_quote('{$signature}')."#i", '	{$signature} {$tracker_profile}');
    find_replace_templatesets("editpost", "#".preg_quote('{$posticons}')."#i", '{$mitspieler}	{$datum} {$ort}
{$posticons}');
    find_replace_templatesets("newthread", "#".preg_quote('{$posticons}')."#i", '{$mitspieler}	{$datum} {$ort}
	{$posticons}');
    find_replace_templatesets("usercp_options", "#".preg_quote('{$calendaroptions}')."#i", '{$tracker_pn}
{$calendaroptions}');
}

function iptracker_deactivate()
{
    global $db;
    $db->delete_query("templates", "title LIKE '%iptracker%'");
    require MYBB_ROOT."/inc/adminfunctions_templates.php";
    find_replace_templatesets("header_welcomeblock_member", "#".preg_quote('| {$iptracker}')."#i", '', 0);
    find_replace_templatesets("showthread", "#".preg_quote('{$tracker_thread}')."#i", '', 0);
    find_replace_templatesets("forumdisplay_thread", "#".preg_quote('{$tracker_forumdisplay}')."#i", '', 0);
    find_replace_templatesets("member_profile", "#".preg_quote('{$tracker_profile}')."#i", '', 0);
    find_replace_templatesets("editpost", "#".preg_quote('{$mitspieler}	{$datum} {$ort}')."#i", '', 0);
    find_replace_templatesets("newthread", "#".preg_quote('{$mitspieler}	{$datum} {$ort}')."#i", '', 0);
    find_replace_templatesets("usercp_options", "#".preg_quote('{$tracker_pn}')."#i", '', 0);

}

//wenn neue Szene erstellt wird
function ip_newscene(){
    global $mybb, $forum, $templates, $day, $month, $year, $ort, $datum, $mitspieler, $db, $spieler, $post_errors, $thread, $days ;

//Zieht sich erstmal die Einstellung
    $ipforum = $mybb->settings['inplay_id'];

    //Usergruppen, die nicht beachtet werden sollen
    $usergruppe = array(7);
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist'])){
        if(isset($mybb->input['previewpost']) || $post_errors)
        {
            $spieler = htmlspecialchars($mybb->get_input('spieler'));
            $day = $mybb->input['day'];
            $month = $mybb->input['month'];
            $year = $mybb->input['year'];
            $ort = htmlspecialchars($mybb->get_input('ort'));
        } else{
            $spieler = htmlspecialchars($thread['spieler']);
            $day = $thread['day'];
            $month = $thread['month'];
            $year = $thread['year'];
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


        $new_array = array(
            "spieler" => $db->escape_string($charakter),
            "day" => $db->escape_string($day),
            "month" => $db->escape_string($month),
            "year" => $db->escape_string($year),
            "ort" => $db->escape_string($ort)
        );

        $db->update_query("threads", $new_array, "tid='{$tid}'");
    }
}

function ip_editscene(){
    global $mybb, $forum, $templates, $datum, $ort, $mitspieler, $db, $spieler, $post_errors, $thread, $day, $month, $year, $days, $edit_month, $edit_year ;

//Zieht sich erstmal die Einstellung
    $ipforum = $mybb->settings['inplay_id'];
    $archiv_id = $mybb->settings['archiv_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if(preg_match("/,$ipforum,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {

        $pid = $mybb->get_input ('pid', MyBB::INPUT_INT);
        if ($thread['firstpost'] == $pid) {
            if(isset($mybb->input['previewpost']) || $post_errors)
            {
                $spieler = htmlspecialchars($mybb->get_input('spieler'));
                $day= htmlspecialchars($mybb->get_input('day'));
                $month= htmlspecialchars($mybb->get_input('month'));
                $year = htmlspecialchars($mybb->get_input('year'));
                $ort = htmlspecialchars($mybb->get_input('ort'));
            } else{
                $spieler = htmlspecialchars($thread['spieler']);
                $day = $thread['day'];
                $month = $thread['month'];;
                $year = $thread['year'];;
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
            $ort = $mybb->input['ort'];


            $new_array = array(
                "spieler" => $db->escape_string($charakter),
                "day" => $db->escape_string($day),
                "month" => $db->escape_string($month),
                "year" => $db->escape_string($year),
                "ort" => $db->escape_string($ort)
            );

            $db->update_query("threads", $new_array, "tid='{$tid}'");
        }
    }
}

//Übersicht der Inplayszenen
function ipscenes()
{
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $db, $page,$szene, $spieler, $szenen_bit, $lastpost, $postdate,  $status, $charaszenen, $charaoffenszenen, $alleoffeneszenen, $alleszenen ;

    if ($mybb->get_input ('action') == 'ipszenen') {
        // Do something, for example I'll create a page using the hello_world_template

        // Add a breadcrumb
        add_breadcrumb ('Deine Inplayszenen', "misc.php?action=ipszenen");

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

            $charaoffenszenen = 0;
            $charaszenen = 0;

            //jetzt ziehen wir uns noch die Szenena
            $select2 = $db->query ("SELECT *, t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.subject, t.ort, t.lastposteruid
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.lastpost = p.dateline 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
        WHERE f.parentlist LIKE '$ipforum,%'
        AND t.spieler like '%$spieler%'
		AND t.visible = '1'
        ORDER BY t.lastpost DESC
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

                if($next == $spieler) {
                    $status = "<center><i class=\"fa fa-star\" aria-hidden=\"true\"></i> <span style=\"text-transform: uppercase; font-size: 12px; font-weight: bold;\">DU BIST DRAN!</span></center>";
                    $alleoffeneszenen++;
                    $charaoffenszenen++;

                }

                else {
                    $status = "<center><i class=\"fa fa-star-o\" aria-hidden=\"true\"></i> <span style=\"text-transform: uppercase; font-size: 12px; font-style: italic;\">$next</span></center>";
                }

                $charaszenen++;
                $szenen['datum'] = $szenen['day'] . "." . $szenen['month'] . "." . $szenen['year'];

                $postdate = my_date ("relative", $szenen['lastpost']);

                $szenen['lastposter'] = build_profile_link ($szenen['lastposter'], $szenen['lastposteruid']);

                if (my_strlen ($szenen['subject']) > 35) {
                    $szenen['subject'] = my_substr ($szenen['subject'], 0, 35) . "...";
                }


                eval("\$szene .= \"" . $templates->get ("iptracker_bit_misc") . "\";");
            }

            eval("\$szenen_bit .= \"" . $templates->get ("iptracker_misc_bit") . "\";");
        }

        eval("\$page = \"" . $templates->get ("iptracker_misc") . "\";");
        output_page ($page);
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
        $select2 = $db->query ("SELECT t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.subject, t.ort, t.lastposteruid
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.lastpost = p.dateline 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
        WHERE f.parentlist LIKE '$ipforum,%'
        AND t.spieler like '%$spieler%'
		  AND t.visible='1'
       ORDER BY t.year desc, t.month desc, t.day desc, t.subject asc
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

            if($next == $spieler) {
                $alleoffeneszenen++;
            }
            if($alleoffeneszenen > '0'){

                $ip_book ="<i class=\"fas fa-book\" style='color: 	 rgba(130, 161, 69, 0.65);'></i>";

            } else{
                $ip_book ="<i class=\"fas fa-book\"></i>";
            }


        }
    }

    eval("\$iptracker = \"" . $templates->get ("iptracker_global") . "\";");
    eval("\$iptracker2 = \"" . $templates->get ("iptracker_global2") . "\";");
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
    $inplay_select = $db->query ("SELECT *, t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.subject, t.ort, t.lastposteruid, t.tid, p.pid
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.tid = p.tid 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
        WHERE p.username = '$charakter'
		AND t.spieler like '%$charakter%'
		AND f.parentlist LIKE '".$inplay_id.",%' 
        AND t.visible='1'
		AND t.spieler != ''
        GROUP BY t.tid
        ORDER BY t.year desc, t.month desc, t.day desc, t.subject asc
       ");



    while ($szenen = $db->fetch_array ($inplay_select)) {
        $aktive++;
        $szenen['datum'] = $szenen['day'] . "." . $szenen['month'] . "." . $szenen['year'];
        $szenen['subject'] = "<a href=\"showthread.php?tid={$szenen['tid']}\" target=\"blank\">{$szenen['subject']}</a>";
        eval("\$inplay .= \"" . $templates->get("iptracker_profile_bit") . "\";");


    }

    //jetzt ziehen wir uns noch die Archiv Szenen
    $archiv_select = $db->query ("SELECT *, t.lastposter, t.lastpost, t.year, t.spieler, t.month, t.day, t.subject, t.ort, t.lastposteruid, t.tid, p.pid
        FROM " . TABLE_PREFIX . "threads t
        LEFT JOIN " . TABLE_PREFIX . "posts p
        on t.tid = p.tid 
        LEFT JOIN " . TABLE_PREFIX . "forums f
        on t.fid = f.fid
        WHERE p.username = '$charakter'
		AND t.spieler like '%$charakter%'
		AND f.parentlist LIKE '%,".$archiv_id."' 
        AND t.visible='1'
		AND t.spieler != ''
        GROUP BY t.tid
        ORDER BY t.year desc, t.month desc, t.day desc, t.subject asc
       ");


    while ($szenen = $db->fetch_array ($archiv_select)) {

        $beendete++;

        $szenen['datum'] = $szenen['day'] . "." . $szenen['month'] . "." . $szenen['year'];
        $szenen['subject'] = "<a href=\"showthread.php?tid={$szenen['tid']}\" target=\"blank\">{$szenen['subject']}</a>";
        eval("\$archiv .= \"" . $templates->get("iptracker_profile_bit") . "\";");


    }

    eval("\$tracker_profile = \"" . $templates->get ("iptracker_profile") . "\";");
}

function ip_showthread(){
    global $db, $mybb, $templates, $forum, $thread, $tracker_thread;

    $inplay_id = $mybb->settings['inplay_id'];
    $archiv_id = $mybb->settings['archiv_id'];
    $forum['parentlist'] = ",".$forum['parentlist'].",";
    if($thread['spieler'] != ''){
        if(preg_match("/,$inplay_id,/i", $forum['parentlist']) OR preg_match("/,$archiv_id,/i", $forum['parentlist'])) {

            $thread['spieler'] = "<i class=\"fa fa-group\" aria-hidden=\"true\"></i> ".$thread['spieler'];

            if($thread['day'] != ''){
                $thread['datum'] = $thread['day'] . "." . $thread['month'] . "." . $thread['year'];
                $thread['datum'] = "<i class=\"fa fa-calendar\" aria-hidden=\"true\"></i> ".$thread['datum'];
            }
            if($thread['ort'] != ''){
                $thread['ort'] = "<i class=\"fa fa-map-signs\" aria-hidden=\"true\"></i> ".$thread['ort'];
            }
            eval("\$tracker_thread = \"" . $templates->get ("iptracker_showthread") . "\";");
        }
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

        eval("\$tracker_forumdisplay = \"" . $templates->get ("iptracker_forumdisplay") . "\";");
        return $thread;
    }

}
