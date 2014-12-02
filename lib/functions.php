<?php
/**
 * functions.php - holds sitewide basic functions
 *
 * prettymuch a temporary step until everything is OO and modular, baby
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: functions.php,v 1.1 2004/07/28 20:37:49 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   framework
 * @category     lib
 * @filesource
 */

//Check to make sure prepend is only included once
if(defined('PHPFF_FUNCTIONS'))
{
	return;
}
//now for don't reinclude me define
define('PHPFF_FUNCTIONS', TRUE);

//template encapsulation function
function show_tpl(&$referenced_array, $files = NULL)
{
	$db = get_db();
	$config = get_config();
	//plugin functions
	function date_format($string, $format = '%M/%D/%Y')
	{
		if(empty($string))
		{
			return '';
		}
		$array = explode(' ', $string, 2);
		$date = explode('-', $array[0], 3);
		$time = explode(':', $array[1], 3);
		unset($array);
		$replace['%%'] = '%';
		$replace['Y'] = $date[0];
		$replace['M'] = $date[1];
		$replace['D'] = $date[2];
		unset($date);
		if($time[0] > 12)
		{
			$replace['h'] = ($time[0] - 12);
			$replace['p'] = 'p.m.';
		}
		else
		{
			$replace['h'] = $time[0];
			$replace['p'] = 'a.m.';
		}
		$replace['m'] = $time[1];
		$replace['s'] = $time[2];
		unset($time);
		$find = array('%%','%Y','%M','%D','%h','%p','%m','%s');
		return str_replace($find, $replace, $format);
	}
	function group_concat($names, $ids, $page, $variable, $nest, $join = ' ')
	{
		$links = array();
		$names = explode(':', $names);
		$ids = explode(':', $ids);
		foreach($names as $key => $name)
		{
			$links[] = '<a href="'.$nest.$page.$variable.'='.$ids[$key].'">'.$name.'</a>';
		}
		return implode($join, $links);
	}
	function rank_images($decimal, $nest)
	{
		global $config;
		//ranking is a decimal between 0 and 1 - star is a 5 star scale, lets find our stars
		$decimal = round($decimal/2, 1);
		//now we find if we have a half star
		$decimal = explode('.', $decimal);
		if(!isset($decimal[1]))
		$decimal[1] = 0;
		//if the second number is greater than 5, we round it all up
		if($decimal[1] >= 5)
		{
			$decimal[0]++;
			$decimal[1] = '0';
		}
		//otherwise we round to half a star
		elseif($decimal[1] > 0)
		{
			$decimal[1] = '5';
		}
		//smash it back together and cast it
		$stars = (float) implode('.', $decimal);
		if($stars == 0)
		{
			$title = 'Not yet Rated';
		}
		else
		{
			$title = $stars.' Stars';
		}
		$string = '<span class="rankstars" title="'.$title.'">';
		//show yellow stars
		$count = 0;
		while($count < $decimal[0])
		{
			$string .= '<img src="'.$nest.'theme/'.$config['theme'].'/solid.gif" alt="*" />';
			$count++;
		}
		//show half star
		if($decimal[1] == '5')
		{
			$string .= '<img src="'.$nest.'theme/'.$config['theme'].'/half.gif" alt="*" />';
			$string .= '<img src="'.$nest.'theme/'.$config['theme'].'/halfblank.gif" alt="*" />';
			$count++;
		}
		//show gray stars
		while($count < 5)
		{
			$string .= '<img src="'.$nest.'theme/'.$config['theme'].'/blank.gif" alt="*" />';
			$count++;
		}
		return $string.'</span>';
	}
	function email($address, $name = NULL)
	{
		if(is_null($name))
		{
			$name = $address;
		}
		//stupid ns4
		if(!(strpos($_SERVER['HTTP_USER_AGENT'], 'Mozilla/4') === false))
		{
			$email = '<a href="mailto:'.$address.'">'.$name.'</a>';
			$email_coded = '';
			for($x=0; $x < strlen($email); $x++)
			{
				$email_coded .= '%' . bin2hex($email[$x]);
			}
			return '<script type="text/javascript">document.write(unescape(\''.$email_coded.'\'))</script>';
		}
		$address_coded = '';
		$name_coded = '';
		for($x=0; $x < strlen($address); $x++)
		{
			if(preg_match('!\w!',$address[$x]))
			{
				$address_coded .= '%' . bin2hex($address[$x]);
			}
			else
			{
				$address_coded .= $address[$x];
			}
		}
		for ($x=0; $x < strlen($name); $x++)
		{
			$name_coded .= '&#x' . bin2hex($name[$x]).';';
		}
		return '<a href="mailto:'.$address_coded.'">'.$name_coded.'</a>';
	}
	function format($string)
	{
		$string = preg_replace("/(\r\n|\n|\r)/", "\n", $string);
		$string = preg_replace('/\n?(.+?)(\n\n|\z)/s', "<p>$1</p>\n", $string);
		$string = preg_replace('|(?<!</p>)\s*\n|', "<br />\n", $string);
		$string =  preg_replace("/\*\*(()|.*)\*\*/U", "<strong>$1</strong>", $string);
		return wrap($string);
	}
	function indent($text, $size = 2, $character = ' ')
	{
		return trim(preg_replace('!^!m', str_repeat($character,$size), $text));
	}
	function wrap($string, $cols = 70, $cut = "\n")
	{
		if(strlen($string) < $cols)
		return $string;
		$tag_open = '<';
		$tag_close = '>';
		$white_space = ' ';
		$count = 0;
		$in_tag = 0;
		$str_len = strlen($string);
		$segment_width = 0;
		$last_white_space = 0;
		$begin_of_line = 0;
		for($i=0 ; $i <= $str_len - 1; $i++)
		{
			if($string[$i] == $tag_open)
			{
				$in_tag++;
			}
			else
			{
				if($string[$i] == $tag_close)
				{
					if($in_tag > 0)
					{
						$in_tag--;
					}
				}
				else
				{
					if($in_tag == 0)
					{
						if($string[$i] != $white_space)
						$segment_width++;
						if($segment_width > $cols)
						{
							if($string[$i] == ' ')
							{
								$string = substr($string, 0, $i).$cut.substr($string,$i+1,$str_len-1);
								$i += strlen($cut);
								$str_len = strlen($string);
								$segment_width = 0;
								$begin_of_line = $i;
								$last_white_space = $i;
							}
							else
							{
								if($last_white_space != $begin_of_line)
								{
									$i = $last_white_space;
								}
								$string = substr($string,0,$i+1).$cut.substr($string,$i+1,$str_len-1);
								$i += strlen($cut);
								$str_len = strlen($string);
								$segment_width = 0;
								$begin_of_line = $i;
								$last_white_space = $i;
							}
						}
						else
						{
							if(isset($string[$i]) and $string[$i] == ' ')
							{
								$last_white_space = $i;
							}
						}
					}
				}
			}
		}
		return $string;
	}
	extract($referenced_array);
	unset($referenced_array);
	if(is_array($files))
	{
		foreach($files as $name => $filename)
		{
			ob_start();
			include(str_replace('lib', 'data/tpl/'.$config['theme'].'/', dirname(__FILE__)).$filename);
			$$name = ob_get_clean();
		}
		unset($name);unset($filename);
	}
	unset($files);
	include(str_replace('lib', 'data', dirname(__FILE__)).'/tpl/'.$config['theme'].'/layout.html');
	//close global db connection
	$db->close();
}

