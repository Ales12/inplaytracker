# Inplaytracker by Alex (Ales)

### Templates
* iptracker_bit_misc 	
* iptracker_forumdisplay 
* iptracker_global 	
* iptracker_infos 	
* iptracker_misc 	
* iptracker_misc_bit 	
* iptracker_mitspieler 
* iptracker_mitspieler_edit 	
* iptracker_pn_usercp 	
* iptracker_profile 	
* iptracker_profile_bit 
* iptracker_showthread

### Variabeln

**header_welcomeblock_member**
```
{$iptracker}
```

**forumdisplay_thread**
```
{$tracker_forumdisplay}
```
**showthread**
```
{$tracker_thread}
```
**member_profile**
```
{$tracker_profile}
```
**editpost**
```
{$ip_infos}
```
**newthread**
```
{$ip_infos}
```
**usercp_options**
```
{$tracker_pn}
```

## Änderungen für Inplaykalender von Jule (sparksfly)

### inplaykalender.php
```
            // get inplay scenes
            if($db->field_exists("spieler", "threads")) {
                $ipdate =  date("Y-m-d", $date);
                $szenen = false;
                $query_scenes = $db->query("SELECT * FROM ".TABLE_PREFIX."threads WHERE date LIKE '%$ipdate%'");
                if(mysqli_num_rows($query_scenes) > 0) {
                    $threadlist = "";
                    while($szenenliste = $db->fetch_array($query_scenes)) {

                        $szenen = true;
                        $threadlist .= "&bull; <a href=\"showthread.php?tid={$szenenliste['tid']}\" target=\"_blank\">{$szenenliste['subject']}</a>";

                    }
                } else { $threadlist = ""; }
            }
```

### inc/plugins/inplaykalender.php
```
            // get inplay scenes
            $szenen = false;
            if($db->field_exists("spieler", "threads")) {
                $ipdate =  date("Y-m-d", $date);

                $query = $db->query("SELECT * FROM ".TABLE_PREFIX."threads WHERE date = '$ipdate'");

                if(mysqli_num_rows($query) > 0) {

                    $threadlist = "";
                    while($szenenliste = $db->fetch_array($query)) {
                        $szenen = true;
                        $threadlist .= "&bull; <a href=\"showthread.php?tid={$szenenliste['tid']}\" target=\"_blank\">{$szenenliste['subject']}</a>";

                    }
                } else { $threadlist = ""; }
            }
						```
