<?php
/**
 * result.mysql.php - query result class for mysql, lots of fun :)
 *
 * extends abstract result class
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: session.interface.php,v 1.1 2004/07/28 20:37:49 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   framework
 * @category     lib
 * @filesource
 */

/**
 * Mysql Query Result Class - mysql result grabbing
 *
 * can handle buffered or unbuffered queries, parses out subqueries and runs them
 * every time.  too many subqueries and you'll have trouble - optimize
 */

class Phpff_Framework_Result_Mysql extends Phpff_Framework_Result_Abstract
{

	//--------------fetch return type constants-------------

	/**
	 * query string fixed for subqueries
	 * @var string
	 */
	private $parsed;

	/**
	 * array of subqueries to replace
	 * @var array
	 */
	private $subqueries;

	/**
	 * holds if we have a cached row
	 * @var bool
	 */
	private $cache;

	/**
	 * type of cached row
	 * @var string
	 */
	private $cachetype;

	/**
	 * holds cached row
	 * @var int
	 */
	private $cacherow;

//----------------------------------------------------------------
//             Setup Methods
//----------------------------------------------------------------

	/**
	 * public function __construct()
	 *
	 * this will set up a usable query to do, from limit to subquery parsing and doing the query
	 *
	 * @param string $query_string original string of query to use
	 * @param resource &$connection reference to mysql connection resource
	 * @param bool $buffer should the query be buffered or not
	 * @param mixed $type int or string of default fetch type to use
	 * @param int $limit limit the query to this size
	 * @param int $offset offset the query start by these rows
	 * 
	 * @return void
	 */
	function __construct($query_string, &$connection, $buffer, $type, $limit, $offset)
	{
		if(!is_resource($connection))
		{
			Phpff_Core_Error::instance()->push(
				$this->errors[self::RESULT_NORESOURCE],
				self::RESULT_NORESOURCE, 'CRITICAL', 'core', __FILE__, __LINE__);
		}
		$this->connection =& $connection;
		$this->buffer = (bool) $buffer;
		$this->query = (string) $query_string;
		$this->type = $this->gettype($type);
		$this->limit($offset, $limit);
		$this->doquery();
		$this->row = -1;
		$this->field = 0;
		$this->cache = FALSE;
		return;
	}

//----------------------------------------------------------------
//             Iterator Methods
//----------------------------------------------------------------

	/**
	 * public function key()
	 *
	 * returns the current row number - ZERO INDEXED
	 *
	 * @return int
	 */
	public function key()
	{
		return $this->row;
	}

	/**
	 * public function current()
	 *
	 * returns the current row according to the set type
	 *
	 * @return int
	 */
	public function current()
	{
		if($this->cache === FALSE)
		{
			$this->next();
		}
		return $this->cache;
	}

	/**
	 * public function prev()
	 *
	 * seeks to the previous row
	 *
	 * @return bool
	 */
	public function prev()
	{
	}

	/**
	 * public function hasMore()
	 *
	 * checks to see if more rows are available
	 *
	 * @return bool
	 */
	public function hasMore()
	{
	}

	/**
	 * public function hasMore()
	 *
	 * checks to see if more rows are available
	 *
	 * @return bool
	 */
	public function hasLess()
	{
	}

	function next(){}

	/**
	 * public function valid()
	 *
	 * checks to see if there is a current row to return
	 *
	 * @return bool
	 */
	function valid()
	{
		if($this->cache === FALSE)
		{
		}
	}

	/**
	 * public function rewind()
	 *
	 * rewinds the query to the first row - buffered only!
	 *
	 * @return bool
	 */
	function rewind()
	{
		if($this->buffer === TRUE)
		{
			return $this->rowSeek(0);
		}
		else
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() needs a buffered result in order to rewind', E_USER_NOTICE);
			return FALSE;
		}
	}

//----------------------------------------------------------------
//             Seek functions
//----------------------------------------------------------------

	/**
	 * public function fieldSeek()
	 *
	 * sets field offset to desired number
	 *
	 * @param int $field field to seek to
	 * @return bool
	 */
	public function fieldSeek($field = 0)
	{
		//if we don't have a resource, forget it
		if(!is_resource($this->resource))
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() does not have a valid result resource', E_USER_WARNING);
			return FALSE;
		}
		//find a field offset that we can actually do
		if($field < 0 or $field > ($this->numFields - 1))
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() cannot seek before the first field or after the last field', E_USER_NOTICE);
			$field = 0;
		}
		//set it
		$this->field = $field;
		return TRUE;
	}

	/**
	 * public function rowSeek()
	 *
	 * sets row offset to desired number
	 *
	 * @param int $row row to seek to
	 * @return bool
	 */
	public function rowSeek($row = 0)
	{
		//if we don't have a resource, forget it
		if(!is_resource($this->resource))
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() does not have a valid result resource', E_USER_WARNING);
			return FALSE;
		}
		elseif($this->buffer === FALSE)
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() cannot do a row seek on an unbuffered query', E_USER_NOTICE);
			return FALSE;
		}
		//can't seek before zero or after limit
		elseif($row < 0 or $row > $this->limit or $row > ($this->numRows - 1))
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() cannot seek before the first row or after the last row', E_USER_NOTICE);
			$row = 0;
		}
		//this only works if it's buffered, baby
		else
		{
			return mysql_data_seek($this->resource, $row);
		}
	}

