<?php

require_once "global.include.php";

$query = $db->query("
	SELECT t.*
	FROM ".TABLE_PREFIX."ror_repo_threads rt 
        LEFT JOIN ".TABLE_PREFIX."threads t ON rt.tid = t.tid  
");

$html = "<h1>All published content  ({$db->num_rows($query)})</h1>
    <a href='list-pages.php' style='padding-bottom: 10px; display: inline-block;'>List all threads</a>";

while($t = $db->fetch_array($query))
{
    $page_url = "show-page.php?tid={$t['tid']}";
    
    $html .='
        <div style="border: 2px solid lightgray; margin-bottom: 5px;">
            <a href='.$page_url.'><h2 style="background-color: #EE7700; padding: 0 10px;">'.$t['subject'].'</h2></a>
            <p style="padding-left: 10px; padding-right: 10px;">Lorem ipsum rigs of rods</p>
            <p style="padding-left: 10px; padding-right: 10px;">Downloads: TODO</p>
        </div>';
    
}

$ht_title = "{$t['subject']} - {$repo_config['html_basic_title']}";
repo_html_template($ht_title, $t['subject'], $html);

