<?php
	/*
	* lightpress cms (Nov 25 2016)
	* https://github.com/codeeverywhereca/lightpresscms
	* Copyright 2016, http://codeeverywhere.ca
	* Licensed under the MIT license.
	*/
  
ini_set("highlight.comment", "#bebebe");
ini_set("highlight.default", "#5A5A5A");
ini_set("highlight.html", "#4F9FCF");
ini_set("highlight.keyword", "#2F7CC4; font-weight: bold");
ini_set("highlight.string", "#D44950");

define('lp_password', 'admin1');

class lightpress {	
	private $db = null;
		
	public function __construct() {
		$this->db = new PDO("sqlite:./.lightpress.db");
	}
	
	public function build() {
		$this->db->query("CREATE TABLE posts ( post_id INTEGER PRIMARY KEY autoincrement, title VARCHAR(65) UNIQUE, 
			date datetime DEFAULT current_timestamp, content TEXT, html_content TEXT, type CHAR(1), 
			last_edit datetime DEFAULT current_timestamp, views INTEGER DEFAULT 0 );");
		$this->db->query("CREATE TABLE tags ( tag_id INTEGER PRIMARY KEY autoincrement, tag_name VARCHAR(65) UNIQUE );");
		$this->db->query("CREATE TABLE map ( post_id INTEGER, tag_id INTEGER );");
	}
	
	// Formating ...
	private function formatTable($str) {
		return preg_replace_callback( '/```table([\s\S]+?)```/i', function($matches){
			$lines = explode(PHP_EOL, trim($matches[1]));
			$table = "<table class=\"table\">\n";
			for( $x=0; $x<count($lines); $x++ ) {
			    $line = explode("|", trim($lines[$x]));
			    $table .= "\t\t\t\t\t<tr>\n\t\t\t\t\t\t<td>" . implode("</td>\n\t\t\t\t\t\t<td>", $line) . "</td>\n\t\t\t\t\t</tr>\n";
			}
			$table .= "</table>";
			return $table;			
		}, $str);
	}
	
	private function formatList($str) {
		return preg_replace_callback( '/```list([\s\S]+?)```/i', function($matches){
			$lines = explode(PHP_EOL, trim($matches[1]));
			$list = "<ul class=\"list\">\n";
			for( $x=0; $x<count($lines); $x++ ) {
			    $list .= "\t\t\t\t\t<li>{$lines[$x]}</li>\n";
			}
			$list .= "</ul>";
			return $list;			
		}, $str);
	}
	
	private function formatCode($str) {
		return preg_replace_callback( '/```(php|sql|js-inc|css-inc|html|js|css)([\s\S]+?)```/i', function($matches) {
			if( in_array($matches[1], array("js", "html", "css", "js-inc", "css-inc") ) ) {
				$matches[2] = "<?php " . trim($matches[2]);
				return '<div class="code ' . $matches[1] . '">' . str_replace("&lt;?php&nbsp;", '', highlight_string($matches[2], true) ) . '</div>';
			} else {
				$matches[2] = trim($matches[2]);
				return '<div class="code ' . $matches[1] . '">' . highlight_string($matches[2], true) . '</div>';
			}
		}, $str);
	}
	
	private function formatTerminal($str) {
		return preg_replace_callback( '/```terminal([\s\S]+?\s*)```/i', function($matches) {			
			return '<div class="terminal"><div class="top"><div class="btns">'
				. '<span class="circle red"></span><span class="circle yellow"></span><span class="circle green"></span>'
				. '</div><div class="title">bash -- 70x32</div></div>'
				. '<pre class="body">' . trim($matches[1]) . '</pre></div>';			
		}, $str);
	}
		
	// Get 'tags' in a post
	private function getTags($text) {
		preg_match('/\[tags?\s*:\s*([a-zA-Z0-9, -]+)\]/', $text, $tags);
		if( count($tags) > 0 ) return $tags[1];
		else return "";
	}
	
