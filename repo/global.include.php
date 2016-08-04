<?php

//Define MyBB and includes
define("IN_MYBB", 1);

require_once "./config.include.php";

require_once $repo_config["mybb_root"] . "/global.php";
//require_once MYBB_ROOT."games/global.php";


function repo_html_template($title, $body_html, $header_html)
{
	global $repo_config;

	print('
	<!DOCTYPE HTML>
	<!--[if lt IE 7]> <html class="no-js ie6 oldie" lang="en"> <![endif]-->
	<!--[if IE 7]>    <html class="no-js ie7 oldie" lang="en"> <![endif]-->
	<!--[if IE 8]>    <html class="no-js ie8 oldie" lang="en"> <![endif]-->
	<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
		<title>'.$title.'</title>
		<meta name="viewport" content="width=device-width" />
		<meta name="author" content="Rigs of Rods Community" />
		<meta name="description" content="The content repository Rigs of Rods,
			a free and open source soft-body physics vehicle simulator." />
		<base href="'.$repo_config['base_url'].'">
		<link rel="stylesheet" href="resources/repo-main.css">
		<!-- Begin $header_html -->
		'.$header_html.'
		<!-- End $header_html -->
		<style>
			body { text-align: left; }
		</style>
	</head>
	<body>
		<div id="page-container">
			<header id="page-header">
				<div class="wrapper">
					<nav id="logo-link">
						<a href="/">
							<img src="resources/ror-logo.png" alt="Project logo">
						</a>
					</nav>
				</div>
			</header>
			<div id="docs-blackbar">
				<div class="wrapper">
					<a href="'.$repo_config['base_url'].'">Rigs of Rods Repository</a>
				</div>
			</div>
			<div id="main" role="main">
				<article id="docs-page">
					<header id="docs-header">
						<div class="wrapper docs-content">
							<h1>Rigs of Rods Content repository</h1>
						</div>
					</header>
					<div class="page wrapper docs-content">
						<!-- Begin $body_html -->
						'.$body_html.'
						<!-- End $body_html -->
					</div>
				</article>
			</div>
			<footer>
				<div class="wrapper">
					<small>Copyright &copy; 2016 Rigs of Rods Community</small>
				</div>
			</footer>
		</div>
		</body>
	</html>');

}