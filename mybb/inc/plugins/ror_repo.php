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
	$plugins->add_hook('admin_config_settings_start', 'ror_repo_settings');
	// We could hook at 'admin_config_settings_begin' only for simplicity sake.
}
else
{
	$plugins->add_hook('index_start', 'ror_repo_index');

	$plugins->add_hook('postbit', 'ror_repo_post'); // Execute on every post

	// EXAMPLE CODE
	// Add our ror_repo_new() function to the misc_start hook so our misc.php?action=hello inserts a new message into the created DB table.
	//$plugins->add_hook('misc_start', 'ror_repo_new');
}

function ror_repo_info()
{
	global $lang;
	$lang->load('ror_repo');

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
function hello_activate()
{
	global $db, $lang;
	$lang->load('ror_repo');

	

	// Include this file because it is where find_replace_templatesets is defined
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// Edit the index template and add our variable to above {$forums}
	find_replace_templatesets('index', '#'.preg_quote('{$forums}').'#', "{\$hello}\n{\$forums}");
}

/*
 * _deactivate():
 *    Called whenever a plugin is deactivated. This should essentially 'hide' the plugin from view
 *    by removing templates/template changes etc. It should not, however, remove any information
 *    such as tables, fields etc - that should be handled by an _uninstall routine. When a plugin is
 *    uninstalled, this routine will also be called before _uninstall() if the plugin is active.
*/
function hello_deactivate()
{
	require_once MYBB_ROOT.'inc/adminfunctions_templates.php';
	
	// remove template edits
	find_replace_templatesets('index', '#'.preg_quote('{$hello}').'#', '');
}

/*
 * _is_installed():
 *   Called on the plugin management page to establish if a plugin is already installed or not.
 *   This should return TRUE if the plugin is installed (by checking tables, fields etc) or FALSE
 *   if the plugin is not installed.
*/
function hello_is_installed()
{
	global $db;

	// If the table exists then it means the plugin is installed because we only drop it on uninstallation
	return $db->table_exists('hello_messages');
}

/*
 * _uninstall():
 *    Called whenever a plugin is to be uninstalled. This should remove ALL traces of the plugin
 *    from the installation (tables etc). If it does not exist, uninstall button is not shown.
*/
function hello_uninstall()
{
	global $db, $mybb;

	if($mybb->request_method != 'post')
	{
		global $page, $lang;
		$lang->load('hello');

		$page->output_confirm_action('index.php?module=config-plugins&action=deactivate&uninstall=1&plugin=hello', $lang->hello_uninstall_message, $lang->hello_uninstall);
	}

	// remove our templates group
	// Query the template groups
	$query = $db->simple_select('templategroups', 'prefix', "prefix='hello'");

	// Build where string for templates
	$sqlwhere = array();

	while($prefix = $db->fetch_field($query, 'prefix'))
	{
		$tprefix = $db->escape_string($prefix);
		$sqlwhere[] = "title='{$tprefix}' OR title LIKE '{$tprefix}=_%' ESCAPE '='";
	}

	if($sqlwhere) // else there are no groups to delete
	{
		// Delete template groups.
		$db->delete_query('templategroups', "prefix='hello'");

		// Delete templates belonging to template groups.
		$db->delete_query('templates', implode(' OR ', $sqlwhere));
	}

	// delete settings group
	$db->delete_query('settinggroups', "name='hello'");

	// remove settings
	$db->delete_query('settings', "name IN ('hello_display1','hello_display2')");

	// This is required so it updates the settings.php file as well and not only the database - they must be synchronized!
	rebuild_settings();

	// drop tables if desired
	if(!isset($mybb->input['no']))
	{
		$db->drop_table('hello_messages');
	}
}

/*
 * Loads the settings language strings.
*/
function hello_settings()
{
	global $lang;

	// Load our language file
	$lang->load('hello');
}

/*
 * Displays the list of messages on index and a form to submit new messages - depending on the setting of course.
*/
function hello_index()
{
	global $mybb;

	// Only run this function is the setting is set to yes
	if($mybb->settings['hello_display1'] == 0)
	{
		return;
	}

	global $db, $lang, $templates, $hello, $theme;

	// Load our language file
	$lang->load('hello');

	// Retreive all messages from the database
	$messages = '';
	$query = $db->simple_select('hello_messages', 'message', '', array('order_by' => 'mid', 'order_dir' => 'DESC'));
	while($message = $db->fetch_field($query, 'message'))
	{
		// htmlspecialchars_uni is similar to PHP's htmlspecialchars but allows unicode
		$message = htmlspecialchars_uni($message);
		$messages .= eval($templates->render('hello_message'));
	}

	// If no messages were found, display that notice.
	if(empty($messages))
	{
		$message = $lang->hello_empty;
		$messages = eval($templates->render('hello_message'));
	}

	// Set $hello as our template and use eval() to do it so we can have our variables parsed
	#eval('$hello = "'.$templates->get('hello_index').'";');
	$hello = eval($templates->render('hello_index'));
}

/*
 * Displays the list of messages under every post - depending on the setting.
 * @param $post Array containing information about the current post. Note: must be received by reference otherwise our changes are not preserved.
*/
function hello_post(&$post)
{
	global $settings;

	// Only run this function is the setting is set to yes
	if($settings['hello_display2'] == 0)
	{
		return;
	}

	global $lang, $templates;

	// Load our language file
	if(!isset($lang->hello))
	{
		$lang->load('hello');
	}

	static $messages;

	// Only retreive messages from the database if they were not retreived already
	if(!isset($messages))
	{
		global $db;

		// Retreive all messages from the database
		$messages = '';
		$query = $db->simple_select('hello_messages', 'message', '', array('order_by' => 'mid', 'order_dir' => 'DESC'));
		while($message = $db->fetch_field($query, 'message'))
		{
			// htmlspecialchars_uni is similar to PHP's htmlspecialchars but allows unicode
			$message = htmlspecialchars_uni($message);
			$messages .= eval($templates->render('hello_message'));
		}

		// If no messages were found, display that notice.
		if(empty($messages))
		{
			$message = $lang->hello_empty;
			$messages = eval($templates->render('hello_message'));
		}
	}

	// Alter the current post's message
	$post['message'] .= eval($templates->render('hello_post'));
}

/*
* This is where new messages get submitted.
*/
function hello_new()
{
	global $mybb;

	// If we're not running the 'hello' action as specified in our form, get out of there.
	if($mybb->get_input('action') != 'hello')
	{
		return;
	}

	// Only accept POST
	if($mybb->request_method != 'post')
	{
		error_no_permission();
	}

	global $lang;

	// Correct post key? This is important to prevent CSRF
	verify_post_check($mybb->get_input('my_post_key'));

	// Load our language file
	$lang->load('hello');

	$message = trim($mybb->get_input('message'));

	// Message cannot be empty
	if(!$message || my_strlen($message) > 100)
	{
		error($lang->hello_message_empty);
	}

	global $db;

	// Escape input data
	$message = $db->escape_string($message);

	// Insert into database
	$db->insert_query('hello_messages', array('message' => $message));

	// Redirect to index.php with a message
	redirect('index.php', $lang->hello_done);
}
