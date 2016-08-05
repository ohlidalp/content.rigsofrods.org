<?php

require_once "global.include.php";

echo "<pre>
HELLO!

<a href='list-pages.php'>List all threads</a>

";

$query = $db->query("
	SELECT t.*
	FROM ".TABLE_PREFIX."ror_repo_threads rt 
        LEFT JOIN ".TABLE_PREFIX."threads t ON rt.tid = t.tid  
");

echo "
PUBLISHED THREADS ({$db->num_rows($query)})
----------------
";

while($t = $db->fetch_array($query))
{
    echo "[{$t['tid']}] <a href='show-page.php?tid={$t['tid']}'>{$t['subject']}</a>\n";
}

echo "</pre>";

