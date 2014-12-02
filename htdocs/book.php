<?php
/**
 * book.php - displays index for book and current chapter being read
 *
 * This is the actual page used to read a book, it will display an index or a single chapter
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: book.php,v 1.2 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

//get a books information and show it
function show_book()
{
	//get the session/etc setup stuff done
	$tpl =& get_tpl();
	$db = get_db();
	//first get our basic book info
	$book = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
		.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
		.'book_wordcount AS wordcount, book_ranking AS ranking, book_notes AS notes, '
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
		.'FROM book '
		.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
		.'LEFT JOIN style ON style.style_id=book.style_id_fk '
		.'LEFT JOIN type ON type.type_id=book.type_id_fk '
		.'LEFT JOIN category ON category.category_id=book.category_id_fk '
		.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
		.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
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
	$tpl['title'] = $tpl['book']['title'].' by '.implode(', ', explode(':', $tpl['book']['author']));
	$tpl['nest'] = '';
	$tpl['keywords'] = 'book story read';
	$tpl['description'] = $tpl['book']['summary'];
	//assign sub "template"
	$files['page'] = 'book.html';
	//now we get our chapters
	$chapter = $db->query('SELECT chapter_title AS title, chapter_id AS id, chapter_update AS `update`, chapter_publish AS publish, chapter_wordcount AS wordcount '
		.'FROM chapter '
		.'WHERE chapter_valid=1 AND book_id_fk='.$_GET['id'].' ORDER BY chapter_order');
	if(!$chapter)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['chapter'] = &$chapter;
	//finally run the counter update
	$increment = $db->query('UPDATE book SET book_views=book_views+1 WHERE book_id='.$_GET['id']);
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//now we can show the chapter
function show_chapter()
{
	//get the session/etc setup stuff done
	$tpl =& get_tpl();
	$db = get_db();
	//then we get our chapter
	$chapter = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, chapter_id AS chid, chapter_title as chtitle, chapter_update AS chupdate, chapter_publish AS chpublish, chapter_order AS `order`, '
		.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
		.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
		.'FROM chapter '
		.'LEFT JOIN book ON chapter.book_id_fk=book.book_id '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'WHERE book_valid=1 AND chapter_valid=1 AND book_id='.$_GET['id'].' AND chapter_id='.$_GET['c'].' GROUP BY book.book_id LIMIT 1');
	if(!$chapter)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($chapter->num_rows < 1)
	header('Location: browse.php');
	$tpl['chapter'] = $chapter->fetch_assoc();
	$chapter->close();
	//get our file
	$path = realpath('../data/files').'/'.$tpl['chapter']['bookid'].'/'.$tpl['chapter']['chid'].'.txt';
	if(!file_exists($path))
	header('Location: browse.php');
	$tpl['chapter']['chapter'] = file_get_contents($path);
	//page assignments
	$tpl['title'] = $tpl['chapter']['title'].' by '.implode(', ', explode(':', $tpl['chapter']['author'])).' ~*~ '.$tpl['chapter']['chtitle'];
	$tpl['nest'] = '';
	$tpl['keywords'] = 'book story read';
	$tpl['description'] = $tpl['chapter']['summary'];
	//assign sub "template"
	$files['page'] = 'chapter.html';
	//now we get our chapters
	$chapterlist = $db->query('SELECT chapter_title AS title, chapter_id AS id '
		.'FROM chapter '
		.'WHERE chapter_valid=1 AND book_id_fk='.$_GET['id'].' ORDER BY chapter_order');
	if(!$chapterlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['chapterlist'] = &$chapterlist;
	$chapter = $db->query('SELECT chapter_id AS prev FROM chapter WHERE book_id_fk='.$tpl['chapter']['bookid'].' AND chapter_order < '.$tpl['chapter']['order'].' ORDER BY chapter_order DESC LIMIT 1');
	$tmp = $chapter->fetch_row();
	$tpl['prev'] = $tmp[0];
	$chapter = $db->query('SELECT chapter_id AS next FROM chapter WHERE book_id_fk='.$tpl['chapter']['bookid'].' AND chapter_order > '.$tpl['chapter']['order'].' ORDER BY chapter_order ASC LIMIT 1');
	$tmp = $chapter->fetch_row();
	$tpl['next'] = $tmp[0];
	$chapter->close();
	//finally run the counter update
	$increment = $db->query('UPDATE chapter SET chapter_views=chapter_views+1 WHERE book_id_fk='.$_GET['id'].' AND chapter_id='.$_GET['c']);
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('prepend.php');
$action = !isset($_GET['c']) ? 0 : 1;
switch($action)
{
	//show a chapter
	case 1:
		show_chapter();
		break;
	//show our book
	default:
		show_book();
		break;
}
include('append.php');
?>
