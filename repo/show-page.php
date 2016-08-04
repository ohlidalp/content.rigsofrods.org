<?php


require_once "global.include.php";

define('THIS_SCRIPT', 'showthread.php');

require_once MYBB_ROOT."inc/functions_post.php";
require_once MYBB_ROOT."inc/functions_indicators.php";
require_once MYBB_ROOT."inc/class_parser.php";

$parser = new postParser;

// Load global language phrases
$lang->load("showthread");



// Get the thread details from the database.
$thread = get_thread($mybb->get_input('tid', MyBB::INPUT_INT));

if(!$thread || substr($thread['closed'], 0, 6) == "moved|")
{
	error($lang->error_invalidthread);
}




$tid = $thread['tid'];
$fid = $thread['fid'];

if(!$thread['username'])
{
	$thread['username'] = $lang->guest;
}



// Make sure we are looking at a real thread here.
if(($thread['visible'] != 1 && $ismod == false) || ($thread['visible'] == 0 && !is_moderator($fid, "canviewunapprove")) || ($thread['visible'] == -1 && !is_moderator($fid, "canviewdeleted")))
{
	error($lang->error_invalidthread);
}

$forumpermissions = forum_permissions($thread['fid']);

// Does the user have permission to view this thread?
if($forumpermissions['canview'] != 1 || $forumpermissions['canviewthreads'] != 1)
{
	error_no_permission();
}

if(isset($forumpermissions['canonlyviewownthreads']) && $forumpermissions['canonlyviewownthreads'] == 1 && $thread['uid'] != $mybb->user['uid'])
{
	error_no_permission();
}



// Does the thread belong to a valid forum?
$forum = get_forum($fid);
if(!$forum || $forum['type'] != "f")
{
	error($lang->error_invalidforum);
}

$threadnoteslink = '';
if(is_moderator($fid, "canmanagethreads") && !empty($thread['notes']))
{
	eval('$threadnoteslink = "'.$templates->get('showthread_threadnoteslink').'";');
}

// Check if this forum is password protected and we have a valid password
check_forum_password($forum['fid']);

// If there is no specific action, we must be looking at the thread.
if(!$mybb->get_input('action'))
{
	$mybb->input['action'] = "thread";
}






$pid = $mybb->input['pid'] = $mybb->get_input('pid', MyBB::INPUT_INT);

// Forumdisplay cache
$forum_stats = $cache->read("forumsdisplay");