//----------------------------------------------------------------
//             Fetch functions
//----------------------------------------------------------------

	/**
	 * public function fetchAll()
	 *
	 * fetch all rows into array according to desired format
	 *
	 * @param mixed $format int or string of constant to change fetch mode to
	 * @param array $array a referenced array of variables to bind into
	 * @return array
	 */
	public function fetchAll($format = NULL, &$array = NULL)
	{
	}

	/**
	 * public function fetch()
	 *
	 * fetch a single row according to desired format
	 * this is similiar to current, only it seeks to the next row
	 *
	 * @param mixed $format int or string of constant to change fetch mode to
	 * @param array $array a referenced array of variables to bind into
	 * @return mixed
	 */
	public function fetch($format = NULL, &$array = NULL)
	{
		if(!is_resource($this->resource))
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() does not have a valid result resource', E_USER_WARNING);
			return FALSE;
		}
		$type = $this->gettype($format);
		//first we check for bind, if we can't bind we'll throw back the default
		if($type == self::FETCH_BIND)
		{
			if(is_null($array))
			{
				trigger_error(__CLASS__.'::'.__METHOD__.'() must have an array of referenced variables to bind', E_USER_WARNING);
				return FALSE;
			}
			$row = mysql_fetch_row($this->resource);
			if(is_array($row) and is_array($array))
			{
				foreach($array as $key => $val)
				{
					$array[$key] = $row[$key];
				}
			}
			return TRUE;
		}
		//or maybe we grab field
		elseif($type == self::FETCH_FIELD)
		{
			$row = mysql_fetch_row($this->resource);
			if(empty($row))
			{
				return FALSE;
			}
			elseif(!isset($row[$this->field]))
			{
				return $row[0];
			}
			else
			{
				return $row[$this->field];
			}
		}
		//or maybe we make an xml stuff
		elseif($type == self::FETCH_XML)
		{
			//first we fetch an assoc row
			$row = mysql_fetch_assoc($this->resource);
			//start the string - use tablename for row
			$table = mysql_field_table($this->resource, 0);
			$xml = '<'.htmlentities($table, ENT_COMPAT).'>'."\n";
			//create the internal xml items by mapping assoc's keys to values
			if(is_array($row))
			{
				foreach($row as $key => $val)
				{
					//notice we escape all entities so xml doesn't die miserably when used
					$xml .= '  <'.htmlentities($key, ENT_COMPAT).'>'."\n".'    '.htmlentities($val, ENT_NOQUOTES)."\n".'  </'.htmlentities($key, ENT_COMPAT).'>'."\n";
				}
			}
			$xml .= '</'.htmlentities($table, ENT_COMPAT).'>';
			return $xml;
		}
		//or we grab an object
		elseif($type == self::FETCH_OBJECT)
		{
			return mysql_fetch_object($this->resource);
		}
		//or we grab a double array
		elseif($type == self::FETCH_BOTH)
		{
			return mysql_fetch_array($this->resource, MYSQL_BOTH);
		}
		//or we grab an assoc array
		elseif($type == self::FETCH_ASSOC)
		{
			return mysql_fetch_assoc($this->resource);
		}
		//or we grab a numeric array
		elseif($type == self::FETCH_ROW)
		{
			return mysql_fetch_row($this->resource);
		}
	}

//----------------------------------------------------------------
//             Misc functions
//----------------------------------------------------------------

	/**
	 * public function limit()
	 *
	 * changes limit and offset for query - should requery and
	 * rewrite query if required.  useful for paging
	 *
	 * @param int $limit size of results to fetch
	 * @param int $offset row offset to start fetching
	 * @return bool
	 */
	public function limit($offset = 0, $limit = -1)
	{
		//if they're null, we set and tag on
		if(is_null($this->limit) and is_null($this->offset))
		{
			$this->limit = $limit;
			$this->offset = $offset;
			if($this->limit < 0)
			{
				$limit = 10000000000000;
			}
			//alter the PARSED query for limit and offset
			$this->parsed .= ' LIMIT '.$offset.', '.$limit;
		}
		//ok, now do we need to change the limit and offset?
		elseif($limit != $this->limit or $offset != $this->offset)
		{
			$this->limit = $limit;
			$this->offset = $offset;
			if($this->limit < 0)
			{
				$limit = 10000000000000;
			}
			//alter the PARSED query for limit and offset
			$this->parsed = preg_replace('(LIMIT\s(?:[0-9]+)[\s]*\,\s*(?:[0-9]+))', 'LIMIT '.$offset.', '.$limit , $this->parsed, 1);
			//requery, urgh
			$this->doquery();
			return;
		}
		else
		{
			return;
		}
	}

	/**
	 * public function free()
	 *
	 * frees current results
	 *
	 * @return bool
	 */
	public function free()
	{
		if(!is_resource($this->resource))
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() does not have a valid result resource', E_USER_WARNING);
			return FALSE;
		}
		else
		{
			return mysql_free_result($this->resource);
		}
	}

