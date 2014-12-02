<?php
/**
 * statement.abstract.php - Abstract database prepared statement class
 *
 * Contains general functionality that a statement class should have
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
 * Abstract Database Driver Class - Must be extended by a driver
 *
 * This class basically defines the db driver api
 * Basics the db needs to support: operators: =, !=, <=, >=, <, >, IS (NOT) NULL, expr (NOT) BETWEEN min AND max, expr (NOT) IN (values), AND, OR, ()
 * comparison: (NOT) LIKE expr ESCAPE 'char', %(wildcard), _(single character), bitwise: |, &, >>, <<, ~
 * WHERE, ORDER BY ASC | DESC, GROUP BY, HAVING, SELECT with subqueries, INSERT, UPDATE, DELETE with number affected rows
 * ansi joins -  [LEFT | RIGHT] [OUTER | INNER | CROSS] JOIN ON, transactions, foreign keys with on delete
 * anything not supported should be fixed by the driver through ereg/preg/string replace
 * before a query is executed
 */

abstract class Phpff_Framework_Driver_Abstract
{
	//--------------Query type constants-------------

	/**
	 * @const CONNECT normal connection
	 */
	const CONNECT = 1;

	/**
	 * @const PCONNECT persistent connection
	 */
	const PCONNECT = 2;

	/**
	 * @const NCONNECT new connection
	 */
	const NCONNECT = 3;

	//---------Public access variables are set during method calls --------

	/**
	 * Holds last errno
	 * @var int
	 */
	public $errno;

	/**
	 * Holds last error string
	 * @var string
	 */
	public $error;

	/**
	 * Holds currently selected database
	 * @var string
	 */
	public $database;

	//--------------Should be considered private in driver--------------

	/**
	 * Holds db connection resource
	 * @var resource
	 */
	protected $connection;

	/**
	 * Holds current transaction status
	 * @var bool
	 */
	protected $transaction;

	/**
	 * Holds current persistent status
	 * @var bool
	 */
	protected $persistent;

	/**
	 * Holds lob object classname
	 * @var string
	 */
	protected $lobclass;

	/**
	 * Holds result object classname
	 * @var string
	 */
	protected $resultclass;

	/**
	 * Holds exec object classname
	 * @var string
	 */
	protected $execclass;

	/**
	 * Holds statement object classname
	 * @var string
	 */
	protected $statementclass;

	/**
	 * Holds manager object classname
	 * @var string
	 */
	protected $managerclass;

	//--------------settings should be change through init--------------

	/**
	 * autocommit on or off
	 * @var bool
	 */
	protected $autocommit;

	/**
	 * Default fetch type to return
	 * @var int
	 */
	protected $fetch;

	/**
	 * database prefix
	 * @var string
	 */
	public $prefix;

//----------------------------------------------------------------
//             Setup Methods
//----------------------------------------------------------------

	/**
	 * public function __construct()
	 *
	 * constructor should at least call init - be sure to set
	 * the lob/result/manager classes!!
	 *
	 * @param array $options array of options to send to init
	 * @uses $this->init() to set settings
	 * @return void
	 */
	public function __construct($options = NULL)
	{
		$this->init($options);
		return;
	}

	/**
	 * public function init()
	 *
	 * sets or changes connection settings
	 *
	 * @param array $options array of options to send to init
	 * @return void
	 */
	abstract public function init($options);

//----------------------------------------------------------------
//             Connection Methods
//----------------------------------------------------------------

	/**
	 * public function connect()
	 *
	 * connects to a database AND selects a db, or changes connection settings
	 * and reconnects - remember to change $this->database and $this->persistent
	 *
	 * @param string $db new database to connect to
	 * @param mixed $type type of connection to make
	 * @return mixed connection resource id or false on failure
	 */
	abstract public function connect($db = NULL, $type = NULL);

	/**
	 * public function disconnect()
	 *
	 * Disconnects from a database
	 *
	 * @return void
	 */
	abstract public function disconnect();

//----------------------------------------------------------------
//             Transaction Methods
//----------------------------------------------------------------

	/**
	 * public function autocommit()
	 *
	 * Toggles autocommit status
	 *
	 * @param bool $switch turn autocommit on and off
	 * @return void
	 */
	abstract public function autocommit($switch = FALSE);

	/**
	 * public function start()
	 *
	 * starts a transaction
	 *
	 * @return bool true on success
	 */
	abstract public function start();

	/**
	 * public function rollback()
	 *
	 * rolls back a transaction, does not autostart a new one
	 *
	 * @return bool true on success
	 */
	abstract public function rollback();