// Show the entire thread (taking into account pagination).
if($mybb->input['action'] == "thread")
{
	if($thread['firstpost'] == 0)
	{
		update_first_post($tid);
	}


	$pollbox = "";
	

	// Create the forum jump dropdown box.
	if($mybb->settings['enableforumjump'] != 0)
	{
		$forumjump = build_forum_jump("", $fid, 1);
	}

	// Fetch some links
	$next_oldest_link = get_thread_link($tid, 0, "nextoldest");
	$next_newest_link = get_thread_link($tid, 0, "nextnewest");

	// Mark this thread as read
	mark_thread_read($tid, $fid);

	// If the forum is not open, show closed newreply button unless the user is a moderator of this forum.
	$newthread = $newreply = '';
	if($forum['open'] != 0 && $forum['type'] == "f")
	{
		if($forumpermissions['canpostthreads'] != 0 && $mybb->user['suspendposting'] != 1)
		{
			eval("\$newthread = \"".$templates->get("showthread_newthread")."\";");
		}

		// Show the appropriate reply button if this thread is open or closed
		if($forumpermissions['canpostreplys'] != 0 && $mybb->user['suspendposting'] != 1 && ($thread['closed'] != 1 || is_moderator($fid, "canpostclosedthreads")) && ($thread['uid'] == $mybb->user['uid'] || $forumpermissions['canonlyreplyownthreads'] != 1))
		{
			eval("\$newreply = \"".$templates->get("showthread_newreply")."\";");
		}
		elseif($thread['closed'] == 1)
		{
			eval("\$newreply = \"".$templates->get("showthread_newreply_closed")."\";");
		}
	}

	
		$modoptions = "&nbsp;";
		$inlinemod = $closeoption = '';
	



	// Work out the thread rating for this thread.
	$rating = '';
	if($mybb->settings['allowthreadratings'] != 0 && $forum['allowtratings'] != 0)
	{
		$rated = 0;
		$lang->load("ratethread");
		if($thread['numratings'] <= 0)
		{
			$thread['width'] = 0;
			$thread['averagerating'] = 0;
			$thread['numratings'] = 0;
		}
		else
		{
			$thread['averagerating'] = (float)round($thread['totalratings']/$thread['numratings'], 2);
			$thread['width'] = (int)round($thread['averagerating'])*20;
			$thread['numratings'] = (int)$thread['numratings'];
		}

		if($thread['numratings'])
		{
			// At least >someone< has rated this thread, was it me?
			// Check if we have already voted on this thread - it won't show hover effect then.
			$query = $db->simple_select("threadratings", "uid", "tid='{$tid}' AND uid='{$mybb->user['uid']}'");
			$rated = $db->fetch_field($query, 'uid');
		}

		$not_rated = '';
		if(!$rated)
		{
			$not_rated = ' star_rating_notrated';
		}

		$ratingvotesav = $lang->sprintf($lang->rating_average, $thread['numratings'], $thread['averagerating']);
		eval("\$ratethread = \"".$templates->get("showthread_ratethread")."\";");
	}
	// Work out if we are showing unapproved posts as well (if the user is a moderator etc.)
	if($ismod && is_moderator($fid, "canviewdeleted") == true && is_moderator($fid, "canviewunapprove") == false)
	{
		$visible = "AND p.visible IN (-1,1)";
	}
	elseif($ismod && is_moderator($fid, "canviewdeleted") == false && is_moderator($fid, "canviewunapprove") == true)
	{
		$visible = "AND p.visible IN (0,1)";
	}
	elseif($ismod && is_moderator($fid, "canviewdeleted") == true && is_moderator($fid, "canviewunapprove") == true)
	{
		$visible = "AND p.visible IN (-1,0,1)";
	}
	else
	{
		$visible = "AND p.visible='1'";
	}

	// Can this user perform searches? If so, we can show them the "Search thread" form
	if($forumpermissions['cansearch'] != 0)
	{
		eval("\$search_thread = \"".$templates->get("showthread_search")."\";");
	}

	// Fetch the ignore list for the current user if they have one
	$ignored_users = array();
	if($mybb->user['uid'] > 0 && $mybb->user['ignorelist'] != "")
	{
		$ignore_list = explode(',', $mybb->user['ignorelist']);
		foreach($ignore_list as $uid)
		{
			$ignored_users[$uid] = 1;
		}
	}

	// Fetch profile fields to display on postbit
	$pfcache = $cache->read('profilefields');

	if(is_array($pfcache))
	{
		foreach($pfcache as $profilefield)
		{
			if($profilefield['postbit'] != 1)
			{
				continue;
			}

			$profile_fields[$profilefield['fid']] = $profilefield;
		}
	}

	// Which thread mode is our user using by default?
	if(!empty($mybb->user['threadmode']))
	{
		$defaultmode = $mybb->user['threadmode'];
	}
	else if($mybb->settings['threadusenetstyle'] == 1)
	{
		$defaultmode = 'threaded';
	}
	else
	{
		$defaultmode = 'linear';
	}

	// If mode is unset, set the default mode
	if(!isset($mybb->input['mode']))
	{
		$mybb->input['mode'] = $defaultmode;
	}

	// Threaded or linear display?

	$threadexbox = '';

	{
		$threadexbox = '';
		
		$mybb->settings['postsperpage'] = 1;

		// Figure out if we need to display multiple pages.
		$page = 1;
		$perpage = $mybb->settings['postsperpage'];
		if($mybb->get_input('page', MyBB::INPUT_INT) && $mybb->get_input('page') != "last")
		{
			$page = $mybb->get_input('page', MyBB::INPUT_INT);
		}

		if(!empty($mybb->input['pid']))
		{
			$post = get_post($mybb->input['pid']);
			if(empty($post) || ($post['visible'] == 0 && !is_moderator($post['fid'], 'canviewunapprove')) || ($post['visible'] == -1 && !is_moderator($post['fid'], 'canviewdeleted')))
			{
				$footer .= '<script type="text/javascript">$(document).ready(function() { $.jGrowl(\''.$lang->error_invalidpost.'\', {theme: \'jgrowl_error\'}); });</script>';
			}
			else
			{
				$query = $db->query("
					SELECT COUNT(p.dateline) AS count FROM ".TABLE_PREFIX."posts p
					WHERE p.tid = '{$tid}'
					AND p.dateline <= '{$post['dateline']}'
					{$visible}
				");
				$result = $db->fetch_field($query, "count");
				if(($result % $perpage) == 0)
				{
					$page = $result / $perpage;
				}
				else
				{
					$page = (int)($result / $perpage) + 1;
				}
			}
		}



		$postcount = (int)$thread['replies']+1;
		$pages = $postcount / $perpage;
		$pages = ceil($pages);

		if($mybb->get_input('page') == "last")
		{
			$page = $pages;
		}

		if($page > $pages || $page <= 0)
		{
			$page = 1;
		}

		if($page)
		{
			$start = ($page-1) * $perpage;
		}
		else
		{
			$start = 0;
			$page = 1;
		}
		$upper = $start+$perpage;

		// Work out if we have terms to highlight
        $highlight = "";
        $threadmode = "";
        if($mybb->seo_support == true)
        {
            if($mybb->get_input('highlight'))
            {
                $highlight = "?highlight=".urlencode($mybb->get_input('highlight'));
            }

			if($defaultmode != "linear")
			{
	            if($mybb->get_input('highlight'))
	            {
	                $threadmode = "&amp;mode=linear";
	            }
	            else
	            {
	                $threadmode = "?mode=linear";
	            }
			}
        }
        else
        {
			if(!empty($mybb->input['highlight']))
			{
				if(is_array($mybb->input['highlight']))
				{
					foreach($mybb->input['highlight'] as $highlight_word)
					{
						$highlight .= "&amp;highlight[]=".urlencode($highlight_word);
					}
				}
				else
				{
					$highlight = "&amp;highlight=".urlencode($mybb->get_input('highlight'));
				}
			}

            if($defaultmode != "linear")
            {
                $threadmode = "&amp;mode=linear";
            }
        }

        $multipage = multipage($postcount, $perpage, $page, str_replace("{tid}", $tid, THREAD_URL_PAGED.$highlight.$threadmode));

		// Lets get the pids of the posts on this page.
		$pids = "";
		$comma = '';
		$query = $db->simple_select("posts p", "p.pid", "p.tid='$tid' $visible", array('order_by' => 'p.dateline', 'limit_start' => $start, 'limit' => $perpage));
		while($getid = $db->fetch_array($query))
		{
			// Set the ID of the first post on page to $pid if it doesn't hold any value
			// to allow this value to be used for Thread Mode/Linear Mode links
			// and ensure the user lands on the correct page after changing view mode
			if(empty($pid))
			{
				$pid = $getid['pid'];
			}
			// Gather a comma separated list of post IDs
			$pids .= "$comma'{$getid['pid']}'";
			$comma = ",";
		}
		if($pids)
		{
			$pids = "pid IN($pids)";

			$attachcache = array();
			if($mybb->settings['enableattachments'] == 1 && $thread['attachmentcount'] > 0 || is_moderator($fid, 'caneditposts'))
			{
				// Now lets fetch all of the attachments for these posts.
				$query = $db->simple_select("attachments", "*", $pids);
				while($attachment = $db->fetch_array($query))
				{
					$attachcache[$attachment['pid']][$attachment['aid']] = $attachment;
				}
			}
		}
		else
		{
			// If there are no pid's the thread is probably awaiting approval.
			error($lang->error_invalidthread);
		}

		// Get the actual posts from the database here.
		$posts = '';
		$query = $db->query("
			SELECT u.*, u.username AS userusername, p.*, f.*, eu.username AS editusername
			FROM ".TABLE_PREFIX."posts p
			LEFT JOIN ".TABLE_PREFIX."users u ON (u.uid=p.uid)
			LEFT JOIN ".TABLE_PREFIX."userfields f ON (f.ufid=u.uid)
			LEFT JOIN ".TABLE_PREFIX."users eu ON (eu.uid=p.edituid)
			WHERE $pids
			ORDER BY p.dateline
		");
		while($post = $db->fetch_array($query))
		{
			if($thread['firstpost'] == $post['pid'] && $thread['visible'] == 0)
			{
				$post['visible'] = 0;
			}
			$posts .= repo_build_postbit($post);
			$post = '';
		}
		//ORIG$plugins->run_hooks("showthread_linear");
	}

	


	


	

	// ORIGeval("\$showthread = \"".$templates->get("showthread")."\";");
	
	$repo_showthread = '<table border="0" cellspacing="0" cellpadding="0" class="tborder tfixed clear">
		<tr>
			<td class="thead">
				<div class="float_right">
					<span class="smalltext"><strong><a href="javascript:;" id="thread_modes">{$lang->thread_modes}</a>{$threadnoteslink}</strong></span>
				</div>
				<div>
					<strong>--repo thread!--</strong>
				</div>
			</td>
		</tr>
<tr><td id="posts_container">
	<div id="posts">
		'.$posts.'
	</div>
</td></tr>
	
	</table>'  ;
	
	// ORIG      output_page($showthread);
	
	$repo_head_extra='
	<script type="text/javascript" src="http://localhost/mybb/jscripts/jquery.js?ver=1806"></script>
<script type="text/javascript" src="http://localhost/mybb/jscripts/jquery.plugins.min.js?ver=1806"></script>
<script type="text/javascript" src="http://localhost/mybb/jscripts/general.js?ver=1807"></script>

<link type="text/css" rel="stylesheet" href="http://localhost/mybb/cache/themes/theme1/global.css" />
<link type="text/css" rel="stylesheet" href="http://localhost/mybb/cache/themes/theme1/css3.css" />


<!-- jeditable (jquery) -->
<script type="text/javascript" src="http://localhost/mybb/jscripts/report.js?ver=1804"></script>
<script src="http://localhost/mybb/jscripts/jeditable/jeditable.min.js"></script>
<script type="text/javascript" src="http://localhost/mybb/jscripts/thread.js?ver=1804"></script>
	';
	
	//repo_html_template("test page", $showthread, $repo_head_extra);
	repo_html_template("test page", $repo_showthread, $repo_head_extra);
}

/**
 * Build a navigation tree for threaded display.
 *
 * @param int $replyto
 * @param int $indent
 * @return string
 */
function buildtree($replyto=0, $indent=0)
{
	global $tree, $mybb, $theme, $mybb, $pid, $tid, $templates, $parser, $lang;

	$indentsize = 13 * $indent;

	++$indent;
	$posts = '';
	if(is_array($tree[$replyto]))
	{
		foreach($tree[$replyto] as $key => $post)
		{
			$postdate = my_date('relative', $post['dateline']);
			$post['subject'] = htmlspecialchars_uni($parser->parse_badwords($post['subject']));

			if(!$post['subject'])
			{
				$post['subject'] = "[".$lang->no_subject."]";
			}

			$post['profilelink'] = build_profile_link($post['username'], $post['uid']);

			if($mybb->input['pid'] == $post['pid'])
			{
				eval("\$posts .= \"".$templates->get("showthread_threaded_bitactive")."\";");
			}
			else
			{
				eval("\$posts .= \"".$templates->get("showthread_threaded_bit")."\";");
			}

			if($tree[$post['pid']])
			{
				$posts .= buildtree($post['pid'], $indent);
			}
		}
		--$indent;
	}
	return $posts;
}

/**
 * Fetch the attachments for a specific post and parse inline [attachment=id] code.
 * Note: assumes you have $attachcache, an array of attachments set up.
 *
 * @param int $id The ID of the item.
 * @param array $post The post or item passed by reference.
 */
function repo_get_post_attachments($id, &$attachcache, &$post)
{
	global $mybb, $lang;

	$validationcount = 0;
	$tcount = 0;
	$post['attachmentlist'] = $post['thumblist'] = $post['imagelist'] = '';

	
	if(isset($attachcache[$id]) && is_array($attachcache[$id]))
	{ // This post has 1 or more attachments
		foreach($attachcache[$id] as $aid => $attachment)
		{
			if($attachment['visible'])
			{ // There is an attachment thats visible!
				$attachment['filename'] = htmlspecialchars_uni($attachment['filename']);
				$attachment['filesize'] = get_friendly_size($attachment['filesize']);
				$ext = get_extension($attachment['filename']);
				if($ext == "jpeg" || $ext == "gif" || $ext == "bmp" || $ext == "png" || $ext == "jpg")
				{
					$isimage = true;
				}
				else
				{
					$isimage = false;
				}
				$attachment['icon'] = get_attachment_icon($ext);
				$attachment['downloads'] = my_number_format($attachment['downloads']);

				if(!$attachment['dateuploaded'])
				{
					$attachment['dateuploaded'] = $attachment['dateline'];
				}
				$attachdate = my_date('relative', $attachment['dateuploaded']);
				// Support for [attachment=id] code
				if(stripos($post['message'], "[attachment=".$attachment['aid']."]") !== false)
				{
					// Show as thumbnail IF image is big && thumbnail exists && setting=='thumb'
					// Show as full size image IF setting=='fullsize' || (image is small && permissions allow)
					// Show as download for all other cases
					if($attachment['thumbnail'] != "SMALL" && $attachment['thumbnail'] != "" && $mybb->settings['attachthumbnails'] == "yes")
					{
						print("REPO: using modified {postbit_attachments_thumbnails_thumbnail}");
						//REM       eval("\$attbit = \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
						$attbit = "<a href=\"".MYBB_ROOT."attachment.php?aid={$attachment['aid']}\" target=\"_blank\">
						<img src=\"".MYBB_ROOT."attachment.php?thumbnail={$attachment['aid']}\" class=\"attachment\" alt=\"\" title=\"{$lang->postbit_attachment_filename} {$attachment['filename']}
						{$lang->postbit_attachment_size} {$attachment['filesize']}
						{$attachdate}\" /></a>&nbsp;&nbsp;&nbsp; ";
					}
					elseif((($attachment['thumbnail'] == "SMALL" && $forumpermissions['candlattachments'] == 1) || $mybb->settings['attachthumbnails'] == "no") && $isimage)
					{
						print("REPO: using ORIG {postbit_attachments_images_image}");
						eval("\$attbit = \"".$templates->get("postbit_attachments_images_image")."\";");
					}
					else
					{
						print("REPO: using ORIG {postbit_attachments_attachment}");
						eval("\$attbit = \"".$templates->get("postbit_attachments_attachment")."\";");
					}
					$post['message'] = preg_replace("#\[attachment=".$attachment['aid']."]#si", $attbit, $post['message']);
				}
				else
				{
					// Show as thumbnail IF image is big && thumbnail exists && setting=='thumb'
					// Show as full size image IF setting=='fullsize' || (image is small && permissions allow)
					// Show as download for all other cases
					if($attachment['thumbnail'] != "SMALL" && $attachment['thumbnail'] != "" && $mybb->settings['attachthumbnails'] == "yes")
					{
						eval("\$post['thumblist'] .= \"".$templates->get("postbit_attachments_thumbnails_thumbnail")."\";");
						if($tcount == 5)
						{
							$thumblist .= "<br />";
							$tcount = 0;
						}
						++$tcount;
					}
					elseif((($attachment['thumbnail'] == "SMALL" && $forumpermissions['candlattachments'] == 1) || $mybb->settings['attachthumbnails'] == "no") && $isimage)
					{
						eval("\$post['imagelist'] .= \"".$templates->get("postbit_attachments_images_image")."\";");
					}
					else
					{
						eval("\$post['attachmentlist'] .= \"".$templates->get("postbit_attachments_attachment")."\";");
					}
				}
			}
			else
			{
				$validationcount++;
			}
		}
		if($validationcount > 0 && is_moderator($post['fid'], "canviewunapprove"))
		{
			if($validationcount == 1)
			{
				$postbit_unapproved_attachments = $lang->postbit_unapproved_attachment;
			}
			else
			{
				$postbit_unapproved_attachments = $lang->sprintf($lang->postbit_unapproved_attachments, $validationcount);
			}
			eval("\$post['attachmentlist'] .= \"".$templates->get("postbit_attachments_attachment_unapproved")."\";");
		}
		if($post['thumblist'])
		{
			eval("\$post['attachedthumbs'] = \"".$templates->get("postbit_attachments_thumbnails")."\";");
		}
		else
		{
			$post['attachedthumbs'] = '';
		}
		if($post['imagelist'])
		{
			eval("\$post['attachedimages'] = \"".$templates->get("postbit_attachments_images")."\";");
		}
		else
		{
			$post['attachedimages'] = '';
		}
		if($post['attachmentlist'] || $post['thumblist'] || $post['imagelist'])
		{
			eval("\$post['attachments'] = \"".$templates->get("postbit_attachments")."\";");
		}
	}
}

/**
 * Build a post bit
 *
 * @param array $post The post data
 * @param int $post_type The type of post bit we're building (1 = preview, 2 = pm, 3 = announcement, else = post)
 * @return string The built post bit
 */
function repo_build_postbit($post, $post_type=0)
{
	global $db, $altbg, $theme, $mybb, $postcounter, $profile_fields;
	global $titlescache, $page, $templates, $forumpermissions, $attachcache;
	global $lang, $ismod, $inlinecookie, $inlinecount, $groupscache, $fid;
	global $plugins, $parser, $cache, $ignored_users, $hascustomtitle;

	$hascustomtitle = 0;

	// Set default values for any fields not provided here
	foreach(array('pid', 'aid', 'pmid', 'posturl', 'button_multiquote', 'subject_extra', 'attachments', 'button_rep', 'button_warn', 'button_purgespammer', 'button_pm', 'button_reply_pm', 'button_replyall_pm', 'button_forward_pm', 'button_delete_pm', 'replink', 'warninglevel') as $post_field)
	{
		if(empty($post[$post_field]))
		{
			$post[$post_field] = '';
		}
	}

	// Set up the message parser if it doesn't already exist.
	if(!$parser)
	{
		require_once MYBB_ROOT."inc/class_parser.php";
		$parser = new postParser;
	}

	if(!function_exists("purgespammer_show"))
	{
		require_once MYBB_ROOT."inc/functions_user.php";
	}

	$unapproved_shade = '';
	if(isset($post['visible']) && $post['visible'] == 0 && $post_type == 0)
	{
		$altbg = $unapproved_shade = 'unapproved_post';
	}
	elseif(isset($post['visible']) && $post['visible'] == -1 && $post_type == 0)
	{
		$altbg = $unapproved_shade = 'unapproved_post deleted_post';
	}
	elseif($altbg == 'trow1')
	{
		$altbg = 'trow2';
	}
	else
	{
		$altbg = 'trow1';
	}
	$post['fid'] = $fid;
	switch($post_type)
	{

		default: // Regular post
			global $forum, $thread, $tid;
			$oldforum = $forum;
			$id = (int)$post['pid'];
			$idtype = 'pid';
			$parser_options['allow_html'] = $forum['allowhtml'];
			$parser_options['allow_mycode'] = $forum['allowmycode'];
			$parser_options['allow_smilies'] = $forum['allowsmilies'];
			$parser_options['allow_imgcode'] = $forum['allowimgcode'];
			$parser_options['allow_videocode'] = $forum['allowvideocode'];
			$parser_options['filter_badwords'] = 1;

			if(!$post['username'])
			{
				$post['username'] = $lang->guest;
			}

			if($post['userusername'])
			{
				$parser_options['me_username'] = $post['userusername'];
			}
			else
			{
				$parser_options['me_username'] = $post['username'];
			}
			break;
	}

	if(!$postcounter)
	{ // Used to show the # of the post
		if($page > 1)
		{
			if(!$mybb->settings['postsperpage'] || (int)$mybb->settings['postsperpage'] < 1)
			{
				$mybb->settings['postsperpage'] = 20;
			}

			$postcounter = $mybb->settings['postsperpage']*($page-1);
		}
		else
		{
			$postcounter = 0;
		}
		$post_extra_style = "border-top-width: 0;";
	}
	elseif($mybb->input['mode'] == "threaded")
	{
		$post_extra_style = "border-top-width: 0;";
	}
	else
	{
		$post_extra_style = "margin-top: 5px;";
	}

	if(!$altbg)
	{ // Define the alternate background colour if this is the first post
		$altbg = "trow1";
	}
	$postcounter++;

	// Format the post date and time using my_date
	$post['postdate'] = my_date('relative', $post['dateline']);

	// Dont want any little 'nasties' in the subject
	$post['subject'] = $parser->parse_badwords($post['subject']);

	// Pm's have been htmlspecialchars_uni()'ed already.
	if($post_type != 2)
	{
		$post['subject'] = htmlspecialchars_uni($post['subject']);
	}

	if(empty($post['subject']))
	{
		$post['subject'] = '&nbsp;';
	}

	$post['author'] = $post['uid'];
	$post['subject_title'] = $post['subject'];

	// Get the usergroup
	if($post['userusername'])
	{
		if(!$post['displaygroup'])
		{
			$post['displaygroup'] = $post['usergroup'];
		}
		$usergroup = $groupscache[$post['displaygroup']];
	}
	else
	{
		$usergroup = $groupscache[1];
	}

	if(!is_array($titlescache))
	{
		$cached_titles = $cache->read("usertitles");
		if(!empty($cached_titles))
		{
			foreach($cached_titles as $usertitle)
			{
				$titlescache[$usertitle['posts']] = $usertitle;
			}
		}

		if(is_array($titlescache))
		{
			krsort($titlescache);
		}
		unset($usertitle, $cached_titles);
	}


	if($post['userusername'])
	{
		// This post was made by a registered user
		$post['username'] = $post['userusername'];
		$post['profilelink_plain'] = get_profile_link($post['uid']);
		$post['username_formatted'] = format_name($post['username'], $post['usergroup'], $post['displaygroup']);
		$post['profilelink'] = build_profile_link($post['username_formatted'], $post['uid']);

		if(trim($post['usertitle']) != "")
		{
			$hascustomtitle = 1;
		}

		if($usergroup['usertitle'] != "" && !$hascustomtitle)
		{
			$post['usertitle'] = $usergroup['usertitle'];
		}
		elseif(is_array($titlescache) && !$usergroup['usertitle'])
		{
			reset($titlescache);
			foreach($titlescache as $key => $titleinfo)
			{
				if($post['postnum'] >= $key)
				{
					if(!$hascustomtitle)
					{
						$post['usertitle'] = $titleinfo['title'];
					}
					$post['stars'] = $titleinfo['stars'];
					$post['starimage'] = $titleinfo['starimage'];
					break;
				}
			}
		}

		$post['usertitle'] = htmlspecialchars_uni($post['usertitle']);

		if($usergroup['stars'])
		{
			$post['stars'] = $usergroup['stars'];
		}

		if(empty($post['starimage']))
		{
			$post['starimage'] = $usergroup['starimage'];
		}

		if($post['starimage'] && $post['stars'])
		{
			// Only display stars if we have an image to use...
			$post['starimage'] = str_replace("{theme}", $theme['imgdir'], $post['starimage']);

			$post['userstars'] = '';
			for($i = 0; $i < $post['stars']; ++$i)
			{
				eval("\$post['userstars'] .= \"".$templates->get("postbit_userstar", 1, 0)."\";");
			}

			$post['userstars'] .= "<br />";
		}

		$postnum = $post['postnum'];
		$post['postnum'] = my_number_format($post['postnum']);
		$post['threadnum'] = my_number_format($post['threadnum']);



		$post['useravatar'] = '';
		if(isset($mybb->user['showavatars']) && $mybb->user['showavatars'] != 0 || $mybb->user['uid'] == 0)
		{
			$useravatar = format_avatar($post['avatar'], $post['avatardimensions'], $mybb->settings['postmaxavatarsize']);
			eval("\$post['useravatar'] = \"".$templates->get("postbit_avatar")."\";");
		}

		$post['button_find'] = '';


		if($mybb->settings['enablepms'] == 1 && $post['receivepms'] != 0 && $mybb->usergroup['cansendpms'] == 1 && my_strpos(",".$post['ignorelist'].",", ",".$mybb->user['uid'].",") === false)
		{
			eval("\$post['button_pm'] = \"".$templates->get("postbit_pm")."\";");
		}

		$post['button_rep'] = '';
		if($post_type != 3 && $mybb->settings['enablereputation'] == 1 && $mybb->settings['postrep'] == 1 && $mybb->usergroup['cangivereputations'] == 1 && $usergroup['usereputationsystem'] == 1 && ($mybb->settings['posrep'] || $mybb->settings['neurep'] || $mybb->settings['negrep']) && $post['uid'] != $mybb->user['uid'] && (!isset($post['visible']) || $post['visible'] == 1) && (!isset($thread['visible']) || $thread['visible'] == 1))
		{
			if(!$post['pid'])
			{
				$post['pid'] = 0;
			}

			eval("\$post['button_rep'] = \"".$templates->get("postbit_rep_button")."\";");
		}





		

	}
	

	$post['button_edit'] = '';
	$post['button_quickdelete'] = '';
	$post['button_quickrestore'] = '';
	$post['button_quote'] = '';
	$post['button_quickquote'] = '';
	$post['button_report'] = '';
	$post['button_reply_pm'] = '';
	$post['button_replyall_pm'] = '';
	$post['button_forward_pm']  = '';
	$post['button_delete_pm'] = '';



	$post['editedmsg'] = '';
	if(!$post_type)
	{
		if(!isset($forumpermissions))
		{
			$forumpermissions = forum_permissions($fid);
		}
		




		// Quick Delete button
		$can_delete_thread = $can_delete_post = 0;
		if($mybb->user['uid'] == $post['uid'] && $thread['closed'] == 0)
		{
			if($forumpermissions['candeletethreads'] == 1 && $postcounter == 1)
			{
				$can_delete_thread = 1;
			}
			else if($forumpermissions['candeleteposts'] == 1 && $postcounter != 1)
			{
				$can_delete_post = 1;
			}
		}

		$postbit_qdelete = $postbit_qrestore = '';
		if($mybb->user['uid'] != 0)
		{
			if((is_moderator($fid, "candeleteposts") || is_moderator($fid, "cansoftdeleteposts") || $can_delete_post == 1) && $postcounter != 1)
			{
				$postbit_qdelete = $lang->postbit_qdelete_post;
				$display = '';
				if($post['visible'] == -1)
				{
					$display = "none";
				}
				eval("\$post['button_quickdelete'] = \"".$templates->get("postbit_quickdelete")."\";");
			}
			else if((is_moderator($fid, "candeletethreads") || is_moderator($fid, "cansoftdeletethreads") || $can_delete_thread == 1) && $postcounter == 1)
			{
				$postbit_qdelete = $lang->postbit_qdelete_thread;
				$display = '';
				if($post['visible'] == -1)
				{
					$display = "none";
				}
				eval("\$post['button_quickdelete'] = \"".$templates->get("postbit_quickdelete")."\";");
			}

			// Restore Post
			if(is_moderator($fid, "canrestoreposts") && $postcounter != 1)
			{
				$display = "none";
				if($post['visible'] == -1)
				{
					$display = '';
				}
				$postbit_qrestore = $lang->postbit_qrestore_post;
				eval("\$post['button_quickrestore'] = \"".$templates->get("postbit_quickrestore")."\";");
			}

			// Restore Thread
			else if(is_moderator($fid, "canrestorethreads") && $postcounter == 1)
			{
				$display = "none";
				if($post['visible'] == -1)
				{
					$display = "";
				}
				$postbit_qrestore = $lang->postbit_qrestore_thread;
				eval("\$post['button_quickrestore'] = \"".$templates->get("postbit_quickrestore")."\";");
			}
		}

		if(!isset($ismod))
		{
			$ismod = is_moderator($fid);
		}

		// Inline moderation stuff
		if($ismod)
		{
			if(isset($mybb->cookies[$inlinecookie]) && my_strpos($mybb->cookies[$inlinecookie], "|".$post['pid']."|"))
			{
				$inlinecheck = "checked=\"checked\"";
				$inlinecount++;
			}
			else
			{
				$inlinecheck = "";
			}

			eval("\$post['inlinecheck'] = \"".$templates->get("postbit_inlinecheck")."\";");

			if($post['visible'] == 0)
			{
				$invisiblepost = 1;
			}
		}
		else
		{
			$post['inlinecheck'] = "";
		}
		$post['postlink'] = get_post_link($post['pid'], $post['tid']);
		$post_number = my_number_format($postcounter);
		eval("\$post['posturl'] = \"".$templates->get("postbit_posturl")."\";");
		global $forum, $thread;

		if($forum['open'] != 0 && ($thread['closed'] != 1 || is_moderator($forum['fid'], "canpostclosedthreads")) && ($thread['uid'] == $mybb->user['uid'] || $forumpermissions['canonlyreplyownthreads'] != 1))
		{
			eval("\$post['button_quote'] = \"".$templates->get("postbit_quote")."\";");
		}

		if($forumpermissions['canpostreplys'] != 0 && ($thread['uid'] == $mybb->user['uid'] || $forumpermissions['canonlyreplyownthreads'] != 1) && ($thread['closed'] != 1 || is_moderator($fid, "canpostclosedthreads")) && $mybb->settings['multiquote'] != 0 && $forum['open'] != 0 && !$post_type)
		{
			eval("\$post['button_multiquote'] = \"".$templates->get("postbit_multiquote")."\";");
		}

		if($mybb->user['uid'] != "0")
		{
			eval("\$post['button_report'] = \"".$templates->get("postbit_report")."\";");
		}
	}
	elseif($post_type == 3) // announcement
	{
		if($mybb->usergroup['canmodcp'] == 1 && $mybb->usergroup['canmanageannounce'] == 1 && is_moderator($fid, "canmanageannouncements"))
		{
			eval("\$post['button_edit'] = \"".$templates->get("announcement_edit")."\";");
			eval("\$post['button_quickdelete'] = \"".$templates->get("announcement_quickdelete")."\";");
		}
	}

	$post['iplogged'] = '';
	$show_ips = $mybb->settings['logip'];
	$ipaddress = my_inet_ntop($db->unescape_binary($post['ipaddress']));



	if(isset($post['smilieoff']) && $post['smilieoff'] == 1)
	{
		$parser_options['allow_smilies'] = 0;
	}

	if($mybb->user['showimages'] != 1 && $mybb->user['uid'] != 0 || $mybb->settings['guestimages'] != 1 && $mybb->user['uid'] == 0)
	{
		$parser_options['allow_imgcode'] = 0;
	}

	if($mybb->user['showvideos'] != 1 && $mybb->user['uid'] != 0 || $mybb->settings['guestvideos'] != 1 && $mybb->user['uid'] == 0)
	{
		$parser_options['allow_videocode'] = 0;
	}

	// If we have incoming search terms to highlight - get it done.
	if(!empty($mybb->input['highlight']))
	{
		$parser_options['highlight'] = $mybb->input['highlight'];
		$post['subject'] = $parser->highlight_message($post['subject'], $parser_options['highlight']);
	}

	$post['message'] = $parser->parse_message($post['message'], $parser_options);

	$post['attachments'] = '';
	if($mybb->settings['enableattachments'] != 0)
	{
		repo_get_post_attachments($id, $post);
	}





	$post['icon'] = "";
	$post['signature'] = "";



	if($mybb->settings['postlayout'] == "classic")
	{
		eval("\$postbit = \"".$templates->get("postbit_classic")."\";");
	}
	else
	{
		eval("\$postbit = \"".$templates->get("postbit")."\";");
	}
	$GLOBALS['post'] = "";

	return $postbit;
}

