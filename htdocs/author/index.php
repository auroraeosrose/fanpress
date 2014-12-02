<?php
/**
 * index.php - authors main page
 *
 * allows author to manage profile
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: index.php,v 1.2 2004/07/28 20:37:48 liz Exp $
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
	//if we're logged in, shove it
	$author = $session->get('author', 'user');
	if(empty($author))
	header('Location: ../user/index.php?a=2');
	//check for submission
	if(isset($_POST['submit']))
	{
		if(strlen($_POST['name']) < 3)
		{
			$tpl['error'] = 'Author names must be at least 3 characters long.';
		}
		//now check username
		$result = $db->query('SELECT COUNT(author_id) AS count FROM author WHERE author_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\' and user_id_fk!='.$session->get('user', 'user'));
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $result->fetch_row();
		$result->close();
		if($total[0] > 0)
		{
			$tpl['error'] = 'That author name is already taken, please choose another.';
		}
		elseif(empty($_POST['email']))
		{
			$tpl['error'] = 'You must enter an email address to change your address.';
		}
		elseif(!preg_match( "/^[-^!#$%&'*+\/=?`{|}~.\w]+@[-a-zA-Z0-9]+(\.[-a-zA-Z0-9]+)+$/", $_POST['email']))
		{
			$tpl['error'] = 'The email address you entered is not valid.';
		}
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE author SET author_name=\''.mysqli_real_escape_string($db, $_POST['name']).'\', author_contact=\''.mysqli_real_escape_string($db, $_POST['email']).'\', '
				.'author_text=\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['text']))).'\' WHERE user_id_fk='.$session->get('user', 'user').' LIMIT 1');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['error'] = 'Your profile information has been changed.';
		}
	}
	//get author information
	$result = $db->query('SELECT author_id AS id, author_date AS date, author_name AS name, author_contact AS email, author_text AS text '
		.'FROM author WHERE user_id_fk=\''.$session->get('user', 'user').'\' LIMIT 1');
	if(!$result)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['authorinfo'] = $result->fetch_assoc();
	$result->close();
	//page assignments
	$tpl['title'] = 'Edit Profile';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library author account edit profile';
	$tpl['description'] = 'Fanfiction Library Author Account Edit Profile';
	//assign sub "template"
	$files['page'] = 'authprofile.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('../prepend.php');
$action = !isset($_REQUEST['a']) ? 0 : 1;
switch($action)
{
	//default is the edit profile page
	default:
		edit_profile();
		break;
}
include('../append.php');
?>
