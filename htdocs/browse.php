<?php
/**
 * browse.php - Allows visitors to browse lists of current books
 *
 * Shows lists of books by category, title, or in order by date or a list of current published authors
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: browse.php,v 1.2 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

function latest()
{
	//get the session/etc setup stuff done
	$tpl =& get_tpl();
	$db = get_db();
	$config = get_config();
	//page assignments
	$tpl['title'] = 'Browse Latest';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'library browse directory latest';
	$tpl['description'] = 'Browse the fanfiction library by publish/update date';
	//assign sub "template"
	$files['page'] = 'latest.html';
	//current size(limit)
	$size = !isset($_GET['s']) ? 50 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$tpl['offset'] = $offset + 1;
	//create any filters
	$where = '';
	//cat filter
	if(isset($_GET['cid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['cid']))
		{
			$where .= ' AND (book.category_id_fk='.implode(' OR book.category_id_fk=', $_GET['cid']).') ';
		}
		else
		{
			$where .= ' AND book.category_id_fk='.$_GET['cid'].' ';
		}
	}
	//style filter
	if(isset($_GET['sid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['sid']))
		{
			$where .= ' AND (book.style_id_fk='.implode(' OR book.style_id_fk=', $_GET['sid']).') ';
		}
		else
		{
			$where .= ' AND book.style_id_fk='.$_GET['sid'].' ';
		}
	}
	//type filter
	if(isset($_GET['tid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['tid']))
		{
			$where .= ' AND (book.type_id_fk='.implode(' OR book.type_id_fk=', $_GET['tid']).') ';
		}
		else
		{
			$where .= ' AND book.type_id_fk='.$_GET['tid'].' ';
		}
	}
	//rating filter
	if(isset($_GET['rid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['rid']))
		{
			$where .= ' AND (book.rating_id_fk='.implode(' OR book.rating_id_fk=', $_GET['rid']).') ';
		}
		else
		{
			$where .= ' AND book.rating_id_fk='.$_GET['rid'].' ';
		}
	}
	//harder filters with subqueries...fun - characters
	if(isset($_GET['chid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktocharacter WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['chid']))
		{
			$where .= 'character_id_fk='.implode(' OR character_id_fk=', $_GET['chid']).' ';
		}
		else
		{
			$where .= 'character_id_fk='.$_GET['chid'].' ';
		}
		$where .= ' )';
	}
	//genres
	if(isset($_GET['gid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktogenre WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['gid']))
		{
			$where .= 'genre_id_fk='.implode(' OR genre_id_fk=', $_GET['gid']).' ';
		}
		else
		{
			$where .= 'genre_id_fk='.$_GET['gid'].' ';
		}
		$where .= ' )';
	}
	//warnings
	if(isset($_GET['wid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktowarning WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['wid']))
		{
			$where .= 'warning_id_fk='.implode(' OR warning_id_fk=', $_GET['wid']).' ';
		}
		else
		{
			$where .= 'warning_id_fk='.$_GET['wid'].' ';
		}
		$where .= ' )';
	}
	//wordcount filter
	if(isset($_GET['w']) and $_GET['w'] > 0)
	{
		$word = array(0, 1000, 5000, 10000, 25000, 50000, 75000, 100000);
		$where .= ' AND book.book_wordcount > '.$word[$_GET['w']];
	}
	//ranking filter
	if(isset($_GET['r']) and $_GET['r'] > 0)
	{
		$where .= ' AND book.book_ranking >= '.$_GET['r'];
	}
	//status filter
	if(isset($_GET['u']) and $_GET['u'] == 1)
	{
		$where .= ' AND book.book_completed = 0';
	}
	elseif(isset($_GET['u']) and $_GET['u'] == 2)
	{
		$where .= ' AND book.book_completed = 1';
	}
	//grab count
	$count = $db->query('SELECT COUNT(book_id) FROM book WHERE book_valid=1 '.$where);
	if(!$count)
	{
		printf('Errormessage: %s', $db->error);
	}
	$total = $count->fetch_row();
	$tpl['total'] = $total = $total[0];
	$count->close();
	//message for no authors
	if($total < 1)
	$tpl['error'] = 'No Books Found';
	//do paging
	if($total > $size)
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
		$page_total = ceil($total/$size);
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
	//create order clause
	if(!isset($_GET['t']) or $_GET['t'] == 0)
	{
		$order = 'ORDER BY book_update DESC, book_publish DESC, book_title ASC, book_id ASC';
	}
	else
	{
		$order = 'ORDER BY book_publish DESC, book_update DESC, book_title ASC, book_id ASC';
	}
	//grab books for list - welcome to the query from HELL
	$books = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
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
		.'WHERE book_valid=1 '.$where.' GROUP BY book.book_id '.$order.' LIMIT '.$offset.', '.$size);
	if(!$books)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['books'] =& $books;
	//get category list
	$catlist = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
	if(!$catlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get character list
	$charlist = $db->query('SELECT character_id AS id, character_name AS name FROM `character` ORDER BY character_name ASC');
	if(!$charlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get genres
	$genrelist = $db->query('SELECT genre_id AS id, genre_name AS name FROM genre ORDER BY genre_name ASC');
	if(!$genrelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get ratings
	$ratinglist = $db->query('SELECT rating_id AS id, rating_name AS name FROM rating ORDER BY rating_name ASC');
	if(!$ratinglist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get warnings
	$warninglist = $db->query('SELECT warning_id AS id, warning_name AS name FROM warning ORDER BY warning_name ASC');
	if(!$warninglist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get styles
	$stylelist = $db->query('SELECT style_id AS id, style_name AS name FROM style ORDER BY style_name ASC');
	if(!$stylelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get types
	$typelist = $db->query('SELECT type_id AS id, type_name AS name FROM type ORDER BY type_id ASC');
	if(!$typelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//create sidebox for filtering
	ob_start();
	include('../data/tpl/'.$config['theme'].'/latestfilter.html');
	$tpl['side'] = ob_get_clean();
	//free results
	$catlist->close();
	$charlist->close();
	$genrelist->close();
	$ratinglist->close();
	$warninglist->close();
	$typelist->close();
	$stylelist->close();
	//include sidebar functions
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function categories()
{
	//get the session/etc setup stuff done
	$tpl =& get_tpl();
	$db = get_db();
	$config = get_config();
	//page assignments
	//page assignments
	$tpl['title'] = 'Browse by Category';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'library browse directory category';
	$tpl['description'] = 'Browse the fanfiction library by category name';
	//assign sub "template"
	$files['page'] = 'categories.html';
	//set our parent cat
	$id = !isset($_GET['cid']) ? 0 : $_GET['cid'];
	//let's get our sub-categories list
	$catlist = $db->query('SELECT category_id AS id, category_name AS name, category_total AS total FROM category WHERE category_parent='.$id.' ORDER BY category_name ASC');
	if(!$catlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['listotal'] = $catlist->num_rows;
	$tpl['catlist'] = &$catlist;
	//first we get parent for current cat
	$result = $db->query('SELECT category_id AS id, category_name AS name, category_parent AS parent, 1 AS end FROM category WHERE category_id='.$id);
	if(!$result)
	{
		printf('Errormessage: %s', $db->error);
	}
	$path[] = $result->fetch_assoc();
	//recursive sucker to get path, can be MESSY
	function get_path($id, $path, &$db)
	{
		$result = $db->query('SELECT category_id AS id, category_name AS name, category_parent AS parent FROM category WHERE category_id='.$id);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tmp = $result->fetch_assoc();
		array_unshift($path, $tmp);
		$result->close();
		//stick it on the front, cause we're going from back to front
		if(!is_null($tmp['parent']) and $tmp['parent'] > 0)
		{
			$path = get_path($tmp['parent'], $path, $db);
		}
		return $path;
	}
	if($path[0]['parent'] > 0)
	{
		$tpl['path'] = get_path($path[0]['parent'], $path, $db);
	}
	else
	{
		if(!empty($path[0]))
		$tpl['path'] = $path;
	}
	//current size(limit)
	$size = !isset($_GET['s']) ? 50 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$tpl['offset'] = $offset + 1;
	//cat filter is automatic
	$where = ' AND book.category_id_fk='.$id;
	//rating filter
	if(isset($_GET['rid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['rid']))
		{
			$where .= ' AND (book.rating_id_fk='.implode(' OR book.rating_id_fk=', $_GET['rid']).') ';
		}
		else
		{
			$where .= ' AND book.rating_id_fk='.$_GET['rid'].' ';
		}
	}
	//style filter
	if(isset($_GET['sid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['sid']))
		{
			$where .= ' AND (book.style_id_fk='.implode(' OR book.style_id_fk=', $_GET['sid']).') ';
		}
		else
		{
			$where .= ' AND book.style_id_fk='.$_GET['sid'].' ';
		}
	}
	//type filter
	if(isset($_GET['tid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['tid']))
		{
			$where .= ' AND (book.type_id_fk='.implode(' OR book.type_id_fk=', $_GET['tid']).') ';
		}
		else
		{
			$where .= ' AND book.type_id_fk='.$_GET['tid'].' ';
		}
	}
	//harder filters with subqueries...fun - characters
	if(isset($_GET['chid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktocharacter WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['cid']))
		{
			$where .= 'character_id_fk='.implode(' OR character_id_fk=', $_GET['chid']).' ';
		}
		else
		{
			$where .= 'character_id_fk='.$_GET['chid'].' ';
		}
		$where .= ' )';
	}
	//genres
	if(isset($_GET['gid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktogenre WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['gid']))
		{
			$where .= 'genre_id_fk='.implode(' OR genre_id_fk=', $_GET['gid']).' ';
		}
		else
		{
			$where .= 'genre_id_fk='.$_GET['gid'].' ';
		}
		$where .= ' )';
	}
	//warnings
	if(isset($_GET['wid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktowarning WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['wid']))
		{
			$where .= 'warning_id_fk='.implode(' OR warning_id_fk=', $_GET['wid']).' ';
		}
		else
		{
			$where .= 'warning_id_fk='.$_GET['wid'].' ';
		}
		$where .= ' )';
	}
	//wordcount filter
	if(isset($_GET['w']) and $_GET['w'] > 0)
	{
		$word = array(0, 1000, 5000, 10000, 25000, 50000, 75000, 100000);
		$where .= ' AND book.book_wordcount > '.$word[$_GET['w']];
	}
	//ranking filter
	if(isset($_GET['r']) and $_GET['r'] > 0)
	{
		$where .= ' AND book.book_ranking >= '.$_GET['r'];
	}
	//status filter
	if(isset($_GET['u']) and $_GET['u'] == 1)
	{
		$where .= ' AND book.book_completed = 0';
	}
	elseif(isset($_GET['u']) and $_GET['u'] == 2)
	{
		$where .= ' AND book.book_completed = 1';
	}
	//grab announcement count
	$count = $db->query('SELECT COUNT(book_id) FROM book WHERE book_valid=1'.$where);
	if(!$count)
	{
		printf('Errormessage: %s', $db->error);
	}
	$total = $count->fetch_row();
	$tpl['total'] = $total = $total[0];
	$count->close();
	//message for no authors
	if($total < 1)
	$tpl['error'] = 'No Books Found';
	//do paging
	if($total > $size)
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
		$page_total = ceil($total/$size);
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
	//create order clause
	if(isset($_GET['o']) and $_GET['o'] == 2)
	{
		$order = 'ORDER BY book_title ASC, book_update DESC, book_publish DESC, book_id ASC';
	}
	elseif(isset($_GET['o']) and $_GET['o'] == 1)
	{
		$order = 'ORDER BY book_publish DESC, book_update DESC, book_title ASC, book_id ASC';
	}
	else
	{
		$order = 'ORDER BY book_update DESC, book_publish DESC, book_title ASC, book_id ASC';
	}
	//grab books for list - welcome to the query from HELL
	$books = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
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
		.'WHERE book_valid=1 '.$where.' GROUP BY book.book_id '.$order.' LIMIT '.$offset.', '.$size);
	if(!$books)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['books'] =& $books;
	//get character list
	$charlist = $db->query('SELECT character_id AS id, character_name AS name FROM `character` ORDER BY character_name ASC');
	if(!$charlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get genres
	$genrelist = $db->query('SELECT genre_id AS id, genre_name AS name FROM genre ORDER BY genre_name ASC');
	if(!$genrelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get ratings
	$ratinglist = $db->query('SELECT rating_id AS id, rating_name AS name FROM rating ORDER BY rating_name ASC');
	if(!$ratinglist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get warnings
	$warninglist = $db->query('SELECT warning_id AS id, warning_name AS name FROM warning ORDER BY warning_name ASC');
	if(!$warninglist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get styles
	$stylelist = $db->query('SELECT style_id AS id, style_name AS name FROM style ORDER BY style_name ASC');
	if(!$stylelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get types
	$typelist = $db->query('SELECT type_id AS id, type_name AS name FROM type ORDER BY type_id ASC');
	if(!$typelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//create sidebox for filtering
	ob_start();
	include('../data/tpl/'.$config['theme'].'/categoriesfilter.html');
	$tpl['side'] = ob_get_clean();
	//free results
	$charlist->close();
	$genrelist->close();
	$ratinglist->close();
	$warninglist->close();
	$typelist->close();
	$stylelist->close();
	//include sidebar functions
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function titles()
{
	$tpl =& get_tpl();
	$db = get_db();
	$config = get_config();
	//page assignments
	$tpl['title'] = 'Browse by Title';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'library browse directory title';
	$tpl['description'] = 'Browse the fanfiction library by title name';
	//assign sub "template"
	$files['page'] = 'titles.html';
	//create abcd list
	$tpl['alphabet'][] = 'NUM';
	$i = 'A';
	for($n = 0; $n < 26; $n++)
	{
		$tpl['alphabet'][] = $i;
		$i++;
	}
	$tpl['alphabet'][] = 'ALL';
	//now we set the letter, or it may not be
	$letter = !isset($_GET['l']) ? NULL : (string) $_GET['l'];
	//check it for idiots
	if($letter == 'ALL' or (!is_null($letter) and !in_array($letter, $tpl['alphabet'])))
	$letter = NULL;
	$where = '';
	if(!is_null($letter))
	{
		if($letter == 'NUM')
		{
			$tpl['title'] .= ': #';
		}
		else
		{
			//append the letter
			$tpl['title'] .= ': '.$letter;
		}
		$tpl['letter'] = $letter;
		$where = ' AND book_order=\''.$letter.'\' ';
	}
	//current size(limit)
	$size = !isset($_GET['s']) ? 50 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$tpl['offset'] = $offset + 1;
	//create any filters
	//cat filter
	if(isset($_GET['cid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['cid']))
		{
			$where .= ' AND (book.category_id_fk='.implode(' OR book.category_id_fk=', $_GET['cid']).') ';
		}
		else
		{
			$where .= ' AND book.category_id_fk='.$_GET['cid'].' ';
		}
	}
	//style filter
	if(isset($_GET['sid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['sid']))
		{
			$where .= ' AND (book.style_id_fk='.implode(' OR book.style_id_fk=', $_GET['sid']).') ';
		}
		else
		{
			$where .= ' AND book.style_id_fk='.$_GET['sid'].' ';
		}
	}
	//type filter
	if(isset($_GET['tid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['tid']))
		{
			$where .= ' AND (book.type_id_fk='.implode(' OR book.type_id_fk=', $_GET['tid']).') ';
		}
		else
		{
			$where .= ' AND book.type_id_fk='.$_GET['tid'].' ';
		}
	}
	//rating filter
	if(isset($_GET['rid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['rid']))
		{
			$where .= ' AND (book.rating_id_fk='.implode(' OR book.rating_id_fk=', $_GET['rid']).') ';
		}
		else
		{
			$where .= ' AND book.rating_id_fk='.$_GET['rid'].' ';
		}
	}
	//harder filters with subqueries...fun - characters
	if(isset($_GET['chid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktocharacter WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['chid']))
		{
			$where .= 'character_id_fk='.implode(' OR character_id_fk=', $_GET['chid']).' ';
		}
		else
		{
			$where .= 'character_id_fk='.$_GET['chid'].' ';
		}
		$where .= ' )';
	}
	//genres
	if(isset($_GET['gid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktogenre WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['gid']))
		{
			$where .= 'genre_id_fk='.implode(' OR genre_id_fk=', $_GET['gid']).' ';
		}
		else
		{
			$where .= 'genre_id_fk='.$_GET['gid'].' ';
		}
		$where .= ' )';
	}
	//warnings
	if(isset($_GET['wid']))
	{
		$where .= 'AND book_id IN(SELECT book_id_fk AS book_id FROM booktowarning WHERE ';
		//if chid's an array then we smash it
		if(is_array($_GET['wid']))
		{
			$where .= 'warning_id_fk='.implode(' OR warning_id_fk=', $_GET['wid']).' ';
		}
		else
		{
			$where .= 'warning_id_fk='.$_GET['wid'].' ';
		}
		$where .= ' )';
	}
	//wordcount filter
	if(isset($_GET['w']) and $_GET['w'] > 0)
	{
		$word = array(0, 1000, 5000, 10000, 25000, 50000, 75000, 100000);
		$where .= ' AND book.book_wordcount > '.$word[$_GET['w']];
	}
	//ranking filter
	if(isset($_GET['r']) and $_GET['r'] > 0)
	{
		$where .= ' AND book.book_ranking >= '.$_GET['r'];
	}
	//status filter
	if(isset($_GET['u']) and $_GET['u'] == 1)
	{
		$where .= ' AND book.book_completed = 0';
	}
	elseif(isset($_GET['u']) and $_GET['u'] == 2)
	{
		$where .= ' AND book.book_completed = 1';
	}
	//grab announcement count
	$count = $db->query('SELECT COUNT(book_id) FROM book WHERE book_valid=1 '.$where);
	if(!$count)
	{
		printf('Errormessage: %s', $db->error);
	}
	$total = $count->fetch_row();
	$tpl['total'] = $total = $total[0];
	$count->close();
	//message for no authors
	if($total < 1)
	$tpl['error'] = 'No Books Found';
	//do paging
	if($total > $size)
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
		$page_total = ceil($total/$size);
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
	//grab books for list - welcome to the query from HELL
	$books = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
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
		.'WHERE book_valid=1 '.$where.' GROUP BY book.book_id ORDER BY book_title ASC, book_update DESC LIMIT '.$offset.', '.$size);
	if(!$books)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['books'] =& $books;
	//get category list
	$catlist = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
	if(!$catlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get character list
	$charlist = $db->query('SELECT character_id AS id, character_name AS name FROM `character` ORDER BY character_name ASC');
	if(!$charlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get genres
	$genrelist = $db->query('SELECT genre_id AS id, genre_name AS name FROM genre ORDER BY genre_name ASC');
	if(!$genrelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get ratings
	$ratinglist = $db->query('SELECT rating_id AS id, rating_name AS name FROM rating ORDER BY rating_name ASC');
	if(!$ratinglist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get warnings
	$warninglist = $db->query('SELECT warning_id AS id, warning_name AS name FROM warning ORDER BY warning_name ASC');
	if(!$warninglist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get styles
	$stylelist = $db->query('SELECT style_id AS id, style_name AS name FROM style ORDER BY style_name ASC');
	if(!$stylelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//get types
	$typelist = $db->query('SELECT type_id AS id, type_name AS name FROM type ORDER BY type_id ASC');
	if(!$typelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//create sidebox for filtering
	ob_start();
	include('../data/tpl/'.$config['theme'].'/titlesfilter.html');
	$tpl['side'] = ob_get_clean();
	//free results
	$catlist->close();
	$charlist->close();
	$genrelist->close();
	$ratinglist->close();
	$warninglist->close();
	$typelist->close();
	$stylelist->close();
	//include sidebar functions
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function authors()
{
	$tpl =& get_tpl();
	$db = get_db();
	$config = get_config();
	//page assignments
	$tpl['title'] = 'Browse by Author';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'library browse directory author';
	$tpl['description'] = 'Browse the fanfiction library by author name';
	//assign sub "template"
	$files['page'] = 'authors.html';
	//create abcd list
	$tpl['alphabet'][] = 'NUM';
	$i = 'A';
	for($n = 0; $n < 26; $n++)
	{
		$tpl['alphabet'][] = $i;
		$i++;
	}
	$tpl['alphabet'][] = 'ALL';
	//now we set the letter, or it may not be
	$letter = !isset($_GET['l']) ? NULL : (string) $_GET['l'];
	//check it for idiots
	if($letter == 'ALL' or (!is_null($letter) and !in_array($letter, $tpl['alphabet'])))
	$letter = NULL;
	$where = '';
	if(!is_null($letter))
	{
		//append the letter
		$tpl['title'] .= ': '.$letter;
		$tpl['letter'] = $letter;
		$where = ' AND author_order=\''.$letter.'\' ';
	}
	//current size(limit)
	$size = !isset($_GET['s']) ? 50 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$tpl['offset'] = $offset + 1;
	if(isset($_GET['cid']))
	{
		//if cid's an array then we smash it
		if(is_array($_GET['cid']))
		{
			$clause = ' category_id_fk='.implode(' OR category_id_fk=', $_GET['cid']).' ';
		}
		else
		{
			$clause = ' category_id_fk='.$_GET['cid'].' ';
		}
		$cid_where = ' AND author_id IN(SELECT author_id_fk AS author_id FROM book LEFT JOIN booktoauthor ON book_id=book_id_fk WHERE '.$clause.' )';
	}
	else
	{
		$cid_where = '';
	}
	//grab announcement count
	$count = $db->query('SELECT COUNT(author_id) FROM author WHERE author_count > 0'.$where.$cid_where);
	if(!$count)
	{
		printf('Errormessage: %s', $db->error);
	}
	$total = $count->fetch_row();
	$tpl['total'] = $total = $total[0];
	$count->close();
	//message for no authors
	if($total < 1)
	$tpl['error'] = 'No Authors Found';
	//do paging
	if($total > $size)
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
		$page_total = ceil($total/$size);
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
	//grab authors for list
	$authors = $db->query('SELECT author_id AS id, author_name AS name FROM author WHERE author_count > 0'.$where.$cid_where.' ORDER BY author_name DESC, author_id ASC LIMIT '.$offset.', '.$size);
	if(!$authors )
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['authors'] =& $authors;
	//get category list
	$catlist = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
	if(!$catlist)
	{
		printf('Errormessage: %s', $db->error);
	}
	//create sidebox for filtering
	ob_start();
	include('../data/tpl/'.$config['theme'].'/authorsfilter.html');
	$tpl['side'] = ob_get_clean();
	//free catlist
	$catlist->close();
	//include sidebar functions
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
	//case 3 is authors browsing
	case 3:
		authors();
		break;
	//case 2 is titles browsing
	case 2:
		titles();
		break;
	//case 1 is categories browsing
	case 1:
		categories();
		break;
	//default is the latest page
	default:
		latest();
		break;
}
include('append.php');
?>
