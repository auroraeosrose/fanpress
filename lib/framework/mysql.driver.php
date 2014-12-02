<?php
/**
 * mysql.driver.php - Mysql Database driver class - works with mysql 4.0 and higher
 *
 * because basic mysql is so featureless in comparison to other databases, this class
 * requires two changes to a basic mysql installation.  First of all, innodb tables
 * must be available, and http://www.codeproject.com/Purgatory/mygroupconcat.asp installed
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: session.interface.php,v 1.1 2004/07/28 20:37:49 liz Exp $
 * @requires     ext mysql
 * @requires     external http://www.codeproject.com/Purgatory/mygroupconcat.asp
 * @requires     external http://dev.mysql.com/doc/mysql/en/GROUP-BY-Functions.html user notes area
 * @package      phpfanfiction
 * @subpackage   framework
 * @category     lib
 * @filesource
 */

/**
 * Mysql Driver Class - extends abstract driver class
 *
 * Since mysql supports all basic items but not accurate affected rows(which we can fake)
 * subqueries, and stored procedures (which we emulate), foreign keys and transactions - so we use only innodb tables
 * and some sort of group_concat function - so a udf needs to be installed :)
 * remember that character sets aren't really supported in mysql 4.0, so the convert and collate stuff doesn't work
 */

class Phpff_Framework_Mysql_Driver extends Phpff_Framework_Driver_Abstract
{
	/**
	 * default database
	 * @var string
	 */
	private $dbname;

	/**
	 * Host to connect to
	 * @var string
	 */
	private $host;

	/**
	 * Port to connect to
	 * @var int
	 */
	private $port;

	/**
	 * Socket to use when connecting
	 * @var string
	 */
	private $socket;

	/**
	 * username to connect with
	 * @var string
	 */
	private $username;

	/**
	 * password to use with username
	 * @var string
	 */
	private $password;

	/**
	 * init statement
	 * @var mixed
	 */
	private $initstatement;

	/**
	 * any warnings from a statement query
	 * @var string
	 */
	private $warnings;

	/**
	 * a huge array with vsprintfs for functions
	 * @var array
	 */
	private $functions = array(
		//string functions, yeah fun
		'bitlength' => 'BIT_LENGTH(%s)', 'charlength' => 'CHAR_LENGTH(%s)',
		'concat' => 'CONCAT(%s, %s)', 'trim' => 'TRIM(%2$s FROM %1$s)',
		'upper' => 'UPPER(%s)', 'lower' => 'LOWER(%s)',
		'position' => 'POSITION(%2$s IN %1$s)', 'replace' => 'REPLACE(%s, %s, %s)', 'substring' => 'SUBSTRING(%s FROM %s FOR %s)',
		//mathematic functions
		'mod' => 'MOD(%s, %s)', 'round' => 'ROUND(%s, %s)', 'random' => 'RAND()', 'log' => 'LOG(%s, %s)',
		//date and time
		'date' => 'CURRENT_DATE', 'time' => 'CURRENT_TIME', 'timestamp' => 'CURRENT_TIMESTAMP',
		'extract' => 'EXTRACT(%s FROM \'%s\')', 'adddate' => 'timestamp %s + interval %s %s', 'subtractdate' => 'timestamp %s - interval %s %s',
		//aggregate functions - remember you need the udf :)
		'sum' => 'SUM(%s)', 'count' => 'COUNT(%s)', 'min' => 'MIN(%s)',
		'max' => 'MAX(%s)', 'avg' => 'AVG(%s)', 'list' => 'REPLACE(GROUP_CONCAT(%s), \':,:\', \'%s\')',
		//sequence functions
		'currval' => '(SELECT MAX(value) FROM sequences WHERE name=\'%s\')', 'nextval' => '(SELECT MAX(value) + 1 FROM sequences WHERE name=\'%s\')', 'setval' => '(UPDATE sequences SET value=%2$s WHERE name=\'%1$s\') %2$s',
		//change type - character types aren't really supported in mysql 4.0 so we'll ignore adding convert and return NOTHING for collate, hehe
		'cast' => 'CAST(%s AS %s)', 'convert' => '%s', 'collate' => '',
		//ips
		'setip' => 'INET_ATON(%s)', 'getip' => 'INET_NTOA(%s)'
	);

