<?php
/**
 * pgsql.driver.php - Postgresql driver - for 7.4 and higher
 *
 * driver for new versions of the postgresql db, must have prepare statement
 * and triggers (for transparent serializing)
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
 * Postgresql Driver Class - for 7.4 and higher
 *
 * All basic sql is supported in this version of postgresql, there is ONE udf required
 * for list(group concat) functionality and triggers are used for transparent identities
 */

class Phpff_Framework_Pgsql_Driver extends Phpff_Framework_Driver_Abstract
{
	//---------Variables used for connection settings --------

	/**
	 * Holds server host location
	 * @var string
	 */
	private $host;

	/**
	 * ip address of db server host
	 * @var string
	 */
	private $hostaddr;

	/**
	 * port to connect to, use null if using sockets
	 * @var int
	 */
	private $port;

	/**
	 * options to use when connecting
	 * @var string
	 */
	private $options;

	/**
	 * name of user to connect with
	 * @var string
	 */
	private $user;

	/**
	 * password to use
	 * @var string
	 */
	private $password;

	/**
	 * default db to use
	 * @var string
	 */
	private $dbname;

	/**
	 * ssl mode to use for the connection
	 * @var string
	 */
	private $sslmode;

	/**
	 * connection timeout to set in seconds, keep it above 2
	 * @var int
	 */
	private $timeout;

	/**
	 * whether the udf has been added
	 * @var bool
	 */
	private $udf;

	/**
	 * a huge array with vsprintfs for functions
	 * @var array
	 */
	private $functions = array(
		'currval' => 'CURRVAL(\'%s\')', 'nextval' => 'NEXTVAL(\'%s\')', 'setval' => 'SETVAL(\'%s\', %d)',
		'concat' => '\'%s\'||\'%s\'', 'upper' => 'UPPER(\'%s\')', 'lower' => 'LOWER(\'%s\')',
		'trim' => 'TRIM(%2$s FROM \'%1$s\')', 'bitlength' => 'BIT_LENGTH(\'%s\')', 'charlength' => 'CHAR_LENGTH(\'%s\')',
		'position' => 'POSITION(\'%s\' IN \'%s\')', 'replace' => 'REPLACE(\'%s\', \'%s\', \'%s\')', 'substr' => 'SUBSTRING(\'%s\' FROM %d FOR %d)',
		'date' => 'CURRENT_DATE', 'time' => 'CURRENT_TIME', 'timestamp' => 'CURRENT_TIMESTAMP',
		'extract' => 'EXTRACT(%s FROM \'%s\')', 'adddate' => 'timestamp \'%s\' + interval %s %s', 'subtractdate' => 'timestamp \'%s\' - interval %s %s',
		'sum' => 'SUM(%s)', 'count' => 'COUNT(%s)', 'min' => 'MIN(%s)', 'max' => 'MAX(%s)', 'avg' => 'AVG(%s)',
		'mod' => 'MOD(%d, %d)', 'round' => 'ROUND(%d, %d)', 'random' => 'RANDOM()', 'log' => 'LOG(%d, %s)', 'list' => 'REPLACE(GROUP_CONCAT(%s), \':,:\', \'%s\')'
	);

	/**
	 * maps interval choices to postgres names
	 * @var array
	 */
	private $intervals = array(
		'UNIX' => 'EPOCH', 'YEAR' => 'YEAR', 'MONTH' => 'MONTH', 'DAY' => 'DAY',
		'HOUR' => 'HOUR', 'MINUTE' => 'MINUTE', 'SECOND' => 'SECOND'
	);

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
		$this->lobclass = 'Phpff_Framework_Pgsql_Lob';
		$this->execclass = 'Std_Class';
		$this->statementclass = 'Phpff_Framework_Pgsql_Statement';
		$this->resultclass = 'Phpff_Framework_Pgsql_Result';
		$this->managerclass = 'Phpff_Framework_Pgsql_Manager';
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
	public function init($options)
	{
		$this->host = (!isset($options['host'])) ? ((!is_null($this->host)) ? NULL : $this->host) : (string) $options['host'];
		$this->hostaddr = (!isset($options['hostaddr'])) ? ((!is_null($this->hostaddr)) ? NULL : $this->hostaddr) : (string) $options['hostaddr'];
		$this->options = (!isset($options['options'])) ? ((!is_null($this->options)) ? NULL : $this->options) : (string) $options['options'];
		$this->sslmode = (!isset($options['sslmode'])) ? ((!is_null($this->sslmode)) ? NULL : $this->sslmode) : (string) $options['sslmode'];
		$this->timeout = (!isset($options['timeout']) or (isset($options['timeout']) and $options['timeout'] < 2)) ? ((!is_null($this->timeout)) ? NULL : $this->timeout) : (int) $options['timeout'];
		$this->user = (!isset($options['user'])) ? ((!is_null($this->user)) ? 'postgres' : $this->user) : (string) $options['user'];
		$this->password = (!isset($options['password'])) ? ((!is_null($this->password)) ? '' : $this->password) : (string) $options['password'];
		$this->dbname = (!isset($options['dbname'])) ? ((!is_null($this->dbname)) ? 'test' : $this->dbname) : (string) $options['dbname'];
		$this->port = (!isset($options['port'])) ? ((!is_null($this->port)) ? NULL : $this->port) : (int) $options['port'];
		return;
	}

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
	public function connect($db = NULL, $type = NULL)
	{
		//get our type
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
		//create our connection string
		$dsn_array = array('host', 'hostaddr', 'options', 'sslmode', 'timeout', 'user', 'password', 'dbname', 'port');
		$dsn = '';
		foreach($dsn_array as $name)
		{
			if(!is_null($this->$name))
			{
				$dsn .= $name.'='.$this->$name.' ';
			}
		}
		if($type == self::PCONNECT)
		{
			$this->connection = pg_pconnect($dsn);
			$this->persistent = TRUE;
		}
		elseif($type == self::NCONNECT)
		{
			if(is_null($this->connection))
			{
				$this->connection = pg_connect($dsn);
				$this->persistent = FALSE;
			}
			pg_connection_reset($this->connection);
		}
		else
		{
			$this->connection = pg_connect($dsn);
			$this->persistent = FALSE;
		}
		$this->errno = pg_connection_status($this->connection);
		$this->error = pg_last_error($this->connection);
		return $this->connection;
	}

