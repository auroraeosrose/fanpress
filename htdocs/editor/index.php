<?php
/**
 * index.php - allows editors to screen library info
 *
 * creates a queue of stories, authors, chapters to approve, disapprove for editors
 *
 * This is released under the GPL, see license.txt for details
 *
 * @author       Elizabeth Smith <liz@phpfanfiction.com>
 * @copyright    Elizabeth Smith @2004
 * @link         http://phpfanfiction.com
 * @license      http://www.opensource.org/licenses/gpl-license.php GPL
 * @version      $Id: index.php,v 1.2 2004/07/28 20:37:49 liz Exp $
 * @requires     none
 * @package      phpfanfiction
 * @subpackage   core
 * @category     htdocs
 * @filesource
 */

function author_queue()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$editor = $session->get('editor', 'user');
	if(empty($editor))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Author Queue ~*~ Editor';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library list author queue approve editor';
	$tpl['description'] = 'Fanfiction Library Editor author approval queue';
	//assign sub "template"
	$files['page'] = 'editorauthorqueue.html';
	//we do approve or reject first
	if(isset($_POST['approve']))
	{
		if(empty($_POST['comment']))
		{
			$tpl['error'] = 'No comments were recorded.';
		}
		else
		{
			//grab stuff
			$authors = $db->query('SELECT author_contact AS contact, author_name AS name FROM author WHERE author_id='.$_POST['id'].' LIMIT 1');
			if(!$authors)
			{
				printf('Errormessage: %s', $db->error);
			}
			$info = $authors->fetch_assoc();
			$authors->close();
			//set valid
			$authors = $db->query('UPDATE author LEFT JOIN user ON user_id=user_id_fk SET author_valid=1, author_date=NOW(), user_level=1 WHERE author_id='.$_POST['id']);
			if(!$authors)
			{
				printf('Errormessage: %s', $db->error);
			}
			//send mail
			$config = get_config();
			ob_start();
			include('../../data/tpl/'.$config['theme'].'/email/authapprove.txt');
			$message = ob_get_clean();
			$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
				.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
			mail($info['contact'], 'Author Approved', $message, $headers);
			unset($_REQUEST['id']);
		}
	}
	elseif(isset($_POST['reject']))
	{
		if(empty($_POST['comment']))
		{
			$tpl['error'] = 'No comments were recorded.';
		}
		else
		{
			//grab stuff
			$authors = $db->query('SELECT author_contact AS contact, author_name AS name FROM author WHERE author_id='.$_POST['id'].' LIMIT 1');
			if(!$authors)
			{
				printf('Errormessage: %s', $db->error);
			}
			$info = $authors->fetch_assoc();
			$authors->close();
			//remove author
			$authors = $db->query('DELETE FROM author WHERE author_id='.$_POST['id'].' LIMIT 1');
			if(!$authors)
			{
				printf('Errormessage: %s', $db->error);
			}
			//send mail
			$config = get_config();
			ob_start();
			include('../../data/tpl/'.$config['theme'].'/email/authreject.txt');
			$message = ob_get_clean();
			$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
				.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
			mail($info['contact'], 'Author Rejected', $message, $headers);
			unset($_REQUEST['id']);
		}
	}
	//if id is set, then we show the author with approve/reject options
	if(isset($_REQUEST['id']))
	{
		//grab everything
		$authors = $db->query('SELECT author_id AS id, author_file AS file FROM author WHERE author_id='.$_REQUEST['id'].' LIMIT 1');
		if(!$authors)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['authors'] = $authors->fetch_assoc();
		$authors->close();
	}
	//otherwise we show the list
	else
	{
		//create abcd list
		$tpl['alphabet'][] = 'NUM';
		$i = 'A';
		for($n = 0; $n < 26; $n++)
		{
			$tpl['alphabet'][] = $i;
			$i++;
		}
		$tpl['alphabet'][] = 'ALL';
		//now we set the letter, or it may not be
		$letter = !isset($_GET['l']) ? NULL : (string) $_GET['l'];
		//check it for idiots
		if($letter == 'ALL' or (!is_null($letter) and !in_array($letter, $tpl['alphabet'])))
		$letter = NULL;
		$where = '';
		if(!is_null($letter))
		{
			//append the letter
			$tpl['title'] .= ': '.$letter;
			$tpl['letter'] = $letter;
			$where = ' AND author_order=\''.$letter.'\' ';
		}
		//current size(limit)
		$size = !isset($_GET['s']) ? 50 : (int) $_GET['s'];
		$tpl['size'] = $size;
		//current page - get offset
		$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
		$tpl['page'] = $page;
		//find offset
		$offset = ($page - 1) * $size;
		$tpl['offset'] = $offset + 1;
		//grab announcement count
		$count = $db->query('SELECT COUNT(author_id) FROM author WHERE author_valid=0'.$where);
		if(!$count)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $count->fetch_row();
		$tpl['total'] = $total = $total[0];
		$count->close();
		//message for no authors
		if($total < 1)
		$tpl['error'] = 'No Authors Found';
		//do paging
		if($total > $size)
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
			$page_total = ceil($total/$size);
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
		//grab list
		$authors = $db->query('SELECT author_id AS id, author_date AS date, author_name AS name, user_id AS uid, user_name AS uname FROM author LEFT JOIN user ON user_id=user_id_fk WHERE author_valid=0'.$where.' ORDER BY author_name DESC, author_id ASC LIMIT '.$offset.', '.$size);
		if(!$authors)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['authors'] =& $authors ;
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function book_queue()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$editor = $session->get('editor', 'user');
	if(empty($editor))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Book Queue ~*~ Editor';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library list book queue approve editor';
	$tpl['description'] = 'Fanfiction Library Editor book approval queue';
	//assign sub "template"
	$files['page'] = 'editorbookqueue.html';
	//we do approve or reject first
	if(isset($_POST['approve']))
	{
		if(empty($_POST['comment']))
		{
			$tpl['error'] = 'No comments were recorded.';
		}
		else
		{
			$book = $db->query('SELECT book_id AS id, book_title AS title, book_summary AS summary, chapter_id AS chid, category_id_fk AS cat, '
				.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS authors, '
				.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
				.'group_concat(DISTINCT author_contact ORDER BY author_id ASC SEPARATOR \':\') AS authoremail '
				.'FROM book '
				.'LEFT JOIN chapter ON book.book_id=chapter.book_id_fk '
				.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
				.'WHERE book.book_id='.$_REQUEST['id'].' GROUP BY book.book_id ORDER BY chapter_order LIMIT 1');
			$info = $book->fetch_assoc();
			$book->close();
			//set book valid
			$valid = $db->query('UPDATE book SET book_valid=1, book_publish=NOW(), book_update=NOW() WHERE book_id='.$_POST['id']);
			//set chapter valid
			$valid = $db->query('UPDATE chapter SET chapter_valid=1, chapter_publish=NOW(), chapter_update=NOW() WHERE book_id_fk='.$_POST['id']);
			//category must increment
			$valid = $db->query('UPDATE category SET category_total = category_total +1 WHERE category_id='.$info['cat']);
			//every author must increment
			$where = array();
			$authors = explode(':', $info['authorid']);
			$where = 'author_id='.implode(' OR author_id=', explode(':', $info['authorid']));
			$valid = $db->query('UPDATE author SET author_count=author_count+1 WHERE '.$where);
			$config = get_config();
			ob_start();
			include('../../data/tpl/'.$config['theme'].'/email/bookapprove.txt');
			$message = ob_get_clean();
			$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
				.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
			$authors = explode(':', $info['authoremail']);
			foreach($authors as $address)
			{
				mail($address, 'Book Approve', $message, $headers);
			}
			unset($_REQUEST['id']);
		}
	}
	elseif(isset($_POST['reject']))
	{
		if(empty($_POST['comment']))
		{
			$tpl['error'] = 'No comments were recorded.';
		}
		else
		{
			$book = $db->query('SELECT book_id AS id, book_title AS title, book_summary AS summary, chapter_id AS chid, '
				.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS authors, '
				.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
				.'group_concat(DISTINCT author_contact ORDER BY author_id ASC SEPARATOR \':\') AS authoremail '
				.'FROM book '
				.'LEFT JOIN chapter ON book.book_id=chapter.book_id_fk '
				.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
				.'WHERE book.book_id='.$_REQUEST['id'].' GROUP BY book.book_id ORDER BY chapter_order LIMIT 1');
			$info = $book->fetch_assoc();
			$book->close();
			//remove file and book folder
			$path = str_replace('htdocs/editor', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
			unlink($path.'/'.$info['id'].'/'.$info['chid'].'.txt');
			rmdir($path.'/'.$info['id']);
			//remove chapter, bookto mappings, and actual book
			$delete = $db->query('DELETE FROM chapter WHERE chapter_id='.$info['chid']);
			$delete = $db->query('DELETE FROM booktoauthor WHERE book_id_fk='.$info['id']);
			$delete = $db->query('DELETE FROM booktogenre WHERE book_id_fk='.$info['id']);
			$delete = $db->query('DELETE FROM booktowarning WHERE book_id_fk='.$info['id']);
			$delete = $db->query('DELETE FROM booktocharacter WHERE book_id_fk='.$info['id']);
			$delete = $db->query('DELETE FROM book WHERE book_id='.$info['id']);
			//send mail
			$config = get_config();
			ob_start();
			include('../../data/tpl/'.$config['theme'].'/email/bookreject.txt');
			$message = ob_get_clean();
			$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
				.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
			$authors = explode(':', $info['authoremail']);
			foreach($authors as $address)
			{
				mail($address, 'Book Rejected', $message, $headers);
			}
			unset($_REQUEST['id']);
		}
	}
	//if id is set, then we show the author with approve/reject options
	if(isset($_REQUEST['id']))
	{
		$path = str_replace('htdocs/editor', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
		//grab everything
		$book = $db->query('SELECT book_id AS id, book_title AS title, book_summary AS summary, book_notes AS notes, '
			.'chapter_title AS chtitle, chapter_id AS chid, '
			.'rating_name AS rating, category_name AS category, type_name AS type, '
			.'group_concat(DISTINCT genre_name ORDER BY genre_id ASC SEPARATOR \', \') AS genres, '
			.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
			.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
			.'group_concat(DISTINCT warning_name ORDER BY warning_id ASC SEPARATOR \', \') AS warnings, '
			.'group_concat(DISTINCT character_name ORDER BY character_id ASC SEPARATOR \', \') AS `characters` '
			.'FROM book '
			.'LEFT JOIN chapter ON book.book_id=chapter.book_id_fk '
			.'LEFT JOIN type ON book.type_id_fk=type.type_id '
			.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
			.'LEFT JOIN category ON category.category_id=book.category_id_fk '
			.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
			.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
			.'WHERE book.book_id='.$_REQUEST['id'].' GROUP BY book.book_id ORDER BY chapter_order LIMIT 1');
		if(!$book)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['book'] = $book->fetch_assoc();
		$book->close();
		ob_start();
		include($path.'/'.$tpl['book']['id'].'/'.$tpl['book']['chid'].'.txt');
		$tpl['book']['chapter'] = ob_get_clean();
	}
	//otherwise we show the list
	else
	{
		//create abcd list
		$tpl['alphabet'][] = 'NUM';
		$i = 'A';
		for($n = 0; $n < 26; $n++)
		{
			$tpl['alphabet'][] = $i;
			$i++;
		}
		$tpl['alphabet'][] = 'ALL';
		//now we set the letter, or it may not be
		$letter = !isset($_GET['l']) ? NULL : (string) $_GET['l'];
		//check it for idiots
		if($letter == 'ALL' or (!is_null($letter) and !in_array($letter, $tpl['alphabet'])))
		$letter = NULL;
		$where = '';
		if(!is_null($letter))
		{
			//append the letter
			$tpl['title'] .= ': '.$letter;
			$tpl['letter'] = $letter;
			$where = ' AND book_order=\''.$letter.'\' ';
		}
		//current size(limit)
		$size = !isset($_GET['s']) ? 50 : (int) $_GET['s'];
		$tpl['size'] = $size;
		//current page - get offset
		$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
		$tpl['page'] = $page;
		//find offset
		$offset = ($page - 1) * $size;
		$tpl['offset'] = $offset + 1;
		//grab announcement count
		$count = $db->query('SELECT COUNT(book_id) FROM book WHERE book_valid=0'.$where);
		if(!$count)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $count->fetch_row();
		$tpl['total'] = $total = $total[0];
		$count->close();
		//message for no authors
		if($total < 1)
		$tpl['error'] = 'No Books Found';
		//do paging
		if($total > $size)
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
			$page_total = ceil($total/$size);
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
		//grab announcements for list
		$books = $db->query(
			'SELECT book_id AS id, book_title AS title, book_wordcount AS wordcount, book_publish AS date, group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
			.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid FROM book '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'WHERE book_valid=0'.$where.' GROUP BY book.book_id  ORDER BY book_title DESC, book_id ASC LIMIT '.$offset.', '.$size);
		if(!$books)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['books'] =& $books ;
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function chapter_queue()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$editor = $session->get('editor', 'user');
	if(empty($editor))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Chapter Queue ~*~ Editor';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library list chapter queue approve editor';
	$tpl['description'] = 'Fanfiction Library Editor chapter approval queue';
	//assign sub "template"
	$files['page'] = 'editorchapterqueue.html';
	//we do approve or reject first
	if(isset($_POST['approve']))
	{
		if(empty($_POST['comment']))
		{
			$tpl['error'] = 'No comments were recorded.';
		}
		else
		{
			$book = $db->query('SELECT book_id AS id, book_title AS title, chapter_title AS chtitle, chapter_wordcount AS wordcount, '
				.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS authors, '
				.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
				.'group_concat(DISTINCT author_contact ORDER BY author_id ASC SEPARATOR \':\') AS authoremail '
				.'FROM chapter LEFT JOIN book ON book_id=chapter.book_id_fk '
				.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
				.'WHERE chapter_id='.$_REQUEST['id'].' GROUP BY book_id LIMIT 1');
			$info = $book->fetch_assoc();
			$book->close();
			//set chapter valid
			$valid = $db->query('UPDATE chapter SET chapter_valid=1, chapter_publish=NOW(), chapter_update=NOW() WHERE chapter_id='.$_POST['id']);
			//update book information
			$valid = $db->query('UPDATE book SET book_update=NOW(), book_wordcount = book_wordcount + '.$info['wordcount'].', book_chapters= book_chapters + 1  WHERE book_id='.$info['id']);
			$config = get_config();
			ob_start();
			include('../../data/tpl/'.$config['theme'].'/email/chapterapprove.txt');
			$message = ob_get_clean();
			$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
				.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
			$authors = explode(':', $info['authoremail']);
			foreach($authors as $address)
			{
				mail($address, 'Chapter Approve', $message, $headers);
			}
			unset($_REQUEST['id']);
		}
	}
	elseif(isset($_POST['reject']))
	{
		if(empty($_POST['comment']))
		{
			$tpl['error'] = 'No comments were recorded.';
		}
		else
		{
			$book = $db->query('SELECT book_id AS id, book_title AS title, chapter_title AS chtitle, '
				.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS authors, '
				.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
				.'group_concat(DISTINCT author_contact ORDER BY author_id ASC SEPARATOR \':\') AS authoremail '
				.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
				.'WHERE chapter_id='.$_REQUEST['id'].' LIMIT 1');
			$info = $book->fetch_assoc();
			$book->close();
			//remove file
			$path = str_replace('htdocs/editor', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
			unlink($path.'/'.$info['id'].'/'.$_REQUEST['id'].'.txt');
			//remove chapter
			$delete = $db->query('DELETE FROM chapter WHERE chapter_id='.$_REQUEST['id']);
			//send mail
			$config = get_config();
			ob_start();
			include('../../data/tpl/'.$config['theme'].'/email/chapterreject.txt');
			$message = ob_get_clean();
			$headers = 'From: bot@'.$config['domain']."\n".'X-Sender: admin@'.$config['domain']."\n"
				.'X-Mailer: PHP'."\n".'X-Priority: 3'."\n".'Return-Path: no-reply@'.$config['domain']."\n";
			$authors = explode(':', $info['authoremail']);
			foreach($authors as $address)
			{
				mail($address, 'Chapter Rejected', $message, $headers);
			}
			unset($_REQUEST['id']);
		}
	}
	//if id is set, then we show the author with approve/reject options
	if(isset($_REQUEST['id']))
	{
		$path = str_replace('htdocs/editor', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
		//grab everything
		$book = $db->query('SELECT book_id AS id, book_title AS title, chapter_title AS chtitle, chapter_id AS chid, '
				.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS authors, '
				.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
				.'group_concat(DISTINCT author_contact ORDER BY author_id ASC SEPARATOR \':\') AS authoremail '
			.'FROM chapter LEFT JOIN book ON book.book_id=chapter.book_id_fk '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'WHERE chapter_id='.$_REQUEST['id'].' GROUP BY book.book_id ORDER BY chapter_order LIMIT 1');
		if(!$book)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['chapter'] = $book->fetch_assoc();
		$book->close();
		ob_start();
		include($path.'/'.$tpl['chapter']['id'].'/'.$tpl['chapter']['chid'].'.txt');
		$tpl['chapter']['chapter'] = ob_get_clean();
	}
	//otherwise we show the list
	else
	{
		//create abcd list
		$tpl['alphabet'][] = 'NUM';
		$i = 'A';
		for($n = 0; $n < 26; $n++)
		{
			$tpl['alphabet'][] = $i;
			$i++;
		}
		$tpl['alphabet'][] = 'ALL';
		//now we set the letter, or it may not be
		$letter = !isset($_GET['l']) ? NULL : (string) $_GET['l'];
		//check it for idiots
		if($letter == 'ALL' or (!is_null($letter) and !in_array($letter, $tpl['alphabet'])))
		$letter = NULL;
		$where = '';
		if(!is_null($letter))
		{
			//append the letter
			$tpl['title'] .= ': '.$letter;
			$tpl['letter'] = $letter;
			$where = ' AND book_order=\''.$letter.'\' ';
		}
		//current size(limit)
		$size = !isset($_GET['s']) ? 50 : (int) $_GET['s'];
		$tpl['size'] = $size;
		//current page - get offset
		$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
		$tpl['page'] = $page;
		//find offset
		$offset = ($page - 1) * $size;
		$tpl['offset'] = $offset + 1;
		//grab announcement count
		$count = $db->query('SELECT COUNT(chapter_id) FROM chapter LEFT JOIN book ON book_id_fk=book_id WHERE chapter_valid=0 AND book_valid = 1'.$where);
		if(!$count)
		{
			printf('Errormessage: %s', $db->error);
		}
		$total = $count->fetch_row();
		$tpl['total'] = $total = $total[0];
		$count->close();
		//message for no authors
		if($total < 1)
		$tpl['error'] = 'No Chapters Found';
		//do paging
		if($total > $size)
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
			$page_total = ceil($total/$size);
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
		//grab announcements for list
		$tpl['chapters'] = $db->query(
			'SELECT book_id AS id, book_title AS title, chapter_id AS chid, chapter_title AS chtitle, chapter_wordcount AS wordcount, chapter_publish AS date, group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
			.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid FROM chapter LEFT JOIN book ON book_id=chapter.book_id_fk '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'WHERE chapter_valid=0 AND book_valid=1'.$where.' GROUP BY book.book_id ORDER BY chapter_title, book_title DESC, book_id ASC LIMIT '.$offset.', '.$size);
	}
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

function featured()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$editor = $session->get('editor', 'user');
	if(empty($editor))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Featured Stories ~*~ Editor';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library list featured story editor';
	$tpl['description'] = 'Fanfiction Library Editor featured approval queue';
	//assign sub "template"
	$files['page'] = 'editorfeatured.html';
	//delete any right away
	if(isset($_GET['did']))
	{
		$did = (int) $_GET['did'];
		//delete the feature
		$delete = $db->query('DELETE FROM featured WHERE featured_id='.$did.' and user_id_fk='.$session->get('user', 'user').' LIMIT 1');
		if(!$delete)
		{
			printf('Errormessage: %s', $db->error);
		}
	}
	//create rating
	elseif(isset($_POST['new']))
	{
		if(empty($_POST['btitle']))
		{
			$tpl['error'] = 'You must submit a book title to feature';
		}
		//find it
		$result = $db->query('SELECT book_id AS id FROM book WHERE book_title = \''.$db->real_escape_string(htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['btitle'])))).'\'');
		if($result->num_rows < 1)
		{
			$tpl['error'] = 'That title does not exists, please check your spelling and case.';
		}
		else
		{
			$id = $result->fetch_row();
			$id = $id[0];
		}
		$result->close();
		if(empty($_POST['title']) or empty($_POST['summary']) or empty($_POST['text']))
		{
			$tpl['error'] = 'A title, summary, and text are required.';
		}
		//clean em
		$_POST['title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		$_POST['summary'] = htmlentities(strip_tags($_POST['summary']));
		$_POST['text'] = htmlentities(strip_tags($_POST['text']));
		if(!isset($tpl['error']))
		{
			$result = $db->query('INSERT INTO featured(book_id_fk, user_id_fk, featured_title, featured_summary, featured_text, featured_date) '
				.'VALUES('.$id.', '.$session->get('user', 'user').',\''.$db->real_escape_string($_POST['title']).'\',\''.$db->real_escape_string($_POST['summary']).'\',\''.$db->real_escape_string($_POST['text']).'\', NOW())');
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	//grab edit rating
	elseif(isset($_GET['id']))
	{
		$edit = $db->query('SELECT featured_id AS id, featured_title AS title, featured_summary AS summary, featured_text AS text FROM featured WHERE featured_id='.$_GET['id'].' LIMIT 1');
		if(!$edit)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['edit'] = $edit->fetch_assoc();
		$edit->close();
	}
	//edit rating
	elseif(isset($_POST['edit']))
	{
		if(empty($_POST['title']) or empty($_POST['summary']) or empty($_POST['text']))
		{
			$tpl['error'] = 'A title, summary, and text are required.';
		}
		//clean em
		$_POST['title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		$_POST['summary'] = htmlentities(strip_tags($_POST['summary']));
		$_POST['text'] = htmlentities(strip_tags($_POST['text']));
		if(!isset($tpl['error']))
		{
			$result = $db->query('UPDATE featured SET featured_title=\''.$db->real_escape_string($_POST['title']).'\', featured_summary=\''.$db->real_escape_string($_POST['summary']).'\', featured_text=\''.$db->real_escape_string($_POST['text']).'\' WHERE featured_id='.$_POST['id']);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
		}
	}
	$featurelist = $db->query(
	'SELECT featured_id AS id, featured_title AS title, featured_summary AS summary, featured_date AS date, '
	.'book_id AS bookid, book_title AS booktitle, book_completed AS completed, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
	.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
	.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
	.'FROM featured '
	.'LEFT JOIN book ON featured.book_id_fk=book.book_id '
	.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
	.'WHERE featured.user_id_fk='.$session->get('user', 'user').' GROUP BY book.book_id ORDER BY featured_date DESC');
	if(!$featurelist)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['featurelist'] =& $featurelist;
	$tpl['total'] = $featurelist->num_rows;
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
	//create/edit/delete featured stories
	case 3:
		featured();
		break;
	//approve/reject chapters in the queue
	case 2:
		chapter_queue();
		break;
	//approve/reject books in the queue
	case 1:
		book_queue();
		break;
	//approve/reject authors in the queue
	default:
		author_queue();
		break;
}
include('../append.php');
?>
