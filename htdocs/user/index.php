<?php
/**
 * index.php - logged in user account page
 *
 * allows user to manage their profile and favorites, and request upgrade to author account
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: index.php,v 1.2 2004/07/28 20:37:49 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

function edit_profile()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	$user = $session->get('user', 'user');
	if(empty($user))
	header('Location: login.php');
	//check for submission
	if(isset($_POST['submit']))
	{
		if($_POST['form'] == 1)
		{
			if((empty($_POST['password1']) and !empty($_POST['password2'])) or (!empty($_POST['password1']) and empty($_POST['password2'])))
			{
				$tpl['error'] = 'You must enter a new password twice to change your password.';
			}
			elseif(strlen($_POST['name']) < 3)
			{
				$tpl['error'] = 'User names must be at least 3 characters long.';
			}
			elseif(!empty($_POST['password1']) and strlen($_POST['password1']) < 6)
			{
				$tpl['error'] = 'Passwords be at least 3 characters long.';
			}
			elseif(!empty($_POST['password1']) and strcmp($_POST['password1'], $_POST['password2']))
			{
				$tpl['error'] = 'The two passwords you entered do not match.';
			}
			elseif(!is_numeric($_POST['year']) or !is_numeric($_POST['day']) or !is_numeric($_POST['month']))
			{
				$tpl['error'] = 'Enter the month as a number between 01 and 12, the day as a number between 01 and 31, and the year as a four digit number e.g. 1987';
			}
			elseif($_POST['year'] < 1940 or $_POST['year'] > (gmdate('Y') - 13))
			{
				$tpl['error'] = 'You must be at least 13.  If you were born before 1940, please contact the administrator.';
			}
			elseif(checkdate(settype($_POST['month'], 'int'), settype($_POST['day'], 'int'), settype($_POST['year'], 'int')) == FALSE)
			{
				$tpl['error'] = 'The birthdate you entered is not valid.';
			}
			else
			{
				//now check username
				$result = $db->query('SELECT COUNT(user_id) AS count FROM user WHERE user_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and user_id!='.$session->get('user', 'user'));
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
				else
				{
					if(isset($_POST['password1']) and !empty($_POST['password1']))
					{
						$password = ' user_password=\''.md5($_POST['password1']).'\',';
					}
					else
					{
						$password = '';
					}
					$birthday = $_POST['year'].'-'.$_POST['month'].'-'.$_POST['day'];
					$result = $db->query('UPDATE user SET user_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\','.$password.' user_birthday=\''.$birthday.'\' WHERE user_id='.$session->get('user', 'user'));
					if(!$result)
					{
						printf('Errormessage: %s', $db->error);
					}
					$tpl['error'] = 'Your basic information has been changed, you will now be logged out.';
					$tpl['meta'] = '<meta http-equiv="Refresh" content="4;url=login.php?a=4">';
				}
			}
		}
		elseif($_POST['form'] == 2)
		{
			if(empty($_POST['email']))
			{
				$tpl['error'] = 'You must enter an email address to change your address.';
			}
			elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
			{
				$tpl['error'] = 'The email address you entered is not valid.';
			}
			else
			{
				$hash = md5(uniqid());
				$result = $db->query('UPDATE user SET user_valid=0, user_email=\''.mysqli_real_escape_string($db, $_POST['email']).'\', user_hash=\''.$hash.'\' WHERE user_id=\''.mysqli_real_escape_string($db, $session->get('user', 'user')).'\'');
				if(!$result)
				{
					printf('Errormessage: %s', $db->error);
				}
				$config = get_config();
				ob_start();
				include('../../data/tpl/'.$config['theme'].'/email/email.txt');
				$message = ob_get_clean();
				$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
					.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
				mail($_POST['email'], 'Activate Your New Email', $message, $headers);
				$tpl['error'] = 'An email has been sent to the account you entered.  Follow the directions inside to confirm your account.  You are now being logged out.';
				$tpl['meta'] = '<meta http-equiv="Refresh" content="3;url=login.php?a=4">';
			}
		}
		else
		{
			if(!empty($_POST['website']) and !preg_match('"^http://"', $_POST['website']))
			{
				$_POST['website'] = 'http://'.$_POST['website'];
			}
			$result = $db->query('UPDATE user SET user_website=\''.mysqli_real_escape_string($db, $_POST['website']).'\', user_aim=\''.mysqli_real_escape_string($db, $_POST['aim']).'\', '
				.'user_icq=\''.mysqli_real_escape_string($db, $_POST['icq']).'\', user_msn=\''.mysqli_real_escape_string($db, $_POST['msn']).'\', user_yim=\''.mysqli_real_escape_string($db, $_POST['yim']).'\', '
				.'user_biography=\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['biography']))).'\', user_gender='.settype($_POST['gender'], 'int').' WHERE user_id='.$session->get('user', 'user'));
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['error'] = 'Your profile information has been changed.';
		}
	}
	//get user information
	$result = $db->query('SELECT user_id AS id, user_date AS date, user_name AS name, user_birthday AS birthday, user_website as website, user_email AS email, '
		.'user_aim AS aim, user_icq AS icq, user_yim AS yim, user_msn AS msn, user_biography AS biography, user_gender AS gender '
		.'FROM user WHERE user_id=\''.mysqli_real_escape_string($db, $session->get('user', 'user')).'\' LIMIT 1');
	if(!$result)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['userinfo'] = $result->fetch_assoc();
	$result->close();
	//page assignments
	$tpl['title'] = 'Edit Profile';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library user account edit profile';
	$tpl['description'] = 'Fanfiction Library User Account Edit Profile';
	//assign sub "template"
	$files['page'] = 'profile.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function manage_favorites()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	$user = $session->get('user', 'user');
	if(empty($user))
	header('Location: login.php');
	//delete any right away
	if(isset($_GET['did']))
	{
		//book delete
		if(isset($_GET['b']))
		{
			$delete = $db->query('DELETE FROM usertobook WHERE usertobook_id='.$_GET['did'].' LIMIT 1');
		}
		//author delete
		else
		{
			$delete = $db->query('DELETE FROM usertoauthor WHERE usertoauthor_id='.$_GET['did'].' LIMIT 1');
		}
	}
	elseif(isset($_GET['id']))
	{
		//book edit info
		if(isset($_GET['b']))
		{
			$edit = $db->query('SELECT usertobook_id AS id, usertobook_comment AS comment, \'book\' AS book FROM usertobook WHERE usertobook_id='.$_GET['id'].' LIMIT 1');
		}
		//author edit info
		else
		{
			$edit = $db->query('SELECT usertoauthor_id AS id, usertoauthor_comment AS comment FROM usertoauthor WHERE usertoauthor_id='.$_GET['id'].' LIMIT 1');
		}
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
		//just update it
		if(isset($_POST['b']))
		{
			$result = $db->query('UPDATE usertobook SET usertobook_comment=\''.$db->real_escape_string(htmlentities(strip_tags($_POST['text']))).'\' WHERE usertobook_id='.$_POST['id']);
		}
		else
		{
			$result = $db->query('UPDATE usertoauthor SET usertoauthor_comment=\''.$db->real_escape_string(htmlentities(strip_tags($_POST['text']))).'\' WHERE usertoauthor_id='.$_POST['id']);
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
		.'WHERE book_valid=1 AND usertobook.user_id_fk='.$session->get('user', 'user').' GROUP BY book.book_id ORDER BY book_update');
	if(!$bookfavs)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['booktotal'] = $bookfavs->num_rows;
	$tpl['bookfavs'] =& $bookfavs;
	$authorfavs = $db->query('SELECT usertoauthor_id AS id, usertoauthor_comment AS comment, author_id AS authid, author_name AS name, author_count AS books, author_date AS date '
		.'FROM author LEFT JOIN usertoauthor ON author_id_fk=author_id WHERE usertoauthor.user_id_fk='.$session->get('user', 'user').' and author_valid=1 ORDER BY author_name ASC');
	if(!$authorfavs)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['authortotal'] = $authorfavs->num_rows;
	$tpl['authorfavs'] =& $authorfavs;
	//page assignments
	$tpl['title'] = 'Manage Favorites';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library user account edit profile';
	$tpl['description'] = 'Fanfiction Library User Account Edit Profile';
	//assign sub "template"
	$files['page'] = 'userfavorites.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function upgrade_account()
{
	function undoword($string)
	{
		$badlatin1_cp1252_to_htmlent = array(
			'\x80'=>'&#x20AC;', '\x81'=>'?', '\x82'=>'&#x201A;', '\x83'=>'&#x0192;',
			'\x84'=>'&#x201E;', '\x85'=>'&#x2026;', '\x86'=>'&#x2020;', '\x87'=>'&#x2021;',
			'\x88'=>'&#x02C6;', '\x89'=>'&#x2030;', '\x8A'=>'&#x0160;', '\x8B'=>'&#x2039;',
			'\x8C'=>'&#x0152;', '\x8D'=>'?', '\x8E'=>'&#x017D;', '\x8F'=>'?',
			'\x90'=>'?', '\x91'=>'&#x2018;', '\x92'=>'&#x2019;', '\x93'=>'&#x201C;',
			'\x94'=>'&#x201D;', '\x95'=>'&#x2022;', '\x96'=>'&#x2013;', '\x97'=>'&#x2014;',
			'\x98'=>'&#x02DC;', '\x99'=>'&#x2122;', '\x9A'=>'&#x0161;', '\x9B'=>'&#x203A;',
			'\x9C'=>'&#x0153;', '\x9D'=>'?', '\x9E'=>'&#x017E;', '\x9F'=>'&#x0178;');
		return strtr($string, $badlatin1_cp1252_to_htmlent);
	}
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	$user = $session->get('user', 'user');
	if(empty($user))
	header('Location: login.php');
	//delete request must come first
	if(isset($_POST['delete']))
	{
		$delete = $db->query('DELETE FROM author WHERE user_id_fk='.$session->get('user', 'user').' AND author_valid=0 LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['error'] = 'Your application has been deleted';
	}
	//now we check for application
	$application = $db->query('SELECT author_name AS name, author_contact AS email, author_file AS text FROM author WHERE user_id_fk='.$session->get('user', 'user').' AND author_valid=0 LIMIT 1');
	if(!$application)
	{
		printf('Errormessage: %s', $db->error);
	}
	if($application->num_rows > 0)
	{
		$tpl['edit'] = $application->fetch_assoc();
	}
	$application->close();
	//if isset submit and no edit is found insert a row
	if(isset($_POST['submit']) and !isset($tpl['edit']))
	{
		//check for empties
		if(empty($_POST['name']) or empty($_POST['email']))
		{
			$tpl['error'] = 'You must provide an author name and an email address.';
		}
		//check length
		elseif(strlen($_POST['name']) < 3)
		{
			$tpl['error'] = 'Pen names must be at least 3 characters long.';
		}
		//check name
		else
		{
			$result = $db->query('SELECT COUNT(author_id) AS count FROM author WHERE author_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' AND user_id_fk != '.$session->get('user', 'user'));
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $result->fetch_row();
			$result->close();
			if($total[0] > 0)
			{
				$tpl['error'] = 'That pen name is already taken, please choose another.';
			}
			//check email
			elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
			{
				$tpl['error'] = 'The email address you entered is not valid.';
			}
			//check for text
			elseif(empty($_POST['text']))
			{
				$tpl['error'] = 'You must enter your text in the textarea box.';
			}
			else
			{
				$string = htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))));
			}
			if(isset($string))
			{
				$word_count = count(preg_split('/\W+/', $string, -1, PREG_SPLIT_NO_EMPTY));
				if($word_count < 500)
				{
					$tpl['error'] = 'Your story must be at least 500 words in length.';
				}
			}
		}
		if(!isset($tpl['error']))
		{
			$letter = str_split($_POST['name']);
			$letter = strtoupper($letter[0]);
			$new = $db->query('INSERT INTO author(author_name, author_contact, user_id_fk, author_file, author_date, author_order) VALUES(\''.$db->real_escape_string(htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])))).'\', \''.$_POST['email'].'\', '.$session->get('user', 'user').', \''.$db->real_escape_string(htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))))).'\', NOW(), \''.$letter.'\')');
			if(!$new)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['error'] = 'Your application has been submitted.  You may return at any time to delete or edit your application';
		}
		$application = $db->query('SELECT author_name AS name, author_contact AS email, author_file AS text FROM author WHERE user_id_fk='.$session->get('user', 'user').' AND author_valid=0 LIMIT 1');
		if(!$application)
		{
			printf('Errormessage: %s', $db->error);
		}
		if($application->num_rows > 0)
		{
			$tpl['edit'] = $application->fetch_assoc();
		}
		$application->close();
	}
	//elseif isset submit then we're updating
	elseif(isset($_POST['submit']))
	{
		//check for empties
		if(empty($_POST['name']) or empty($_POST['email']))
		{
			$tpl['error'] = 'You must provide an author name and an email address.';
		}
		//check length
		elseif(strlen($_POST['name']) < 3)
		{
			$tpl['error'] = 'Pen names must be at least 3 characters long.';
		}
		//check name
		else
		{
			$result = $db->query('SELECT COUNT(author_id) AS count FROM author WHERE author_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' AND user_id_fk != '.$session->get('user', 'user'));
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $result->fetch_row();
			$result->close();
			if($total[0] > 0)
			{
				$tpl['error'] = 'That pen name is already taken, please choose another.';
			}
			//check email
			elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
			{
				$tpl['error'] = 'The email address you entered is not valid.';
			}
			//check for text
			elseif(empty($_POST['text']))
			{
				$tpl['error'] = 'You must enter your text in the textarea box.';
			}
			else
			{
				$string = htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))));
			}
			if(isset($string))
			{
				$word_count = count(preg_split('/\W+/', $string, -1, PREG_SPLIT_NO_EMPTY));
				if($word_count < 500)
				{
					$tpl['error'] = 'Your story must be at least 500 words in length.';
				}
			}
		}
		if(!isset($tpl['error']))
		{
			$letter = str_split($_POST['name']);
			$letter = strtoupper($letter[0]);
			$update = $db->query('UPDATE author SET author_name=\''.$db->real_escape_string(htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])))).'\', author_contact=\''.$_POST['email'].'\', author_file=\''.$db->real_escape_string(htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))))).'\', author_date=NOW(), author_order=\''.$letter.'\' WHERE user_id_fk='.$session->get('user', 'user').' LIMIT 1');
			if(!$update)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['error'] = 'Your application has been altered';
		}
		$application = $db->query('SELECT author_name AS name, author_contact AS email, author_file AS text FROM author WHERE user_id_fk='.$session->get('user', 'user').' AND author_valid=0 LIMIT 1');
		if(!$application)
		{
			printf('Errormessage: %s', $db->error);
		}
		if($application->num_rows > 0)
		{
			$tpl['edit'] = $application->fetch_assoc();
		}
		$application->close();
	}
	//preview request
	if(isset($_POST['preview']))
	{
		//check for empties
		if(empty($_POST['name']) or empty($_POST['email']))
		{
			$tpl['error'] = 'You must provide an author name and an email address.';
		}
		//check length
		elseif(strlen($_POST['name']) < 3)
		{
			$tpl['error'] = 'Pen names must be at least 3 characters long.';
		}
		//check name
		else
		{
			$result = $db->query('SELECT COUNT(author_id) AS count FROM author WHERE author_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $result->fetch_row();
			$result->close();
			if($total[0] > 0)
			{
				$tpl['error'] = 'That pen name is already taken, please choose another.';
			}
			//check email
			elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
			{
				$tpl['error'] = 'The email address you entered is not valid.';
			}
			//check for text or upload
			elseif(isset($_FILES['datafile']) and $_FILES['datafile']['error'] == 4 and empty($_POST['text']))
			{
				$tpl['error'] = 'You must either upload a file or enter your text in the textarea box.';
			}
			//check for text and upload
			elseif(isset($_FILES['datafile']) and $_FILES['datafile']['error'] != 4 and !empty($_POST['text']))
			{
				$tpl['error'] = 'You must EITHER upload a file OR enter your text in the textarea box, not both.';
			}
			//if we have an upload
			elseif(isset($_FILES['datafile']) and $_FILES['datafile']['error'] != 4)
			{
				if($_FILES['datafile']['error'] == 1 or $_FILES['datafile']['error']==2)
				{
					$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
				}
				elseif($_FILES['datafile']['error'] == 3)
				{
					$tpl['error'] = 'There was a problem with your upload, please try again.';
				}
				elseif($_FILES['datafile']['size'] < 1)
				{
					$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
				}
				elseif($_FILES['datafile']['type'] != 'text/plain' and $_FILES['datafile']['type'] != 'text/html')
				{
					$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
				}
				elseif(!is_uploaded_file($_FILES['datafile']['tmp_name']))
				{
					$tpl['error'] = 'Your file was not uploaded properly';
				}
				else
				{
					$string = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['datafile']['tmp_name'])))));
				}
			}
			else
			{
				$string = htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))));
			}
			if(isset($string))
			{
				$word_count = count(preg_split('/\W+/', $string, -1, PREG_SPLIT_NO_EMPTY));
				if($word_count < 500)
				{
					$tpl['error'] = 'Your story must be at least 500 words in length.';
				}
			}
		}
		if(!isset($tpl['error']))
		{
			$tpl['edit']['name'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['name'])));
			$tpl['edit']['email'] = $_POST['email'];
			$tpl['edit']['text'] = $string;
		}
	}
	//page assignments
	$tpl['title'] = 'Upgrade Accont';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library user account upgrade request';
	$tpl['description'] = 'Fanfiction Library User Account upgrade request';
	//template
	$files['page'] = 'upgrade.html';
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
	//request author account
	case 2:
		upgrade_account();
		break;
	//manage your favorites
	case 1:
		manage_favorites();
		break;
	//default is the edit profile page
	default:
		edit_profile();
		break;
}
include('../append.php');
?>