//----------------------------------------------------------------
//             Private methods
//----------------------------------------------------------------

	/**
	 * private function doquery()
	 *
	 * actually performs the query
	 *
	 * @return void
	 */
	private function doquery()
	{
		//do any subqueries first
		if(!empty($this->subqueries))
		{
			$format = array();
			foreach($this->subqueries as $key => $string)
			{
				$format[] = $this->dosubquery($string);
				//we only do a subquery once, it does not get redone ever
				unset($this->subqueries[$key]);
			}
			$this->parsed = vsprintf($this->parsed, $format);
		}
		//do query
		if($this->buffer == TRUE)
		{
			$this->resource = mysql_query($this->parsed, $this->connection);
		}
		else
		{
			$this->resource = mysql_unbuffered_query($this->parsed, $this->connection);
		}
		if($this->resource == FALSE)
		{
			$this->errno = mysql_errno($this->connection);
			$this->error = mysql_error($this->connection);
			trigger_error(__CLASS__.'::'.__METHOD__.'() the query '.$this->query.' parsed to '.$this->parsed.' has an error', E_USER_WARNING);
		}
		elseif($this->buffer === TRUE)
		{
			$this->numRows = mysql_num_rows($this->resource);
			$this->numFields = mysql_num_fields($this->resource);
		}
		return;
	}

	/**
	 * private function dosubquery()
	 *
	 * actually performs any subqueries
	 *
	 * @param string $query query string to execute
	 * @return mixed
	 */
	private function dosubquery($query)
	{
		//do query
		$worked = mysql_query($query, $this->connection);
		if($worked === FALSE)
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() the query '.$query.' has an error', E_USER_NOTICE);
			$this->warnings[] = mysql_error($this->connection);
			$this->numWarnings++;
			return NULL;
		}
		elseif(is_resource($worked))
		{
			$results = array();
			while($row = mysql_fetch_row($worked))
			{
				if(is_numeric($row[0]))
				{
					$results[] = $row[0];
				}
				else
				{
					$results[] = '\''.mysql_real_escape_string($row[0], $this->connection).'\'';
				}
			}
			return implode(', ', $results);
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * private function parse()
	 *
	 * parses subqueries and replaces them with proper vsprintf stuff
	 * creates an array of subqueries to run before the main query
	 *
	 * @return void
	 */
	private function parse()
	{
		//explode on a (
		$array = explode('(', $this->query);
		//the zero is the beginning of the query
		array_shift($array);
		//now we check each piece for subquery
		$is_sub = array();
		foreach($array as $key => $string)
		{
			$check = trim($string);
			if(stripos($string, 'select') === 0 or stripos($check, 'update') === 0 or stripos($check, 'delete') === 0 or stripos($check, 'insert') === 0)
			{
				$is_sub[] = $key;
			}
		}
		//set our bases
		$nest = 0;
		$i = 0;
		//loop while checking for subquery
		while($i < count($array) + 1)
		{
			//if it's empty, throw it away
			if(empty($array[$i]))
			{
				unset($array[$i]);
			}
			elseif(!in_array($i, $is_sub))
			{
				//previous in array
				$prev = $i - 1;
				//entire query is added on, but adds a nest
				$query = $array[$prev].'('.$array[$i];
				$nest++;
				unset($array[$i]);
				//now we explode on close and check for certain number of nests
				$end = explode(')', $query);
				//now we start to build the query
				$query = $end[0];
				unset($end[0]);
				foreach($end as $string)
				{
					if($nest > 0)
					{
						$query .= ')'.$string;
						$nest--;
					}
				}
				$array[$prev] = $query;
			}
			else
			{
				$end = explode(')', $array[$i]);
				$array[$i] = $end[0];
			}
			$i++;
		}
		//let's make replacement arrays
		$find = array();
		$replace = array();
		foreach($array as $string)
		{
			if(stripos(trim($string), 'select') === 0)
			{
				$find[] = $string;
				$replace[] = '%s';
			}
			else
			{
				$find[] = '('.$string.')';
				$replace[] = '';
			}
		}
		//now we store the subqueries
		$this->subqueries = $array;
		//then we create a query to use with vsprintf
		$this->parsed = str_ireplace($find, $replace, $this->query);
		return;
	}
}
?>