	//Add tag to DB, returns tag ID
	private function addTag($name) {
		$stmt = $this->db->prepare("select tag_id from tags where tag_name = ?;");
		$stmt->bindParam(1, $name, PDO::PARAM_STR);
	    $stmt->execute();
	    $row = $stmt->fetch(PDO::FETCH_ASSOC);
	    
	    //if not found, add to tags
	    if($row === false) {
		    $stmt = $this->db->prepare("insert into tags (tag_name) values (?)");
			$stmt->bindParam(1, $name, PDO::PARAM_STR);
			$stmt->execute();
			return $this->db->lastInsertId();
	    } else {
		    return $row['tag_id'];
		}
	}
	
	//Create new post
	public function save($post_id, $title, $content, $type = 'P', $date) {
		$title = trim($title);
		$content = trim($content);
		$type = strtoupper( trim($type) );
		
		$tags = $this->getTags($content);
		$html_content = preg_replace('/\[tags?\s*:\s*([a-zA-Z0-9, -]+)\]/', '', $content);
		
		// Remove code blocks
		$formatBlocksBuffer = array();
		$formatBlocksBufferCount = 0;
		$script = "";
		$style = "";
		
		$html_content = preg_replace_callback( '/```([a-z-]{1,9})([\s\S]+?)```/i', function($matches)
			use (&$formatBlocksBuffer, &$formatBlocksBufferCount) {
			$formatBlocksBufferCount++;
			$key = "--{$matches[1]}$formatBlocksBufferCount--";			
			$formatBlocksBuffer[ $key ] = "```" . $matches[1] . $matches[2] . "```";
			return $key;			
		}, $html_content);
				
		// Order Of Operations Is Important!!!
		// Format HEADING ... #some post heading
		$html_content = preg_replace('/\r?\n?#\s*([a-z0-9_\-@\?$+%\)\(, \.]+)/i', "\n\t\t\t\t\t<h2>$1</h2>\n", $html_content);
		
		// Format BOLD ... **bold text**
		$html_content = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html_content);
		
		// Format LINKS ... http://www.
		$html_content = preg_replace('/(http|https|ftp|ftps|ssh|vnc)\:\/\/([^\s]+)\.?/', '<a href="$1://$2">$2</a>', $html_content);
				
		// Format IMAGES ... [image:path/to/image.jpg]
		//$html_content = preg_replace('/\[image:([a-zA-Z0-9,_\/\-\.&\(\)]+)\]/', '<img src="$1" title="$1"/>', $html_content);
		$html_content = preg_replace_callback( '/\[image:([a-zA-Z0-9,_\/\-\.&\(\)]+)\]/', function($matches) {
			$src = $matches[1];
			preg_match('/(.+)-(j|g|p)([0-9]{3}x[0-9]{3})\.jpg$/i', $matches[1], $link);
			$img = array("j" => ".jpg", "g" => ".gif", "p" => ".png");
			$link = $link[1] . $img[$link[2]];
			return "<p class=\"img\"><a href=\"$link\"><img src=\"$src\" title=\"$src\"/></a></p>";
		}, $html_content);

		// Format Line Breaks
		$html_content = preg_replace('/(^|\n\r?)\s?((?!<)[\s\S]+?)(\n\r?|$)/i', "\n\t\t\t\t\t<p>$2</p>", $html_content);
		$html_content = preg_replace('/<p>\s*<\/p>/i', "", $html_content);
		$html_content = preg_replace('/<p>\s*(<h2>.*<\/h2>)\s*<\/p>/i', "$1", $html_content);
		
		$html_content = preg_replace('/<p>--/i', "--", $html_content);
		$html_content = preg_replace('/--<\/p>/i', "--", $html_content);
		
		// Put back blocks
		$html_content = str_replace( array_keys($formatBlocksBuffer), array_values($formatBlocksBuffer), $html_content);
		
		// ```skip, quotes
		$html_content = preg_replace('/```skip([\s\S]+?)```/i', "$1", $html_content);
		$html_content = preg_replace('/```quotes?([\s\S]+?)```/i', "<pre class=\"quote\">$1</pre>", $html_content);
		
		$html_content = $this->formatTable($html_content);
		$html_content = $this->formatList($html_content);
		$html_content = $this->formatCode($html_content);
		$html_content = $this->formatTerminal($html_content);
		
