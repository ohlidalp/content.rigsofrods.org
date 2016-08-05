<?php
/**
	Rigs of Rods repository plugin
	Written by Petr "only_a_ptr" Ohlidal, 2016
	
	Licensed as GPLv3 
*/

// Inspired by http://community.mybb.com/mods.php?action=view&pid=90

// Make sure we can't access this file directly from the browser.
if(!defined('IN_MYBB'))
{
	die('This file cannot be accessed directly.');
}

if(defined('IN_ADMINCP'))
{
	// Add our ror_repo_settings() function to the setting management module to load language strings.
	$plugins->add_hook('admin_config_settings_manage', 'ror_repo_settings');
	$plugins->add_hook('admin_config_settings_change', 'ror_repo_settings');
	$plugins->add_hook('admin_config_settings_start' , 'ror_repo_settings');
	// We could hook at 'admin_config_settings_begin' only for simplicity sake.
}
else
{
    $plugins->add_hook("editpost_end", "ror_repo_editpost_end");
    $plugins->add_hook("editpost_do_editpost_end", "ror_repo_do_editpost_end");
}

define('REPO_TABLE_THREADS',   "ror_repo_threads");
define('REPO_TABLE_DOWNLOADS', "ror_repo_downloads");
define('REPO_TABLE_RATINGS',   "ror_repo_ratings");

// -----------------------------------------------------------------------------
// Basic plugin functions, see http://docs.mybb.com/1.8/development/plugins/
// -----------------------------------------------------------------------------

function ror_repo_info()
{
	global $lang;
	//$lang->load('ror_repo');//EXAMPLE CODE

	return array(
		'name'			=> 'Rigs of Rods content repository',
		'description'	=> $lang->ror_repo_desc,
		'website'		=> 'http://www.rigsofrods.org', // Optional
		'author'		=> 'Rigs of Rods community',
		'authorsite'	=> 'https://github.com/RigsOfRods', // Optional
		'version'		=> '0.1',
		'compatibility'	=> '18*', // A CSV list of MyBB versions supported. Ex, '121,123', '12*'. Wildcards supported.
		'codename'		=> 'ror_repo' // An unique code name to be used by updated from the official MyBB Mods community.
	);
}

