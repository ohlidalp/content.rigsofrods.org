<?php
/**
	Rigs of Rods repository plugin
	Written by Petr "only_a_ptr" Ohlidal, 2016
	
	Licensed as GPLv3 
*/

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

}

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

		$db->write_query("CREATE TABLE `".TABLE_PREFIX."ror_repo_downloads` (
			`id` int UNSIGNED NOT NULL auto_increment,
			`aid` int UNSIGNED NULL default NULL,       # MyBB attachment ID (NULL means URL is used)
			`pid` int UNSIGNED NOT NULL,                # MyBB post ID
			`url` varchar(256) NULL default NULL,       # Download URL (only if `aid` isn't specified)
			`guid` BINARY(16) NULL UNIQUE,              # see http://stackoverflow.com/a/7277900
			`type` int UNSIGNED NOT NULL default 0,     # 0-unknown, 1-vehicle ZIP, 2-map ZIP, 3-pack ZIP, 4-skinzip
			PRIMARY KEY (`id`)
		) ENGINE=MyISAM{$collation}");
		
		$db->write_query("CREATE TABLE `".TABLE_PREFIX."ror_repo_ratings` (
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
	
    
    // EXAMPLE CODE
    
    //global $db, $lang;
    //$lang->load('ror_repo');
    
	// Include this file because it is where find_replace_templatesets is defined
	//require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// Edit the index template and add our variable to above {$forums}
	//find_replace_templatesets('index', '#'.preg_quote('{$forums}').'#', "{\$hello}\n{\$forums}");
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

        // EXAMPLE CODE
	//require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// remove template edits
	//find_replace_templatesets('index', '#'.preg_quote('{$hello}').'#', '');
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
	return $db->table_exists('ror_repo_downloads');
}

/*
 * _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, uninstall button is not shown.
*/
function ror_repo_uninstall()
{
	global $db, $mybb;

	// drop tables if desired
	if($db->table_exists('ror_repo_downloads'))
	{
		$db->drop_table('ror_repo_downloads');
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

