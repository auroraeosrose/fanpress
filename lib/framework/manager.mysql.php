<?php
/**
 * manager.mysql.php - manager for mysql 4.0 version driver
 *
 * allows basic management for a mysql database
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
 * Mysql Manager - Manages a mysql 4.0.x database server
 *
 * make sure your user has permissions for the management you want to do or you'll
 * have pretty happy errors
 */

class Phpff_Framework_Manager_Mysql extends Phpff_Framework_Manager_Abstract
{

//----------------------------------------------------------------
//             Server Management
//----------------------------------------------------------------


//----------------------------------------------------------------
//             Database Management
//----------------------------------------------------------------

	/**
	 * public function createDatabase()
	 * 
	 * creates a new database
	 *
	 * @param string $name name of database to create
	 * @return bool true on success, false on failure
	 */
	public function createDatabase($name)
	{
		//create a database
		$query = 'CREATE DATABASE '.$this->identifier($name);
		return $this->__statement($query);
	}

	/**
	 * public function renameDatabase()
	 * 
	 * renames a database
	 *
	 * @param string $old name of database to rename
	 * @param string $new new database name
	 * @return bool true on success, false on failure
	 */
	public function renameDatabase($old, $new)
	{
		//create the new database
		$this->createDatabase($new);
		//rename all current tables to new ones
		
		//delete the old database
		$this->dropDatabase($old);
		if(empty($this->error))
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}

	/**
	 * public function dropDatabase()
	 * 
	 * deletes a database
	 *
	 * @param string $name name of database to drop
	 * @return bool true on success, false on failure
	 */
	public function dropDatabase($name)
	{
		//create a database
		$query = 'DROP DATABASE '.$this->identifier($name);
		return $this->__statement($query);
	}

//----------------------------------------------------------------
//             Table Management
//----------------------------------------------------------------


//----------------------------------------------------------------
//             Sequence Management
//----------------------------------------------------------------

	/**
	 * public function createSequence()
	 * 
	 * creates a sequence for the database
	 *
	 * @param string $name name of sequence
	 * @param string $start integer to start with
	 * @param string $increment amount to use for increment
	 * @return bool true on success, false on failure
	 */
	public function createSequence($name, $start = 0, $increment = 0)
	{
		//check to see if sequence table exists, if not create it
		//if it does, add a new sequence
		$query = 'INSERT INTO '.$this->db->prefix.'sequence(name, id, inc) VALUES ('.$this->db->quote((string) $name).', '.(int) $start.', '.(int) $increment.')';
		return $this->statement($query);
	}

	/**
	 * public function alterSequence()
	 * 
	 * changes a sequence for the database
	 *
	 * @param string $name name of sequence
	 * @param array $changes can change name, value, increment
	 * @return bool true on success, false on failure
	 */
	public function alterSequence($name, $changes)
	{
	}

	/**
	 * public function dropSequence()
	 * 
	 * deletes a sequence from the database
	 *
	 * @param string $name name of sequence to drop
	 * @return bool true on success, false on failure
	 */
	public function dropSequence($name)
	{
	}

	/**
	 * public function infoSequence()
	 * 
	 * returns an array of info about a sequence
	 *
	 * @param string $name name of sequence to drop
	 * @return array information about the sequence
	 */
	public function infoSequence($name)
	{
	}

	/**
	 * public function listSequence()
	 * 
	 * lists all the sequences in a database
	 *
	 * @return array numeric array of string sequence names
	 */
	public function listSequence()
	{
	}

//----------------------------------------------------------------
//             User Management
//----------------------------------------------------------------

}
?>

