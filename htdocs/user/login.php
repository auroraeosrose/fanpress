<?php
/**
 * login.php - manages user login functions
 *
 * checks login, logs user out, manages registration, confirmation, and lost passwords
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: login.php,v 1.2 2004/07/28 20:37:49 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

function login()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$user = $session->get('user', 'user');
	if(!empty($user))
	header('Location: index.php');
	//check for successful login
	if(isset($_POST['submit']) and (empty($_POST['name']) or empty($_POST['password'])))
	{
		$tpl['error'] = 'You must enter a username and a password.';
	}
	elseif(isset($_POST['submit']))
	{
		$result = $db->query('SELECT user_id AS id, user_name AS name, user_password AS password, user_level AS level, user_valid AS valid FROM user WHERE user_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' LIMIT 1');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$user = $result->fetch_assoc();
		$result->close();
		if(is_null($user))
		{
			$tpl['error'] = 'That user does not exist.';
		}
		elseif($user['valid'] == 0)
		{
			$tpl['error'] = 'That user account has not been activated.';
		}
		elseif(strcmp(md5($_POST['password']), $user['password']))
		{
			$tpl['error'] = 'That password is not correct.';
		}
		else
		{
			$session->set($user['id'], 'user', 'user');
			$session->set($user['name'], 'username', 'user');
			if($user['level'] > 0)
			{
				$session->set(TRUE, 'author', 'user');
			}
			if($user['level'] > 1)
			{
				$session->set(TRUE, 'editor', 'user');
			}
			if($user['level'] > 2)
			{
				$session->set(TRUE, 'admin', 'user');
			}
			//safety thingy
			$session->regen();
			$session->pause();
			//print_r($session->uncompress($_SESSION['phpff']));
			header('Location: index.php');
			die;
		}
	}
	//page assignments
	$tpl['title'] = 'Login';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library user login account';
	$tpl['description'] = 'Fanfiction Library User Login';
	//assign sub "template"
	$files['page'] = 'login.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function register()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$user = $session->get('user', 'user');
	if(!empty($user))
	header('Location: index.php');
	//stupid net4 gets a free ride
	if(preg_match('/Mozilla\/4.\d[1-9]/' , $_SERVER['HTTP_USER_AGENT']) and isset($_GET['agree']) and $_GET['agree'] == 1)
	$_POST['agree'] = 1;
	//check for successful disclaimer
	if(isset($_POST['agree']))
	{
		//basic checking
		if(isset($_POST['submit']))
		{
			if((empty($_POST['name']) or empty($_POST['password1']) or empty($_POST['password2']) or empty($_POST['email']) or empty($_POST['month']) or empty($_POST['day']) or empty($_POST['year'])))
			{
				$tpl['error'] = 'All fields are required.  Please fill out every field.';
			}
			elseif(strlen($_POST['name']) < 3)
			{
				$tpl['error'] = 'User names must be at least 3 characters long.';
			}
			elseif(strlen($_POST['password1']) < 6)
			{
				$tpl['error'] = 'Passwords must be at least 3 characters long.';
			}
			elseif(strcmp($_POST['password1'], $_POST['password2']))
			{
				$tpl['error'] = 'The two passwords you entered do not match.';
			}
			elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
			{
				$tpl['error'] = 'The email address you entered is not valid.';
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
				$password = md5($_POST['password1']);
				$birthday = $_POST['year'].'-'.$_POST['month'].'-'.$_POST['day'];
				$result = $db->query('INSERT INTO user(user_name, user_password, user_email, user_date, user_birthday, user_hash, user_order) '
					.'VALUES(\''.mysqli_real_escape_string($db, $_POST['name']).'\',\''.$password.'\',\''.mysqli_real_escape_string($db, $_POST['email']).'\',NOW(),\''.$birthday.'\', \''.$hash.'\', \''.$letter.'\')');
				if(!$result)
				{
					printf('Errormessage: %s', $db->error);
				}
				$config = get_config();
				ob_start();
				include('../../data/tpl/'.$config['theme'].'/email/new.txt');
				$message = ob_get_clean();
				$headers = '';
				$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
					.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
				mail($_POST['email'], 'Activate Your New Account', $message, $headers);
				$tpl['error'] = 'An email has been sent to the account you entered.  Follow the directions inside to confirm your account.';
			}
		}
		//page assignments
		$tpl['title'] = 'Register';
		$tpl['nest'] = '../';
		$tpl['keywords'] = 'fanfiction library user login account register';
		$tpl['description'] = 'Fanfiction Library User Account Request';
		//assign sub "template"
		$files['page'] = 'register.html';
		//create sidebar
		include('../../lib/sidebar.php');
		//show the "template"
		show_tpl($tpl, $files);
		return;
	}
	else
	{
		//page assignments
		$tpl['title'] = 'Disclaimer';
		$tpl['nest'] = '../';
		$tpl['keywords'] = 'fanfiction library user login account register disclaimer';
		$tpl['description'] = 'Fanfiction Library User Account Request Disclaimer';
		//assign sub "template"
		$files['page'] = 'disclaimer.html';
		//create sidebar
		include('../../lib/sidebar.php');
		//show the "template"
		show_tpl($tpl, $files);
		return;
	}
}

function confirmation()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$user = $session->get('user', 'user');
	if(!empty($user))
	header('Location: index.php');
	//check for submit
	if(isset($_POST['submit']))
	{
		if(empty($_POST['name']) or empty($_POST['password']) or empty($_POST['code']))
		{
			$tpl['error'] = 'All fields are required.  Please fill out every field.';
		}
		else
		{
			//now check username, password, and hash
			$result = $db->query('SELECT user_id AS id, user_name AS name, user_password AS password, user_level AS level, user_hash AS hash FROM user WHERE user_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\'');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$user = $result->fetch_assoc();
			$result->close();
			if(is_null($user))
			{
				$tpl['error'] = 'That username is not correct, please use the name provided in the email message.  Remember it is case sensitive.';
			}
			elseif(strcmp(md5($_POST['password']), $user['password']))
			{
				$tpl['error'] = 'That password is not correct.';
			}
			elseif(strcmp($_POST['code'], $user['hash']))
			{
				$tpl['error'] = 'That confirmation code is not correct.';
			}
			else
			{
				$result = $db->query('UPDATE user SET user_valid=1, user_hash=NULL WHERE user_id='.$user['id']);
				if(!$result)
				{
					printf('Errormessage: %s', $db->error);
				}
				$session->set($user['id'], 'user', 'user');
				$session->set($user['name'], 'username', 'user');
				if($user['level'] > 0)
				{
					$session->set(TRUE, 'author', 'user');
				}
				if($user['level'] > 1)
				{
					$session->set(TRUE, 'editor', 'user');
				}
				if($user['level'] > 2)
				{
					$session->set(TRUE, 'admin', 'user');
				}
				//safety thingy
				$session->regen();
				$session->pause();
				die(header('Location: index.php'));
			}
		}
	}
	//page assignments
	$tpl['title'] = 'Confirmation';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library user login account register confirm';
	$tpl['description'] = 'Fanfiction Library User Account Request Confirmation';
	//assign sub "template"
	$files['page'] = 'confirm.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function lost_password()
{
	function random_pass()
	{
		$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz0123456789';
		srand((double)microtime()*1000000);
		$i = 0;
		$pass = '' ;
		while ($i <= 7)
		{
			$num = rand() % 33;
			$tmp = substr($chars, $num, 1);
			$pass = $pass.$tmp;
			$i++;
		}
		return $pass;
	}
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$user = $session->get('user', 'user');
	if(!empty($user))
	header('Location: index.php');
	//check for submit
	if(isset($_POST['submit']))
	{
		if(empty($_POST['email']) or (!isset($_POST['password']) and !isset($_POST['name'])))
		{
			$tpl['error'] = 'You must enter your email address and choose to have your password, username, or both sent.';
		}
		else
		{
			//good email address?
			$result = $db->query('SELECT user_id AS id, user_name AS name FROM user WHERE user_email=\''.mysqli_real_escape_string($db, $_POST['email']).'\' AND user_valid=1');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$user = $result->fetch_assoc();
			$result->close();
			if(is_null($user))
			{
				$tpl['error'] = 'There is no confirmed user at this site with that email address.';
			}
			else
			{
				//is password set? make a new password
				if(isset($_POST['password']))
				{
					$newpass = random_pass();
					$result = $db->query('UPDATE user SET user_password=\''.md5($newpass).'\' WHERE user_id='.$user['id']);
					if(!$result)
					{
						printf('Errormessage: %s', $db->error);
					}
				}
				//get the message
				$config = get_config();
				ob_start();
				include('../../data/tpl/'.$config['theme'].'/email/lost.txt');
				$message = ob_get_clean();
				$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
					.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
				mail($_POST['email'], 'Lost Information', $message, $headers);
				$tpl['error'] = 'An email has been sent to the account you entered with the requested information.  If you requested your password, remember that a new password will be created.';
			}
		}
	}
	//page assignments
	$tpl['title'] = 'Confirmation';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library user login account register confirm';
	$tpl['description'] = 'Fanfiction Library User Account Request Confirmation';
	//assign sub "template"
	$files['page'] = 'lost.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function logout()
{
	$session = get_session();
	//if we're NOT logged in, shove it
	$user = $session->get('user', 'user');
	if(empty($user))
	header('Location: login.php?a=0');
	$session->destroy();
	header('Location: '.$_SERVER['PHP_SELF']);
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('../prepend.php');
$action = !isset($_REQUEST['a']) ? 0 : (int) $_REQUEST['a'];
switch($action)
{
	//logout
	case 4:
		logout();
		break;
	//confirmation form
	case 3:
		confirmation();
		break;
	//lost password
	case 2:
		lost_password();
		break;
	//registration form
	case 1:
		register();
		break;
	//default is the login page
	default:
		login();
		break;
}
include('../append.php');
?>
