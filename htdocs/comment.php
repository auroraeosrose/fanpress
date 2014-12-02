<?php
/**
 * comment.php - takes care of book comment features
 *
 * allows users to add a book to favorites, read current public book comments, and add an additional comment
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: comment.php,v 1.2 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

//lists comments from a story
function list_comments()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	$user = $session->get('user', 'user');
	//if we're not logged in, shove it
	if(empty($user))
	header('Location: user/login.php');
	//grab all book info we need
	$book = $db->query('SELECT book_id AS bookid, book_summary AS summary, book_title AS title, book_comments AS comments, book_publish AS publish, book_update AS `update` , '
		.'book_ranking AS ranking, '
		.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
		.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
		.'FROM book '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'WHERE book_valid=1 AND book_id='.$_GET['id'].' GROUP BY book.book_id LIMIT 1');
	if(!$book)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($book->num_rows < 1)
	header('Location: browse.php');
	$tpl['book'] = $book->fetch_assoc();
	$book->close();
	//page assignments
	$tpl['title'] = $tpl['book']['title'].' by '.implode(', ', explode(':', $tpl['book']['author'])).' Comments';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'book story read comments';
	$tpl['description'] = $tpl['book']['summary'];
	//assign sub "template"
	$files['page'] = 'comments.html';
	//current size(limit)
	$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$comments = $db->query('SELECT comment_text AS text, comment_reply AS reply, comment_date AS date, user_id AS id, user_name AS name '
		.'FROM comment LEFT JOIN user ON user_id_fk=user_id WHERE book_id_fk='.$_GET['id'].' AND (comment_private=0 OR (comment_private=1 AND comment.user_id_fk='.$session->get('user', 'user').')) ORDER BY comment_date DESC, user_name ASC LIMIT '.$offset.', '.$size);
	if(!$comments)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['comments'] =& $comments;
	//get total for paging
	$count = $db->query('SELECT COUNT(comment_id) FROM comment WHERE comment_private=0 AND book_id_fk='.$_GET['id']);
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

//add a comment
function add_comments()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	$user = $session->get('user', 'user');
	//if we're not logged in, shove it
	if(empty($user))
	header('Location: user/login.php');
	//grab all book info we need
	$book = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
		.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
		.'book_wordcount AS wordcount, book_ranking AS ranking, book_notes AS notes, '
		.'rating_id AS ratingid, rating_name AS rating, '
		.'category_name AS catname, category_id AS catid, '
		.'type_id AS typeid, type_name AS type, '
		.'style_id AS styleid, style_name AS style, '
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
		.'WHERE book_valid=1 AND book_id='.$_REQUEST['id'].' GROUP BY book.book_id LIMIT 1');
	if(!$book)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($book->num_rows < 1)
	header('Location: browse.php');
	$tpl['book'] = $book->fetch_assoc();
	$book->close();
	//deal with submission
	if(isset($_POST['submit']))
	{
		if(empty($_POST['text']))
		{
			$tpl['error'] = 'You must enter some text for your comment';
		}
		else
		{
			if(isset($_POST['private']))
			{
				$private = 1;
			}
			else
			{
				$private = 0;
			}
			//ranking
			$rank = round((($tpl['book']['ranking'] + $_POST['rank']) / 2), 10);
			$create = $db->query('INSERT INTO comment(comment_text, comment_private, user_id_fk, book_id_fk, comment_date) '
				.'VALUES(\''.$db->real_escape_string(htmlentities(strip_tags($_POST['text']))).'\', '.$private.', '.$session->get('user', 'user').', '.$_POST['id'].', NOW())');
			$update = $db->query('UPDATE book SET book_comments = book_comments + 1, book_ranking='.$rank.' WHERE book_id='.$_POST['id']);
			$tpl['book']['ranking'] = $rank;
			$tpl['book']['comments']++;
			$tpl['error'] = 'Your comment has been added';
		}
	}
	//page assignments
	$tpl['title'] = $tpl['book']['title'].' by '.implode(', ', explode(':', $tpl['book']['author'])).' Comments';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'book story add comments';
	$tpl['description'] = $tpl['book']['summary'];
	//assign sub "template"
	$files['page'] = 'commentform.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function fav_book()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	$user = $session->get('user', 'user');
	//if we're not logged in, shove it
	if(empty($user))
	header('Location: user/login.php');
	//grab all book info we need
	$book = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
		.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
		.'book_wordcount AS wordcount, book_ranking AS ranking, book_notes AS notes, '
		.'rating_id AS ratingid, rating_name AS rating, '
		.'category_name AS catname, category_id AS catid, '
		.'type_id AS typeid, type_name AS type, '
		.'style_id AS styleid, style_name AS style, '
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
		.'LEFT JOIN category ON category.category_id=book.category_id_fk '
		.'LEFT JOIN style ON style.style_id=book.style_id_fk '
		.'LEFT JOIN type ON type.type_id=book.type_id_fk '
		.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
		.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
		.'WHERE book_valid=1 AND book_id='.$_REQUEST['id'].' GROUP BY book.book_id LIMIT 1');
	if(!$book)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($book->num_rows < 1)
	header('Location: browse.php');
	$tpl['book'] = $book->fetch_assoc();
	$book->close();
	//now we check for a book
	if(isset($_POST['submit']))
	{
		$result = $db->query('SELECT COUNT(usertobook_id) FROM usertobook WHERE user_id_fk='.$session->get('user', 'user').' AND book_id_fk='.$_POST['id']);
		$row = $result->fetch_row();
		$result->close();
		if($row[0] < 1)
		{
			$result = $db->query('INSERT INTO usertobook(book_id_fk, user_id_fk, usertobook_comment) VALUES('.$_POST['id'].', '.$session->get('user', 'user').', \''.$db->real_escape_string(htmlentities(strip_tags($_POST['text']))).'\')');
		}
		else
		{
			$tpl['error'] = 'This book is already a favorite, use the favorites manager in your profile.';
		}
	}
	//page assignments
	$tpl['title'] = $tpl['book']['title'].' by '.implode(', ', explode(':', $tpl['book']['author'])).' Comments';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'book story add favorites';
	$tpl['description'] = $tpl['book']['summary'];
	//assign sub "template"
	$files['page'] = 'favorites.html';
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('prepend.php');
$action = !isset($_REQUEST['a']) ? 0 : (int) $_REQUEST['a'];
switch($action)
{
	//add a favorite book and meta redirect back
	case 2:
		fav_book();
		break;
	//add a comment
	case 1:
		add_comments();
		break;
	//default is listing the comments
	default:
		list_comments();
		break;
}
include('append.php');
?>