//start by undoing any bad ini settings
function undo_bad_ini()
{
	//undo any registered globals, icky ick
	if((bool) ini_get('register_gobals') === TRUE)
	{
		$safelist = array('_GET', '_POST', '_COOKIE', '_SERVER', '_FILES', '_REQUEST');
		foreach($GLOBALS as $name => $value)
		{
			if(array_search($name, $safelist) === FALSE)
			{
				unset($$name);
			}
		}
		unset($name, $value, $safelist);
	}
	//undo any magic quotes crap
	set_magic_quotes_runtime(0);
	if(get_magic_quotes_gpc())
	{
		//recursive stripslashes
		if(!function_exists('undo_slashes'))
		{
			function undo_slashes(&$array)
			{
			  if(is_array($array))
				  return array_map('undo_slashes', $array);
			  else
				  return stripslashes($array);
			}
		}
		//strip em
		$_GET = array_map('undo_slashes', $_GET);
		$_POST = array_map('undo_slashes', $_POST);
		$_COOKIE = array_map('undo_slashes', $_COOKIE);
	}
	if((bool) ini_get('magic_quotes_sybase') === TRUE)
	{
		//recursive stripslashes
		if(!function_exists('undo_sybase'))
		{
			//recursive strip escape single quotes
			function undo_sybase(&$array)
			{
				if(is_array($array))
					return array_map('undo_sybase', $array);
				else
					return str_replace('\'\'', '\'', $array);
			}
		}
		//strip em
		$_GET = array_map('undo_sybase', $_GET);
		$_POST = array_map('undo_sybase', $_POST);
		$_COOKIE = array_map('undo_sybase', $_COOKIE);
	}
	//undo any ouput buffering
	ini_set('zlib.output_compression', 0);
	ini_set('implicit_flush', 0);
	//clean any existing buffers for sanity
	while(ob_get_level() > 0)
	{
		ob_end_clean();
	}
	return;
}

