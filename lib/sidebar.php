<?php
/**
 * sidebar.php - Does queries to create information for sidebar
 *
 * This fetches the latest user, author, books and featured books for display in the sidebar area
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: sidebar.php,v 1.1 2004/07/19 18:05:13 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     lib
 * @filesource
 */

$files['sidebar'] = 'sidebar.html';
//featured pick
$s_result = $db->query(
	'SELECT featured_id AS id, featured_title AS title, featured_summary AS summary, featured_date AS date, '
	.'user_id AS userid, user_name AS username, '
	.'book_id AS bookid, book_title AS booktitle, book_completed AS completed, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
	.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
	.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
	.'FROM featured '
	.'LEFT JOIN book ON featured.book_id_fk=book.book_id '
	.'LEFT JOIN user ON featured.user_id_fk=user.user_id '
	.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
	.'GROUP BY book.book_id ORDER BY featured_date DESC LIMIT 1');
if(!$s_result)
{
	printf('Errormessage: %s', $db->error);
}
$tpl['featured'] = $s_result->fetch_assoc();
$s_result->close();
//latest update
$s_result = $db->query(
	'SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
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
	.'FROM book '
	.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
	.'LEFT JOIN type ON type.type_id=book.type_id_fk '
	.'LEFT JOIN style ON style.style_id=book.style_id_fk '
	.'LEFT JOIN category ON category.category_id=book.category_id_fk '
	.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
	.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
	.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
	.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
	.'WHERE book_valid=1 GROUP BY book.book_id ORDER BY book_update DESC LIMIT 1');
if(!$s_result)
{
	printf('Errormessage: %s', $db->error);
}
$tpl['latest_update'] = $s_result->fetch_assoc();
$s_result->close();
if(!is_null($tpl['latest_update']))
{
	//latest publish
	$s_result = $db->query(
		'SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
		.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
		.'book_wordcount AS wordcount, book_chapters AS chapters, book_ranking AS ranking, '
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
		.'LEFT JOIN type ON type.type_id=book.type_id_fk '
		.'LEFT JOIN style ON style.style_id=book.style_id_fk '
		.'LEFT JOIN category ON category.category_id=book.category_id_fk '
		.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
		.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
		.'WHERE book_id != '.$tpl['latest_update']['bookid'].' AND book_valid=1 GROUP BY book.book_id ORDER BY book_publish DESC LIMIT 1');
	if(!$s_result)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['latest_publish'] = $s_result->fetch_assoc();
	$s_result->close();
}
//latest author
$s_result = $db->query('SELECT author_name AS author, author_id AS authorid, author_date AS date, user_id_fk AS userid FROM author '
	.'WHERE author_valid=1 ORDER BY author_date DESC LIMIT 1');
if(!$s_result)
{
	printf('Errormessage: %s', $db->error);
}
$tpl['latest_author'] = $s_result->fetch_assoc();
$s_result->close();
//latest user
if(empty($tpl['latest_author']))
{
	$where = '';
}
else
{
	$where = ' user_id != '.$tpl['latest_author']['userid'].' AND ';
}
$s_result = $db->query('SELECT user_name AS user, user_id AS userid, user_date AS date FROM user '
	.'WHERE'.$where.' user_valid=1 ORDER BY user_date DESC LIMIT 1');
if(!$s_result)
{
	printf('Errormessage: %s', $db->error);
}
$tpl['latest_user'] = $s_result->fetch_assoc();
$s_result->close();
//get total for books
$count = $db->query('SELECT COUNT(book_id) FROM book WHERE book_valid = 1');
if(!$count)
{
	printf('Errormessage: %s', $db->error);
}
$total = $count->fetch_row();
$total = $total[0];
$count->close();
$tpl['totals']['books'] = $total;
//get total for authors
$count = $db->query('SELECT COUNT(author_id) FROM author WHERE author_valid = 1 AND author_count > 0');
if(!$count)
{
	printf('Errormessage: %s', $db->error);
}
$total = $count->fetch_row();
$total = $total[0];
$count->close();
$tpl['totals']['authors'] = $total;
return;
?>
