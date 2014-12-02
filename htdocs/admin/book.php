<?php
function list_books()
{
	$tpl =& get_tpl();
	$db = get_db();
	$session = get_session();
	//if we're logged in, shove it
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//create abcd list
	$tpl['alphabet'][] = 'NUM';
	$i = 'A';
	for($n = 0; $n < 26; $n++)
	{
		$tpl['alphabet'][] = $i;
		$i++;
	}
	$tpl['alphabet'][] = 'ALL';
	//page assignments
	$tpl['title'] = 'List Books ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, book administrate list';
	$tpl['description'] = 'Fanfiction Library book Administration List';
	//assign sub "template"
	$files['page'] = 'adminbooklist.html';
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
		$where = ' book_order=\''.$letter.'\' ';
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
	$count = $db->query('SELECT COUNT(book_id) FROM book'.$where);
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
	//grab books for list - welcome to the query from HELL
	$books = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, '
		.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
		.'book_wordcount AS wordcount, book_valid AS valid, book_chapters AS chapters, book_ranking AS ranking, '
		.'rating_id AS ratingid, rating_name AS rating, '
		.'category_name AS catname, category_id AS catid, '
		.'type_id AS typeid, type_name AS type, '
		.'style_id AS styleid, style_name AS style, '
		.'group_concat(DISTINCT genre_name ORDER BY genre_id ASC SEPARATOR \':\') AS genre, '
		.'group_concat(DISTINCT genre_id ORDER BY genre_id ASC SEPARATOR \':\') AS genreid, '
		.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
		.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
		.'group_concat(DISTINCT warning_name ORDER BY warning_id ASC SEPARATOR \':\') AS warning, '
		.'group_concat(DISTINCT warning_id ORDER BY warning_id ASC SEPARATOR \':\') as warningid, '
		.'group_concat(DISTINCT character_name ORDER BY character_id ASC SEPARATOR \':\') AS `character`, '
		.'group_concat(DISTINCT character_id ORDER BY character_id ASC SEPARATOR \':\') as characterid '
		.'FROM book '
		.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
		.'LEFT JOIN type ON type.type_id=book.type_id_fk '
		.'LEFT JOIN style ON style.style_id=book.style_id_fk '
		.'LEFT JOIN category ON category.category_id=book.category_id_fk '
		.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
		.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
		.$where.' GROUP BY book.book_id ORDER BY book_title ASC, book_update DESC LIMIT '.$offset.', '.$size);
	if(!$books)
	{
		printf('Errormessage: %s', $db->error);
	}
	$tpl['books'] =& $books;
	//create sidebar
	include('../../lib/sidebar.php');
	//show the "template"
	show_tpl($tpl, $files);
	return;
}

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
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'New Book ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, book administrate create new';
	$tpl['description'] = 'Fanfiction Library book Administration Create new';
	//assign sub "template"
	$files['page'] = 'adminbooknew.html';
	//basic checking
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
		elseif(empty($_POST['notes']) and $_FILES['notesfile']['error'] == 4)
		{
			$tpl['error'] = 'You must either upload a file or enter text in the textarea box.  Please leave ALL author notes in this area, and not in your chapters.';
		}
		//check for text and upload
		elseif($_FILES['notesfile']['error'] != 4 and !empty($_POST['notes']))
		{
			$tpl['error'] = 'You must EITHER upload a file OR enter your text in the textarea box, not both.';
		}
		//if we have an upload
		elseif(empty($_POST['notes']) and ($_FILES['notesfile']['error'] == 1 or $_FILES['notesfile']['error']==2))
		{
			$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
		}
		elseif(empty($_POST['notes']) and $_FILES['notesfile']['error'] == 3)
		{
			$tpl['error'] = 'There was a problem with your upload, please try again.';
		}
		elseif(empty($_POST['notes']) and $_FILES['notesfile']['size'] < 1)
		{
			$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
		}
		elseif(empty($_POST['notes']) and ($_FILES['notesfile']['type'] != 'text/plain' and $_FILES['notesfile']['type'] != 'text/html'))
		{
			$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
		}
		elseif(empty($_POST['notes']) and !is_uploaded_file($_FILES['notesfile']['tmp_name']))
		{
			$tpl['error'] = 'Your file was not uploaded properly';
		}
		elseif(empty($_POST['notes']))
		{
			$_POST['notes'] = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['notesfile']['tmp_name'])))));
		}
		else
		{
			$_POST['notes'] = htmlentities(html_entity_decode(undoword(strip_tags($_POST['notes']))));
		}
		$_POST['title'] = $tpl['newbook']['title'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['title'])));
		$_POST['summary'] =  htmlentities(html_entity_decode(undoword(strip_tags($_POST['summary']))));
		//we have to have at least one good author
		if(!isset($tpl['error']))
		{
			if(empty($_POST['authors']))
			{
				$tpl['error'] = 'You must include at least one valid author name';
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
		if(!isset($tpl['error']))
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
			elseif(empty($_POST['text']) and $_FILES['chapterfile']['error'] == 4)
			{
				$tpl['error'] = 'You must either upload a file or enter text in the textarea box.';
			}
			//check for text and upload
			elseif($_FILES['chapterfile']['error'] != 4 and !empty($_POST['text']))
			{
				$tpl['error'] = 'You must EITHER upload a file OR enter your text in the textarea box, not both.';
			}
			//if we have an upload
			elseif(empty($_POST['text']) and ($_FILES['chapterfile']['error'] == 1 or $_FILES['chapterfile']['error']==2))
			{
				$tpl['error'] = 'Your file must be smaller than 5 megabytes in order to upload it properly.';
			}
			elseif(empty($_POST['text']) and $_FILES['chapterfile']['error'] == 3)
			{
				$tpl['error'] = 'There was a problem with your upload, please try again.';
			}
			elseif(empty($_POST['text']) and $_FILES['chapterfile']['size'] < 1)
			{
				$tpl['error'] = 'Your file either did not exist on your machine, was empty, or there was a problem with the upload.';
			}
			elseif(empty($_POST['text']) and ($_FILES['chapterfile']['type'] != 'text/plain' and $_FILES['chapterfile']['type'] != 'text/html'))
			{
				$tpl['error'] = 'You can only upload plain text files or html files.  In word do save as and choose either html or plain text.';
			}
			elseif(empty($_POST['text']) and !is_uploaded_file($_FILES['chapterfile']['tmp_name']))
			{
				$tpl['error'] = 'Your file was not uploaded properly';
			}
			elseif(empty($_POST['text']))
			{
				$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags(file_get_contents($_FILES['chapterfile']['tmp_name'])))));
			}
			else
			{
				$_POST['text'] = htmlentities(html_entity_decode(undoword(strip_tags($_POST['text']))));
			}
		}
		if(count(preg_split('/\W+/', $_POST['text'], -1, PREG_SPLIT_NO_EMPTY)) < 500 and !isset($tpl['error']))
		{
			$tpl['error'] = 'Your story must be at least 500 words in length.';
		}
		$_POST['chtitle'] = htmlentities(strip_tags(preg_replace("/(\r\n|\n|\r)/", '', $_POST['chtitle'])));
	}
	if(isset($_POST['submit']) and !isset($tpl['error']))
	{
		$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
		$letter = str_split($_POST['title']);
		$letter = strtoupper($letter[0]);
		//insert the book
		$newbook = $db->query('INSERT INTO book(book_title, book_order, book_summary, type_id_fk, style_id_fk, rating_id_fk, book_publish, book_update, category_id_fk, book_notes, book_wordcount, book_chapters) '
			.'VALUES (\''.$db->real_escape_string($_POST['title']).'\', \''.$letter.'\', \''.$db->real_escape_string($_POST['summary']).'\', '.$_POST['tid'].','.$_POST['sid'].','.$_POST['rid'].', NOW(), NOW(), '.$_POST['cid'].', '
			.'\''.$db->real_escape_string($_POST['notes']).'\', '.count(preg_split('/\W+/', $_POST['text'], -1, PREG_SPLIT_NO_EMPTY)).', 1)');
		//get book id
		$id = $db->insert_id;
		//get author ids
		$authors = explode(', ', $_POST['authors']);
		$authors = 'WHERE author_name=\''.implode('\' OR author_name=\'', array_map('trim', $authors)).'\' OR';
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
		$tpl['error'] = 'Your book has been created.  An email will be sent to the authors with the status of this book after it has been screened.';
		unset($_POST);
	}
	//get category list
	$tpl['catlist'] = $db->query('SELECT category_id AS id, category_name AS name, category_description AS description FROM category ORDER BY category_name ASC');
	//get type list
	$tpl['typelist'] = $db->query('SELECT type_id AS id, type_name AS name, type_description AS description FROM type ORDER BY type_id ASC');
	//get style list
	$tpl['stylelist'] = $db->query('SELECT style_id AS id, style_name AS name, style_description AS description FROM style ORDER BY style_id ASC');
	//get ratings
	$tpl['ratinglist'] = $db->query('SELECT rating_id AS id, rating_name AS name, rating_description AS description FROM rating ORDER BY rating_name ASC');
	//get character list
	$tpl['charlist'] = $db->query('SELECT character_id AS id, character_name AS name, character_description AS description FROM `character` ORDER BY character_name ASC');
	//get genres
	$tpl['genrelist'] = $db->query('SELECT genre_id AS id, genre_name AS name, genre_description AS description FROM genre ORDER BY genre_name ASC');
	//get warnings
	$tpl['warninglist'] = $db->query('SELECT warning_id AS id, warning_name AS name, warning_description AS description FROM warning ORDER BY warning_name ASC');
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
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Edit Book ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, book administrate edit';
	$tpl['description'] = 'Fanfiction Library book Administration Edit';
	//assign sub "template"
	$files['page'] = 'adminbookedit.html';
	//delete un chapter
	if(isset($_GET['id']) and isset($_GET['did']))
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
		$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
		unlink($path.'/'.$_GET['id'].'/'.$_GET['did'].'.txt');
		$tpl['error'] = 'Your chapter has been deleted.';
	}
	elseif(isset($_GET['uid']))
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
	elseif(isset($_GET['eid']))
	{
		//get current order
		$result = $db->query('SELECT chapter_order FROM chapter WHERE chapter_id='.$_GET['eid'].' AND book_id_fk='.$_GET['id']);
		$tmp = $result->fetch_row();
		$result->close();
		$db->query('UPDATE chapter SET chapter_order = '.$tmp[0].' WHERE chapter_order='.($tmp[0]+1).' AND book_id_fk='.$_GET['id'].' AND chapter_id !='.$_GET['eid']);
		$db->query('UPDATE chapter SET chapter_update=NOW(), chapter_order = chapter_order + 1 WHERE chapter_id='.$_GET['eid']);
		$db->query('UPDATE book SET book_update=NOW() WHERE book_id='.$_GET['id']);
	}
	elseif(isset($_GET['d']))
	{
		$db->query('DELETE from comment WHERE comment_id = '.$_GET['d'].' LIMIT 1');
	}
	elseif(isset($_POST['edit']))
	{
		if(isset($_POST['private']))
		{
			$private = 1;
		}
		else
		{
			$private = 0;
		}
		$db->query('UPDATE comment SET comment_private='.$private.', comment_reply=\''.htmlentities(html_entity_decode(undoword(strip_tags($_POST['reply'])))).'\', comment_text=\''.htmlentities(html_entity_decode(undoword(strip_tags($_POST['text'])))).'\' WHERE comment_id='.$_POST['e']);
	}
	elseif(isset($_POST['editchapter']))
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
			$publish = $_POST['bpyear'].'-'.$_POST['bpmonth'].'-'.$_POST['bpday'];
			$update = $_POST['buyear'].'-'.$_POST['bumonth'].'-'.$_POST['buday'];
			//subtract
			$db->query('UPDATE book SET book_wordcount=(bookwordcount-chapter_wordcount) + '.$wordcount.', chapter_views='.$_POST['views'].', chapter_valid = '.$_POST['valid'].', '
				.'chapter_title=\''.$_POST['title'].'\', chapter_update='.$update.', chapter_publish='.$publish.', chapter_wordcount='.$wordcount.' '
				.'LEFT JOIN chapter ON book_id=book_id_fk WHERE book_id='.$_POST['id'].' AND chapter_id='.$_POST['e']);
			//change file
			$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
			file_put_contents($path.'/'.$_POST['id'].'/'.$_POST['e'].'.txt', $_POST['text']);
			$tpl['error'] = 'Your chapter has been altered.';
			unset($_REQUEST['e']);
		}
	}
	elseif(isset($_POST['new']))
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
		if(!isset($tpl['error']))
		{
			//first we need to know the next order number
			$result = $db->query('SELECT chapter_order, chapter_valid FROM chapter WHERE book_id_fk='.$_POST['id'].' ORDER BY chapter_order DESC LIMIT 1');
			$tmp = $result->fetch_row();
			$result->close();
			//if we have one waiting, we're gonna die off
			if($tmp[1] == 0)
			{
				$tpl['error'] = 'This book already has a chapter waiting for approval.  Please resubmit this chapter after the previous chapter is accepted or rejected';
			}
			else
			{
				//insert the chapter info - don't update the book until it's approved
				$db->query('INSERT INTO chapter(book_id_fk, chapter_title, chapter_wordcount, chapter_publish, chapter_update, chapter_order) '
					.'VALUES('.$_POST['id'].', \''.$_POST['title'].'\','.$wordcount.', NOW(), NOW(), '.($tmp[0] +1).')');
					echo $db->error;
				$num = $db->insert_id;
				//create file
				$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__)));
				file_put_contents($path.'/'.$_POST['id'].'/'.$num.'.txt', $_POST['text']);
				$tpl['error'] = 'Your chapter has been created.  An email will be sent to the authors with the status of the chapter after it has been screened.';
				unset($_REQUEST['n']);
			}
		}
	}
	elseif(isset($_POST['submit']))
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
		//we have to have at least one good author
		if(!isset($tpl['error']))
		{
			if(empty($_POST['authors']))
			{
				$tpl['error'] = 'You must include at least one valid author name';
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
		if(!isset($tpl['error']))
		{
			//get old cat number
			$result = $db->query('SELECT category_id_fk FROM book WHERE book_id='.$_POST['id']);
			$cat = $result->fetch_row();
			$cat = $cat[0];
			$result->close();
			$publish = $_POST['bpyear'].'-'.$_POST['bpmonth'].'-'.$_POST['bpday'];
			$update = $_POST['buyear'].'-'.$_POST['bumonth'].'-'.$_POST['buday'];
			//update category counts
			$db->query('UPDATE category SET category_total = category_total -1 WHERE category_id='.$cat);
			if($_POST['valid'] ==1)
			$db->query('UPDATE category SET category_total = category_total +1 WHERE category_id='.$_POST['cid']);
			//update the book
			$newbook = $db->query('UPDATE book SET book_title=\''.$db->real_escape_string($_POST['title']).'\', book_order=\''.$_POST['order'].'\', book_summary=\''.$db->real_escape_string($_POST['summary']).'\', '
				.'style_id_fk='.$_POST['sid'].', type_id_fk='.$_POST['tid'].', category_id_fk='.$_POST['cid'].', book_notes=\''.$db->real_escape_string($_POST['notes']).'\', book_completed='.$_POST['status'].', '
				.'book_update = '.$update.', book_publish='.$publish.', book_views = '.$_POST['views'].', book_ranking = '.$_POST['ranking'].' WHERE book_id='.$_POST['id']);
			//delete all authors
			$db->query('DELETE FROM booktoauthor WHERE book_id_fk='.$_POST['id']);
			//find authors
			$authors = explode(', ', $_POST['authors']);
			$authors = 'WHERE author_name=\''.implode('\' OR author_name=\'', array_map('trim', $authors)).'\'';
			$result = $db->query('SELECT author_id FROM author '.$authors);
			echo $db->error;
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
	//first of all, we grab and show a message if we have an id
	if(isset($_REQUEST['e']) and isset($_REQUEST['c']))
	{
		$result = $db->query('SELECT chapter_id AS id, chapter_title AS title, chapter_wordcount AS wordcount, book_id AS bid, book_title AS btitle, chapter_valid AS valid, '
			.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, chapter_views AS views, chapter_order AS `order`, chapter_update AS `update`, chapter_publish AS publish, '
			.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid '
			.'FROM chapter LEFT JOIN book ON book_id=chapter.book_id_fk LEFT JOIN booktoauthor ON book_id=booktoauthor.book_id_fk LEFT JOIN author ON author_id_fk=author_id '
			.'WHERE chapter_valid=1 AND chapter_id='.$_REQUEST['e'].' GROUP BY book_id LIMIT 1');
		$tpl['chapter'] = $result->fetch_assoc();
		$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__))).'/'.$tpl['chapter']['bid'].'/'.$tpl['chapter']['id'].'.txt';
		$tpl['chapter']['text'] = file_get_contents($path);
		$result->close();
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
	elseif(isset($_REQUEST['c']))
	{
		//get list of chapters
		$tpl['chapters'] = $db->query('SELECT book_id_fk AS bid, chapter_id AS id, chapter_title AS title, chapter_wordcount AS wordcount, chapter_publish AS publish, chapter_update AS `update` '
		.'FROM chapter WHERE book_id_fk='.$_REQUEST['id'].' ORDER BY chapter_order');
		//get top and bottom chapters
		$result = $db->query('SELECT chapter_id FROM chapter WHERE book_id_fk='.$_REQUEST['id'].' ORDER BY chapter_order DESC');
		$tmp = $result->fetch_row();
		$tpl['max'] = $tmp[0];
		$result->close();
		//get top and bottom chapters
		$result = $db->query('SELECT chapter_id FROM chapter WHERE book_id_fk='.$_REQUEST['id'].' ORDER BY chapter_order ASC');
		$tmp = $result->fetch_row();
		$tpl['min'] = $tmp[0];
		$result->close();
	}
	elseif(isset($_REQUEST['m']))
	{
		if(isset($_GET['e']))
		{
			$request = $db->query('SELECT comment_text, comment_reply, comment_private FROM comment WHERE comment_id='.$_GET['e'].' LIMIT 1');
			$tmp = $request->fetch_row();
			$tpl['e_text'] = $tmp[0];
			$tpl['e_reply'] = $tmp[1];
			$tpl['e_private'] = $tmp[2];
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
			.'FROM comment LEFT JOIN user ON user_id_fk=user_id WHERE book_id_fk='.$_REQUEST['id'].' ORDER BY comment_date DESC, user_name ASC LIMIT '.$offset.', '.$size);
		if(!$comments)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['comments'] =& $comments;
		//get total for paging
		$count = $db->query('SELECT COUNT(comment_id) FROM comment WHERE comment_private=0 AND book_id_fk='.$_REQUEST['id']);
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
	elseif(isset($_REQUEST['n']))
	{
		$id = (int) $_REQUEST['id'];
		//grab the book
		$result = $db->query('SELECT book_id AS id, book_title AS title FROM book WHERE book.book_id='.$id.' LIMIT 1');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['new'] = $result->fetch_assoc();
		$result->close();
	}
	elseif(isset($_REQUEST['id']))
	{
		$id = (int) $_REQUEST['id'];
		//grab the book
		$result = $db->query('SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS status, book_order AS `order`, '
		.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , book_valid AS valid, book_ranking AS ranking, '
		.'book_wordcount AS wordcount, book_chapters AS chapters, book_ranking AS ranking, book_notes AS text, book_views AS views, '
		.'rating_id AS rid, type_id AS tid, style_id AS sid, category_id AS cid, '
		.'group_concat(DISTINCT genre_id ORDER BY genre_id ASC SEPARATOR \':\') AS gid, '
		.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \', \') AS authors, '
		.'group_concat(DISTINCT warning_id ORDER BY warning_id ASC SEPARATOR \':\') as wid, '
		.'group_concat(DISTINCT character_id ORDER BY character_id ASC SEPARATOR \':\') as chid '
		.'FROM book '
		.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
		.'LEFT JOIN type ON type.type_id=book.type_id_fk '
		.'LEFT JOIN style ON style.style_id=book.style_id_fk '
		.'LEFT JOIN category ON category.category_id=book.category_id_fk '
		.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
		.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
		.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
		.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
		.'WHERE book.book_id='.$id.' GROUP BY book.book_id LIMIT 1');
		if(!$result)
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['result'] = $result->fetch_assoc();
		$result->close();
	}
	//full text search creation, fun baby fun
	if(isset($_POST['search']) or isset($_GET['f']))
	{
		$sess_search = $session->get('search');
		if(isset($_GET['f']) and !empty($sess_search))
		{
			$_POST = unserialize(gzuncompress(urldecode($sess_search)));
		}
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['btitle']) and !isset($_POST['bsummary']) and !isset($_POST['bauthor']))) and (empty($_POST['bumonth']) and empty($_POST['buday']) and empty($_POST['buyear'])) and (empty($_POST['bpmonth']) and empty($_POST['bpday']) and empty($_POST['bpyear'])) and (empty($_POST['bcats']) and empty($_POST['bchar']) and empty($_POST['bgenre']) and empty($_POST['brating']) and empty($_POST['bwarning']) and empty($_POST['bcount']) and empty($_POST['brank']) and empty($_POST['bstatus'])))
		{
			$tpl['error'] = 'In order to find books, you must enter text to search for, and the areas you want to look.  Or you may choose a date combination to search.  Or you may choose items from the select boxes.';
		}
		else
		{
			//current size(limit)
			$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
			$tpl['size'] = $size;
			//current page - get offset
			$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
			$tpl['page'] = $page;
			//find offset
			$offset = ($page - 1) * $size;
			$tpl['offset'] = $offset + 1;
			//two queries, count and a limited version - first set up where
			$where = array();
			//add date to where clause
			if(!empty($_POST['buyear']))
			{
				$tmp = (int) $_POST['buyear'];
				$where[] = 'YEAR(book_update)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bumonth']))
			{
				$tmp = (int) $_POST['bumonth'];
				$where[] = 'MONTH(book_update)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['buday']))
			{
				$tmp = (int) $_POST['buday'];
				$where[] = 'DAY(book_update)=\''.$tmp.'\' ';
			}
			//add date to where clause
			if(!empty($_POST['bpyear']))
			{
				$tmp = (int) $_POST['bpyear'];
				$where[] = 'YEAR(book_publish)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bpmonth']))
			{
				$tmp = (int) $_POST['bpmonth'];
				$where[] = 'MONTH(book_publish)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bpday']))
			{
				$tmp = (int) $_POST['bpday'];
				$where[] = 'DAY(book_publish)=\''.$tmp.'\' ';
			}
			//create against
			if(isset($_POST['bool']) and !empty($_POST['string']) and (isset($_POST['btitle']) or isset($_POST['bsummary']) or isset($_POST['bauthor'])))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']) and (isset($_POST['btitle']) or isset($_POST['bsummary']) or isset($_POST['bauthor'])))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\') ';
			}
			else
			{
				$against = '';
			}
			//create match
			$match = '';
			if(isset($_POST['btitle']) and isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_title, book_summary) ';
			}
			elseif(isset($_POST['btitle']) and !isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_title) ';
			}
			elseif(!isset($_POST['btitle']) and isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_summary) ';
			}
			if(isset($_POST['bauthor']) and empty($match))
			{
				$match = 'MATCH (author.author_name) '.$against;
			}
			elseif(isset($_POST['bauthor']) and !empty($match))
			{
				$match = '(MATCH (author.author_name) '.$against.' AND '.$match.')';
			}
			//add to the where clause
			if(!empty($against) and !isset($_POST['bauthor']))
			{
				$where[] = $match.$against;
			}
			elseif(!empty($against))
			{
				$where[] = $match;
			}
			//cat filter
			if(isset($_POST['bcats']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['bcats']))
				{
					$where[] = ' (book.category_id_fk='.implode(' OR book.category_id_fk=', $_POST['bcats']).') ';
				}
				else
				{
					$where[] = ' book.category_id_fk='.$_POST['bcats'].' ';
				}
			}
			//type filter
			if(isset($_POST['btype']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['btype']))
				{
					$where[] = ' (book.type_id_fk='.implode(' OR book.type_id_fk=', $_POST['btype']).') ';
				}
				else
				{
					$where[] = ' book.type_id_fk='.$_POST['btype'].' ';
				}
			}
			//style filter
			if(isset($_POST['bstyle']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['bstyle']))
				{
					$where[] = ' (book.style_id_fk='.implode(' OR book.style_id_fk=', $_POST['bstyle']).') ';
				}
				else
				{
					$where[] = ' book.style_id_fk='.$_POST['bstyle'].' ';
				}
			}
			//rating filter
			if(isset($_POST['brating']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['brating']))
				{
					$where[] = ' (book.rating_id_fk='.implode(' OR book.rating_id_fk=', $_POST['brating']).') ';
				}
				else
				{
					$where[] = ' book.rating_id_fk='.$_POST['brating'].' ';
				}
			}
			//harder filters with subqueries...fun - characters
			if(isset($_POST['bchar']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktocharacter WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bchar']))
				{
					$clause .= 'character_id_fk='.implode(' OR character_id_fk=', $_POST['bchar']).' ';
				}
				else
				{
					$clause .= 'character_id_fk='.$_POST['bchar'].' ';
				}
				$where[] = $clause.' )';
			}
			//genres
			if(isset($_POST['bgenre']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktogenre WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bgenre']))
				{
					$clause .= 'genre_id_fk='.implode(' OR genre_id_fk=', $_POST['bgenre']).' ';
				}
				else
				{
					$clause .= 'genre_id_fk='.$_POST['bgenre'].' ';
				}
				$where[] = $clause.' )';
			}
			//warnings
			if(isset($_POST['bwarning']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktowarning WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bwarning']))
				{
					$clause .= 'warning_id_fk='.implode(' OR warning_id_fk=', $_POST['bwarning']).' ';
				}
				else
				{
					$clause .= 'warning_id_fk='.$_POST['bwarning'].' ';
				}
				$where[] = $clause.' )';
			}
			//wordcount filter
			if(isset($_POST['bcount']) and $_POST['bcount'] > 0)
			{
				$word = array(0, 1000, 5000, 10000, 25000, 50000, 75000, 100000);
				$where[] = ' book.book_wordcount > '.$word[$_POST['bcount']];
			}
			//ranking filter
			if(isset($_POST['brank']) and $_POST['brank'] > 0)
			{
				$where[] = ' book.book_ranking >= '.$_POST['brank'];
			}
			//status filter
			if(isset($_POST['bstatus']) and $_POST['bstatus'] == 1)
			{
				$where[] = ' book.book_completed = 0';
			}
			elseif(isset($_POST['bstatus']) and $_POST['bstatus'] == 2)
			{
				$where[] = ' book.book_completed = 1';
			}
			//cram that where clause
			$where = 'WHERE '.implode('AND ', $where).' ';
			//now the order clause
			if(!empty($against))
			{
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, book_update DESC, book_publish DESC, book_title ASC';
			}
			else
			{
				$order = 'ORDER BY book_update DESC, book_publish DESC, book_title ASC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(book_id) FROM book LEFT JOIN booktoauthor ON booktoauthor.book_id_fk = book.book_id LEFT JOIN author ON booktoauthor.author_id_fk=author.author_id '.$where);
			if(!$count)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $count->fetch_row();
			$total = $tpl['total'] = $total[0];
			if($total < 1)
			$tpl['error'] = 'No Matches Found';
			$count->close();
			//do actual query
			$query = 'SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, book_valid AS valid, '
			.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
			.'book_wordcount AS wordcount, book_chapters AS chapters, book_ranking AS ranking, '
			.'rating_id AS ratingid, rating_name AS rating, '
			.'type_id AS typeid, type_name AS type, '
			.'style_id AS styleid, style_name AS style, '
			.'category_name AS catname, category_id AS catid, '
			.'group_concat(DISTINCT genre_name ORDER BY genre_id ASC SEPARATOR \':\') AS genre, '
			.'group_concat(DISTINCT genre_id ORDER BY genre_id ASC SEPARATOR \':\') AS genreid, '
			.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
			.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
			.'group_concat(DISTINCT warning_name ORDER BY warning_id ASC SEPARATOR \':\') AS warning, '
			.'group_concat(DISTINCT warning_id ORDER BY warning_id ASC SEPARATOR \':\') as warningid, '
			.'group_concat(DISTINCT character_name ORDER BY character_id ASC SEPARATOR \':\') AS `character`, '
			.'group_concat(DISTINCT character_id ORDER BY character_id ASC SEPARATOR \':\') as characterid ';
			if(!empty($against))
			{
				$query .= ', '.$match.' AS relevance ';
			}
			$query .= 'FROM book '
			.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
			.'LEFT JOIN type ON type.type_id=book.type_id_fk '
			.'LEFT JOIN style ON style.style_id=book.style_id_fk '
			.'LEFT JOIN category ON category.category_id=book.category_id_fk '
			.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
			.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
			.$where.' GROUP BY (book_id) '.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['books'] = &$result;
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
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
		}
	}
	if(isset($tpl['error']) or !isset($tpl['search']) or !isset($_GET['id']))
	{
		$tpl['catlist'] = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
		if(!$tpl['catlist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['typelist'] = $db->query('SELECT type_id AS id, type_name AS name FROM type ORDER BY type_id ASC');
		if(!$tpl['typelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get character list
		$tpl['charlist'] = $db->query('SELECT character_id AS id, character_name AS name FROM `character` ORDER BY character_name ASC');
		if(!$tpl['charlist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get genres
		$tpl['genrelist'] = $db->query('SELECT genre_id AS id, genre_name AS name FROM genre ORDER BY genre_name ASC');
		if(!$tpl['genrelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get ratings
		$tpl['ratinglist'] = $db->query('SELECT rating_id AS id, rating_name AS name FROM rating ORDER BY rating_name ASC');
		if(!$tpl['ratinglist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get styles
		$tpl['stylelist'] = $db->query('SELECT style_id AS id, style_name AS name FROM style ORDER BY style_name ASC');
		if(!$tpl['stylelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get warnings
		$tpl['warninglist'] = $db->query('SELECT warning_id AS id, warning_name AS name FROM warning ORDER BY warning_name ASC');
		if(!$tpl['warninglist'])
		{
			printf('Errormessage: %s', $db->error);
		}
	}
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
	$admin = $session->get('admin', 'user');
	if(empty($admin))
	header('Location: ../index.php');
	//page assignments
	$tpl['title'] = 'Delete Books ~*~ Admin';
	$tpl['nest'] = '../';
	$tpl['keywords'] = 'fanfiction library admin, book administrate delete';
	$tpl['description'] = 'Fanfiction Library book Administration Delete';
	//assign sub "template"
	$files['page'] = 'adminbookdelete.html';
	//do the delete - we have to majorly cascade this
	if(isset($_POST['submit']))
	{
		if(isset($_POST['delete']) and is_array($_POST['delete']))
		{
			$book_ids = array();
			foreach($_POST['delete'] as $id => $junk)
			{
				$book_ids[] = $id;
			}
			if(!empty($book_ids))
			{
				//now we make a pretty where clause
				$where = 'book_id_fk='.implode(' OR book_id_fk=', $book_ids);
				//now, we delete from all book dependencies
				$delete = $db->query('DELETE FROM booktoauthor WHERE '.$where);
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
				$delete = $db->query('DELETE FROM booktocharacter WHERE '.$where);
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
				$delete = $db->query('DELETE FROM booktogenre WHERE '.$where);
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
				$delete = $db->query('DELETE FROM booktowarning WHERE '.$where);
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
				//now delete le chapters
				$delete = $db->query('DELETE FROM chapter WHERE '.$where);
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
				foreach($book_ids as $id)
				{
					$path = str_replace('htdocs/admin', 'data/files', str_replace('\\', '/', dirname(__FILE__))).'/';
					//unlink all files inside
					$handle = opendir($path.$id);
					while(FALSE!==($file = readdir($handle)))
					{
						if($file != '.' and $file != '..')
						unlink($path.$id.'/'.$file);
					}
					closedir($handle);
					rmdir($path.$id);
				}
				//delete any featured
				$delete = $db->query('DELETE FROM featured WHERE book_id_fk='.implode(' OR book_id_fk=', $book_ids));
				//delete the books
				$delete = $db->query('DELETE FROM book WHERE book_id='.implode(' OR book_id=', $book_ids));
				if(!$delete)
				{
					printf('Errormessage: %s', $db->error);
				}
			}
			$tpl['error'] = $db->affected_rows.' books were deleted.';
		}
	}
	//full text search creation, fun baby fun
	if(isset($_POST['search']) or isset($_GET['f']))
	{
		$sess_search = $session->get('search');
		if(isset($_GET['f']) and !empty($sess_search))
		{
			$_POST = unserialize(gzuncompress(urldecode($sess_search)));
		}
		//make sure we have something to search for
		if((empty($_POST['string']) or (!isset($_POST['btitle']) and !isset($_POST['bsummary']) and !isset($_POST['bauthor']))) and (empty($_POST['bumonth']) and empty($_POST['buday']) and empty($_POST['buyear'])) and (empty($_POST['bpmonth']) and empty($_POST['bpday']) and empty($_POST['bpyear'])) and (empty($_POST['bcats']) and empty($_POST['bchar']) and empty($_POST['bgenre']) and empty($_POST['brating']) and empty($_POST['bwarning']) and empty($_POST['bcount']) and empty($_POST['brank']) and empty($_POST['bstatus'])))
		{
			$tpl['error'] = 'In order to find books, you must enter text to search for, and the areas you want to look.  Or you may choose a date combination to search.  Or you may choose items from the select boxes.';
		}
		else
		{
			//current size(limit)
			$size = !isset($_GET['s']) ? 15 : (int) $_GET['s'];
			$tpl['size'] = $size;
			//current page - get offset
			$page = !isset($_GET['p']) ? 1 : (int) $_GET['p'];
			$tpl['page'] = $page;
			//find offset
			$offset = ($page - 1) * $size;
			$tpl['offset'] = $offset + 1;
			//two queries, count and a limited version - first set up where
			$where = array();
			//add date to where clause
			if(!empty($_POST['buyear']))
			{
				$tmp = (int) $_POST['buyear'];
				$where[] = 'YEAR(book_update)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bumonth']))
			{
				$tmp = (int) $_POST['bumonth'];
				$where[] = 'MONTH(book_update)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['buday']))
			{
				$tmp = (int) $_POST['buday'];
				$where[] = 'DAY(book_update)=\''.$tmp.'\' ';
			}
			//add date to where clause
			if(!empty($_POST['bpyear']))
			{
				$tmp = (int) $_POST['bpyear'];
				$where[] = 'YEAR(book_publish)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bpmonth']))
			{
				$tmp = (int) $_POST['bpmonth'];
				$where[] = 'MONTH(book_publish)=\''.$tmp.'\' ';
			}
			if(!empty($_POST['bpday']))
			{
				$tmp = (int) $_POST['bpday'];
				$where[] = 'DAY(book_publish)=\''.$tmp.'\' ';
			}
			//create against
			if(isset($_POST['bool']) and !empty($_POST['string']) and (isset($_POST['btitle']) or isset($_POST['bsummary']) or isset($_POST['bauthor'])))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\' IN BOOLEAN MODE) ';
			}
			elseif(!empty($_POST['string']) and (isset($_POST['btitle']) or isset($_POST['bsummary']) or isset($_POST['bauthor'])))
			{
				$against = 'AGAINST (\''.mysqli_real_escape_string($db, htmlentities(strip_tags($_POST['string']))).'\') ';
			}
			else
			{
				$against = '';
			}
			//create match
			$match = '';
			if(isset($_POST['btitle']) and isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_title, book_summary) ';
			}
			elseif(isset($_POST['btitle']) and !isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_title) ';
			}
			elseif(!isset($_POST['btitle']) and isset($_POST['bsummary']))
			{
				$match = 'MATCH (book_summary) ';
			}
			if(isset($_POST['bauthor']) and empty($match))
			{
				$match = 'MATCH (author.author_name) '.$against;
			}
			elseif(isset($_POST['bauthor']) and !empty($match))
			{
				$match = '(MATCH (author.author_name) '.$against.' AND '.$match.')';
			}
			//add to the where clause
			if(!empty($against) and !isset($_POST['bauthor']))
			{
				$where[] = $match.$against;
			}
			elseif(!empty($against))
			{
				$where[] = $match;
			}
			//cat filter
			if(isset($_POST['bcats']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['bcats']))
				{
					$where[] = ' (book.category_id_fk='.implode(' OR book.category_id_fk=', $_POST['bcats']).') ';
				}
				else
				{
					$where[] = ' book.category_id_fk='.$_POST['bcats'].' ';
				}
			}
			//type filter
			if(isset($_POST['btype']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['btype']))
				{
					$where[] = ' (book.type_id_fk='.implode(' OR book.type_id_fk=', $_POST['btype']).') ';
				}
				else
				{
					$where[] = ' book.type_id_fk='.$_POST['btype'].' ';
				}
			}
			//style filter
			if(isset($_POST['bstyle']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['bstyle']))
				{
					$where[] = ' (book.style_id_fk='.implode(' OR book.style_id_fk=', $_POST['bstyle']).') ';
				}
				else
				{
					$where[] = ' book.style_id_fk='.$_POST['bstyle'].' ';
				}
			}
			//rating filter
			if(isset($_POST['brating']))
			{
				//if cid's an array then we smash it
				if(is_array($_POST['brating']))
				{
					$where[] = ' (book.rating_id_fk='.implode(' OR book.rating_id_fk=', $_POST['brating']).') ';
				}
				else
				{
					$where[] = ' book.rating_id_fk='.$_POST['brating'].' ';
				}
			}
			//harder filters with subqueries...fun - characters
			if(isset($_POST['bchar']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktocharacter WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bchar']))
				{
					$clause .= 'character_id_fk='.implode(' OR character_id_fk=', $_POST['bchar']).' ';
				}
				else
				{
					$clause .= 'character_id_fk='.$_POST['bchar'].' ';
				}
				$where[] = $clause.' )';
			}
			//genres
			if(isset($_POST['bgenre']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktogenre WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bgenre']))
				{
					$clause .= 'genre_id_fk='.implode(' OR genre_id_fk=', $_POST['bgenre']).' ';
				}
				else
				{
					$clause .= 'genre_id_fk='.$_POST['bgenre'].' ';
				}
				$where[] = $clause.' )';
			}
			//warnings
			if(isset($_POST['bwarning']))
			{
				$clause = 'book_id IN(SELECT book_id_fk AS book_id FROM booktowarning WHERE ';
				//if chid's an array then we smash it
				if(is_array($_POST['bwarning']))
				{
					$clause .= 'warning_id_fk='.implode(' OR warning_id_fk=', $_POST['bwarning']).' ';
				}
				else
				{
					$clause .= 'warning_id_fk='.$_POST['bwarning'].' ';
				}
				$where[] = $clause.' )';
			}
			//wordcount filter
			if(isset($_POST['bcount']) and $_POST['bcount'] > 0)
			{
				$word = array(0, 1000, 5000, 10000, 25000, 50000, 75000, 100000);
				$where[] = ' book.book_wordcount > '.$word[$_POST['bcount']];
			}
			//ranking filter
			if(isset($_POST['brank']) and $_POST['brank'] > 0)
			{
				$where[] = ' book.book_ranking >= '.$_POST['brank'];
			}
			//status filter
			if(isset($_POST['bstatus']) and $_POST['bstatus'] == 1)
			{
				$where[] = ' book.book_completed = 0';
			}
			elseif(isset($_POST['bstatus']) and $_POST['bstatus'] == 2)
			{
				$where[] = ' book.book_completed = 1';
			}
			//cram that where clause
			$where = 'WHERE '.implode('AND ', $where).' ';
			//now the order clause
			if(!empty($against))
			{
				$order = 'HAVING relevance > 0.2 ORDER BY relevance DESC, book_update DESC, book_publish DESC, book_title ASC';
			}
			else
			{
				$order = 'ORDER BY book_update DESC, book_publish DESC, book_title ASC';
			}
			//create count query
			$count = $db->query('SELECT COUNT(book_id) FROM book LEFT JOIN booktoauthor ON booktoauthor.book_id_fk = book.book_id LEFT JOIN author ON booktoauthor.author_id_fk=author.author_id '.$where);
			if(!$count)
			{
				printf('Errormessage: %s', $db->error);
			}
			$total = $count->fetch_row();
			$total = $tpl['total'] = $total[0];
			if($total < 1)
			$tpl['error'] = 'No Matches Found';
			$count->close();
			//do actual query
			$query = 'SELECT book_id AS bookid, book_title AS title, book_summary AS summary, book_completed AS completed, book_valid AS valid, '
			.'book_comments AS comments, book_chapters AS chapters, book_publish AS publish, book_update AS `update` , '
			.'book_wordcount AS wordcount, book_chapters AS chapters, book_ranking AS ranking, '
			.'rating_id AS ratingid, rating_name AS rating, '
			.'type_id AS typeid, type_name AS type, '
			.'style_id AS styleid, style_name AS style, '
			.'category_name AS catname, category_id AS catid, '
			.'group_concat(DISTINCT genre_name ORDER BY genre_id ASC SEPARATOR \':\') AS genre, '
			.'group_concat(DISTINCT genre_id ORDER BY genre_id ASC SEPARATOR \':\') AS genreid, '
			.'group_concat(DISTINCT author_name ORDER BY author_id ASC SEPARATOR \':\') AS author, '
			.'group_concat(DISTINCT author_id ORDER BY author_id ASC SEPARATOR \':\') AS authorid, '
			.'group_concat(DISTINCT warning_name ORDER BY warning_id ASC SEPARATOR \':\') AS warning, '
			.'group_concat(DISTINCT warning_id ORDER BY warning_id ASC SEPARATOR \':\') as warningid, '
			.'group_concat(DISTINCT character_name ORDER BY character_id ASC SEPARATOR \':\') AS `character`, '
			.'group_concat(DISTINCT character_id ORDER BY character_id ASC SEPARATOR \':\') as characterid ';
			if(!empty($against))
			{
				$query .= ', '.$match.' AS relevance ';
			}
			$query .= 'FROM book '
			.'LEFT JOIN rating ON rating.rating_id=book.rating_id_fk '
			.'LEFT JOIN type ON type.type_id=book.type_id_fk '
			.'LEFT JOIN style ON style.style_id=book.style_id_fk '
			.'LEFT JOIN category ON category.category_id=book.category_id_fk '
			.'LEFT JOIN booktogenre ON booktogenre.book_id_fk=book.book_id LEFT JOIN genre on booktogenre.genre_id_fk=genre.genre_id '
			.'LEFT JOIN booktoauthor ON booktoauthor.book_id_fk=book.book_id LEFT JOIN author on booktoauthor.author_id_fk=author.author_id '
			.'LEFT JOIN booktowarning ON booktowarning.book_id_fk=book.book_id LEFT JOIN warning on booktowarning.warning_id_fk=warning.warning_id '
			.'LEFT JOIN booktocharacter ON booktocharacter.book_id_fk=book.book_id LEFT JOIN `character` on booktocharacter.character_id_fk=character.character_id '
			.$where.' GROUP BY (book_id) '.$order.' LIMIT '.$offset.', '.$size;
			$result = $db->query($query);
			if(!$result)
			{
				printf('Errormessage: %s', $db->error);
			}
			$tpl['books'] = &$result;
			$session->set(urlencode(gzcompress(serialize($_POST))), 'search');
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
		}
	}
	if(isset($tpl['error']) or !isset($tpl['search']))
	{
		$tpl['catlist'] = $db->query('SELECT category_id AS id, category_name AS name FROM category ORDER BY category_name ASC');
		if(!$tpl['catlist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		$tpl['typelist'] = $db->query('SELECT type_id AS id, type_name AS name FROM type ORDER BY type_id ASC');
		if(!$tpl['typelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get character list
		$tpl['charlist'] = $db->query('SELECT character_id AS id, character_name AS name FROM `character` ORDER BY character_name ASC');
		if(!$tpl['charlist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get genres
		$tpl['genrelist'] = $db->query('SELECT genre_id AS id, genre_name AS name FROM genre ORDER BY genre_name ASC');
		if(!$tpl['genrelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get ratings
		$tpl['ratinglist'] = $db->query('SELECT rating_id AS id, rating_name AS name FROM rating ORDER BY rating_name ASC');
		if(!$tpl['ratinglist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get styles
		$tpl['stylelist'] = $db->query('SELECT style_id AS id, style_name AS name FROM style ORDER BY style_name ASC');
		if(!$tpl['stylelist'])
		{
			printf('Errormessage: %s', $db->error);
		}
		//get warnings
		$tpl['warninglist'] = $db->query('SELECT warning_id AS id, warning_name AS name FROM warning ORDER BY warning_name ASC');
		if(!$tpl['warninglist'])
		{
			printf('Errormessage: %s', $db->error);
		}
	}
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
	//delete an announcement, uses same search as edit
	case 3:
		delete_book();
		break;
	//edit an announcement
	case 2:
		edit_book();
		break;
	//new announcement
	case 1:
		new_book();
		break;
	//default is listing announcements
	default:
		list_books();
		break;
}
include('../append.php');
?>
