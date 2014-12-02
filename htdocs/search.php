<?php
/**
 * search.php - searches all areas of the site and displays results
 *
 * This allows a search in announcements, users, authors, or books and displays results
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: search.php,v 1.2 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

function show_forms($error = FALSE)
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	$sess_search = $session->get('search');
	//if the f is set, we have results
	if(isset($_GET['f']) and !empty($sess_search))
	{
		$_POST = unserialize(gzuncompress(urldecode($sess_search)));
		return show_results();
	}
	//page assignments
	$tpl['title'] = 'Search Site';
	$tpl['nest'] = '';
	$tpl['keywords'] = 'fanfiction site search';
	$tpl['description'] = 'Fanfiction site Search';
	$tpl['onload'] = ' onload="javascript:showForm(\'\')"';
	//assign sub "template"
	$files['page'] = 'search.html';
	$tpl['catlist'] = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
	if(!$tpl['catlist'])
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['typelist'] = $db->query('SELECT type_id AS id, type_name AS name FROM type ORDER BY type_id ASC');
	if(!$tpl['typelist'])
	{
		printf('Errormessage: %s', $db->error);
	}
	//get character list
	$tpl['charlist'] = $db->query('SELECT character_id AS id, character_name AS name FROM `character` ORDER BY character_name ASC');
	if(!$tpl['charlist'])
	{
		printf('Errormessage: %s', $db->error);
	}
	//get genres
	$tpl['genrelist'] = $db->query('SELECT genre_id AS id, genre_name AS name FROM genre ORDER BY genre_name ASC');
	if(!$tpl['genrelist'])
	{
		printf('Errormessage: %s', $db->error);
	}
	//get ratings
	$tpl['ratinglist'] = $db->query('SELECT rating_id AS id, rating_name AS name FROM rating ORDER BY rating_name ASC');
	if(!$tpl['ratinglist'])
	{
		printf('Errormessage: %s', $db->error);
	}
	//get styles
	$tpl['stylelist'] = $db->query('SELECT style_id AS id, style_name AS name FROM style ORDER BY style_name ASC');
	if(!$tpl['stylelist'])
	{
		printf('Errormessage: %s', $db->error);
	}
	//get warnings
	$tpl['warninglist'] = $db->query('SELECT warning_id AS id, warning_name AS name FROM warning ORDER BY warning_name ASC');
	if(!$tpl['warninglist'])
	{
		printf('Errormessage: %s', $db->error);
	}
	if($error != FALSE)
	{
		$tpl['error'] = $error;
	}
	//create sidebar
	include('../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function show_results()
{
	//type must be chosen
	if($_POST['t'] == '')
	{
		return show_forms('You must choose a type of search to perform');
	}
	elseif($_POST['t'] == 0)
	{
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['atitle']) and !isset($_POST['atext']))) and (empty($_POST['amonth']) and empty($_POST['aday']) and empty($_POST['ayear'])))
		{
			return show_forms('In order to find announcements, you must enter text to search for, and choose to search the text, title, or both.  Or you may choose a date combination to search.');
		}
		else
		{
			$tpl =& get_tpl();
			$db = get_db();
			$session = get_session();
			//page assignments
			$tpl['title'] = 'Announcement Search Results';
			$tpl['nest'] = '';
			$tpl['keywords'] = 'fanfiction announcement search results';
			$tpl['description'] = 'Fanfiction Site announcements search results';
			//assign sub "template"
			$files['page'] = 'result.html';
			//current size(limit)
			$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
			$tpl['size'] = $size;
			//current page - get offset
			$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
			$tpl['page'] = $page;
			//find offset
			$offset = ($page - 1) * $size;
			$tpl['offset'] = $offset + 1;
			//two queries, count and a limited version - first set up where
			$where = array();
			//add date to where clause
			if(!empty($_POST['ayear']))
			{
				$tmp = (int) $_POST['ayear'];
				$where[] = 'YEAR(announcement_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['amonth']))
			{
				$tmp = (int) $_POST['amonth'];
				$where[] = 'MONTH(announcement_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['aday']))
			{
				$tmp = (int) $_POST['aday'];
				$where[] = 'DAY(announcement_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['atitle']) and isset($_POST['atext']))
			{
				$match = 'MATCH (announcement_title, announcement_text) ';
			}
			elseif(isset($_POST['atitle']) and !isset($_POST['atext']))
			{
				$match = 'MATCH (announcement_title) ';
			}
			elseif(!isset($_POST['atitle']) and isset($_POST['atext']))
			{
				$match = 'MATCH (announcement_text) ';
			}
			//create against
			if(isset($_POST['bool']))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']) and !empty($match))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\') ';
			}
			else
			{
				$against = '';
			}
			//add to the where clause
			if(!empty($against))
			{
				$where[] = $match.$against;
			}
			//cram that where clause
			$where = 'WHERE '.implode('AND ', $where).' ';
			//now the order clause
			if(!empty($against))
			{
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, announcement_date DESC';
			}
			else
			{
				$order = 'ORDER BY announcement_date DESC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(announcement_id) FROM announcement '.$where);
			if(!$count)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $count->fetch_row();
			$total = $tpl['total'] = $total[0];
			if($total < 1)
			return show_forms('No Matches Found');
			$count->close();
			//do actual query
			$query = 'SELECT announcement_title AS title, announcement_date AS date, announcement_id AS id';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= ' FROM announcement '.$where.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['results'] = &$result;
			$tpl['type'] = 'Announcement';
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
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
		}
	}
	elseif($_POST['t'] == 1)
	{
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['uname']) and !isset($_POST['uemail']))) and (empty($_POST['umonth']) and empty($_POST['uday']) and empty($_POST['uyear'])))
		{
			return show_forms('In order to find users, you must enter text to search for, and choose to search user names, emails, or both.  Or you may choose a date combination to search.');
		}
		else
		{
			$tpl =& get_tpl();
			$db = get_db();
			$session = get_session();
			//page assignments
			$tpl['title'] = 'User Search Results';
			$tpl['nest'] = '';
			$tpl['keywords'] = 'fanfiction user search results';
			$tpl['description'] = 'Fanfiction Site user search results';
			//assign sub "template"
			$files['page'] = 'result.html';
			//current size(limit)
			$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
			$tpl['size'] = $size;
			//current page - get offset
			$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
			$tpl['page'] = $page;
			//find offset
			$offset = ($page - 1) * $size;
			$tpl['offset'] = $offset + 1;
			//two queries, count and a limited version - first set up where
			$where = array();
			//add date to where clause
			if(!empty($_POST['uyear']))
			{
				$tmp = (int) $_POST['uyear'];
				$where[] = 'YEAR(user_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['umonth']))
			{
				$tmp = (int) $_POST['umonth'];
				$where[] = 'MONTH(user_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['uday']))
			{
				$tmp = (int) $_POST['uday'];
				$where[] = 'DAY(user_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['uname']) and isset($_POST['uemail']))
			{
				$match = 'MATCH (user_name, user_email) ';
			}
			elseif(isset($_POST['uname']) and !isset($_POST['uemail']))
			{
				$match = 'MATCH (user_name) ';
			}
			elseif(!isset($_POST['uname']) and isset($_POST['uemail']))
			{
				$match = 'MATCH (user_email) ';
			}
			//create against
			if(isset($_POST['bool']))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']) and isset($match))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\') ';
			}
			else
			{
				$against = '';
			}
			//add to the where clause
			if(!empty($against))
			{
				$where[] = $match.$against;
			}
			//cram that where clause
			$where = 'WHERE '.implode('AND ', $where).' ';
			//now the order clause
			if(!empty($against))
			{
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, user_name ASC';
			}
			else
			{
				$order = 'ORDER BY user_name ASC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(user_id) FROM user '.$where);
			if(!$count)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $count->fetch_row();
			$total = $tpl['total'] = $total[0];
			if($total < 1)
			return show_forms('No Matches Found');
			$count->close();
			//do actual query
			$query = 'SELECT user_name AS name, user_date AS date, user_email AS email, user_id AS id';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= ' FROM user '.$where.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['results'] = &$result;
			$tpl['type'] = 'User';
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
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
		}
	}
	elseif($_POST['t'] == 2)
	{
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['atext']) and !isset($_POST['afile']) and !isset($_POST['aname']) and !isset($_POST['aemail']))) and (empty($_POST['month']) and empty($_POST['day']) and empty($_POST['year'])))
		{
			return show_forms('In order to find authors, you must enter text to search for, and the areas you want to look.  Or you may choose a date combination to search.');
		}
		else
		{
			$tpl =& get_tpl();
			$db = get_db();
			$session = get_session();
			//page assignments
			$tpl['title'] = 'Author Search Results';
			$tpl['nest'] = '';
			$tpl['keywords'] = 'fanfiction author search results';
			$tpl['description'] = 'Fanfiction Site author search results';
			//assign sub "template"
			$files['page'] = 'result.html';
			//current size(limit)
			$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
			$tpl['size'] = $size;
			//current page - get offset
			$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
			$tpl['page'] = $page;
			//find offset
			$offset = ($page - 1) * $size;
			$tpl['offset'] = $offset + 1;
			//two queries, count and a limited version - first set up where
			$where = array();
			//add date to where clause
			if(!empty($_POST['year']))
			{
				$tmp = (int) $_POST['year'];
				$where[] = 'YEAR(author_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['month']))
			{
				$tmp = (int) $_POST['month'];
				$where[] = 'MONTH(author_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['day']))
			{
				$tmp = (int) $_POST['day'];
				$where[] = 'DAY(author_date)=\''.$tmp.'\' ';
			}
			$array_match = array();
			//create match
			if(isset($_POST['atext']))
			{
				$array_match = 'author_text';
			}
			if(isset($_POST['afile']))
			{
				$array_match = 'author_file';
			}
			if(isset($_POST['aname']))
			{
				$array_match = 'author_name';
			}
			if(isset($_POST['aemail']))
			{
				$array_match = 'author_email';
			}
			if(!empty($array_match))
			$match = 'MATCH ('.implode(', ', $array_match).') ';
			//create against
			if(isset($_POST['bool']))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']) and !empty($match))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\') ';
			}
			else
			{
				$against = '';
			}
			//add to the where clause
			if(!empty($against))
			{
				$where[] = $match.$against;
			}
			//cram that where clause
			$where = 'WHERE '.implode('AND ', $where).' ';
			//now the order clause
			if(!empty($against))
			{
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, author_name ASC';
			}
			else
			{
				$order = 'ORDER BY author_name ASC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(author_id) FROM author '.$where);
			if(!$count)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $count->fetch_row();
			$total = $tpl['total'] = $total[0];
			if($total < 1)
			return show_forms('No Matches Found');
			$count->close();
			//do actual query
			$query = 'SELECT author_name AS name, author_contact AS email, author_date AS date, author_id AS id';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= ' FROM author '.$where.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['results'] = &$result;
			$tpl['type'] = 'Author';
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
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
		}
	}
	else
	{
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['btitle']) and !isset($_POST['bsummary']) and !isset($_POST['bauthor']))) and (empty($_POST['bumonth']) and empty($_POST['buday']) and empty($_POST['buyear'])) and (empty($_POST['bpmonth']) and empty($_POST['bpday']) and empty($_POST['bpyear'])) and (empty($_POST['bcats']) and empty($_POST['bchar']) and empty($_POST['bgenre']) and empty($_POST['brating']) and empty($_POST['bwarning']) and empty($_POST['bcount']) and empty($_POST['brank']) and empty($_POST['bstatus'])))
		{
			return show_forms('In order to find books, you must enter text to search for, and the areas you want to look.  Or you may choose a date combination to search.  Or you may choose items from the select boxes.');
		}
		else
		{
			$tpl =& get_tpl();
			$db = get_db();
			$session = get_session();
			//page assignments
			$tpl['title'] = 'Book Search Results';
			$tpl['nest'] = '';
			$tpl['keywords'] = 'fanfiction book search results';
			$tpl['description'] = 'Fanfiction Site book search results';
			//assign sub "template"
			$files['page'] = 'result.html';
			//current size(limit)
			$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
			$tpl['size'] = $size;
			//current page - get offset
			$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
			$tpl['page'] = $page;
			//find offset
			$offset = ($page - 1) * $size;
			$tpl['offset'] = $offset + 1;
			//two queries, count and a limited version - first set up where
			$where = array();
			//add date to where clause
			if(!empty($_POST['buyear']))
			{
				$tmp = (int) $_POST['buyear'];
				$where[] = 'YEAR(book_update)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bumonth']))
			{
				$tmp = (int) $_POST['bumonth'];
				$where[] = 'MONTH(book_update)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['buday']))
			{
				$tmp = (int) $_POST['buday'];
				$where[] = 'DAY(book_update)=\''.$tmp.'\' ';
			}
			//add date to where clause
			if(!empty($_POST['bpyear']))
			{
				$tmp = (int) $_POST['bpyear'];
				$where[] = 'YEAR(book_publish)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bpmonth']))
			{
				$tmp = (int) $_POST['bpmonth'];
				$where[] = 'MONTH(book_publish)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bpday']))
			{
				$tmp = (int) $_POST['bpday'];
				$where[] = 'DAY(book_publish)=\''.$tmp.'\' ';
			}
			//create match
			$match = '';
			if(isset($_POST['btitle']) and isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_title, book_summary) ';
			}
			elseif(isset($_POST['btitle']) and !isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_title) ';
			}
			elseif(!isset($_POST['btitle']) and isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_summary) ';
			}
			if(isset($_POST['bauthor']))
			{
				$match = '(MATCH (author.author_name) '.$against.' AND '.$match;
			}
			//create against
			if(isset($_POST['bool']) and !empty($_POST['string']) and (isset($_POST['btitle']) or isset($_POST['bsummary']) or isset($_POST['bauthor'])))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']) and (isset($_POST['btitle']) or isset($_POST['bsummary']) or isset($_POST['bauthor'])))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\') ';
			}
			else
			{
				$against = '';
			}
			//add to the where clause
			if(!empty($against) and !isset($_POST['bauthor']))
			{
				$where[] = $match.$against;
			}
			elseif(!empty($against))
			{
				$where[] = $match.$against.')';
			}
			//cat filter
			if(isset($_POST['bcats']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['bcats']))
				{
					$where[] = ' (book.category_id_fk='.implode(' OR book.category_id_fk=', $_POST['bcats']).') ';
				}
				else
				{
					$where[] = ' book.category_id_fk='.$_POST['bcats'].' ';
				}
			}
			//type filter
			if(isset($_POST['btype']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['btype']))
				{
					$where[] = ' (book.type_id_fk='.implode(' OR book.type_id_fk=', $_POST['btype']).') ';
				}
				else
				{
					$where[] = ' book.type_id_fk='.$_POST['btype'].' ';
				}
			}
			//style filter
			if(isset($_POST['bstyle']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['bstyle']))
				{
					$where[] = ' (book.style_id_fk='.implode(' OR book.style_id_fk=', $_POST['bstyle']).') ';
				}
				else
				{
					$where[] = ' book.style_id_fk='.$_POST['bstyle'].' ';
				}
			}
			//rating filter
			if(isset($_POST['brating']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['brating']))
				{
					$where[] = ' (book.rating_id_fk='.implode(' OR book.rating_id_fk=', $_POST['brating']).') ';
				}
				else
				{
					$where[] = ' book.rating_id_fk='.$_POST['brating'].' ';
				}
			}
			//harder filters with subqueries...fun - characters
			if(isset($_POST['bchar']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktocharacter WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bchar']))
				{
					$clause .= 'character_id_fk='.implode(' OR character_id_fk=', $_POST['bchar']).' ';
				}
				else
				{
					$clause .= 'character_id_fk='.$_POST['bchar'].' ';
				}
				$where[] = $clause.' )';
			}
			//genres
			if(isset($_POST['bgenre']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktogenre WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bgenre']))
				{
					$clause .= 'genre_id_fk='.implode(' OR genre_id_fk=', $_POST['bgenre']).' ';
				}
				else
				{
					$clause .= 'genre_id_fk='.$_POST['bgenre'].' ';
				}
				$where[] = $clause.' )';
			}
			//warnings
			if(isset($_POST['bwarning']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktowarning WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bwarning']))
				{
					$clause .= 'warning_id_fk='.implode(' OR warning_id_fk=', $_POST['bwarning']).' ';
				}
				else
				{
					$clause .= 'warning_id_fk='.$_POST['bwarning'].' ';
				}
				$where[] = $clause.' )';
			}
			//wordcount filter
			if(isset($_POST['bcount']) and $_POST['bcount'] > 0)
			{
				$word = array(0, 1000, 5000, 10000, 25000, 50000, 75000, 100000);
				$where[] = ' book.book_wordcount > '.$word[$_POST['bcount']];
			}
			//ranking filter
			if(isset($_POST['brank']) and $_POST['brank'] > 0)
			{
				$where[] = ' book.book_ranking >= '.$_POST['brank'];
			}
			//status filter
			if(isset($_POST['bstatus']) and $_POST['bstatus'] == 1)
			{
				$where[] = ' book.book_completed = 0';
			}
			elseif(isset($_POST['bstatus']) and $_POST['bstatus'] == 2)
			{
				$where[] = ' book.book_completed = 1';
			}
			//cram that where clause
			$where = 'WHERE '.implode('AND ', $where).' ';
			//now the order clause
			if(!empty($against))
			{
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, book_update DESC, book_publish DESC, book_title ASC';
			}
			else
			{
				$order = 'ORDER BY book_update DESC, book_publish DESC, book_title ASC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(book_id) FROM book LEFT JOIN booktoauthor ON booktoauthor.book_id_fk = book.book_id LEFT JOIN author ON booktoauthor.author_id_fk=author.author_id '.$where);
			if(!$count)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $count->fetch_row();
			$total = $tpl['total'] = $total[0];
			if($total < 1)
			return show_forms('No Matches Found');
			$count->close();
			//do actual query
			$query = 'SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
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
			.'group_concat(DISTINCT character_id ORDER BY character_id ASC SEPARATOR \':\') as characterid ';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= 'FROM book '
			.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
			.'LEFT JOIN type ON type.type_id=book.type_id_fk '
			.'LEFT JOIN style ON style.style_id=book.style_id_fk '
			.'LEFT JOIN category ON category.category_id=book.category_id_fk '
			.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
			.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
			.$where.' GROUP BY (book_id) '.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['results'] = &$result;
			$tpl['type'] = 'Book';
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
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
$action = !isset($_POST['search']) ? 0 : 1;
switch($action)
{
	//give us our results
	case 1:
		show_results();
		break;
	//this is just a search page :)
	default:
		show_forms();
		break;
}
include('append.php');
?>
