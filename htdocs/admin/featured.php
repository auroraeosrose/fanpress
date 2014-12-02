<?php
function list_featured()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		$delete = $db->query('DELETE FROM featured WHERE featured_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//page assignments
	$tpl['title'] = 'List Features ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, featured administrate list';
	$tpl['description'] = 'Fanfiction Library featured Administration List';
	//assign sub "template"
	$files['page'] = 'adminfeaturedlist.html';
	//current size(limit)
	$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
	$tpl['size'] = $size;
	//current page - get offset
	$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
	$tpl['page'] = $page;
	//find offset
	$offset = ($page - 1) * $size;
	$tpl['offset'] = $offset + 1;
	//grab announcement count
	$count = $db->query('SELECT COUNT(featured_id) FROM featured');
	if(!$count)
	{
		printf('Errormessage: %s', $db->error);
	}
	$total = $count->fetch_row();
	$tpl['total'] = $total = $total[0];
	$count->close();
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
	//grab announcements for list
	$featurelist = $db->query('SELECT user_id as uid, user_name AS uname, book_title AS btitle, book_id AS bid, featured_title AS title, featured_date AS date, featured_id AS id FROM featured LEFT JOIN user ON featured.user_id_fk=user.user_id LEFT JOIN book ON book.book_id=featured.book_id_fk ORDER BY featured_date DESC, user_id_fk ASC LIMIT '.$offset.', '.$size);
	if(!$featurelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['featurelist'] =& $featurelist;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function new_featured()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'New Featured ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, featured administrate create new';
	$tpl['description'] = 'Fanfiction Library featured Administration Create new';
	//assign sub "template"
	$files['page'] = 'adminfeaturednew.html';
	//first we check
	if(isset($_POST['preview']) or isset($_POST['submit']))
	{
		if(empty($_POST['title']) or empty($_POST['text']) or empty($_POST['summary']) or empty($_POST['btitle']))
		{
			$tpl['error'] = 'You must enter a title, summary, book title and text to create a featured story.';
		}
		$result = $db->query('SELECT book_id FROM book WHERE book_title = \''.$db->real_escape_string(htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['btitle'])))).'\'');
		if($result->num_rows < 1)
		{
			$tpl['error'] = 'That book title does not exists, please check your spelling and case.';
		}
		else
		{
			$id = $result->fetch_row();
			$id = $id[0];
		}
		$result->close();
	}
	if(isset($_POST['preview']))
	{
		$tpl['preview'] = 1;
		$tpl['f_title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		$tpl['f_summary'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['summary'])));
		$tpl['f_text'] = htmlentities(strip_tags($_POST['text']));
		$tpl['f_date'] = date('Y-m-d h:i:s');
	}
	elseif(isset($_POST['submit']) and !isset($tpl['error']))
	{
		$result = $db->query('INSERT INTO featured(featured_title, featured_text, user_id_fk, book_id_fk, featured_date, featured_summary) '
			.'VALUES(\''.mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])))).'\', \''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['text']))).'\', '.$session->get('user', 'user').', '.$id.', NOW(), \''.mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['summary'])))).'\')');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = 'Your featured story has been created.';
		$tpl['noshow'] = TRUE;
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function edit_featured()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//delete if needed
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		$delete = $db->query('DELETE FROM featured WHERE featured_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//page assignments
	$tpl['title'] = 'Edit Featured ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, featured administrate edit';
	$tpl['description'] = 'Fanfiction Library featured Administration Edit';
	//assign sub "template"
	$files['page'] = 'adminfeaturededit.html';
	//first we check
	if(isset($_POST['preview']) or isset($_POST['submit']))
	{
		if(empty($_POST['title']) or empty($_POST['text']) or empty($_POST['summary']))
		{
			$tpl['error'] = 'You must enter a title, summary, text for a featured story.';
		}
	}
	if(isset($_POST['preview']))
	{
		$tpl['preview'] = 1;
		$tpl['f_title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		$tpl['f_summary'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['summary'])));
		$tpl['f_text'] = htmlentities(strip_tags($_POST['text']));
		$tpl['f_date'] = date('Y-m-d h:i:s');
	}
	elseif(isset($_POST['submit']) and !isset($tpl['error']))
	{
		$result = $db->query('UPDATE featured SET featured_title=\''.mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])))).'\', featured_text=\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['text']))).'\', featured_summary=\''.mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['summary'])))).'\' WHERE featured_id='.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = 'Your featured story has been changed.';
	}
	//first of all, we grab and show a message if we have an id
	if(isset($_REQUEST['id']))
	{
		//grab the announcement
		$featured = $db->query('SELECT featured_id AS id, featured_title AS title, featured_text AS text, featured_summary AS summary, featured_date AS date FROM featured WHERE featured_id='.$_REQUEST['id'].' LIMIT 1');
		if(!$featured)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $featured->fetch_assoc();
		$featured->close();
	}
	//full text search creation, fun baby fun
	if(isset($_POST['search']) or isset($_GET['f']))
	{
		$sess_search = $session->get('search');
		if(isset($_GET['f']) and !empty($sess_search))
		{
			$_POST = unserialize(gzuncompress(urldecode($sess_search)));
		}
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['title']) and !isset($_POST['text']) and !isset($_POST['summary']))) and (empty($_POST['month']) and empty($_POST['day']) and empty($_POST['year'])))
		{
			$tpl['error'] = 'You must enter text to search for, and choose to search the text, title, summary, or any combination.  Or you may choose a date combination to search.';
		}
		else
		{
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
				$where[] = 'YEAR(featured_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['month']))
			{
				$tmp = (int) $_POST['month'];
				$where[] = 'MONTH(featured_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['day']))
			{
				$tmp = (int) $_POST['day'];
				$where[] = 'DAY(featured_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['title']) or isset($_POST['summary']) or isset($_POST['text']))
			{
				$match = array();
				if(isset($_POST['title']))
				{
					$match[] = 'featured_title';
				}
				elseif(isset($_POST['text']))
				{
					$match[] = 'featured_text';
				}
				elseif(isset($_POST['summary']))
				{
					$match[] = 'featured_summary';
				}
				$match = 'MATCH('.implode(', ', $match).') ';
			}
			//create against
			if(isset($_POST['bool']))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']))
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
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, featured_date DESC';
			}
			else
			{
				$order = 'ORDER BY featured_date DESC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(featured_id) FROM featured '.$where);
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
			//do actual query
			$query = 'SELECT user_id as uid, user_name AS uname, book_title AS btitle, book_id AS bid, featured_title AS title, featured_date AS date, featured_id AS id ';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= 'FROM featured LEFT JOIN user ON featured.user_id_fk=user.user_id LEFT JOIN book ON book.book_id=featured.book_id_fk '.$where.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['results'] = &$result;
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
		}
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl,$files);
	return;
}