	/**
	 * maps extract choices to mysql names
	 * @var array
	 */
	private $extract = array(
		'year' => 'YEAR', 'month' => 'MONTH', 'day' => 'DAY',
		'hour' => 'HOUR', 'minute' => 'MINUTE', 'second' => 'SECOND',
		'time' => '', 'date' => '', 'unix' => ''
	);

	/**
	 * maps cast choices to mysql names
	 * @var array
	 */
	private $cast = array(
		'binary' => 'BINARY', 'char' => 'CHAR', 'date' => 'DATE', 'time' => 'TIME',
		'timestamp' => 'DATETIME', 'signed' => 'SIGNED INTEGER', 'unsigned' => 'UNSIGNED INTEGER'
	);

//----------------------------------------------------------------
//             Setup Methods
//----------------------------------------------------------------

	/**
	 * public function __construct()
	 *
	 * constructor calls init and sets classes
	 *
	 * @param array $options array of options to send to init
	 * @uses $this->init() to set settings
	 * @return void
	 */
	public function __construct($options = NULL)
	{
		$this->lobclass = 'Phpff_Framework_Lob_Mysql';
		$this->execclass = 'Phpff_Framework_Exec_Mysql';
		$this->statementclass = 'Phpff_Framework_Statement_Mysql';
		$this->resultclass = 'Phpff_Framework_Result_Mysql';
		$this->managerclass = 'Phpff_Framework_Manager_Mysql';
		$this->init($options);
		return;
	}

