<?php

require_once "global.include.php";

$fid = 4; // Showrooms

// Start Getting Threads (copied from 'forumdisplay.php')
$tids = $threadcache = array();
$query = $db->query("
	SELECT t.*
	FROM ".TABLE_PREFIX."threads t
	WHERE t.fid='$fid' 

");

while($thread = $db->fetch_array($query))
{
    $threadcache[$thread['tid']] = $thread;
}

echo "<pre>
SHOWROOM THREADS
----------------
";
foreach ($threadcache as $t)
{
    echo "[{$t['tid']}] <a href='show-page.php?tid={$t['tid']}'>{$t['subject']}</a>\n";
}
echo "\n\n\nDEBUG OUTPUT:\n";
var_dump($threadcache);
echo "</pre>";