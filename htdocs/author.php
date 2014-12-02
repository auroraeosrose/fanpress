<?php
/**
 * author.php - used for author profile functions
 *
 * displays a basic author profile, a list of an author's books, the story the author used when applying to the library, and allows the author to be added to favorites
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: author.php,v 1.2 2004/07/28 20:37:48 liz Exp $
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
	$profile = $db->query(
	'SELECT author_id AS id, author_name AS name, author_contact AS email, author_date AS date, '
		.'author_text AS text, user_name AS user, user_id AS userid '
		.'FROM author LEFT JOIN user ON user_id=user_id_fk WHERE author_id='.$_GET['id'].' AND author_valid=1 LIMIT 1');
	if(!$profile )
	{
		printf('Errormessage: %s', $db->error);
	}
	if($profile ->num_rows < 1)
	{
		header('Location: index.php');
	}
	else
	{
		$tpl['profile'] = $profile ->fetch_assoc();
	}
	$profile ->close();
	//page assignments
	$tpl['title'] = $tpl['profile']['name'].'\'s Profile';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'author profile information contact links';
	$tpl['description'] = 'Profile information for an author';
	//assign sub "template"
	$files['page'] = 'author.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

// shows a profile
function show_books()
{
	$tpl =& get_tpl();
	$db = get_db();
	$profile = $db->query(
	'SELECT author_id AS id, author_name AS name, author_contact AS email, author_date AS date, '
		.'user_name AS user, user_id AS userid '
		.'FROM author LEFT JOIN user ON user_id=user_id_fk WHERE author_id='.$_GET['id'].' AND author_valid=1 LIMIT 1');
	if(!$profile )
	{
		printf('Errormessage: %s', $db->error);
	}
	if($profile ->num_rows < 1)
	{
		header('Location: index.php');
	}
	else
	{
		$tpl['profile'] = $profile ->fetch_assoc();
	}
	$profile ->close();
	//first we have to make a list of books to get, ugh
	$bookids = array();
	$list = $db->query('SELECT DISTINCT book_id_fk FROM booktoauthor WHERE author_id_fk='.$_GET['id']);
	while($tmp = $list->fetch_row())
	{
		$bookids[] = $tmp[0];
	}
	if(empty($bookids))
	{
		$tpl['books'] = FALSE;
	}
	else
	{
		$where = '(book_id='.implode(' OR book_id=', $bookids).')';
		$list->close();
		//now we grab major bookage
		$books = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
			.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
			.'book_wordcount AS wordcount, book_chapters AS chapters, book_ranking AS ranking, '
			.'rating_id AS ratingid, rating_name AS rating, '
			.'style_id AS styleid, style_name AS style, '
			.'type_id AS typeid, type_name AS type, '
			.'category_name AS catname, category_id AS catid, '
			.'group_concat(DISTINCT genre_name ORDER BY genre_id ASC SEPARATOR \':\') AS genre, '
			.'group_concat(DISTINCT genre_id ORDER BY genre_id ASC SEPARATOR \':\') AS genreid, '
			.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
			.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
			.'group_concat(DISTINCT warning_name ORDER BY warning_id ASC SEPARATOR \':\') AS warning, '
			.'group_concat(DISTINCT warning_id ORDER BY warning_id ASC SEPARATOR \':\') as warningid, '
			.'group_concat(DISTINCT character_name ORDER BY character_id ASC SEPARATOR \':\') AS `character`, '
			.'group_concat(DISTINCT character_id ORDER BY character_id ASC SEPARATOR \':\') as characterid '
			.'FROM book '
			.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
			.'LEFT JOIN style ON style.style_id=book.style_id_fk '
			.'LEFT JOIN type ON type.type_id=book.type_id_fk '
			.'LEFT JOIN category ON category.category_id=book.category_id_fk '
			.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
			.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
			.'WHERE book_valid=1 AND '.$where.' GROUP BY book.book_id ORDER BY book_update');
		if(!$books)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['books'] =& $books;
	}
	//page assignments
	$tpl['title'] = $tpl['profile']['name'].'\'s Profile';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'author profile information contact links';
	$tpl['description'] = 'Profile information for an author';
	//assign sub "template"
	$files['page'] = 'authorbook.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

// shows a profile
function show_story()
{
	$tpl =& get_tpl();
	$db = get_db();
	$profile = $db->query(
	'SELECT author_id AS id, author_name AS name, author_contact AS email, author_date AS date, '
		.'author_file AS file, user_name AS user, user_id AS userid '
		.'FROM author LEFT JOIN user ON user_id=user_id_fk WHERE author_id='.$_GET['id'].' AND author_valid=1 LIMIT 1');
	if(!$profile )
	{
		printf('Errormessage: %s', $db->error);
	}
	if($profile ->num_rows < 1)
	{
		header('Location: index.php');
	}
	else
	{
		$tpl['profile'] = $profile ->fetch_assoc();
	}
	$profile ->close();
	//page assignments
	$tpl['title'] = $tpl['profile']['name'].'\'s Profile';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'author profile information contact links';
	$tpl['description'] = 'Profile information for an author';
	//assign sub "template"
	$files['page'] = 'authorstory.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function fav_author()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're not logged in, shove it
	$userid = $session->get('user', 'user');
	if(empty($userid))
	header('Location: user/login.php');
	//grab all book info we need
	$profile = $db->query(
	'SELECT author_id AS id, author_name AS name, author_contact AS email, author_date AS date, '
		.'author_file AS file, user_name AS user, user_id AS userid '
		.'FROM author LEFT JOIN user ON user_id=user_id_fk WHERE author_id='.$_REQUEST['id'].' AND author_valid=1 LIMIT 1');
	if(!$profile )
	{
		printf('Errormessage: %s', $db->error);
	}
	if($profile ->num_rows < 1)
	{
		header('Location: index.php');
	}
	else
	{
		$tpl['profile'] = $profile ->fetch_assoc();
	}
	$profile ->close();
	//now we check for a book
	if(isset($_POST['submit']))
	{
		$result = $db->query('SELECT COUNT(usertoauthor_id) FROM usertoauthor WHERE user_id_fk='.$userid.' AND author_id_fk='.$_POST['id']);
		$row = $result->fetch_row();
		$result->close();
		if($row[0] < 1)
		{
			$result = $db->query('INSERT INTO usertoauthor(author_id_fk, user_id_fk, usertoauthor_comment) VALUES('.$_POST['id'].', '.$userid.', \''.$db->real_escape_string(htmlentities(strip_tags($_POST['text']))).'\')');
		}
		else
		{
			$tpl['error'] = 'This author is already a favorite, use the favorites manager in your profile.';
		}
	}
	//page assignments
	$tpl['title'] = $tpl['profile']['name'].'\'s Profile';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'author profile information contact links';
	$tpl['description'] = 'Profile information for an author';
	//assign sub "template"
	$files['page'] = 'authorfavs.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('prepend.php');
$action = !isset($_REQUEST['f']) ? 0 : $_REQUEST['f'];
switch($action)
{
	//default is the featured list
	case 3:
		fav_author();
		break;
	//default is the featured list
	case 2:
		show_story();
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
