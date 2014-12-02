<?php
function categories()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//recursive sucker to get em all, can be MESSY
	function get_depends($start, $id, $check, &$db)
	{
		$result = $db->query('SELECT category_id AS id FROM category WHERE category_parent='.$id.' and category_id != '.$start);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->num_rows;
		//do we need to check em?
		if($total > 0)
		{
			while($tmp = $result->fetch_row())
			{
				$check[] = $tmp[0];
				$check = get_depends($start, $tmp[0], $check, $db);
			}
		}
		$result->close();
		return $check;
	}
	//delete any right away - remember to cascade it PLUS cascade characters
	if(isset($_GET['did']))
	{
		//create 2 where clauses for all dependencies
		$where = array();
		$where2 = array();
		$ids[] = (int) $_GET['did'];
		$ids = get_depends($_GET['did'], $ids[0], $ids, $db);
		foreach($ids as $id)
		{
			$where[] = 'category_id='.$id;
			$where2[] = 'category_id_fk='.$id;
		}
		$where = implode(' OR ', $where);
		$where2 = implode(' OR ', $where2);
		//set all books with that category equal to 0
		$books = $db->query('UPDATE book SET category_id_fk=0 WHERE '.$where2);
		if(!$books)
		{
			printf('Errormessage: %s', $db->error);
		}
		//then delete the categories
		$delete = $db->query('DELETE FROM category WHERE 1=1 AND '.$where.' LIMIT '.count($ids));
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		$cats = $db->affected_rows;
		//now fetch all character ids
		$result = $db->query('SELECT character_id FROM `character` WHERE '.$where2);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$charids = array();
		while($row = $result->fetch_row())
		{
			$charids[] = 'character_id_fk='.$row[0];
		}
		$result->close();
		$where3 = implode(' OR ', $charids);
		//delete the character mappings
		$delete = $db->query('DELETE FROM booktocharacter WHERE 1=1 AND '.$where3);
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now delete the characters
		$delete = $db->query('DELETE FROM `character` WHERE 1=1 AND '.$where2.' LIMIT '.count($ids));
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = $cats.' categories and '.$db->affected_rows.' characters from those categories were deleted.';
	}
	//page assignments
	$tpl['title'] = 'Manage Categories ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, manage categories administrate list';
	$tpl['description'] = 'Fanfiction Library manage categories Administration List';
	//assign sub "template"
	$files['page'] = 'admincategories.html';
	//create rating
	if(isset($_POST['new']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(category_id) AS count FROM category WHERE category_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a category with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO category(category_name, category_description, category_parent) VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.mysqli_real_escape_string($db, $_POST['description']).'\', '.$_POST['parent'].')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit category
	elseif(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		$edit = $db->query('SELECT category_id AS id, category_name AS name, category_description AS description, category_parent AS catid FROM category WHERE category_id='.$id.' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit category, gets nasty with nesting
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(category_id) AS count FROM category WHERE category_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and category_id != '.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a category with that name, please choose another.';
		}
		//get all sub categories
		$check = get_depends($_POST['id'], $_POST['id'], array(), $db);
		//now, if the parent set is in the check array, we error
		if(in_array($_POST['parent'], $check))
		{
			$tpl['error'] = 'You cannot make a category a sub-category of itself or its children.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE category SET category_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', category_description=\''.mysqli_real_escape_string($db, $_POST['description']).'\', category_parent='.$_POST['parent'].' WHERE category_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab categories for list
	$categories = $db->query('SELECT cat1.category_id AS id, cat1.category_name AS name, cat2.category_id AS catid, cat2.category_name AS catname FROM category AS cat1 LEFT JOIN category AS cat2 ON cat1.category_parent=cat2.category_id ORDER BY cat1.category_name ASC');
	if(!$categories)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['total'] = $categories->num_rows;
	$tpl['categories'] =& $categories;
	//get all the available parents
	$parents = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
	if(!$parents)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['parents'] = &$parents;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function ratings()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Manage Ratings ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, ratings manage administrate';
	$tpl['description'] = 'Fanfiction Library ratings administrate';
	//assign sub "template"
	$files['page'] = 'adminratings.html';
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		//set all books with that rating equal to 0
		$books = $db->query('UPDATE book SET rating_id_fk=0 WHERE rating_id_fk='.$did);
		if(!$books)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now delete the rating
		$delete = $db->query('DELETE FROM rating WHERE rating_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//create rating
	elseif(isset($_POST['new']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(rating_id) AS count FROM rating WHERE rating_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a rating with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO rating(rating_name, rating_description) VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.mysqli_real_escape_string($db, $_POST['description']).'\')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit rating
	elseif(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		$edit = $db->query('SELECT rating_id AS id, rating_name AS name, rating_description AS description FROM rating WHERE rating_id='.$id.' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit rating
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(rating_id) AS count FROM rating WHERE rating_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and rating_id != '.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a rating with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE rating SET rating_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', rating_description=\''.mysqli_real_escape_string($db, $_POST['description']).'\' WHERE rating_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//get all the ratings
	$ratings = $db->query('SELECT rating_id AS id, rating_name AS name FROM rating');
	if(!$ratings)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['total'] = $ratings->num_rows;
	$tpl['ratings'] = &$ratings;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function styles()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Manage Styles ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, styles manage administrate';
	$tpl['description'] = 'Fanfiction Library styles administrate';
	//assign sub "template"
	$files['page'] = 'adminstyles.html';
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		//set all books with that rating equal to 0
		$books = $db->query('UPDATE book SET style_id_fk=0 WHERE style_id_fk='.$did);
		if(!$books)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now delete the rating
		$delete = $db->query('DELETE FROM style WHERE style_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//create rating
	elseif(isset($_POST['new']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(style_id) AS count FROM style WHERE style_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a style with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO style(style_name, style_description) VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.mysqli_real_escape_string($db, $_POST['description']).'\')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit rating
	elseif(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		$edit = $db->query('SELECT style_id AS id, style_name AS name, style_description AS description FROM style WHERE style_id='.$id.' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit rating
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(style_id) AS count FROM style WHERE style_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and style_id != '.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a style with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE style SET style_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', style_description=\''.mysqli_real_escape_string($db, $_POST['description']).'\' WHERE style_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//get all the styles
	$styles = $db->query('SELECT style_id AS id, style_name AS name FROM style');
	if(!$styles)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['total'] = $styles->num_rows;
	$tpl['styles'] = &$styles;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function types()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Manage Types ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, types manage administrate';
	$tpl['description'] = 'Fanfiction Library types administrate';
	//assign sub "template"
	$files['page'] = 'admintypes.html';
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		//set all books with that rating equal to 0
		$books = $db->query('UPDATE book SET type_id_fk=0 WHERE type_id_fk='.$did);
		if(!$books)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now delete the rating
		$delete = $db->query('DELETE FROM type WHERE type_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//create rating
	elseif(isset($_POST['new']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(type_id) AS count FROM type WHERE type_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a type with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO type(type_name, type_description) VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.mysqli_real_escape_string($db, $_POST['description']).'\')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit rating
	elseif(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		$edit = $db->query('SELECT type_id AS id, type_name AS name, type_description AS description FROM type WHERE type_id='.$id.' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit rating
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(type_id) AS count FROM type WHERE type_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and type_id != '.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a type with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE type SET type_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', type_description=\''.mysqli_real_escape_string($db, $_POST['description']).'\' WHERE type_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//get all the types
	$types = $db->query('SELECT type_id AS id, type_name AS name FROM type');
	if(!$types)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['total'] = $types->num_rows;
	$tpl['types'] = &$types;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function characters()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Manage Characters ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, characters manage administrate';
	$tpl['description'] = 'Fanfiction Library characters administrate';
	//assign sub "template"
	$files['page'] = 'admincharacters.html';
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		//delete the character mappings
		$delete = $db->query('DELETE FROM booktocharacter WHERE character_id_fk'.$did);
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now delete the character
		$delete = $db->query('DELETE FROM `character` WHERE character_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//create character
	elseif(isset($_POST['new']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique in cateogry
		$result = $db->query('SELECT COUNT(character_id) AS count FROM `character` WHERE character_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' AND category_id_fk='.$_POST['category']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a character with that name in that category, please choose another name or category.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO `character`(character_name, character_description, category_id_fk) VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.mysqli_real_escape_string($db, $_POST['description']).'\', '.$_POST['category'].')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit character
	elseif(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		$edit = $db->query('SELECT character_id AS id, character_name AS name, character_description AS description, category_id_fk AS catid FROM `character` WHERE character_id='.$id.' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit character
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique in cateogry
		$result = $db->query('SELECT COUNT(character_id) AS count FROM `character` WHERE character_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' AND category_id_fk='.$_POST['category'].' AND character_id!='.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a character with that name in that category, please choose another name or category.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE `character` SET character_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', character_description=\''.mysqli_real_escape_string($db, $_POST['description']).'\', category_id_fk='.$_POST['category'].' WHERE character_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//get all the characters
	$characters = $db->query('SELECT character_id AS id, character_name AS name, category_name AS category, category_id AS catid FROM `character` LEFT JOIN category ON category.category_id=character.category_id_fk ORDER BY category_id_fk ASC');
	if(!$characters)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['total'] = $characters->num_rows;
	$tpl['characters'] = &$characters;
	//get all the categories
	$categories = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
	if(!$categories)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['categories'] = &$categories;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function genres()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Manage Genres ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, genres manage administrate';
	$tpl['description'] = 'Fanfiction Library genres administrate';
	//assign sub "template"
	$files['page'] = 'admingenres.html';
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		//delete the genre mappings
		$delete = $db->query('DELETE FROM booktogenre WHERE genre_id_fk'.$did);
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now delete the genre
		$delete = $db->query('DELETE FROM genre WHERE genre_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//create genre
	elseif(isset($_POST['new']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(genre_id) AS count FROM genre WHERE genre_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a genre with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO genre(genre_name, genre_description) VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.mysqli_real_escape_string($db, $_POST['description']).'\')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit genre
	elseif(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		$edit = $db->query('SELECT genre_id AS id, genre_name AS name, genre_description AS description FROM genre WHERE genre_id='.$id.' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit genre
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(genre_id) AS count FROM genre WHERE genre_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and genre_id != '.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a genre with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE genre SET genre_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', genre_description=\''.mysqli_real_escape_string($db, $_POST['description']).'\' WHERE genre_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//get all the genres
	$genres = $db->query('SELECT genre_id AS id, genre_name AS name FROM genre');
	if(!$genres)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['total'] = $genres->num_rows;
	$tpl['genres'] = &$genres;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function warnings()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Manage Warnings ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, warnings manage administrate';
	$tpl['description'] = 'Fanfiction Library warnings administrate';
	//assign sub "template"
	$files['page'] = 'adminwarnings.html';
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		//delete the warning mappings
		$delete = $db->query('DELETE FROM booktowarning WHERE warning_id_fk'.$did);
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		//now delete the warning
		$delete = $db->query('DELETE FROM warning WHERE warning_id='.$did.' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//create warning
	elseif(isset($_POST['new']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(warning_id) AS count FROM warning WHERE warning_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a warning with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO warning(warning_name, warning_description) VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.mysqli_real_escape_string($db, $_POST['description']).'\')');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit warning
	elseif(isset($_GET['id']))
	{
		$id = (int) $_GET['id'];
		$edit = $db->query('SELECT warning_id AS id, warning_name AS name, warning_description AS description FROM warning WHERE warning_id='.$id.' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit warning
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['name']) or empty($_POST['description']))
		{
			$tpl['error'] = 'A name and description are required.';
		}
		//clean em
		$_POST['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
		$_POST['description'] = htmlentities(strip_tags($_POST['description']));
		//now check names, must be unique
		$result = $db->query('SELECT COUNT(warning_id) AS count FROM warning WHERE warning_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and warning_id != '.$_POST['id']);
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'There is already a warning with that name, please choose another.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE warning SET warning_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', warning_description=\''.mysqli_real_escape_string($db, $_POST['description']).'\' WHERE warning_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//get all the warnings
	$warnings = $db->query('SELECT warning_id AS id, warning_name AS name FROM warning');
	if(!$warnings)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['total'] = $warnings->num_rows;
	$tpl['warnings'] = &$warnings;
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
	//view, edit, add, delete warnings
	case 6:
		warnings();
		break;
	//view, edit, add, delete genres
	case 5:
		genres();
		break;
	//view, edit, add, delete characters
	case 4:
		characters();
		break;
	//view, edit, add, delete types
	case 3:
		types();
		break;
	//view, edit, add, delete styles
	case 2:
		styles();
		break;
	//view, edit, add, delete ratings
	case 1:
		ratings();
		break;
	//view, edit, add, delete categories
	default:
		categories();
		break;
}
include('../append.php');
?>