	/**
	 * public function commit()
	 *
	 * commits a transaction, does not autostart a new one
	 *
	 * @return bool true on success
	 */
	abstract public function commit();

//----------------------------------------------------------------
//             Data Handling Methods
//----------------------------------------------------------------

	/**
	 * public function quote()
	 *
	 * escapes a string to put in db - default is '
	 *
	 * @param string $string string to quote
	 * @return string quoted string
	 */
	public function quote($string)
	{
		return str_replace('\'','\'\'',$string);
	}

	/**
	 * public function identifier()
	 *
	 * escapes an identifier to use in a query - default is "
	 *
	 * @param string $string identifier to escape
	 * @return string escaped string
	 */
	public function identifier($string)
	{
		return '"'.$string.'"';
	}

	/**
	 * public function encode()
	 *
	 * encodes binary information so it can be put in a db field
	 *
	 * @param string $binary binary string to decode
	 * @return string encoded string
	 */
	public function encode($binary)
	{
		return gzcompress(base64_encode($binary));
	}

	/**
	 * public function decode()
	 *
	 * decodes binary information taken from a db field
	 *
	 * @param string $binary string to decode
	 * @return string decoded binary string
	 */
	public function decode($binary)
	{
		return base64_decode(gzuncompress($binary));
	}

//----------------------------------------------------------------
//             get db functions for specific db
//----------------------------------------------------------------

	/**
	 * public function getFunction()
	 *
	 * returns a properly formated sql function for the db
	 * you use this by sending the default function name, and any needed arguments
	 * example: getFunction('round', 'mycolumn', 2)
	 *
	 * some dbs will need udfs - ALL THESE MUST BE SUPPORTED OR EMULATED 
	 * aggregate: sum(column), count(column), min(column), max(column), avg(column), list(column, seperator)
	 * strings: concat(string, string), substr(string,position,length), upper(string), lower(string), trim(string, both|leading|trailing),
	 * bitlength(string), charlength(string), replace(find, replace, string), position(substr, string)
	 * misc: mod(1, 2), round(number, digits), random(), log(number, base)
	 * datetime: date, time, timestamp, extract(interval, timestamp)
	 * interval choices for add, subtract are YEAR, MONTH, DAY, HOUR, MINUTE, SECOND
	 * extract choices are UNIX, DATE, TIME, TIMESTAMP, YEAR, MONTH, DAY, HOUR, MINUTE, SECOND
	 * sequences: currval(seqname), nextval(seqname), setval(seqname, value)
	 * remember there are sequences AND serials: for serials use lastid, some dbs fake serials with
	 * sequences/generators, some dbs emulate sequences/generators
	 *
	 * @param string $function string to decode
	 * @params mixed any number of optional arguments to throw into the sql function to return
	 * @return string sql function string
	 */
	abstract public function getFunction($function);

//----------------------------------------------------------------
//             Get Object Methods
//----------------------------------------------------------------

	/**
	 * public function lob()
	 *
	 * creates a large object (for either character or binary) to work with
	 *
	 * @return object lob class to work with
	 */
	abstract public function lob();

	/**
	 * public function query()
	 *
	 * creates a result object query to work with
	 *
	 * @param string $query_string string to use for query
	 * @param array $limit size(default is -1, all) and offset(default is 0)
	 * @param mixed $type int of constant or string type to translate to constant
	 * @param bool $buffered buffered or unbuffered query
	 * @return object result class to work with
	 */
	abstract public function query($query_string, $limit = NULL, $type = NULL, $buffered = TRUE);

	/**
	 * public function exec()
	 *
	 * used with insert/update/delete - usually returns just stdclass object
	 * with query string used, any errors or warnings, and a bool result
	 *
	 * @param string $query_string string to use for query
	 * @return object exec class to work with
	 */
	abstract public function exec($query_string);

	/**
	 * public function prepare()
	 *
	 * gets a prepared statement object set up to use
	 *
	 * @param string $query_string string to use for prepared query
	 * @return object prepared statement class to work with
	 */
	abstract public function prepare($query_string);

//----------------------------------------------------------------
//             Protected method - gets type with switchdown
//----------------------------------------------------------------

	/**
	 * protected function gettype($type)
	 *
	 * figures out which connect type using a switch
	 *
	 * @param mixed $type string of constant or int version of constant
	 * @return int type of connect do
	 */
	protected function gettype($type)
	{
		//although type is here, we don't do much with it
		switch(TRUE)
		{
			case(is_string($type)):
				if(defined('self::'.$type))
				{
					$type = constant('self::'.$type);
					break;
				}
			case(!is_null($type)):
				if($type == self::CONNECT or $type == self::PCONNECT or $type == self::NCONNECT)
				{
					break;
				}
			default:
				$type = self::CONNECT;
		}
		return $type;
	}
}
?>