	/**
	 * public function disconnect()
	 *
	 * Disconnects from a database
	 *
	 * @return void
	 */
	public function disconnect()
	{
	}

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
	public function autocommit($switch = FALSE)
	{
		//autocommit in postgres is implicit unless you've already done a transaction
		
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
	}

//----------------------------------------------------------------
//             Data Handling Methods
//----------------------------------------------------------------

	/**
	 * public function quote()
	 *
	 * pg has it's own escape string function
	 *
	 * @param string $string string to quote
	 * @return string quoted string
	 */
	public function quote($string)
	{
		return pg_escape_string($string);
	}

	/**
	 * public function encode()
	 *
	 * "bytea" is postgres's in table binary handling
	 *
	 * @param string $binary binary string to decode
	 * @return string encoded string
	 */
	public function encode($binary)
	{
		return pg_escape_bytea($binary);
	}

	/**
	 * public function decode()
	 *
	 * unecodes bytea escaping
	 *
	 * @param string $binary string to decode
	 * @return string decoded binary string
	 */
	public function decode($binary)
	{
		return pg_unescape_bytea($binary);
	}

//----------------------------------------------------------------
//             get db functions for specific db
//----------------------------------------------------------------

	/**
	 * public function getFunction()
	 *
	 * all sql functions are supported except for list which needs a udf, and three
	 * versions of extract (date, time, timestamp) have to be done differently
	 *
	 * @param string $function string to decode
	 * @params mixed any number of optional arguments to throw into the sql function to return
	 * @return string sql function string
	 */
	public function getFunction($function)
	{
		//datetime array for finding interval
		$interval = array('extract', 'adddate', 'subtractdate');
		//get args
		$args = func_get_args();
		//get rid of function
		unset($args[0]);
		if(in_array(strtolower($function), $interval))
		{
			if(isset($args[1]))
			$args[1] = $this->intervals[strtoupper($args[1])];
		}
		//now for specialties
		if($function == 'extract')
		{
			if($args[2] == 'DATE')
			{
				//format it to a date
				return 'TO_TIMESTAMP(\''.$args[1].'\', \'YYYY-MM-DD\')';
			}
			elseif($args[2] == 'TIME')
			{
				//format it to a time
				return 'TO_TIMESTAMP(\''.$args[1].'\', \'HH24:MI:SS\')';
			}
			elseif($args[2] == 'TIMESTAMP')
			{
				//format it to a timestamp
				return 'TO_TIMESTAMP(\''.$args[1].'\', \'YYYY-MM-DD HH24:MI:SS\')';
			}
		}
		//make sure the udf exists
		$this->checkudf();
		//make the perdy string
		return vsprintf($this->functions[$function], $args);
	}

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
	public function lob()
	{
	}

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
	public function query($query_string, $limit = NULL, $type = NULL, $buffered = TRUE)
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
//             Private Methods
//----------------------------------------------------------------

	/**
	 * private function checkudf()
	 *
	 * checks to see if a udf exists, if not it creates it
	 *
	 * @return void
	 */
	private function checkudf()
	{
		//don't redo it if it exists
		if($this->udf == TRUE)
		{
			return;
		}
		//connection should be there
		if(!$this->connection)
		{
			$this->connect();
		}
		$worked = pg_query($this->connection, 'SELECT count(*) FROM pg_catalog.pg_proc WHERE proisagg AND proname = \'group_concat\'');
		$total = pg_fetch_row($worked);
		if($total[0] == 1)
		{
			$this->udf = TRUE;
			return;
		}
		else
		{
			$udf = 'CREATE OR REPLACE FUNCTION SINGLE_CONCAT(varchar,varchar) RETURNS varchar'."\n"
			.'AS \'SELECT CASE $1 '."\n"
			.'WHEN \\\'\\\' THEN $2 '."\n"
			.'ELSE CASE POSITION($1 in $2)'."\n"
			.'WHEN 0 THEN $2'."\n"
			.'ELSE $1 || \\\':,:\\\' || $2'."\n"
			.'END'."\n"
			.'END AS RESULT;\''."\n"
			.'LANGUAGE SQL;'."\n"
			.'CREATE AGGREGATE GROUP_CONCAT(BASETYPE = varchar, SFUNC = SINGLE_CONCAT, STYPE = varchar, INITCOND = \'\');'."\n";
			pg_query($this->connection, $udf);
			$this->udf = TRUE;
			return;
		}
	}
}
?>

