<?php
/**
 * featured.php - displays list of featured books and the featured story chosen
 *
 * This is two pages in one, will display a list of current featured books, and also display the featured article as well
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: featured.php,v 1.2 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

//lists all current featured articles
function list_featured()
{
	//get the session/etc setup stuff done
	$tpl =& get_tpl();
	$db = get_db();
	//page assignments
	$tpl['title'] = 'Featured Books';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'featured books picks fanfiction library';
	$tpl['description'] = 'Fanfiction Library Featured books';
	//assign sub "template"
	$files['page'] = 'featured.html';
	//current size(limit)
	$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$tpl['offset'] = $offset + 1;
	$featurelist = $db->query(
	'SELECT featured_id AS id, featured_title AS title, featured_summary AS summary, featured_date AS date, '
	.'user_id AS userid, user_name AS username, '
	.'book_id AS bookid, book_title AS booktitle, book_publish AS publish, book_update AS `update` , '
	.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
	.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
	.'FROM featured '
	.'LEFT JOIN book ON featured.book_id_fk=book.book_id '
	.'LEFT JOIN user ON featured.user_id_fk=user.user_id '
	.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
	.'GROUP BY book.book_id ORDER BY featured_date DESC LIMIT '.$offset.', '.$size);
	if(!$featurelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['featurelist'] =& $featurelist;
	//get total for paging
	$count = $db->query('SELECT COUNT(featured_id) FROM featured');
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

//grabs and prints a featured article
function view_featured()
{
	//get the session/etc setup stuff done
	$tpl =& get_tpl();
	$db = get_db();
	$feature = $db->query(
	'SELECT featured_title AS title, featured_summary AS summary, featured_text AS text, featured_date AS date, '
	.'user_id AS userid, user_name AS username, '
	.'book_id AS bookid, book_title AS booktitle, book_completed AS completed, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
	.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
	.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
	.'FROM featured '
	.'LEFT JOIN book ON featured.book_id_fk=book.book_id '
	.'LEFT JOIN user ON featured.user_id_fk=user.user_id '
	.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
	.'WHERE featured_id='.$_GET['id'].' GROUP BY book.book_id LIMIT 1');
	if(!$feature)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($feature->num_rows < 1)
	{
		header('Location: featured.php');
	}
	else
	{
		$tpl['featuredstory'] = $feature->fetch_assoc();
	}
	//page assignments
	$tpl['title'] = $tpl['featuredstory']['title'].' by '.$tpl['featuredstory']['username'];
	$tpl['nest'] = '';
	$tpl['keywords'] = 'featured books picks fanfiction library';
	$tpl['description'] = $tpl['featuredstory']['summary'];
	//assign sub "template"
	$files['page'] = 'viewfeatured.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('prepend.php');
$action = !isset($_GET['id']) ? 0 : 1;
switch($action)
{
	//if we have an id, we show a feature :)
	case 1:
		view_featured();
		break;
	//default is the featured list
	default:
		list_featured();
		break;
}
include('append.php');
?>