/*
 * _install():
 *   Called whenever a plugin is installed by clicking the 'Install' button in the plugin manager.
 *   If no install routine exists, the install button is not shown and it assumed any work will be
 *   performed in the _activate() routine.
*/
function ror_repo_install()
{
	global $db;

	// create table if it doesn't exist already
	if(!$db->table_exists('ror_repo_downloads'))
	{
		$collation = $db->build_create_table_collation();

		$db->write_query("CREATE TABLE `".TABLE_PREFIX.REPO_TABLE_DOWNLOADS."` (
			`id` int UNSIGNED NOT NULL auto_increment,
			`aid` int UNSIGNED NULL default NULL,       # MyBB attachment ID (NULL means URL is used)
			`pid` int UNSIGNED NOT NULL,                # MyBB post ID
			`url` varchar(256) NULL default NULL,       # Download URL (only if `aid` isn't specified)
			`guid` BINARY(16) NULL UNIQUE,              # see http://stackoverflow.com/a/7277900
			`type` int UNSIGNED NOT NULL default 0,     # 0-unknown, 1-vehicle ZIP, 2-map ZIP, 3-pack ZIP, 4-skinzip
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM{$collation}");
        
        $db->write_query("CREATE TABLE `".TABLE_PREFIX.REPO_TABLE_THREADS."` (
            `id` int UNSIGNED NOT NULL auto_increment,
            `tid` int UNSIGNED NOT NULL,                # MyBB thread ID
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM{$collation}");
		
		$db->write_query("CREATE TABLE `".TABLE_PREFIX.REPO_TABLE_RATINGS."` (
			`download_id` int(10) UNSIGNED NOT NULL,       # Repo download ID
			`uid` int(10) UNSIGNED NOT NULL,               # MyBB user ID
			`rating` tinyint UNSIGNED NOT NULL default 0,  # The rating
			PRIMARY KEY (`download_id`, `uid`)
		) ENGINE=MyISAM{$collation}");
	}
}

/*
 * _activate():
 *    Called whenever a plugin is activated via the Admin CP. This should essentially make a plugin
 *    'visible' by adding templates/template changes, language changes etc.
*/
function ror_repo_activate()
{
    global $db;

	$insert_array = array(
		'title'		=> 'edit_post_publish_ror_repo',
		'template'	=> $db->escape_string('<tr>'
            .'<td class="trow2"><strong>Content repository:</strong></td>'
            .'<td class="trow2"><input type="checkbox" name="ror_repo_publish_thread" value="1" {$ror_repo_publish_checked}>Publish in content repository?</td>'
            .'</tr>'),
		'sid'		=> '-1',
		'version'	=> '',
		'dateline'	=> TIME_NOW
	);
	$db->insert_query("templates", $insert_array);
    
	// For find_replace_templatesets()
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// Edit the templates and insert our variables
	find_replace_templatesets("editpost", "#".preg_quote('{$posticons}')."#i", '{$ror_repo_publish_thread}{$posticons}');
}

/*
 * _deactivate():
 *    Called whenever a plugin is deactivated. This should essentially 'hide' the plugin from view
 *    by removing templates/template changes etc. It should not, however, remove any information
 *    such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is
 *    uninstalled, this routine will also be called before _uninstall() if the plugin is active.
*/
function ror_repo_deactivate()
{
	global $db;
	$db->delete_query("templates", "title IN('edit_post_publish_ror_repo')");
    
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// Remove template edits
	find_replace_templatesets("editpost", "#".preg_quote('{$ror_repo_publish_thread}')."#i", '', 0);
}

/*
 * _is_installed():
 *   Called on the plugin management page to establish if a plugin is already installed or not.
 *   This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE
 *   if the plugin is not installed.
*/
function ror_repo_is_installed()
{
	global $db;

	// If the table exists then it means the plugin is installed because we only drop it on uninstallation
	return (bool) $db->table_exists(REPO_TABLE_DOWNLOADS);
}

/*
 * _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, uninstall button is not shown.
*/
function ror_repo_uninstall()
{
	global $db, $mybb;

	// drop tables
    $tables = [REPO_TABLE_THREADS, REPO_TABLE_DOWNLOADS, REPO_TABLE_RATINGS];
    foreach ($tables as $table_name)
    { 
    	if ($db->table_exists($table_name))
    	{
    		$db->drop_table($table_name);
    	}
    }
}

/*
 * Loads the settings language strings.
*/
function ror_repo_settings()
{
	global $lang;
      // EXAMPLE CODE
	// Load our language file
	//$lang->load('ror_repo');
}

// -----------------------------------------------------------------------------
// Hook functions
// -----------------------------------------------------------------------------

function ror_repo_editpost_end() // Entering edit form
{    
    global $lang, $mybb, $thread, $templates, $post_errors;
    global $ror_repo_publish_thread;

	$pid = $mybb->get_input('pid', MyBB::INPUT_INT);
	if($thread['firstpost'] == $pid)
	{
        $ror_repo_publish_checked = ror_repo_is_thread_published($thread['tid']) ? "checked" : "";
		eval(" \$ror_repo_publish_thread = \"".$templates->get("edit_post_publish_ror_repo")."\"; ");
	}
}

// Update description
function ror_repo_do_editpost_end() // Edit form submitted
{
	global $db, $mybb, $tid;
    
    $make_public = (bool) $mybb->get_input('ror_repo_publish_thread') == '1';
    ror_repo_publish_thread($tid, $make_public);
}

// -----------------------------------------------------------------------------
// Helper functions
// -----------------------------------------------------------------------------

function ror_repo_publish_thread($tid, $do_publish)
{
    global $db;
    
    if ($do_publish && !ror_repo_is_thread_published($tid))
    {
        $db->insert_query(REPO_TABLE_THREADS, array("tid" => $tid));
    }
    else
    {
        $db->delete_query(REPO_TABLE_THREADS, "`tid` = {$tid}");
    }
}

function ror_repo_is_thread_published($tid)
{
    global $db;

    $sql_result = $db->simple_select(REPO_TABLE_THREADS, "id", "tid = ".$db->escape_string($tid));
    return (bool) ($db->num_rows($sql_result) > 0);    
}