//gets configuration stuff, globals it...sigh
function get_config()
{
	static $config = array();
	if(!empty($config))
	{
		return $config;
	}
	else
	{
		//now get our config settings
		$config = parse_ini_file(PHPFF_DATA_PATH.'config.ini', TRUE);
		return $config;
	}
}

//gets the template array
function &get_tpl()
{
	if(!isset($GLOBALS['phpff_tpl_var_array']))
	{
		$GLOBALS['phpff_tpl_var_array'] = array();
	}
	return $GLOBALS['phpff_tpl_var_array'];
}

//creates db connection
function get_db()
{
	static $db;
	if(!empty($db))
	{
		return $db;
	}
	else
	{
		$config = get_config();
		if(!empty($config['port']))
		{
			$db = new mysqli($config['host'], $config['user'], $config['password'], $config['database'], $config['port']);
		}
		else
		{
			$db = new mysqli($config['host'], $config['user'], $config['password'], $config['database']);
		}
		if(mysqli_connect_errno())
		{
			printf("Connect failed: %s\n", mysqli_connect_error());
			exit();
		}
		return $db;
	}
}

//creates session object
function get_session($unset = NULL)
{
	static $session;
	if(!is_null($unset))
	{
		unset($session);
	}
	elseif(!empty($session))
	{
		return $session;
	}
	else
	{
		$config = get_config();
		$session = new Phpff_Framework_Session($config['session']);
		return $session;
	}
}

function send_headers()
{
	header('Cache-Control: no-cache, must-revalidate, max_age=0, post-check=0, pre-check=0');
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Content-Type: text/html; charset=UTF-8');
}

//autoload override
function __autoload($class)
{
	//we want an array of class name pieces, all in lower case
	$array = explode('_', strtolower($class));
	//now, the array is expected to have 5 pieces
	if(count($array) != 3)
	{
		trigger_error('Class '.$class.' is not in PACKAGE_MODULE_TYPE(_NAME) format', E_USER_ERROR);
		return FALSE;
	}
	//default type is class
	if(!isset($array[3]))
	{
		$type = 'class';
	}
	else
	{
		$type = strtolower($array[3]);
	}
	//path
	$path = PHPFF_LIB_PATH.$array[1].'/'.$array[2].'.'.$type.'.php';
	if(file_exists($path))
	{
		include($path);
		return TRUE;
	}
	else
	{
		trigger_error('Class '.$class.' does not exist at '.$path, E_USER_ERROR);
		return FALSE;
	}
}

?>