function delete_featured()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Delete Featured ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, featured administrate delete';
	$tpl['description'] = 'Fanfiction Library featured Administration Delete';
	//assign sub "template"
	$files['page'] = 'adminfeatureddelete.html';
	//do the delete needs to go HERE
	if(isset($_POST['submit']))
	{
		if(isset($_POST['delete']) and is_array($_POST['delete']))
		{
			$where = array();
			foreach($_POST['delete'] as $id => $junk)
			{
				$where[] = 'featured_id='.$id;
			}
			$where = implode(' OR ', $where);
		}
		$delete = $db->query('DELETE FROM featured WHERE 1=1 AND '.$where.' LIMIT '.count($_POST['delete']));
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = $db->affected_rows.' featured stories were deleted.';
	}
	//full text search creation, fun baby fun
	if(isset($_POST['search']) or isset($_GET['f']))
	{
		$sess_search = $session->get('search');
		if(isset($_GET['f']) and !empty($sess_search))
		{
			$_POST = unserialize(gzuncompress(urldecode($sess_search)));
		}
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['title']) and !isset($_POST['text']) and !isset($_POST['summary']))) and (empty($_POST['month']) and empty($_POST['day']) and empty($_POST['year'])))
		{
			$tpl['error'] = 'You must enter text to search for, and choose to search the text, title, summary, or any combination.  Or you may choose a date combination to search.';
		}
		else
		{
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
				$where[] = 'YEAR(featured_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['month']))
			{
				$tmp = (int) $_POST['month'];
				$where[] = 'MONTH(featured_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['day']))
			{
				$tmp = (int) $_POST['day'];
				$where[] = 'DAY(featured_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['title']) or isset($_POST['summary']) or isset($_POST['text']))
			{
				$match = array();
				if(isset($_POST['title']))
				{
					$match[] = 'featured_title';
				}
				elseif(isset($_POST['text']))
				{
					$match[] = 'featured_text';
				}
				elseif(isset($_POST['summary']))
				{
					$match[] = 'featured_summary';
				}
				$match = 'MATCH('.implode(', ', $match).') ';
			}
			//create against
			if(isset($_POST['bool']))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']))
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
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, featured_date DESC';
			}
			else
			{
				$order = 'ORDER BY featured_date DESC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(featured_id) FROM featured '.$where);
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
			//do actual query
			$query = 'SELECT user_id as uid, user_name AS uname, book_title AS btitle, book_id AS bid, featured_title AS title, featured_date AS date, featured_id AS id ';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= 'FROM featured LEFT JOIN user ON featured.user_id_fk=user.user_id LEFT JOIN book ON book.book_id=featured.book_id_fk '.$where.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['results'] = &$result;
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
		}
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('../prepend.php');
$action = !isset($_REQUEST['a']) ? 0 : (int) $_REQUEST['a'];
switch($action)
{
	//delete an announcement, uses same search as edit
	case 3:
		delete_featured();
		break;
	//edit an announcement
	case 2:
		edit_featured();
		break;
	//new announcement
	case 1:
		new_featured();
		break;
	//default is listing announcements
	default:
		list_featured();
		break;
}
include('../append.php');
?>
