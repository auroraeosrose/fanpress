<?php
/**
 * user.php - used for users profile functions
 *
 * displays a users profile, a list of user book or author favorites
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: user.php,v 1.2 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

// shows a profile
function show_profile()
{
	$tpl =& get_tpl();
	$db = get_db();
	$user = $db->query(
	'SELECT user_id AS id, user_name AS name, user_email AS email, user_date AS date, user_website AS website, '
		.'user_aim AS aim, user_msn AS msnm, user_yim AS yim, user_icq AS icq, user_gender AS gender, '
		.'user_birthday AS birthday, user_biography AS biography, user_level AS level, '
		.'author_name AS author, author_id AS authorid '
		.'FROM user LEFT JOIN author ON user_id=user_id_fk WHERE user_id='.$_GET['id'].' AND user_valid=1 LIMIT 1');
	if(!$user)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($user->num_rows < 1)
	{
		header('Location: index.php');
	}
	else
	{
		$tpl['userinfo'] = $user->fetch_assoc();
	}
	$user->close();
	//page assignments
	$tpl['title'] = $tpl['userinfo']['name'].'\'s Profile';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'user profile information contact links';
	$tpl['description'] = 'Profile information for a user';
	//assign sub "template"
	$files['page'] = 'user.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

// shows favorite books and comments
function show_books()
{
	$tpl =& get_tpl();
	$db = get_db();
	$user = $db->query(
	'SELECT user_id AS id, user_name AS name, user_email AS email, user_website AS website, '
		.'author_name AS author, author_id AS authorid '
		.'FROM user LEFT JOIN author ON user_id=user_id_fk WHERE user_id='.$_GET['id'].' AND user_valid=1 LIMIT 1');
	if(!$user)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($user->num_rows < 1)
	{
		header('Location: index.php');
	}
	else
	{
		$tpl['userinfo'] = $user->fetch_assoc();
	}
	$user->close();
	//now we grab major bookage
	$books = $db->query('SELECT usertobook_comment AS bookcomments, book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
		.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
		.'book_wordcount AS wordcount, book_chapters AS chapters, book_ranking AS ranking, '
		.'rating_id AS ratingid, rating_name AS rating, '
		.'type_id AS typeid, type_name AS type, '
		.'style_id AS styleid, style_name AS style, '
		.'category_name AS catname, category_id AS catid, '
		.'group_concat(DISTINCT genre_name ORDER BY genre_id ASC SEPARATOR \':\') AS genre, '
		.'group_concat(DISTINCT genre_id ORDER BY genre_id ASC SEPARATOR \':\') AS genreid, '
		.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
		.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
		.'group_concat(DISTINCT warning_name ORDER BY warning_id ASC SEPARATOR \':\') AS warning, '
		.'group_concat(DISTINCT warning_id ORDER BY warning_id ASC SEPARATOR \':\') as warningid, '
		.'group_concat(DISTINCT character_name ORDER BY character_id ASC SEPARATOR \':\') AS `character`, '
		.'group_concat(DISTINCT character_id ORDER BY character_id ASC SEPARATOR \':\') as characterid '
		.'FROM usertobook '
		.'LEFT JOIN book ON usertobook.book_id_fk=book.book_id '
		.'LEFT JOIN type ON type.type_id=book.type_id_fk '
		.'LEFT JOIN style ON style.style_id=book.style_id_fk '
		.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
		.'LEFT JOIN category ON category.category_id=book.category_id_fk '
		.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
		.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
		.'WHERE book_valid=1 AND usertobook.user_id_fk='.$_GET['id'].' GROUP BY book.book_id ORDER BY book_update');
	if(!$books)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['books'] =& $books;
	//page assignments
	$tpl['title'] = $tpl['userinfo']['name'].'\'s Profile';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'user profile information contact links book favorites';
	$tpl['description'] = 'Profile information for a user, show user favorite books';
	//assign sub "template"
	$files['page'] = 'userbook.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

// shows favorite authors and comments
function show_authors()
{
	$tpl =& get_tpl();
	$db = get_db();
	$user = $db->query(
	'SELECT user_id AS id, user_name AS name, user_email AS email, user_website AS website, '
		.'author_name AS author, author_id AS authorid '
		.'FROM user LEFT JOIN author ON user_id=user_id_fk WHERE user_id='.$_GET['id'].' AND user_valid=1 LIMIT 1');
	if(!$user)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($user->num_rows < 1)
	{
		header('Location: index.php');
	}
	else
	{
		$tpl['userinfo'] = $user->fetch_assoc();
	}
	$user->close();
	//now we grab authorage
	$authors = $db->query('SELECT usertoauthor_comment AS comment, author_id AS id, author_name AS name, author_count AS books, author_date AS date '
		.'FROM author LEFT JOIN usertoauthor ON author_id_fk=author_id WHERE usertoauthor.user_id_fk='.$_GET['id'].' and author_valid=1 ORDER BY author_name ASC');
	if(!$authors )
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['authors'] =& $authors;
	//page assignments
	$tpl['title'] = $tpl['userinfo']['name'].'\'s Profile';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'user profile information contact links book favorites';
	$tpl['description'] = 'Profile information for a user, show user favorite authors';
	//assign sub "template"
	$files['page'] = 'userauthor.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('prepend.php');
$action = !isset($_GET['f']) ? 0 : $_GET['f'];
switch($action)
{
	//default is the featured list
	case 2:
		show_authors();
		break;
	//default is the featured list
	case 1:
		show_books();
		break;
	//default is the featured list
	default:
		show_profile();
		break;
}
include('append.php');
?>