		// add in <style> + <scripts>
		$style = "";
		$script = "";
		foreach( $formatBlocksBuffer as $key => $str ) {
			if( substr($key, 0, 8) == "--js-inc" ) {
				$str = preg_replace('/[^:]\/\/[^\r\n]*/i', '', $str);
				$str = preg_replace('/\s+/', ' ', $str);
				$script .= "\n" . substr( $str, 9, strlen($str) - 12) . "";
			} else if( substr($key, 0, 9) == "--css-inc" ) {
				$str = preg_replace('/\s+/', ' ', $str);
				$style .= "\n" . substr( $str, 10, strlen($str) - 13) . "";
			}			
		}
		
		if( strlen($style) > 0 )
			$html_content = "\n<style>" .  $style . "</style>" . $html_content;
		if( strlen($script) > 0 )
			$html_content = "\n<script type=\"text/javascript\"> window.onload = function() { " . $script . " } </script>" . $html_content;
		
		$html_content = trim($html_content);
						
		// Check if exist
		$stmt = $this->db->prepare("select count(1) as count, post_id from posts where post_id = ?;");
		$stmt->bindParam(1, $post_id, PDO::PARAM_STR);
		$stmt->execute();
		$data = $stmt->fetch(PDO::FETCH_ASSOC);
		
		$ex = 0;
		
