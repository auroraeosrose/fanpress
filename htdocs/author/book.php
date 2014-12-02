<?php
/**
 * book.php - authors book management area
 *
 * allows authors to view book stats, create new books, edit books, add chapters, reply to comments, and delete books
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: book.php,v 1.3 2004/07/28 20:37:48 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

function new_book()
{
	function undoword($string)
	{
		$string = preg_replace("/([\x80-\xFF])/e","chr(0xC0|ord('\\1')>>6).chr(0x80|ord('\\1')&0x3F)",$string);
		$badlatin1_cp1252_to_htmlent = array(
			'\x80'=>'&#x20AC;', '\x81'=>'?', '\x82'=>'&#x201A;', '\x83'=>'&#x0192;',
			'\x84'=>'&#x201E;', '\x85'=>'&#x2026;', '\x86'=>'&#x2020;', '\x87'=>'&#x2021;',
			'\x88'=>'&#x02C6;', '\x89'=>'&#x2030;', '\x8A'=>'&#x0160;', '\x8B'=>'&#x2039;',
			'\x8C'=>'&#x0152;', '\x8D'=>'?', '\x8E'=>'&#x017D;', '\x8F'=>'?',
			'\x90'=>'?', '\x91'=>'&#x2018;', '\x92'=>'&#x2019;', '\x93'=>'&#x201C;',
			'\x94'=>'&#x201D;', '\x95'=>'&#x2022;', '\x96'=>'&#x2013;', '\x97'=>'&#x2014;',
			'\x98'=>'&#x02DC;', '\x99'=>'&#x2122;', '\x9A'=>'&#x0161;', '\x9B'=>'&#x203A;',
			'\x9C'=>'&#x0153;', '\x9D'=>'?', '\x9E'=>'&#x017E;', '\x9F'=>'&#x0178;');
		return strtr($string, $badlatin1_cp1252_to_htmlent);
	}
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$author = $session->get('author', 'user');
	if(empty($author))
	header('Location: ../user/index.php?a=2');
	//page assignments
	$tpl['title'] = 'Create New Book';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library author new book';
	$tpl['description'] = 'Fanfiction Library Author new book';
	//assign sub "template"
	$files['page'] = 'authornewbook.html';
	//check the forms
	if(isset($_POST['submit']))
	{
		if($_POST['step'] == 2)
		{
			if(empty($_POST['title']))
			{
				$tpl['error'] = 'You must provide a book title.';
			}
			elseif(strlen($_POST['title']) < 4)
			{
				$tpl['error'] = 'Your title must be at least 4 characters long.';
			}
			elseif(empty($_POST['summary']))
			{
				$tpl['error'] = 'You must provide a summary.';
			}
			elseif(count(preg_split('/\W+/', $_POST['summary'], -1, PREG_SPLIT_NO_EMPTY)) < 10)
			{
				$tpl['error'] = 'Please provide a complete, comprehensive summary.  A good summary is at least 10 words long.';
			}
			elseif(strlen($_POST['summary']) > 500)
			{
				$tpl['error'] = 'Please keep your summaries under 500 characters for easier readability.';
			}
			//check for text or upload
			elseif(empty($_POST['notes']) and $_FILES['datafile']['error'] == 4)
			{
				$tpl['error'] = 'You must either upload a file or enter text in the textarea box.  Please leave ALL author notes in this area, and not in your chapters.';
			}
			//check for text and upload
			elseif($_FILES['datafile']['error'] != 4 and !empty($_POST['notes']))
			{
				$tpl['error'] = 'You must EITHER upload a file OR enter your text in the textarea box, not both.';
			}
			//if we have an upload
			elseif(empty($_POST['notes']) and ($_FILES['datafile']['error'] == 1 or $_FILES['datafile']['error']==2))
			{
				$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
			}
			elseif(empty($_POST['notes']) and $_FILES['datafile']['error'] == 3)
			{
				$tpl['error'] = 'There was a problem with your upload, please try again.';
			}
			elseif(empty($_POST['notes']) and $_FILES['datafile']['size'] < 1)
			{
				$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
			}
			elseif(empty($_POST['notes']) and ($_FILES['datafile']['type'] != 'text/plain' and $_FILES['datafile']['type'] != 'text/html'))
			{
				$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
			}
			elseif(empty($_POST['notes']) and !is_uploaded_file($_FILES['datafile']['tmp_name']))
			{
				$tpl['error'] = 'Your file was not uploaded properly';
			}
			elseif(empty($_POST['notes']))
			{
				$_POST['notes'] = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['datafile']['tmp_name'])))));
			}
			else
			{
				$_POST['notes'] = htmlentities(html_entity_decode(undoword(strip_tags($_POST['notes']))));
			}
			$_POST['title'] = $tpl['newbook']['title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
			$_POST['summary'] =  htmlentities(html_entity_decode(undoword(strip_tags($_POST['summary']))));
		}
		elseif($_POST['step'] == 3)
		{
			if(empty($_POST['authors']))
			{
				unset($_POST['authors']);
			}
			else
			{
				$authors = explode(', ', $_POST['authors']);
				$total = count($authors);
				$authors = 'WHERE author_name=\''.implode('\' OR author_name=\'', array_map('trim', $authors)).'\'';
				$result = $db->query('SELECT COUNT(author_id) FROM author '.$authors);
				$count = $result->fetch_row();
				$result->close();
				if($count[0] != $total)
				{
					$tpl['error'] = 'One of the author names you entered is incorrect.  Check the directory for the correct spelling, and seperate names with commas';
				}
			}
		}
		elseif($_POST['step'] == 4)
		{
			if(empty($_POST['chtitle']))
			{
				$tpl['error'] = 'You must provide a chapter title.';
			}
			elseif(strlen($_POST['chtitle']) < 4)
			{
				$tpl['error'] = 'Your chapter title must be at least 4 characters long.';
			}
			//check for text or upload
			elseif(empty($_POST['text']) and $_FILES['datafile']['error'] == 4)
			{
				$tpl['error'] = 'You must either upload a file or enter text in the textarea box.';
			}
			//check for text and upload
			elseif($_FILES['datafile']['error'] != 4 and !empty($_POST['text']))
			{
				$tpl['error'] = 'You must EITHER upload a file OR enter your text in the textarea box, not both.';
			}
			//if we have an upload
			elseif(empty($_POST['text']) and ($_FILES['datafile']['error'] == 1 or $_FILES['datafile']['error']==2))
			{
				$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
			}
			elseif(empty($_POST['text']) and $_FILES['datafile']['error'] == 3)
			{
				$tpl['error'] = 'There was a problem with your upload, please try again.';
			}
			elseif(empty($_POST['text']) and $_FILES['datafile']['size'] < 1)
			{
				$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
			}
			elseif(empty($_POST['text']) and ($_FILES['datafile']['type'] != 'text/plain' and $_FILES['datafile']['type'] != 'text/html'))
			{
				$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
			}
			elseif(empty($_POST['text']) and !is_uploaded_file($_FILES['datafile']['tmp_name']))
			{
				$tpl['error'] = 'Your file was not uploaded properly';
			}
			elseif(empty($_POST['text']))
			{
				$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['datafile']['tmp_name'])))));
			}
			else
			{
				$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))));
			}
			if(count(preg_split('/\W+/', $_POST['text'], -1, PREG_SPLIT_NO_EMPTY)) < 500)
			{
				$tpl['error'] = 'Your story must be at least 500 words in length.';
			}
			$_POST['chtitle'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['chtitle'])));
		}
	}
	if(!isset($_POST['submit']) and !isset($_POST['back']))
	{
		$tpl['step'] = 0;
	}
	elseif(isset($_POST['back']))
	{
		$tpl['step'] = $_POST['step'] - 2;
	}
	elseif(isset($tpl['error']))
	{
		$tpl['step'] = $_POST['step'] - 1;
	}
	else
	{
		$tpl['step'] = $_POST['step'];
	}
	if($tpl['step'] == 1)
	{
		//get category list
		$tpl['catlist'] = $db->query('SELECT category_id AS id, category_name AS name, category_description AS description FROM category ORDER BY category_name ASC');
		if(!$tpl['catlist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get type list
		$tpl['typelist'] = $db->query('SELECT type_id AS id, type_name AS name, type_description AS description FROM type ORDER BY type_id ASC');
		if(!$tpl['typelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get style list
		$tpl['stylelist'] = $db->query('SELECT style_id AS id, style_name AS name, style_description AS description FROM style ORDER BY style_id ASC');
		if(!$tpl['stylelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get ratings
		$tpl['ratinglist'] = $db->query('SELECT rating_id AS id, rating_name AS name, rating_description AS description FROM rating ORDER BY rating_name ASC');
		if(!$tpl['ratinglist'])
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	elseif($tpl['step'] == 2)
	{
		//get character list
		$tpl['charlist'] = $db->query('SELECT character_id AS id, character_name AS name, character_description AS description FROM `character` WHERE category_id_fk=0 or category_id_fk='.$_POST['cid'].' ORDER BY character_name ASC');
		if(!$tpl['charlist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get genres
		$tpl['genrelist'] = $db->query('SELECT genre_id AS id, genre_name AS name, genre_description AS description FROM genre ORDER BY genre_name ASC');
		if(!$tpl['genrelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get warnings
		$tpl['warninglist'] = $db->query('SELECT warning_id AS id, warning_name AS name, warning_description AS description FROM warning ORDER BY warning_name ASC');
		if(!$tpl['warninglist'])
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	elseif($tpl['step'] == 4)
	{
		//get authorname
		$result = $db->query('SELECT author_name AS name FROM author WHERE user_id_fk='.$session->get('user', 'user').' LIMIT 1');
		$tmp = $result->fetch_assoc();
		$tpl['authorname'] = $tmp['name'];
		$result->close();
		//get category name
		$result = $db->query('SELECT category_name AS name FROM category WHERE category_id='.$_POST['cid'].' LIMIT 1');
		$tmp = $result->fetch_assoc();
		$tpl['catname'] = $tmp['name'];
		$result->close();
		//get type name
		$result = $db->query('SELECT type_name AS name FROM type WHERE type_id='.$_POST['tid'].' LIMIT 1');
		$tmp = $result->fetch_assoc();
		$tpl['type'] = $tmp['name'];
		$result->close();
		//get style name
		$result = $db->query('SELECT style_name AS name FROM style WHERE style_id='.$_POST['tid'].' LIMIT 1');
		$tmp = $result->fetch_assoc();
		$tpl['style'] = $tmp['name'];
		$result->close();
		//get rating name
		$result = $db->query('SELECT rating_name AS name FROM rating WHERE rating_id='.$_POST['rid'].' LIMIT 1');
		$tmp = $result->fetch_assoc();
		$tpl['rating'] = $tmp['name'];
		$result->close();
		//get characters
		if(isset($_POST['chid']))
		{
			$characters = array();
			$where = 'WHERE character_id='.implode(' OR character_id=', $_POST['chid']);
			$result = $db->query('SELECT character_name AS name FROM `character` '.$where);
			while($tmp = $result->fetch_assoc())
			{
				$characters[] = $tmp['name'];
			}
			$result->close();
			$tpl['characters'] = implode(', ', $characters);
		}
		else
		{
			$tpl['characters'] = 'NONE';
		}
		//get genres
		if(isset($_POST['gid']))
		{
			$genres = array();
			$where = 'WHERE genre_id='.implode(' OR genre_id=', $_POST['gid']);
			$result = $db->query('SELECT genre_name AS name FROM genre '.$where);
			while($tmp = $result->fetch_assoc())
			{
				$genres[] = $tmp['name'];
			}
			$result->close();
			$tpl['genres'] = implode(', ', $genres);
		}
		else
		{
			$tpl['genres'] = 'NONE';
		}
		//get warnings
		if(isset($_POST['wid']))
		{
			$warning = array();
			$where = 'WHERE warning_id='.implode(' OR warning_id=', $_POST['wid']);
			$result = $db->query('SELECT warning_name AS name FROM warning '.$where);
			while($tmp = $result->fetch_assoc())
			{
				$warning[] = $tmp['name'];
			}
			$result->close();
			$tpl['warnings'] = implode(', ', $warning);
		}
		else
		{
			$tpl['warnings'] = 'NONE';
		}
	}
	elseif($tpl['step'] == 5)
	{
		$path = str_replace('htdocs/author', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
		$letter = str_split($_POST['title']);
		$letter = strtoupper($letter[0]);
		//insert the book
		$newbook = $db->query('INSERT INTO book(book_title, book_order, book_summary, type_id_fk, style_id_fk, rating_id_fk, book_publish, book_update, category_id_fk, book_notes, book_wordcount, book_chapters) '
			.'VALUES (\''.$db->real_escape_string($_POST['title']).'\', \''.$letter.'\', \''.$db->real_escape_string($_POST['summary']).'\', '.$_POST['tid'].','.$_POST['sid'].','.$_POST['rid'].', NOW(), NOW(), '.$_POST['cid'].', '
			.'\''.$db->real_escape_string($_POST['notes']).'\', '.count(preg_split('/\W+/', $_POST['text'], -1, PREG_SPLIT_NO_EMPTY)).', 1)');
		//get book id
		$id = $db->insert_id;
		//get author ids
		if(isset($_POST['authors']))
		{
			$authors = explode(', ', $_POST['authors']);
			$authors = 'WHERE author_name=\''.implode('\' OR author_name=\'', array_map('trim', $authors)).'\' OR';
		}
		else
		{
			$authors = 'WHERE ';
		}
		$result = $db->query('SELECT author_id FROM author '.$authors.' user_id_fk='.$session->get('user', 'user'));
		$authors = array();
		while($tmp = $result->fetch_row())
		{
			$authors[] = $tmp[0];
		}
		$result->close();
		$insert = array();
		foreach($authors as $aid)
		{
			$insert[] = '('.$aid.', '.$id.')';
		}
		//insert the warnings
		$newbook = $db->query('INSERT INTO booktoauthor(author_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
		//insert characters
		if(isset($_POST['chid']) and is_array($_POST['chid']))
		{
			$insert = array();
			foreach($_POST['chid'] as $chid)
			{
				$insert[] = '('.$chid.', '.$id.')';
			}
			//insert the warnings
			$newbook = $db->query('INSERT INTO booktocharacter(character_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
		}
		//insert genres
		if(isset($_POST['gid']) and is_array($_POST['gid']))
		{
			$insert = array();
			foreach($_POST['gid'] as $gid)
			{
				$insert[] = '('.$gid.', '.$id.')';
			}
			$newbook = $db->query('INSERT INTO booktogenre(genre_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
		}
		//insert warnings
		if(isset($_POST['wid']) and is_array($_POST['wid']))
		{
			$insert = array();
			foreach($_POST['wid'] as $wid)
			{
				$insert[] = '('.$wid.', '.$id.')';
			}
			//insert the warnings
			$newbook = $db->query('INSERT INTO booktowarning(warning_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
		}
		//insert chapter
		$newbook = $db->query('INSERT INTO chapter(chapter_title, chapter_order, chapter_publish, chapter_update, chapter_wordcount, book_id_fk) '
			.'VALUES (\''.$db->real_escape_string($_POST['chtitle']).'\', 1, NOW(), NOW(),'.count(preg_split('/\W+/', $_POST['text'], -1, PREG_SPLIT_NO_EMPTY)).', '.$id.')');
		$chid = $db->insert_id;
		//create folder
		@mkdir($path.'/'.$id);
		//create file
		file_put_contents($path.'/'.$id.'/'.$chid.'.txt', $_POST['text']);
		$tpl['error'] = 'Your book has been created.  An email will be sent to you with the status of your book after it has been screened.';
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function edit_book()
{
	function undoword($string)
	{
		$badlatin1_cp1252_to_htmlent = array(
			'\x80'=>'&#x20AC;', '\x81'=>'?', '\x82'=>'&#x201A;', '\x83'=>'&#x0192;',
			'\x84'=>'&#x201E;', '\x85'=>'&#x2026;', '\x86'=>'&#x2020;', '\x87'=>'&#x2021;',
			'\x88'=>'&#x02C6;', '\x89'=>'&#x2030;', '\x8A'=>'&#x0160;', '\x8B'=>'&#x2039;',
			'\x8C'=>'&#x0152;', '\x8D'=>'?', '\x8E'=>'&#x017D;', '\x8F'=>'?',
			'\x90'=>'?', '\x91'=>'&#x2018;', '\x92'=>'&#x2019;', '\x93'=>'&#x201C;',
			'\x94'=>'&#x201D;', '\x95'=>'&#x2022;', '\x96'=>'&#x2013;', '\x97'=>'&#x2014;',
			'\x98'=>'&#x02DC;', '\x99'=>'&#x2122;', '\x9A'=>'&#x0161;', '\x9B'=>'&#x203A;',
			'\x9C'=>'&#x0153;', '\x9D'=>'?', '\x9E'=>'&#x017E;', '\x9F'=>'&#x0178;');
		return strtr($string, $badlatin1_cp1252_to_htmlent);
	}
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$author = $session->get('author', 'user');
	if(empty($author))
	header('Location: ../user/index.php?a=2');
	//check our form
	if(isset($_POST['submit']))
	{
		if(empty($_POST['title']))
		{
			$tpl['error'] = 'You must provide a book title.';
		}
		elseif(strlen($_POST['title']) < 4)
		{
			$tpl['error'] = 'Your title must be at least 4 characters long.';
		}
		elseif(empty($_POST['summary']))
		{
			$tpl['error'] = 'You must provide a summary.';
		}
		elseif(count(preg_split('/\W+/', $_POST['summary'], -1, PREG_SPLIT_NO_EMPTY)) < 10)
		{
			$tpl['error'] = 'Please provide a complete, comprehensive summary.  A good summary is at least 10 words long.';
		}
		elseif(strlen($_POST['summary']) > 500)
		{
			$tpl['error'] = 'Please keep your summaries under 500 characters for easier readability.';
		}
		//check for text or upload
		elseif(empty($_POST['notes']) and $_FILES['datafile']['error'] == 4)
		{
			$tpl['error'] = 'You must either upload a file or enter text in the textarea box.  Please leave ALL author notes in this area, and not in your chapters.';
		}
		//if we have an upload
		elseif($_FILES['datafile']['error'] == 1 or $_FILES['datafile']['error']==2)
		{
			$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
		}
		elseif($_FILES['datafile']['error'] == 3)
		{
			$tpl['error'] = 'There was a problem with your upload, please try again.';
		}
		elseif($_FILES['datafile']['error'] == 0 and $_FILES['datafile']['size'] < 1)
		{
			$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
		}
		elseif($_FILES['datafile']['error'] == 0 and $_FILES['datafile']['type'] != 'text/plain' and $_FILES['datafile']['type'] != 'text/html')
		{
			$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
		}
		elseif($_FILES['datafile']['error'] == 0 and !is_uploaded_file($_FILES['datafile']['tmp_name']))
		{
			$tpl['error'] = 'Your file was not uploaded properly';
		}
		elseif($_FILES['datafile']['error'] == 0)
		{
			$_POST['notes'] = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['datafile']['tmp_name'])))));
		}
		else
		{
			$_POST['notes'] = htmlentities(html_entity_decode(undoword(strip_tags($_POST['notes']))));
		}
		$_POST['title'] =  htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		$_POST['summary'] =  htmlentities(html_entity_decode(undoword(strip_tags($_POST['summary']))));
		if(empty($_POST['authors']))
		{
			unset($_POST['authors']);
		}
		else
		{
			$authors = explode(', ', $_POST['authors']);
			$total = count($authors);
			$authors = 'WHERE author_name=\''.implode('\' OR author_name=\'', array_map('trim', $authors)).'\'';
			$result = $db->query('SELECT COUNT(author_id) FROM author '.$authors);
			$count = $result->fetch_row();
			$result->close();
			if($count[0] != $total)
			{
				$tpl['error'] = 'One of the author names you entered is incorrect.  Check the directory for the correct spelling, and seperate names with commas';
			}
		}
		if(isset($tpl['error']))
		{
			$_GET['m'] = 1;
		}
		else
		{
			//get old cat number
			$result = $db->query('SELECT category_id_fk FROM book WHERE book_id='.$_POST['id']);
			$cat = $result->fetch_row();
			$cat = $cat[0];
			$result->close();
			//update category counts
			$db->query('UPDATE category SET category_total = category_total -1 WHERE category_id='.$cat);
			$db->query('UPDATE category SET category_total = category_total +1 WHERE category_id='.$_POST['cid']);
			$letter = str_split($_POST['title']);
			$letter = strtoupper($letter[0]);
			//update the book
			$newbook = $db->query('UPDATE book SET book_title=\''.$db->real_escape_string($_POST['title']).'\', book_order=\''.$letter.'\', book_summary=\''.$db->real_escape_string($_POST['summary']).'\', '
				.'style_id_fk='.$_POST['sid'].', type_id_fk='.$_POST['tid'].', category_id_fk='.$_POST['cid'].', book_notes=\''.$db->real_escape_string($_POST['notes']).'\', book_completed='.$_POST['status'].' WHERE book_id='.$_POST['id']);
			//delete all authors
			$db->query('DELETE FROM booktoauthor WHERE book_id_fk='.$_POST['id']);
			//find authors
			if(isset($_POST['authors']))
			{
				$authors = explode(', ', $_POST['authors']);
				$authors = 'WHERE author_name=\''.implode('\' OR author_name=\'', array_map('trim', $authors)).'\' OR';
			}
			else
			{
				$authors = 'WHERE ';
			}
			$result = $db->query('SELECT author_id FROM author '.$authors.' user_id_fk='.$session->get('user', 'user'));
			$authors = array();
			while($tmp = $result->fetch_row())
			{
				$authors[] = $tmp[0];
			}
			$result->close();
			$insert = array();
			foreach($authors as $aid)
			{
				$insert[] = '('.$aid.', '.$_POST['id'].')';
			}
			//insert the authors
			$newbook = $db->query('INSERT INTO booktoauthor(author_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
			//delete all characters
			$db->query('DELETE FROM booktocharacter WHERE book_id_fk='.$_POST['id']);
			//insert characters
			if(isset($_POST['chid']) and is_array($_POST['chid']))
			{
				$insert = array();
				foreach($_POST['chid'] as $chid)
				{
					$insert[] = '('.$chid.', '.$_POST['id'].')';
				}
				$newbook = $db->query('INSERT INTO booktocharacter(character_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
			}
			//delete all genres
			$db->query('DELETE FROM booktogenre WHERE book_id_fk='.$_POST['id']);
			//insert genres
			if(isset($_POST['gid']) and is_array($_POST['gid']))
			{
				$insert = array();
				foreach($_POST['gid'] as $gid)
				{
					$insert[] = '('.$gid.', '.$_POST['id'].')';
				}
				$newbook = $db->query('INSERT INTO booktogenre(genre_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
			}
			//delete all warnings
			$db->query('DELETE FROM booktowarning WHERE book_id_fk='.$_POST['id']);
			//insert warnings
			if(isset($_POST['wid']) and is_array($_POST['wid']))
			{
				$insert = array();
				foreach($_POST['wid'] as $wid)
				{
					$insert[] = '('.$wid.', '.$_POST['id'].')';
				}
				//insert the warnings
				$newbook = $db->query('INSERT INTO booktowarning(warning_id_fk, book_id_fk) VALUES '.implode(', ', $insert));
			}
			$tpl['error'] = 'Your book has been changed';
		}
	}
	//create new chapter
	elseif(isset($_POST['new']) or isset($_POST['preview']))
	{
		if(empty($_POST['title']))
		{
			$tpl['error'] = 'You must provide a chapter title.';
		}
		elseif(strlen($_POST['title']) < 4)
		{
			$tpl['error'] = 'Your chapter title must be at least 4 characters long.';
		}
		//check for text or upload
		elseif(empty($_POST['text']) and $_FILES['datafile']['error'] == 4)
		{
			$tpl['error'] = 'You must either upload a file or enter text in the textarea box.';
		}
		//check for text and upload
		elseif($_FILES['datafile']['error'] != 4 and !empty($_POST['text']))
		{
			$tpl['error'] = 'You must EITHER upload a file OR enter your text in the textarea box, not both.';
		}
		//if we have an upload
		elseif(empty($_POST['text']) and ($_FILES['datafile']['error'] == 1 or $_FILES['datafile']['error']==2))
		{
			$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
		}
		elseif(empty($_POST['text']) and $_FILES['datafile']['error'] == 3)
		{
			$tpl['error'] = 'There was a problem with your upload, please try again.';
		}
		elseif(empty($_POST['text']) and $_FILES['datafile']['size'] < 1)
		{
			$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
		}
		elseif(empty($_POST['text']) and ($_FILES['datafile']['type'] != 'text/plain' and $_FILES['datafile']['type'] != 'text/html'))
		{
			$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
		}
		elseif(empty($_POST['text']) and !is_uploaded_file($_FILES['datafile']['tmp_name']))
		{
			$tpl['error'] = 'Your file was not uploaded properly';
		}
		elseif(empty($_POST['text']))
		{
			$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['datafile']['tmp_name'])))));
		}
		else
		{
			$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))));
		}
		$wordcount = count(preg_split('/\W+/', $_POST['text'], -1, PREG_SPLIT_NO_EMPTY));
		if(!isset($tpl['error']) and $wordcount < 500)
		{
			$tpl['error'] = 'Your chapter must be at least 500 words in length.';
		}
		$_POST['title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		if(isset($tpl['error']) or isset($_POST['preview']))
		{
			$_GET['m'] = 3;
		}
		else
		{
			//first we need to know the next order number
			$result = $db->query('SELECT chapter_order, chapter_valid FROM chapter WHERE book_id_fk='.$_POST['id'].' ORDER BY chapter_order DESC LIMIT 1');
			$tmp = $result->fetch_row();
			$result->close();
			//if we have one waiting, we're gonna die off
			if($tmp[1] == 0)
			{
				$tpl['error'] = 'You currently have a chapter waiting for approval.  Please resubmit this chapter after the previous chapter is accepted or rejected';
			}
			else
			{
				//insert the chapter info - don't update the book until it's approved
				$db->query('INSERT INTO chapter(book_id_fk, chapter_title, chapter_wordcount, chapter_publish, chapter_update, chapter_order) '
					.'VALUES('.$_POST['id'].', \''.$_POST['title'].'\','.$wordcount.', NOW(), NOW(), '.($tmp[0] +1).')');
					echo $db->error;
				$num = $db->insert_id;
				//create file
				$path = str_replace('htdocs/author', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
				file_put_contents($path.'/'.$_POST['id'].'/'.$num.'.txt', $_POST['text']);
				$tpl['error'] = 'Your chapter has been created.  An email will be sent to you with the status of your chapter after it has been screened - then you may alter it with manage chapters.';
			}
		}
	}
	//edit existing chapter
	elseif(isset($_POST['editchapter']))
	{
		$_GET['m'] = 2;
		if(empty($_POST['title']))
		{
			$tpl['error'] = 'You must provide a chapter title.';
		}
		elseif(strlen($_POST['title']) < 4)
		{
			$tpl['error'] = 'Your chapter title must be at least 4 characters long.';
		}
		//check for text or upload
		elseif(empty($_POST['text']) and $_FILES['datafile']['error'] == 4)
		{
			$tpl['error'] = 'You must either upload a file or enter text in the textarea box.';
		}
		//if we have an upload
		elseif($_FILES['datafile']['error'] != 4 and ($_FILES['datafile']['error'] == 1 or $_FILES['datafile']['error']==2))
		{
			$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
		}
		elseif($_FILES['datafile']['error'] != 4 and $_FILES['datafile']['error'] == 3)
		{
			$tpl['error'] = 'There was a problem with your upload, please try again.';
		}
		elseif($_FILES['datafile']['error'] != 4 and $_FILES['datafile']['size'] < 1)
		{
			$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
		}
		elseif($_FILES['datafile']['error'] != 4 and ($_FILES['datafile']['type'] != 'text/plain' and $_FILES['datafile']['type'] != 'text/html'))
		{
			$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
		}
		elseif($_FILES['datafile']['error'] != 4 and !is_uploaded_file($_FILES['datafile']['tmp_name']))
		{
			$tpl['error'] = 'Your file was not uploaded properly';
		}
		elseif($_FILES['datafile']['error'] != 4)
		{
			$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['datafile']['tmp_name'])))));
		}
		else
		{
			$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))));
		}
		$wordcount = count(preg_split('/\W+/', $_POST['text'], -1, PREG_SPLIT_NO_EMPTY));
		if(!isset($tpl['error']) and $wordcount < 500)
		{
			$tpl['error'] = 'Your chapter must be at least 500 words in length.';
		}
		$_POST['title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		if(!isset($tpl['error']))
		{
			//subtract
			$db->query('UPDATE book SET book_wordcount=(bookwordcount-chapter_wordcount) + '.$wordcount.' '
				.'chapter_title=\''.$_POST['title'].'\', chapter_update=NOW(), chapter_wordcount='.$wordcount.' '
				.'LEFT JOIN chapter ON book_id=book_id_fk WHERE book_id='.$_POST['id'].' AND chapter_id='.$_POST['cid']);
			//change file
			$path = str_replace('htdocs/author', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
			file_put_contents($path.'/'.$_POST['id'].'/'.$_POST['cid'].'.txt', $_POST['text']);
			$tpl['error'] = 'Your chapter has been altered.';
			unset($_REQUEST['cid']);
		}
	}
	//add a replay
	elseif(isset($_POST['reply']))
	{
		$db->query('UPDATE comment SET comment_reply=\''.htmlentities(html_entity_decode(undoword(strip_tags($_POST['text'])))).'\' WHERE comment_id='.$_POST['c']);
	}
	//move up move up move down move down lalalala
	elseif(isset($_GET['m']) and $_GET['m'] == 2 and isset($_GET['id']) and isset($_GET['uid']))
	{
		//get current order
		$result = $db->query('SELECT chapter_order FROM chapter WHERE chapter_id='.$_GET['uid'].' AND book_id_fk='.$_GET['id']);
		$tmp = $result->fetch_row();
		$result->close();
		$db->query('UPDATE chapter SET chapter_order = '.$tmp[0].' WHERE chapter_order='.($tmp[0]-1).' AND book_id_fk='.$_GET['id'].' AND chapter_id !='.$_GET['uid']);
		$db->query('UPDATE chapter SET chapter_update=NOW(), chapter_order = chapter_order - 1 WHERE chapter_id='.$_GET['uid']);
		$db->query('UPDATE book SET book_update=NOW() WHERE book_id='.$_GET['id']);
	}
	//move up move up move down move down lalalala
	elseif(isset($_GET['m']) and $_GET['m'] == 2 and isset($_GET['id']) and isset($_GET['eid']))
	{
		//get current order
		$result = $db->query('SELECT chapter_order FROM chapter WHERE chapter_id='.$_GET['eid'].' AND book_id_fk='.$_GET['id']);
		$tmp = $result->fetch_row();
		$result->close();
		$db->query('UPDATE chapter SET chapter_order = '.$tmp[0].' WHERE chapter_order='.($tmp[0]+1).' AND book_id_fk='.$_GET['id'].' AND chapter_id !='.$_GET['eid']);
		$db->query('UPDATE chapter SET chapter_update=NOW(), chapter_order = chapter_order + 1 WHERE chapter_id='.$_GET['eid']);
		$db->query('UPDATE book SET book_update=NOW() WHERE book_id='.$_GET['id']);
	}
	//delete un chapter
	elseif(isset($_GET['m']) and $_GET['m'] == 2 and isset($_GET['id']) and isset($_GET['did']))
	{
		//get order, wordcount for updating
		$result = $db->query('SELECT chapter_order AS `order`, chapter_wordcount AS wordcount FROM chapter WHERE chapter_id='.$_GET['did'].' AND book_id_fk='.$_GET['id']);
		$tmp = $result->fetch_assoc();
		$result->close();
		//delete the row
		$db->query('DELETE FROM chapter WHERE chapter_id='.$_GET['did'].' AND book_id_fk='.$_GET['id']);
		//update other chapters orders
		$db->query('UPDATE chapter SET chapter_order=chapter_order - 1 WHERE chapter_order>'.$tmp['order'].' AND book_id_fk='.$_GET['id']);
		//update the book
		$db->query('UPDATE book SET book_chapters = book_chapters -1, book_wordcount=book_wordcount-'.$tmp['wordcount'].', book_update=NOW() WHERE book_id='.$_GET['id']);
		//erase the file
		$path = str_replace('htdocs/author', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
		unlink($path.'/'.$_GET['id'].'/'.$_GET['did'].'.txt');
		$tpl['error'] = 'Your chapter has been deleted.';
	}
	//decide our mode
	if(isset($_GET['m']) and $_GET['m'] == 3)
	{
		$tpl['mode'] = 3;
		//get list of books
		$result = $db->query('SELECT book_id AS id, book_title AS title '
		.'FROM book LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book_id LEFT JOIN author ON author_id_fk=author_id WHERE book_valid=1 AND user_id_fk='.$session->get('user', 'user').' AND book_id='.$_REQUEST['id'].' LIMIT 1');
		$tpl['book'] = $result->fetch_assoc();
		$result->close();
	}
	elseif(isset($_REQUEST['m']) and $_REQUEST['m'] == 2)
	{
		$tpl['mode'] = 2;
		if(isset($_REQUEST['cid']))
		{
			$result = $db->query('SELECT chapter_id AS id, chapter_title AS title, chapter_wordcount AS wordcount, book_id AS bid, book_title AS btitle, '
				.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
				.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
				.'FROM chapter LEFT JOIN book ON book_id=chapter.book_id_fk LEFT JOIN booktoauthor ON book_id=booktoauthor.book_id_fk LEFT JOIN author ON author_id_fk=author_id '
				.'WHERE chapter_valid=1 AND chapter_id='.$_REQUEST['cid'].' GROUP BY book_id LIMIT 1');
			$tpl['chapter'] = $result->fetch_assoc();
			$path = str_replace('htdocs/author', 'data/files', str_replace('\\', '/', dirname(__FILE__))).'/'.$tpl['chapter']['bid'].'/'.$tpl['chapter']['id'].'.txt';
			$tpl['chapter']['text'] = file_get_contents($path);
			$result->close();
		}
		elseif(isset($_REQUEST['rid']))
		{
			if(isset($_GET['c']))
			{
				$request = $db->query('SELECT comment_text AS text FROM comment WHERE comment_id='.$_GET['c'].' LIMIT 1');
				$tmp = $request->fetch_row();
				$tpl['text'] = $tmp[0];
				$request->close();
			}
			//current size(limit)
			$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
			$tpl['size'] = $size;
			//current page - get offset
			$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
			$tpl['page'] = $page;
			//find offset
			$offset = ($page - 1) * $size;
			$comments = $db->query('SELECT comment_id AS cid, comment_text AS text, comment_reply AS reply, comment_date AS date, comment_private AS private, user_id AS id, user_name AS name '
				.'FROM comment LEFT JOIN user ON user_id_fk=user_id WHERE book_id_fk='.$_REQUEST['rid'].' ORDER BY comment_date DESC, user_name ASC LIMIT '.$offset.', '.$size);
			if(!$comments)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['comments'] =& $comments;
			//get total for paging
			$count = $db->query('SELECT COUNT(comment_id) FROM comment WHERE comment_private=0 AND book_id_fk='.$_REQUEST['rid']);
			if(!$count)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $count->fetch_row();
			$tpl['total'] = $total[0];
			$count->close();
			//do paging
			if($tpl['total'] > $size)
			{
				//do we need a previous link?
				if($offset > 0)
				{
					$tpl['prev'] = TRUE;
				}
				else
				{
					$tpl['prev'] = FALSE;
				}
				//pages
				$page_total = ceil($tpl['total'] /$size);
				//do we need a next link?
				if($page < $page_total)
				{
					$tpl['next'] = TRUE;
				}
				else
				{
					$tpl['next'] = FALSE;
				}
				$tpl['pages'] = array();
				//page array
				for($i = 1; $i <= $page_total; $i++)
				{
					$tpl['pages'][$i] = $i;
				}
			}
		}
		else
		{
			//get list of chapters
			$tpl['chapters'] = $db->query('SELECT book_id_fk AS bid, chapter_id AS id, chapter_title AS title, chapter_wordcount AS wordcount, chapter_publish AS publish, chapter_update AS `update` '
			.'FROM chapter WHERE chapter_valid=1 AND book_id_fk='.$_REQUEST['id'].' ORDER BY chapter_order');
			//get top and bottom chapters
			$result = $db->query('SELECT chapter_id FROM chapter WHERE chapter_valid=1 AND book_id_fk='.$_REQUEST['id'].' ORDER BY chapter_order DESC');
			$tmp = $result->fetch_row();
			$tpl['max'] = $tmp[0];
			$result->close();
			//get top and bottom chapters
			$result = $db->query('SELECT chapter_id FROM chapter WHERE chapter_valid=1 AND book_id_fk='.$_REQUEST['id'].' ORDER BY chapter_order ASC');
			$tmp = $result->fetch_row();
			$tpl['min'] = $tmp[0];
			$result->close();
		}
	}
	elseif(isset($_GET['m']) and $_GET['m'] == 1)
	{
		$tpl['mode'] = 1;
		//get book information
		$result = $db->query('SELECT book_id AS id, book_title AS title, book_summary AS summary, rating_id_fk AS rid, category_id_fk AS cid, type_id_fk AS tid, style_id_fk AS sid, book_notes AS text, book_completed AS status, '
			.'group_concat(DISTINCT character_id_fk ORDER BY booktocharacter_id ASC SEPARATOR \':\') AS chid, '
			.'group_concat(DISTINCT genre_id_fk ORDER BY booktogenre_id ASC SEPARATOR \':\') AS gid, '
			.'group_concat(DISTINCT warning_id_fk ORDER BY booktowarning_id ASC SEPARATOR \':\') AS wid '
			.'FROM book '
			.'LEFT JOIN booktocharacter ON book_id=booktocharacter.book_id_fk '
			.'LEFT JOIN booktogenre ON book_id=booktogenre.book_id_fk '
			.'LEFT JOIN booktowarning ON book_id=booktowarning.book_id_fk '
			.'WHERE book_id='.$_GET['id'].' GROUP BY book_id LIMIT 1');
		$tpl['book'] = $result->fetch_assoc();
		$result->close();
		//get category list
		$tpl['catlist'] = $db->query('SELECT category_id AS id, category_name AS name, category_description AS description FROM category ORDER BY category_name ASC');
		//get type list
		$tpl['typelist'] = $db->query('SELECT type_id AS id, type_name AS name, type_description AS description FROM type ORDER BY type_id ASC');
		//get styles
		$tpl['stylelist'] = $db->query('SELECT style_id AS id, style_name AS name, style_description AS description FROM style ORDER BY style_name ASC');
		//get ratings
		$tpl['ratinglist'] = $db->query('SELECT rating_id AS id, rating_name AS name, rating_description AS description FROM rating ORDER BY rating_name ASC');
		//get character list
		$tpl['charlist'] = $db->query('SELECT character_id AS id, character_name AS name, character_description AS description FROM `character` WHERE category_id_fk=0 or category_id_fk='.$tpl['book']['cid'].' ORDER BY character_name ASC');
		//get genres
		$tpl['genrelist'] = $db->query('SELECT genre_id AS id, genre_name AS name, genre_description AS description FROM genre ORDER BY genre_name ASC');
		//get warnings
		$tpl['warninglist'] = $db->query('SELECT warning_id AS id, warning_name AS name, warning_description AS description FROM warning ORDER BY warning_name ASC');
	}
	else
	{
		$tpl['mode'] = 0;
		//get list of books
		$tpl['books'] = $db->query('SELECT book_id AS id, book_title AS title, book_chapters as chapters, book_wordcount AS wordcount, book_publish AS publish, book_update AS `update`, '
		.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
		.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
		.'FROM book LEFT JOIN booktoauthor ON book_id_fk=book_id LEFT JOIN author ON author_id_fk=author_id WHERE book_valid=1 AND user_id_fk='.$session->get('user', 'user').' GROUP BY book_id ORDER BY book_publish, book_title');
	}
	//page assignments
	$tpl['title'] = 'Edit Books';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library author book edit';
	$tpl['description'] = 'Fanfiction Library Author book edit';
	//assign sub "template"
	$files['page'] = 'authorbookedit.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function delete_book()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$author = $session->get('author', 'user');
	if(empty($author))
	header('Location: ../user/index.php?a=2');
	//if our id is set, then we do the big mama delete
	if(isset($_GET['id']))
	{
		//first, we check to see if this book exists, and is owned by this author
		$result = $db->query('SELECT book_id_fk FROM booktoauthor LEFT JOIN author ON author_id_fk=author_id WHERE user_id_fk='.$session->get('user', 'user'));
		if($result->num_rows < 1)
		{
			$tpl['error'] = 'Either you are not an author for this book or the book you specified does not exist';
			$result->close();
		}
		else
		{
			$result->close();
			//get cat _id_fk to change total
			$result = $db->query('SELECT cat_id_fk FROM book WHERE book_id='.$_GET['id']);
			$cat = $result->fetch_row();
			$cat = $cat[0];
			$result->close();
			//update category count
			$db->query('UPDATE category SET category_total = category_total -1 WHERE category_id='.$cat);
			//delete any favorites
			$db->query('DELETE FROM usertobook WHERE book_id_fk='.$_GET['id']);
			//delete any featured articles
			$db->query('DELETE FROM featured WHERE book_id_fk='.$_GET['id']);
			//delete comments
			$db->query('DELETE FROM comments WHERE book_id_fk='.$_GET['id']);
			//delete any chapters
			$db->query('DELETE FROM chapter WHERE book_id_fk='.$_GET['id']);
			//delete warnings
			$db->query('DELETE FROM booktowarning WHERE book_id_fk='.$_GET['id']);
			//delete genres
			$db->query('DELETE FROM booktogenre WHERE book_id_fk='.$_GET['id']);
			//delete characters
			$db->query('DELETE FROM booktocharacter WHERE book_id_fk='.$_GET['id']);
			//delete authors
			$db->query('DELETE FROM booktoauthor WHERE book_id_fk='.$_GET['id']);
			//delete book
			$db->query('DELETE FROM book WHERE book_id='.$_GET['id']);
			//finally we unlink the directory and all subdirs
			$path = str_replace('htdocs/author', 'data/files', str_replace('\\', '/', dirname(__FILE__))).'/'.$_GET['id'];
			//unlink all files inside
			$handle = opendir($path);
			while(FALSE!==($file = readdir($handle)))
			{
				if($file != '.' and $file != '..')
				unlink($path.'/'.$file);
			}
			closedir($handle);
			rmdir($path);
			//finally, we're all done
			$tpl['error'] = 'Your book and all related information has been deleted';
		}
	}
	//get list of books
	$tpl['books'] = $db->query('SELECT book_id AS id, book_title AS title, book_chapters as chapters, book_wordcount AS wordcount, '
		.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
		.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
		.'FROM book LEFT JOIN booktoauthor ON book_id_fk=book_id LEFT JOIN author ON author_id_fk=author_id WHERE book_valid=1 AND user_id_fk='.$session->get('user', 'user').' GROUP BY book_id ORDER BY book_publish, book_title');
	//page assignments
	$tpl['title'] = 'Delete Books';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library author book delete';
	$tpl['description'] = 'Fanfiction Library Author book delete';
	//assign sub "template"
	$files['page'] = 'authorbookdelete.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function stats_book()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$author = $session->get('author', 'user');
	if(empty($author))
	header('Location: ../user/index.php?a=2');
	//get all info for books
	$tpl['books'] = $db->query('SELECT book_id AS id, book_title AS title, book_chapters AS chapters, book_wordcount AS wordcount, book_views AS views, book_ranking AS ranking, book_comments AS comments, book_publish AS publish, book_update AS `update` '
		.'FROM book LEFT JOIN booktoauthor ON book_id_fk=book_id LEFT JOIN author ON author_id_fk=author_id WHERE book_valid=1 AND user_id_fk='.$session->get('user', 'user').' ORDER BY book_publish, book_title');
	//get all info from chapters sorted by book
	$tpl['chapters'] = $db->query('SELECT chapter.book_id_fk AS bookid, chapter_id AS id, chapter_title AS title, chapter_views AS views, chapter_wordcount AS wordcount, chapter_update AS `update`, chapter_publish AS publish FROM chapter '
		.'LEFT JOIN book ON book_id=chapter.book_id_fk LEFT JOIN booktoauthor ON chapter.book_id_fk=booktoauthor.book_id_fk LEFT JOIN author ON author_id=author_id_fk '
		.'WHERE book_valid=1 AND chapter_valid=1 AND user_id_fk='.$session->get('user', 'user').' ORDER BY chapter.book_id_fk, chapter_order');
	//page assignments
	$tpl['title'] = 'Book Statistics';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library author book stats';
	$tpl['description'] = 'Fanfiction Library Author book stats';
	//assign sub "template"
	$files['page'] = 'authorstat.html';
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

//first we check the get for a set action
define('PHPFF_INCLUDE', TRUE, TRUE);
include('../prepend.php');
$action = !isset($_REQUEST['a']) ? 0 : (int) $_REQUEST['a'];
switch($action)
{
	//view stats
	case 3:
		stats_book();
		break;
	//delete book(s)
	case 2:
		delete_book();
		break;
	//edit book
	case 1:
		edit_book();
		break;
	//create new book
	default:
		new_book();
		break;
}
include('../append.php');
?>
