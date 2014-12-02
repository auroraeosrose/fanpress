<?php
function list_announcements()
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
		$delete = $db->query('DELETE FROM announcement WHERE announcement_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//page assignments
	$tpl['title'] = 'List Announcements ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, announcement administrate list';
	$tpl['description'] = 'Fanfiction Library Announcement Administration List';
	//assign sub "template"
	$files['page'] = 'adminannouncelist.html';
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
	$count = $db->query('SELECT COUNT(announcement_id) FROM announcement');
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
	$announcements = $db->query('SELECT announcement_title AS title, announcement_date AS date, announcement_id AS id FROM announcement ORDER BY announcement_date DESC, announcement_id ASC LIMIT '.$offset.', '.$size);
	if(!$announcements)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['announcements'] =& $announcements;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function new_announcement()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'New Announcement ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, announcement administrate create new';
	$tpl['description'] = 'Fanfiction Library Announcement Administration Create new';
	//assign sub "template"
	$files['page'] = 'adminannouncenew.html';
	if(isset($_POST['preview']))
	{
		if(empty($_POST['title']) or empty($_POST['text']))
		{
			$tpl['error'] = 'You must enter a title and text to create an announcement.';
		}
		else
		{
			$tpl['preview'] = 1;
			$tpl['a_title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
			$tpl['a_text'] = htmlentities(strip_tags($_POST['text']));
			$tpl['a_date'] = date('Y-m-d h:i:s');
		}
	}
	elseif(isset($_POST['submit']))
	{
		$result = $db->query('INSERT INTO announcement(announcement_title, announcement_text, announcement_date) VALUES(\''.mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])))).'\', \''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['text']))).'\', NOW())');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = 'Your announcement has been created.';
		$tpl['noshow'] = TRUE;
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function edit_announcement()
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
		$delete = $db->query('DELETE FROM announcement WHERE announcement_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//page assignments
	$tpl['title'] = 'Edit Announcement ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, announcement administrate edit';
	$tpl['description'] = 'Fanfiction Library Announcement Administration Edit';
	//assign sub "template"
	$files['page'] = 'adminannounceedit.html';
	//first of all, we grab and show a message if we have an id
	if(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		//grab the announcement
		$announcement = $db->query('SELECT announcement_id AS id, announcement_title AS title, announcement_text AS text, announcement_date AS date FROM announcement WHERE announcement_id='.$id.' LIMIT 1');
		if(!$announcement)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['announcement'] = $announcement->fetch_assoc();
		$announcement->close();
	}
	if(isset($_POST['preview']))
	{
		if(empty($_POST['title']) or empty($_POST['text']))
		{
			$tpl['error'] = 'You must enter a title and text to create an announcement.';
		}
		else
		{
			$tpl['preview'] = 1;
			$tpl['a_title'] = strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title']));
			$tpl['a_text'] = strip_tags($_POST['text']);
			$tpl['a_date'] = date('Y-m-d h:i:s');
		}
	}
	elseif(isset($_POST['submit']))
	{
		$id = (int) $_POST['id'];
		$result = $db->query('UPDATE announcement SET announcement_title=\''.mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])))).'\', announcement_text=\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['text']))).'\' WHERE announcement_id='.$id);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
				//grab the announcement
		$announcement = $db->query('SELECT announcement_id AS id, announcement_title AS title, announcement_text AS text, announcement_date AS date FROM announcement WHERE announcement_id='.$id.' LIMIT 1');
		if(!$announcement)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['announcement'] = $announcement->fetch_assoc();
		$announcement->close();
		$tpl['error'] = 'Your announcement has been changed.';
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
		if((empty($_POST['string']) or (!isset($_POST['title']) and !isset($_POST['text']))) and (empty($_POST['month']) and empty($_POST['day']) and empty($_POST['year'])))
		{
			$tpl['error'] = 'You must enter text to search for, and choose to search the text, title, or both.  Or you may choose a date combination to search.';
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
				$where[] = 'YEAR(announcement_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['month']))
			{
				$tmp = (int) $_POST['month'];
				$where[] = 'MONTH(announcement_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['day']))
			{
				$tmp = (int) $_POST['day'];
				$where[] = 'DAY(announcement_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['title']) and isset($_POST['text']))
			{
				$match = 'MATCH (announcement_title, announcement_text) ';
			}
			elseif(isset($_POST['title']) and !isset($_POST['text']))
			{
				$match = 'MATCH (announcement_title) ';
			}
			elseif(!isset($_POST['title']) and isset($_POST['text']))
			{
				$match = 'MATCH (announcement_text) ';
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
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
		}
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function delete_announcement()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Delete Announcements ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, announcement administrate delete';
	$tpl['description'] = 'Fanfiction Library Announcement Administration Delete';
	//assign sub "template"
	$files['page'] = 'adminannouncedelete.html';
	//do the delete needs to go HERE
	if(isset($_POST['submit']))
	{
		if(isset($_POST['delete']) and is_array($_POST['delete']))
		{
			$where = array();
			foreach($_POST['delete'] as $id => $junk)
			{
				$where[] = 'announcement_id='.$id;
			}
			$where = implode(' OR ', $where);
		}
		$delete = $db->query('DELETE FROM announcement WHERE 1=1 AND '.$where.' LIMIT '.count($_POST['delete']));
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = $db->affected_rows.' announcements were deleted.';
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
		if((empty($_POST['string']) or (!isset($_POST['title']) and !isset($_POST['text']))) and (empty($_POST['month']) and empty($_POST['day']) and empty($_POST['year'])))
		{
			$tpl['error'] = 'You must enter text to search for, and choose to search the text, title, or both.  Or you may choose a date combination to search.';
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
				$where[] = 'YEAR(announcement_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['month']))
			{
				$tmp = (int) $_POST['month'];
				$where[] = 'MONTH(announcement_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['day']))
			{
				$tmp = (int) $_POST['day'];
				$where[] = 'DAY(announcement_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['title']) and isset($_POST['text']))
			{
				$match = 'MATCH (announcement_title, announcement_text) ';
			}
			elseif(isset($_POST['title']) and !isset($_POST['text']))
			{
				$match = 'MATCH (announcement_title) ';
			}
			elseif(!isset($_POST['title']) and isset($_POST['text']))
			{
				$match = 'MATCH (announcement_text) ';
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
		delete_announcement();
		break;
	//edit an announcement
	case 2:
		edit_announcement();
		break;
	//new announcement
	case 1:
		new_announcement();
		break;
	//default is listing announcements
	default:
		list_announcements();
		break;
}
include('../append.php');
?>