		// 'post_id' does not exist, create new post
		if( $data['count'] == '0' ) {
			$stmt = $this->db->prepare("insert into posts (title, date, last_edit, content, html_content, type, views) 
				values (:title, datetime('now'), datetime('now'), :content, :html_content, :type, 0);");
			$stmt->bindParam(':title', $title, PDO::PARAM_STR);
			$stmt->bindParam(':content', $content, PDO::PARAM_STR);
			$stmt->bindParam(':html_content', $html_content, PDO::PARAM_STR);
			$stmt->bindParam(':type', $type, PDO::PARAM_STR);
			$ex = $stmt->execute();
			$post_id = $this->db->lastInsertId();
		} else {
			// 'post_id' exists, do edit
			$post_id = $data['post_id'];
			
			$stmt = $this->db->prepare("update posts set last_edit = datetime('now'), title = :title, content = :content, 
				html_content = :html_content, type = :type, date = strftime('%Y-%m-%d %H:%M:%S', :date, 'unixepoch') where post_id = $post_id;");
			$stmt->bindParam(':title', $title, PDO::PARAM_STR);
			$stmt->bindParam(':content', $content, PDO::PARAM_STR);
			$stmt->bindParam(':html_content', $html_content, PDO::PARAM_STR);
			$stmt->bindParam(':type', $type, PDO::PARAM_STR);
			$stmt->bindParam(':date', $date, PDO::PARAM_STR);
			$ex = $stmt->execute();

			//clear existing tags			
			$this->db->exec("delete from map where post_id = $post_id;");	
		}
		
		//add in tags	
		$stmt = $this->db->prepare("insert into map (post_id, tag_id) values ($post_id, ?);");
		$stmt->bindParam(1, $tag_id, PDO::PARAM_INT);
		
		$tags = explode(',', $tags);
		foreach($tags as $tag) {
			if( strlen($tag) == 0 ) continue;
			$tag_id = $this->addTag( strtolower(trim($tag)) );
			if($tag_id == false) continue;
			$stmt->execute();
		}
		
		$post = array(
			'post_id' 	=> $post_id,
			'title' 	=> $title,
			'content' 	=> $content,
			'html_content' => $html_content,
			'type' 		=> $type,
			'date'		=> $date,
			'tags' 		=> implode(',', $tags),
			'newedit' 	=> ($data['count']==0) ? 'new' : 'edit'
		);
		
		if( $ex==1 ) return array('status' => 200, 'post' => $post);
		else return array('status' => 400, 'message' => $stmt->errorInfo()[2] );
	}
	//end create()
	
	// Delete a post by 'post_id'
	public function delete($post_id) {		
		$post_id = trim( $post_id );
		$stmt = $this->db->prepare("delete from posts where post_id = ?;");
		$stmt->bindParam(1, $post_id, PDO::PARAM_INT);
		$stmt->execute();
		if($stmt->rowCount() > 0) return array('status' => 200, 'message' => "your post $post_id has been deleted");
		else return array('status' => 400, 'message' => "your post $post_id has NOT deleted");
	}
	
	// Get post by 'post_id'
	public function get($post_id, $increment = true) {
		$post_id = trim( $post_id );
		
		if ( $increment ) {
			$stmt = $this->db->prepare("update posts set views = views + 1 where post_id = ?;");
			$stmt->bindParam(1, $post_id, PDO::PARAM_INT);
			$stmt->execute();
			if($stmt->rowCount() == 0)
				return array('status' => 400, 'message' => 'post is not found', 'post' => 
					array( 'type' => 'G', 'title' => 'not found', 'html_content' => '<h1>post not found</h1>' ) );
		}
		
		// Get details ...
		$stmt = $this->db->prepare("select post_id, title, strftime('%s', date) as date, strftime('%s', last_edit) as last_edit, views, type, content, html_content 
			from posts where post_id = ?;");
		$stmt->bindParam(1, $post_id, PDO::PARAM_INT);
		$ex = $stmt->execute();
		$post = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if(!$stmt)
			return array('status' => 400, 'message' => $this->db->errorInfo()[2]);
		
		if( $post['type'] == 'D' and $increment )
			return array('status' => 400, 'message' => 'post is a draft', 'post' => 
				array('type' => 'G', 'title' => 'post is a draft', 'html_content' => '<h1>post is draft</h1>'));
		
		// Get tags ...
		$stmt = $this->db->prepare("select tag_name from map join tags on map.tag_id = tags.tag_id where post_id = {$post['post_id']};");		
		$ex = $stmt->execute();
		$post['tags'] = implode(', ', $stmt->fetchAll(PDO::FETCH_COLUMN,0) );
		
		return array('status' => 200, 'post' => $post);
	}
	
	// Get all tags
	public function tags() {
		$data = $this->db->query("select tag_name from tags;")->fetchAll(PDO::FETCH_COLUMN, 0);
		$tags = implode(', ', $data);
		return array('status' => 200, 'tags' => $tags);
	}
	
	// order = date, views
	public function listPosts($order = "date", $type = 'P', $limit = 100) { // P-Post, H-Hidden, G-Page, D-Draft
		$stmt = $this->db->prepare("select post_id, title, strftime('%s', date) date, views, type, strftime('%Y', date) year 
			from posts where type = ? order by $order desc limit ?;");
		$stmt->bindParam(1, $type, PDO::PARAM_STR);
		$stmt->bindParam(2, $limit, PDO::PARAM_INT);
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array('status' => 200, 'posts' => $data);
	}
	
	public function listAllPosts() { // P-Post, H-Hidden, G-Page, D-Draft
		$stmt = $this->db->prepare("select post_id, title, strftime('%s', date) date, views, type, strftime('%Y', date) year from posts order by date desc");
		$stmt->execute();
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array('status' => 200, 'posts' => $data);
	}

	// Get posts by tags ... listByTags('tag') OR listByTags('tag1, tag2, tag3')
	public function listByTags($tags) {
		$tags = explode(',', str_replace(' ', '', $tags));
		$tag_place = str_repeat('?,', count($tags) - 1) . '?';
		$stmt = $this->db->prepare("select post_id, title, strftime('%s', date) as date, views from posts join map 
			on posts.post_id = map.post_id join tags on tags.tag_id = map.tag_id where tag_name in ($tag_place) 
			and type = 'P' order by date asc limit 50;");
		$stmt->execute($tags);
		$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return array('status' => 200, 'posts' => $data);
	}
	
	public function listByYear() {
		$arr = array();
		$posts = $this->listPosts()['posts'];
		foreach( $posts as $post ) {
			$arr[ $post['year'] ][] = $post;
		}
		return array('status' => 200, 'posts' => $arr);
	}
}
?>
