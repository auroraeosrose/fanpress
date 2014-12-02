<?php
/**
 * apppend.php - wraps up page stuff
 *
 * Right now all it really does is unset the session, and so store it properly
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: append.php,v 1.1 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   framework
 * @category     htdocs
 * @filesource
 */

//Check whether phpff_include is defined, if not we die
if(!defined('PHPFF_INCLUDE'))
{
	die('APPEND.PHP CANNOT BE ACCESSED DIRECTLY');
}
//check to make sure the append is only included once
if(defined('PHPFF_APPEND'))
{
	return;
}
define('PHPFF_APPEND', TRUE);
get_session(TRUE);
?>
