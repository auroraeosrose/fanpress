<?php
function list_authors()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//create abcd list
	$tpl['alphabet'][] = 'NUM';
	$i = 'A';
	for($n = 0; $n < 26; $n++)
	{
		$tpl['alphabet'][] = $i;
		$i++;
	}
	$tpl['alphabet'][] = 'ALL';
	//page assignments
	$tpl['title'] = 'List Authors ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, author administrate list';
	$tpl['description'] = 'Fanfiction Library author Administration List';
	//assign sub "template"
	$files['page'] = 'adminauthorlist.html';
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
		$where = ' WHERE author_order=\''.$letter.'\' ';
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
	//grab announcement count
	$count = $db->query('SELECT COUNT(author_id) FROM author'.$where);
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
	//grab announcements for list
	$users = $db->query('SELECT author_id AS id, author_name AS name, user_id AS uid, user_name AS uname, user_level AS level, author_valid AS valid, author_date AS date FROM author LEFT JOIN user ON user_id_fk=user_id '.$where.' ORDER BY author_name DESC, author_id ASC LIMIT '.$offset.', '.$size);
	if(!$users )
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['authors'] =& $users ;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function new_author()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'New Author ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, author administrate create new';
	$tpl['description'] = 'Fanfiction Library Author Administration Create new';
	//assign sub "template"
	$files['page'] = 'adminauthornew.html';
	//basic checking
	if(isset($_POST['submit']))
	{
		if((empty($_POST['uname']) or empty($_POST['aname']) or empty($_POST['email'])))
		{
			$tpl['error'] = 'All fields are required.  Please fill out every field.';
		}
		elseif(strlen($_POST['aname']) < 3)
		{
			$tpl['error'] = 'Author names must be at least 3 characters long.';
		}
		elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
		{
			$tpl['error'] = 'The email address you entered is not valid.';
		}
		//now check username
		$result = $db->query('SELECT user_id, user_level FROM user WHERE user_name=\''.mysqli_real_escape_string($db, $_POST['uname']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->num_rows;
		if($total < 1)
		{
			$tpl['error'] = 'That username does not exist, please choose a valid username';
		}
		else
		{
			$id = $result->fetch_row();
			$level = $id[1];
			$id = $id[0];
		}
		$result->close();
		if(isset($id))
		{
			//now is username already author?
			$result = $db->query('SELECT author_id FROM author WHERE author_id='.$id);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $result->num_rows;
			$result->close();
			if($total > 0)
			{
				$tpl['error'] = 'That user is already an author.';
			}
		}
		if(isset($_POST['submit']) and !isset($tpl['error']))
		{
			$letter = str_split($_POST['aname']);
			$letter = strtoupper($letter[0]);
			$result = $db->query('INSERT INTO author(author_name, author_contact, user_id_fk, author_date, author_order, author_file) '
					.'VALUES(\''.mysqli_real_escape_string($db, $_POST['aname']).'\',\''.mysqli_real_escape_string($db, $_POST['email']).'\', '.$id.', NOW(),\''.$letter.'\', \'Administratively Created Author\')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			if($level > 1)
			{
				$result = $db->query('UPDATE user SET user_level=1 WHERE user_id='.$id);
			}
			$tpl['error'] = 'A new author has been created, the edit page allows you to change and/or validate the author.';
		}
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function edit_author()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Edit Author ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, author administrate edit';
	$tpl['description'] = 'Fanfiction Library author Administration Edit';
	//assign sub "template"
	$files['page'] = 'adminauthoredit.html';
	//first of all, we grab and show a message if we have an id
	if(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		//grab the announcement
		$result = $db->query('SELECT author_id AS id, author_count AS `count`, author_name AS name, user_id AS uid, user_name AS uname, author_contact AS email, author_date AS date, '
			.'author_valid AS valid, author_file AS file, author_order AS `order`, author_text AS `text` FROM author LEFT JOIN user ON user_id_fk=user_id WHERE author_id='.$id.' LIMIT 1');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['result'] = $result->fetch_assoc();
		$result->close();
	}
	if(isset($_POST['submit']))
	{
		if(!isset($_POST['valid']))
		{
			$db->query('UPDATE author LEFT JOIN user ON user_id_fk=user_id SET user_level=0, author_valid=0 WHERE author_id='.$_POST['id']);
		}
		else
		{
			$db->query('UPDATE author LEFT JOIN user ON user_id_fk=user_id SET user_level=1, author_valid=1, author_date=NOW() WHERE author_id='.$_POST['id']);
		}
		function clean_db_input($string, &$db)
		{
			return mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $string))));
		}
		$result = $db->query('UPDATE author SET author_name=\''.clean_db_input($_POST['name'], $db).'\', author_contact=\''.clean_db_input($_POST['email'], $db).'\', '
			.'author_file=\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['story']))).'\', '
			.'author_text=\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['text']))).'\', '
			.'author_order=\''.clean_db_input($_POST['order'], $db).'\' WHERE author_id='.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = 'This author\'s account has been changed.';
		//grab the announcement
		$result = $db->query('SELECT author_id AS id, author_count AS `count`, author_name AS name, user_id AS uid, user_name AS uname, author_contact AS email, author_date AS date, '
			.'author_valid AS valid, author_file AS file, author_order AS `order`, author_text AS `text` FROM author LEFT JOIN user ON user_id_fk=user_id WHERE author_id='.$_POST['id'].' LIMIT 1');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['result'] = $result->fetch_assoc();
		$result->close();
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
		if((empty($_POST['string']) or (!isset($_POST['name']) and !isset($_POST['email']))) and (empty($_POST['month']) and empty($_POST['day']) and empty($_POST['year'])))
		{
			$tpl['error'] = 'You must enter text to search for, and choose to search the username, email, or both.  Or you may choose a date combination to search.';
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
			//create match
			if(isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (author_name, author_contact) ';
			}
			elseif(isset($_POST['name']) and !isset($_POST['email']))
			{
				$match = 'MATCH (author_name) ';
			}
			elseif(!isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (author_contact) ';
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
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, author_date DESC';
			}
			else
			{
				$order = 'ORDER BY author_date DESC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(author_id) FROM author '.$where);
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
			$query = 'SELECT author_id AS id, author_name AS name, user_id AS uid, user_name AS uname, user_level AS level, author_valid AS valid, author_date AS date ';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= 'FROM author LEFT JOIN user ON user_id_fk=user_id '.$where.$order.' LIMIT '.$offset.', '.$size;
			$users = $db->query($query);
			if(!$users)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['authors'] = &$users;
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
		}
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function delete_author()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Delete Authors ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, author administrate delete';
	$tpl['description'] = 'Fanfiction Library Author Administration Delete';
	//assign sub "template"
	$files['page'] = 'adminauthordelete.html';
	//do the delete - we have to majorly cascade this
	if(isset($_POST['submit']))
	{
		if(isset($_POST['delete']) and is_array($_POST['delete']))
		{
			$author_ids = array();
			foreach($_POST['delete'] as $id => $junk)
			{
				$author_ids[] = $id;
			}
			if(!empty($author_ids))
			{
				//now we get every book id using subquery to weed out books with other authors
				$result = $db->query('SELECT book_id_fk FROM booktoauthor WHERE book_id_fk NOT IN (SELECT book_id_fk FROM booktoauthor WHERE author_id_fk!='.implode(' AND author_id_fk!=', $author_ids).')  AND (author_id_fk='.implode(' OR author_id_fk=', $author_ids).')');
				if(!$result)
				{
					printf('Errormessage: %s', $db->error);
				}
				$book_ids = array();
				while($row = $result->fetch_row())
				{
					$book_ids[] = $row[0];
				}
				$result->close();
				if(!empty($book_ids))
				{
					//now we make a pretty where clause
					$where = 'book_id_fk='.implode(' OR book_id_fk=', $book_ids);
					//now, we delete from all book dependencies
					$delete = $db->query('DELETE FROM booktoauthor WHERE '.$where);
					if(!$delete)
					{
						printf('Errormessage: %s', $db->error);
					}
					$delete = $db->query('DELETE FROM booktocharacter WHERE '.$where);
					if(!$delete)
					{
						printf('Errormessage: %s', $db->error);
					}
					$delete = $db->query('DELETE FROM booktogenre WHERE '.$where);
					if(!$delete)
					{
						printf('Errormessage: %s', $db->error);
					}
					$delete = $db->query('DELETE FROM booktowarning WHERE '.$where);
					if(!$delete)
					{
						printf('Errormessage: %s', $db->error);
					}
					//now delete le chapters
					$delete = $db->query('DELETE FROM chapter WHERE '.$where);
					if(!$delete)
					{
						printf('Errormessage: %s', $db->error);
					}
					foreach($book_ids as $id)
					{
						$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__))).'/';
						//unlink all files inside
						$handle = opendir($path.$id);
						while(FALSE!==($file = readdir($handle)))
						{
							if($file != '.' and $file != '..')
							unlink($path.$id.'/'.$file);
						}
						closedir($handle);
						rmdir($path.$id);
					}
					//delete the books
					$delete = $db->query('DELETE FROM book WHERE book_id='.implode(' OR book_id=', $book_ids));
					if(!$delete)
					{
						printf('Errormessage: %s', $db->error);
					}
					//delete any featured
					$delete = $db->query('DELETE FROM featured WHERE (user_id_fk='.implode(' OR user_id_fk=', $user_ids).') or (book_id_fk='.implode(' OR book_id_fk=', $book_ids).')');
				}
				//delete the authors
				$delete = $db->query('DELETE FROM author WHERE author_id='.implode(' OR author_id=', $author_ids));
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
			}
			$tpl['error'] = $db->affected_rows.' users were deleted.';
		}
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
		if((empty($_POST['string']) or (!isset($_POST['name']) and !isset($_POST['email']))) and (empty($_POST['month']) and empty($_POST['day']) and empty($_POST['year'])))
		{
			$tpl['error'] = 'You must enter text to search for, and choose to search the username, email, or both.  Or you may choose a date combination to search.';
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
			//create match
			if(isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (author_name, author_contact) ';
			}
			elseif(isset($_POST['name']) and !isset($_POST['email']))
			{
				$match = 'MATCH (author_name) ';
			}
			elseif(!isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (author_contact) ';
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
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, author_date DESC';
			}
			else
			{
				$order = 'ORDER BY author_date DESC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(author_id) FROM author '.$where);
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
			$query = 'SELECT author_id AS id, author_name AS name, user_id AS uid, user_name AS uname, user_level AS level, author_valid AS valid, author_date AS date ';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= 'FROM author LEFT JOIN user ON user_id_fk=user_id '.$where.$order.' LIMIT '.$offset.', '.$size;
			$users = $db->query($query);
			if(!$users)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['authors'] = &$users;
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
		delete_author();
		break;
	//edit an announcement
	case 2:
		edit_author();
		break;
	//new announcement
	case 1:
		new_author();
		break;
	//default is listing announcements
	default:
		list_authors();
		break;
}
include('../append.php');
?>
