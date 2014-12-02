<?php
function list_users()
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
	$tpl['title'] = 'List Users ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, user administrate list';
	$tpl['description'] = 'Fanfiction Library User Administration List';
	//assign sub "template"
	$files['page'] = 'adminuserlist.html';
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
		$where = ' WHERE user_order=\''.$letter.'\' ';
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
	$count = $db->query('SELECT COUNT(user_id) FROM user'.$where);
	if(!$count)
	{
		printf('Errormessage: %s', $db->error);
	}
	$total = $count->fetch_row();
	$tpl['total'] = $total = $total[0];
	$count->close();
	//message for no authors
	if($total < 1)
	$tpl['error'] = 'No Users Found';
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
	$users = $db->query('SELECT user_id AS id, user_name AS name, user_level AS level, user_valid AS valid, user_date AS date FROM user'.$where.' ORDER BY user_name DESC, user_id ASC LIMIT '.$offset.', '.$size);
	if(!$users )
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['users'] =& $users ;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function new_user()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'New User ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, user administrate create new';
	$tpl['description'] = 'Fanfiction Library User Administration Create new';
	//assign sub "template"
	$files['page'] = 'adminusernew.html';
	//basic checking
	if(isset($_POST['submit']))
	{
		if((empty($_POST['name']) or empty($_POST['password']) or empty($_POST['email']) or empty($_POST['month']) or empty($_POST['day']) or empty($_POST['year'])))
		{
			$tpl['error'] = 'All fields are required.  Please fill out every field.';
		}
		elseif(strlen($_POST['name']) < 3)
		{
			$tpl['error'] = 'User names must be at least 3 characters long.';
		}
		elseif(strlen($_POST['password']) < 6)
		{
			$tpl['error'] = 'Passwords must be at least 3 characters long.';
		}
		elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
		{
			$tpl['error'] = 'The email address you entered is not valid.';
		}
		elseif(!is_numeric($_POST['year']) or !is_numeric($_POST['day']) or !is_numeric($_POST['month']))
		{
			$tpl['error'] = 'Enter the month as a number between 01 and 12, the day as a number between 01 and 31, and the year as a four digit number e.g. 1987';
		}
		elseif(checkdate(settype($_POST['month'], 'int'), settype($_POST['day'], 'int'), settype($_POST['year'], 'int')) == FALSE)
		{
			$tpl['error'] = 'The birthdate you entered is not valid.';
		}
		//now check email and username
		$result = $db->query('SELECT COUNT(user_id) AS count FROM user WHERE user_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'That username is already taken, please choose another.';
		}
		$result = $db->query('SELECT COUNT(user_id) AS count FROM user WHERE user_email=\''.mysqli_real_escape_string($db, $_POST['email']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'That email address is already taken, please choose another.';
		}
		if(isset($_POST['submit']) and !isset($tpl['error']))
		{
			$letter = str_split($_POST['name']);
			$letter = strtoupper($letter[0]);
			$hash = md5(uniqid());
			$password = md5($_POST['password']);
			$birthday = $_POST['year'].'-'.$_POST['month'].'-'.$_POST['day'];
			$result = $db->query('INSERT INTO user(user_name, user_password, user_email, user_date, user_birthday, user_hash, user_order) '
					.'VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.$password.'\',\''.mysqli_real_escape_string($db, $_POST['email']).'\',NOW(),\''.$birthday.'\', \''.$hash.'\', \''.$letter.'\')');			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['error'] = 'A new user has been created, the edit page allows you to change and/or validate the user.';
		}
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function edit_user()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Edit User ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, user administrate edit';
	$tpl['description'] = 'Fanfiction Library user Administration Edit';
	//assign sub "template"
	$files['page'] = 'adminuseredit.html';
	//manage favorites
	if(isset($_REQUEST['fid']))
	{
		//delete book favorite
		if(isset($_GET['dbid']))
		{
			$delete = $db->query('DELETE FROM usertobook WHERE usertobook_id='.$_GET['dbid'].' LIMIT 1');
		}
		//author delete
		elseif(isset($_GET['daid']))
		{
			$delete = $db->query('DELETE FROM usertoauthor WHERE usertoauthor_id='.$_GET['daid'].' LIMIT 1');
		}
		//book edit info
		elseif(isset($_GET['bid']))
		{
			$edit = $db->query('SELECT usertobook_id AS id, usertobook_comment AS comment, \'book\' AS book FROM usertobook WHERE usertobook_id='.$_GET['bid'].' LIMIT 1');
			$tpl['edit'] = $edit->fetch_assoc();
			$edit->close();
		}
		//author edit info
		elseif(isset($_GET['aid']))
		{
			$edit = $db->query('SELECT usertoauthor_id AS id, usertoauthor_comment AS comment FROM usertoauthor WHERE usertoauthor_id='.$_GET['aid'].' LIMIT 1');
			$tpl['edit'] = $edit->fetch_assoc();
			$edit->close();
		}
		//edit comment
		elseif(isset($_POST['editfav']))
		{
			//just update it
			if(isset($_POST['bid']))
			{
				$result = $db->query('UPDATE usertobook SET usertobook_comment=\''.$db->real_escape_string(htmlentities(strip_tags($_POST['text']))).'\' WHERE usertobook_id='.$_POST['bid']);
			}
			else
			{
				$result = $db->query('UPDATE usertoauthor SET usertoauthor_comment=\''.$db->real_escape_string(htmlentities(strip_tags($_POST['text']))).'\' WHERE usertoauthor_id='.$_POST['aid']);
			}
		}
		//get all the favorites
		$bookfavs = $db->query('SELECT usertobook_id AS id, usertobook_comment AS bookcomments, book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
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
			.'WHERE book_valid=1 AND usertobook.user_id_fk='.$_REQUEST['fid'].' GROUP BY book.book_id ORDER BY book_update');
		if(!$bookfavs)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['booktotal'] = $bookfavs->num_rows;
		$tpl['bookfavs'] =& $bookfavs;
		$authorfavs = $db->query('SELECT usertoauthor_id AS id, usertoauthor_comment AS comment, author_id AS authid, author_name AS name, author_count AS books, author_date AS date '
			.'FROM author LEFT JOIN usertoauthor ON author_id_fk=author_id WHERE usertoauthor.user_id_fk='.$_REQUEST['fid'].' and author_valid=1 ORDER BY author_name ASC');
		if(!$authorfavs)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['authortotal'] = $authorfavs->num_rows;
		$tpl['authorfavs'] =& $authorfavs;
	}
	//first of all, we grab and show a message if we have an id
	if(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		//grab the announcement
		$result = $db->query('SELECT user_id AS id, user_name AS name, user_birthday AS birthday, user_email AS email, user_date AS date, '
			.'user_website AS website, user_aim AS aim, user_yim AS yim, user_icq AS icq, user_msn AS msn, user_gender AS gender, user_biography AS biography, '
			.'user_order AS `order`, user_level AS level, user_valid AS valid FROM user WHERE user_id='.$id.' LIMIT 1');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['result'] = $result->fetch_assoc();
		$result->close();
	}
	if(isset($_POST['submit']))
	{
		if(!empty($_POST['password']))
		{
			$password = 'user_password=\''.md5($password).'\', ';
		}
		else
		{
			$password = '';
		}
		if(!isset($_POST['valid']))
		$_POST['valid'] = 0;
		$birthday = $_POST['year'].'-'.$_POST['month'].'-'.$_POST['day'];
		function clean_db_input($string, &$db)
		{
			return mysqli_real_escape_string($db, htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $string))));
		}
		//this gets yucky, we need to check the current level and adjust accordingly -
		$result = $db->query('SELECT user_level FROM user WHERE user_id='.$_POST['id']);
		$level = $result->fetch_row();
		$level = $level[0];
		$result->close();
		//ok, now, if we are NOt same...
		if($level != $_POST['level'])
		{
			//if we were at 0 and our new level is higher, we have to make an author
			if($level == 0 and $_POST['level'] > 0)
			{
				//get user name and email address
				$result = $db->query('SELECT user_name AS name, user_email AS email, user_order AS `order` FROM user WHERE user_id='.$_POST['id']);
				$tmp = $result->fetch_assoc();
				$result->close();
				//make the author
				$new = $db->query('INSERT INTO author(author_name, author_contact, user_id_fk, author_file, author_date, author_valid, author_order) '
					.'VALUES(\''.$db->real_escape_string($tmp['name']).'\','.$db->real_escape_string($tmp['email']).'\', '.$_POST['id'].', \'Administrative Author\', NOW(), 1, \''.$tmp['order'].'\')');
			}
			//now, if we were at 2 or higher and our new level is lower, we must remove features
			if($level > 1 and $_POST['level'] < $level)
			{
				//delete any features
				$db->query('DELETE FROM featured WHERE user_id_fk='.$_POST['id']);
			}
			//now, if we were at 1 or higher and our new level is lower, we're deleting an author and everything else associated with it
			if($level > 0 and $_POST['level'] < $level)
			{
				//get the author id
				$result = $db->query('SELECT author_id FROM author WHERE user_id_fk='.$_POST['id']);
				if(!$result)
				{
					printf('Errormessage: %s', $db->error);
				}
				$aid = $result->fetch_row();
				$aid = $aid[0];
				$result->close();
				//now we get every book id using subquery to weed out books with other authors
				$result = $db->query('SELECT book_id_fk FROM booktoauthor WHERE book_id_fk NOT IN (SELECT book_id_fk FROM booktoauthor WHERE author_id_fk!='.$aid.') AND author_id_fk='.$aid);
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
					$delete = $db->query('DELETE FROM booktogenre WHERE '.$where);
					$delete = $db->query('DELETE FROM booktocharacter WHERE '.$where);
					$delete = $db->query('DELETE FROM booktowarning WHERE '.$where);
					$delete = $db->query('DELETE FROM chapter WHERE '.$where);
					//now we crawl the book folder and delete it
					$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__))).'/';
					foreach($book_ids as $id)
					{
						//unlink all files inside
						$handle = opendir($path.$id);
						while(FALSE!==($file = readdir($handle)))
						{
							if($file != '.' and $file != '..')
							unlink($path.'/'.$file);
						}
						closedir($handle);
						rmdir($path.$id);
					}
					//delete the books
					$delete = $db->query('DELETE FROM book WHERE book_id='.implode(' OR book_id=', $book_ids));
				}
				//delete the author
				$delete = $db->query('DELETE FROM author WHERE author_id='.$aid);
			}
		}
		$result = $db->query('UPDATE user SET '.$password.'user_name=\''.clean_db_input($_POST['name'], $db).'\', user_email=\''.clean_db_input($_POST['email'], $db).'\', '
			.'user_birthday=\''.$birthday.'\', user_website=\''.clean_db_input($_POST['website'], $db).'\', user_aim=\''.clean_db_input($_POST['aim'], $db).'\', '
			.'user_icq=\''.clean_db_input($_POST['icq'], $db).'\', user_msn=\''.clean_db_input($_POST['msn'], $db).'\', user_yim=\''.clean_db_input($_POST['yim'], $db).'\', '
			.'user_gender='.$_POST['gender'].', user_biography=\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['biography']))).'\', '
			.'user_valid='.$_POST['valid'].', user_level='.$_POST['level'].', user_order=\''.clean_db_input($_POST['order'], $db).'\' WHERE user_id='.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now send if we need to
		if(isset($_POST['send']))
		{
			$hash = md5(uniqid());
			$result = $db->query('UPDATE user SET user_hash=\''.$hash.'\', user_valid=0 WHERE user_id='.$id);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			ob_start();
			$config = get_config();
			include('../../data/tpl/'.$config['theme'].'/email/email.txt');
			$message = ob_get_clean();
			$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
				.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
			mail($_POST['email'], 'Activate Your New Account', $message, $headers);
		}
		$tpl['error'] = 'This user\'s account has been changed.';
		//grab the announcement
		$result = $db->query('SELECT user_id AS id, user_name AS name, user_birthday AS birthday, user_email AS email, user_date AS date, '
			.'user_website AS website, user_aim AS aim, user_yim AS yim, user_icq AS icq, user_msn AS msn, user_gender AS gender, user_biography AS biography, '
			.'user_order AS `order`, user_level AS level, user_valid AS valid FROM user WHERE user_id='.$_POST['id'].' LIMIT 1');
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
				$where[] = 'YEAR(user_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['month']))
			{
				$tmp = (int) $_POST['month'];
				$where[] = 'MONTH(user_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['day']))
			{
				$tmp = (int) $_POST['day'];
				$where[] = 'DAY(user_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (user_name, user_email) ';
			}
			elseif(isset($_POST['name']) and !isset($_POST['email']))
			{
				$match = 'MATCH (user_name) ';
			}
			elseif(!isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (user_email) ';
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
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, user_date DESC';
			}
			else
			{
				$order = 'ORDER BY user_date DESC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(user_id) FROM user '.$where);
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
			$query = 'SELECT user_id AS id, user_date AS date, user_name AS name, user_valid AS valid, user_level AS level ';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= ' FROM user '.$where.$order.' LIMIT '.$offset.', '.$size;
			$users = $db->query($query);
			if(!$users)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['users'] = &$users;
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
		}
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function delete_user()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Delete Users ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, user administrate delete';
	$tpl['description'] = 'Fanfiction Library User Administration Delete';
	//assign sub "template"
	$files['page'] = 'adminuserdelete.html';
	//do the delete - we have to majorly cascade this
	if(isset($_POST['submit']))
	{
		if(isset($_POST['delete']) and is_array($_POST['delete']))
		{
			$user_ids = array();
			foreach($_POST['delete'] as $id => $junk)
			{
				$user_ids[] = $id;
			}
			//first, for every user_id we need an array of author_ids
			$result = $db->query('SELECT author_id FROM author WHERE user_id_fk='.implode(' OR user_id_fk=', $user_ids));
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$author_ids = array();
			while($row = $result->fetch_row())
			{
				$author_ids[] = $row[0];
			}
			$result->close();
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
						//unlink all files inside
						$handle = opendir($path.$id);
						while(FALSE!==($file = readdir($handle)))
						{
							if($file != '.' and $file != '..')
							unlink($path.'/'.$file);
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
				}
				//delete the authors
				$delete = $db->query('DELETE FROM author WHERE author_id='.implode(' OR author_id=', $author_ids));
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
			}
			//delete the users favorites
			$delete = $db->query('DELETE FROM usertobook WHERE user_id_fk='.implode(' OR user_id_fk=', $user_ids));
			if(!$delete)
			{
				printf('Errormessage: %s', $db->error);
			}
			$delete = $db->query('DELETE FROM usertoauthor WHERE user_id_fk='.implode(' OR user_id_fk=', $user_ids));
			if(!$delete)
			{
				printf('Errormessage: %s', $db->error);
			}
			//delete any featured
			$delete = $db->query('DELETE FROM featured WHERE (user_id_fk='.implode(' OR user_id_fk=', $user_ids).') or (book_id_fk='.implode(' OR book_id_fk=', $book_ids).')');
			//finally, delete the user
			$delete = $db->query('DELETE FROM user WHERE 1=1 AND (user_id='.implode(' OR user_id=', $user_ids).') LIMIT '.count($user_ids));
			if(!$delete)
			{
				printf('Errormessage: %s', $db->error);
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
				$where[] = 'YEAR(user_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['month']))
			{
				$tmp = (int) $_POST['month'];
				$where[] = 'MONTH(user_date)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['day']))
			{
				$tmp = (int) $_POST['day'];
				$where[] = 'DAY(user_date)=\''.$tmp.'\' ';
			}
			//create match
			if(isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (user_name, user_email) ';
			}
			elseif(isset($_POST['name']) and !isset($_POST['email']))
			{
				$match = 'MATCH (user_name) ';
			}
			elseif(!isset($_POST['name']) and isset($_POST['email']))
			{
				$match = 'MATCH (user_email) ';
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
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, user_date DESC';
			}
			else
			{
				$order = 'ORDER BY user_date DESC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(user_id) FROM user '.$where);
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
			$query = 'SELECT user_id AS id, user_date AS date, user_name AS name ';
			if(!empty($against))
			{
				$query .= ', '.$match.$against.' AS relevance';
			}
			$query .= ' FROM user '.$where.$order;
			$users = $db->query($query);
			if(!$users)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['users'] = &$users;
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
		delete_user();
		break;
	//edit an announcement
	case 2:
		edit_user();
		break;
	//new announcement
	case 1:
		new_user();
		break;
	//default is listing announcements
	default:
		list_users();
		break;
}
include('../append.php');
?>