	/**
	 * public function __destruct()
	 *
	 * calls disconnect
	 *
	 * @uses $this->disconnect() to close off any connection
	 * @return void
	 */
	public function __destruct()
	{
		//commit if needed
		$this->commit();
		$this->disconnect();
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
	public function init($options)
	{
		$this->host = (!isset($options['host'])) ? ((is_null($this->host)) ? 'localhost' : $this->host) : (string) $options['host'];
		$this->username = (!isset($options['username'])) ? ((is_null($this->username)) ? 'root' : $this->username) : (string) $options['username'];
		$this->password = (!isset($options['password'])) ? ((is_null($this->password)) ? '' : $this->password) : (string) $options['password'];
		$this->dbname = (!isset($options['dbname'])) ? ((is_null($this->dbname)) ? 'mysql' : $this->dbname) : (string) $options['dbname'];
		$this->port = (!isset($options['port'])) ? ((is_null($this->port)) ? 3306 : $this->port) : (int) $options['port'];
		$this->socket = (!isset($options['socket'])) ? ((is_null($this->socket)) ? NULL : $this->socket) : (string) $options['socket'];
		$switch = (!isset($options['autocommit'])) ? TRUE : (bool) $options['autocommit'];
		$this->autocommit($switch);
		return;
	}

//----------------------------------------------------------------
//             Connection Methods
//----------------------------------------------------------------

	/**
	 * public function connect()
	 *
	 * connects to a database AND selects a db, or changes current db or
	 * does a new connect - changes $this->database and $this->persistent
	 * mysql has a new connect flag we'll use
	 *
	 * @param string $db new database to connect to
	 * @param mixed $type type of connection to make
	 * @return mixed connection resource id or false on failure
	 */
	public function connect($db = NULL, $type = NULL)
	{
		$type = $this->gettype($type);
		//let's negotiate the db
		if(is_null($db) and is_null($this->database))
		{
			$this->database = $this->dbname;
		}
		elseif(!is_null($db))
		{
			//ok, if the connection isn't null and we're not new, and old db is different from new db, change db ONLY
			if(!is_null($this->connection) and $type != self::NCONNECT and strcmp($this->database, $db))
			{
				$this->database = $db;
				mysql_select_db($this->database, $this->connection);
				$this->errno = mysql_errno($this->connection);
				$this->error = mysql_error($this->connection);
				return $this->connection;
			}
			$this->database = $db;
		}
		//ok, so now we add socket/port to server - socket takes precedence
		if(!is_null($this->socket))
		{
			$server = $this->host.':'.$this->socket;
		}
		elseif(!is_null($this->port))
		{
			$server = $this->host.':'.$this->port;
		}
		else
		{
			$server = $this->host;
		}
		//if it's new
		if($type == self::NCONNECT)
		{
			$this->connection = mysql_connect($server, $this->username, $this->password, TRUE);
			$this->persistent = FALSE;
		}
		//if it's persistent connect
		elseif($type == self::PCONNECT)
		{
			$this->connection = mysql_pconnect($server, $this->username, $this->password);
			$this->persistent = TRUE;
		}
		//otherwise it's just a connection
		else
		{
			$this->connection = mysql_connect($server, $this->username, $this->password, FALSE);
			$this->persistent = FALSE;
		}
		//ok, store errors and return false if we need to, otherwise move on
		if($this->connection == FALSE)
		{
			$this->errno = mysql_errno();
			$this->error = mysql_error();
			return FALSE;
		}
		mysql_select_db($this->database, $this->connection);
		$this->errno = mysql_errno($this->connection);
		$this->error = mysql_error($this->connection);
		//now, do we need to send an init statement?
		if(!is_null($this->initstatement))
		{
			$this->__statement($this->initstatement);
			$this->initstatement = NULL;
		}
		return $this->connection;
	}

	/**
	 * public function disconnect()
	 *
	 * Disconnects from a database - if the connection is not persistent
	 *
	 * @return void
	 */
	public function disconnect()
	{
		if($this->persistent == FALSE and !is_null($this->connection))
		{
			$this->commit();
			mysql_close($this->connection);
			$this->connection = NULL;
		}
		return $this->connection;
	}

//----------------------------------------------------------------
//             Transaction Methods
//----------------------------------------------------------------

	/**
	 * public function autocommit()
	 *
	 * Toggles autocommit status and either stores an initstatement to send or sends a statement
	 *
	 * @param bool $switch turn autocommit on and off
	 * @return void
	 */
	public function autocommit($switch = FALSE)
	{
		if($switch == TRUE)
		{
			if($this->autocommit == FALSE and $this->transaction == TRUE)
			{
				$this->commit();
			}
		}
		if(is_null($this->connection))
		{
			$this->initstatement = 'SET AUTOCOMMIT='.(($switch == FALSE) ? 0 : 1);
		}
		else
		{
			$this->statement('SET AUTOCOMMIT='.(($switch == FALSE) ? 0 : 1));
		}
		$this->transaction = NULL;
		$this->autocommit = $switch;
		return $this->autocommit;
	}

	/**
	 * public function start()
	 *
	 * starts a transaction
	 *
	 * @return bool true on success
	 */
	public function start()
	{
		if($this->autocommit == TRUE)
		$this->autocommit(FALSE);
		if($this->transaction == TRUE)
		$this->commit();
		if($this->statement('START TRANSACTION') == TRUE)
		$this->transaction = TRUE;
		return $this->transaction;
	}

	/**
	 * public function rollback()
	 *
	 * rolls back a transaction, does not autostart a new one
	 *
	 * @return bool true on success
	 */
	public function rollback()
	{
		if($this->transaction == FALSE)
		return FALSE;
		if($this->statement('ROLLBACK') == TRUE)
		$this->transaction = FALSE;
		return $this->transaction;
	}

	/**
	 * public function commit()
	 *
	 * commits a transaction, does not autostart a new one
	 *
	 * @return bool true on success
	 */
	public function commit()
	{
		if($this->transaction == FALSE)
		return FALSE;
		if($this->statement('COMMIT') == TRUE)
		$this->transaction = FALSE;
		return $this->transaction;
	}

//----------------------------------------------------------------
//             Data Handling Methods
//----------------------------------------------------------------

	/**
	 * public function quote()
	 *
	 * escapes a string to put in db - we use real escape string, or
	 * escape string if we haven't connected yet
	 *
	 * @param string $string string to quote
	 * @return string quoted string
	 */
	public function quote($string)
	{
		if(is_null($this->connection))
		{
			return mysql_escape_string($string);
		}
		return mysql_real_escape_string($string, $this->connection);
	}

	/**
	 * public function identifier()
	 *
	 * escapes an identifier to use in a query - mysql uses ticks
	 *
	 * @param string $string identifier to escape
	 * @return string escaped string
	 */
	public function identifier($string)
	{
		return '`'.$string.'`';
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
     * Although the sequence emulation stuff using subqueries, it still works
     * because 4.0 supports 
	 *
	 * @param string $function string to decode
	 * @params mixed any number of optional arguments to throw into the sql function to return
	 * @return string sql function string
	 */
	public function getFunction($function)
	{
		//get args
		$args = func_get_args();
		//get rid of function
		unset($args[0]);
		//now for specialties
		if($function == 'extract')
		{
			if($args[2] == 'unix')
			{
				//format it to a date
				return 'UNIX_TIMESTAMP(\''.$args[1].'\')';
			}
			elseif($args[2] == 'date')
			{
				//format it to a date
				return 'CAST(CONCAT_WS(YEAR(\''.$args[1].'\'), MONTH(\''.$args[1].'\'), DAY(\''.$args[1].'\'), \'-\'), DATE)';
			}
			elseif($args[2] == 'time')
			{
				//format it to a time
				return 'CAST(CONCAT_WS(HOUR(\''.$args[1].'\'), MINUTE(\''.$args[1].'\'), SECOND(\''.$args[1].'\'), \':\'), DATE)';
			}
			else
			{
				$args[2] = $this->extract[$args[2]];
			}
		}
		elseif($function == 'cast')
		{
			$args[2] = $this->cast[$args[2]];
		}
		elseif($function == 'trim' and !isset($args[2]))
		{
			$args[2] = 'both';
		}
		//make the perdy string
		return vsprintf($this->functions[$function], $args);
	}

//----------------------------------------------------------------
//             Get Object Methods
//----------------------------------------------------------------

	/**
	 * public function manager()
	 *
	 * creates a manager object to work with, manager object holds a referenced
	 * driver but is handled seperately
	 *
	 * @return object manager class to work with
	 */
	public function manager()
	{
		return new $this->managerclass($this);
	}

	/**
	 * public function lob()
	 *
	 * creates a large object (for either character or binary) to work with
	 *
	 * @return object lob class to work with
	 */
	public function lob()
	{
	}

	/**
	 * public function exec()
	 *
	 * used with insert/update/delete - usually returns just stdclass object
	 * with query string used, any errors or warnings, and a bool result
	 *
	 * @param string $query_string string to use for query
	 * @return object exec class to work with
	 */
	public function exec($query_string)
	{
	}

	/**
	 * public function prepare()
	 *
	 * gets a prepared statement object set up to use
	 *
	 * @param string $query_string string to use for prepared query
	 * @return object prepared statement class to work with
	 */
	public function prepare($query_string)
	{
	}

//----------------------------------------------------------------
//             Magic Helper Methods
//----------------------------------------------------------------

	/**
	 * public function statement()
	 *
	 * performs a statement query, such as set or transaction stuff
	 *
	 * @param string $query_string string to send
	 * @return bool true or false
	 */
	public function __statement($query_string)
	{
		if(!is_resource($this->connection))
		{
			trigger_error(__CLASS__.'::'.__METHOD__.'() must have a reference to a Mysql connection resource', E_USER_WARNING);
		}
		$worked = mysql_query($query_string, $this->connection);
		if($worked === FALSE)
		{
			$this->errno = mysql_errno($this->connection);
			$this->error = mysql_error($this->connection);
			trigger_error(__CLASS__.'::'.__METHOD__.'() the query '.$query_string.' has an error', E_USER_WARNING);
		}
		return $worked;
	}
}
?>

