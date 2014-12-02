<?php
/**
 * index.php - Main page of site, displays current announcements
 *
 * Displays paged list of announcements sorted by date
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: index.php,v 1.2 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

//announcements function, creates a page of recent announcements
function announcements()
{
	$tpl =& get_tpl();
	$db = get_db();
	//page assignments
	$tpl['title'] = 'Announcements';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'announcements news fanfiction library';
	$tpl['description'] = 'Fanfiction Library Announcements';
	//assign sub "template"
	$files['page'] = 'announce.html';
	//current size(limit)
	$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$announcements = $db->query('SELECT announcement_title AS title, announcement_text AS text, announcement_date AS date FROM announcement ORDER BY announcement_date DESC, announcement_id ASC LIMIT '.$offset.', '.$size);
	if(!$announcements)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['announcements'] =& $announcements;
	//get total for paging
	$count = $db->query('SELECT COUNT(announcement_id) FROM announcement');
	if(!$count)
	{
		printf('Errormessage: %s', $db->error);
	}
	$total = $count->fetch_row();
	$tpl['total'] = $total[0];
	$count->close();
	//do paging
	if($tpl['total'] > $size)
	{
		//do we need a previous link?
		if($offset > 0)
		{
			$tpl['prev'] = TRUE;
		}
		else
		{
			$tpl['prev'] = FALSE;
		}
		//pages
		$page_total = ceil($tpl['total'] /$size);
		//do we need a next link?
		if($page < $page_total)
		{
			$tpl['next'] = TRUE;
		}
		else
		{
			$tpl['next'] = FALSE;
		}
		$tpl['pages'] = array();
		//page array
		for($i = 1; $i <= $page_total; $i++)
		{
			$tpl['pages'][$i] = $i;
		}
	}
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('prepend.php');
$action = !isset($_GET['a']) ? 0 : (int) $_GET['a'];
switch($action)
{
	//default is the news page
	default:
		announcements();
		break;
}
include('append.php');
?>
