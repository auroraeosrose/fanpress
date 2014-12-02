<?php
/**
 * prepend.php - sets up all important page stuff
 *
 * Sets important paths, starts up registry, sessions, gets autoload, includes going
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: prepend.php,v 1.1 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   framework
 * @category     htdocs
 * @filesource
 */

//Check whether phpff_include is defined, if not we die
if(!defined('PHPFF_INCLUDE'))
{
	die('PREPEND.PHP CANNOT BE ACCESSED DIRECTLY');
}
//Check to make sure prepend is only included once
if(defined('PHPFF_PREPEND'))
{
	return;
}

//now for important defines
define('PHPFF_PREPEND', TRUE);
//sitewide defines, change these if you've moved stuff around
define('PHPFF_LIB_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../lib').'/'));
define('PHPFF_DATA_PATH', str_replace('\\', '/', realpath(dirname(__FILE__).'/../data').'/'));
define('PHPFF_WEB_PATH', str_replace('\\', '/', dirname(__FILE__)));

//include functions page
if(file_exists(PHPFF_LIB_PATH.'functions.php'))
{
	include(PHPFF_LIB_PATH.'functions.php');
}

//start our session
$session = get_session();
$session->start();
//set up empty tpl array and set user/admin
$tpl =& get_tpl();
if(!is_null($session->get('admin', 'user')))
{
	$tpl['admin'] = TRUE;
}
else
{
	$tpl['admin'] = FALSE;
}
if(!is_null($session->get('editor', 'user')))
{
	$tpl['editor'] = TRUE;
}
else
{
	$tpl['editor'] = FALSE;
}
if(!is_null($session->get('author', 'user')))
{
	$tpl['author'] = TRUE;
}
else
{
	$tpl['author'] = FALSE;
}
if(!is_null($session->get('user', 'user')))
{
	$tpl['user'] = $session->get('username', 'user');
}
else
{
	$tpl['user'] = FALSE;
}
?>
