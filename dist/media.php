<?php
/*
	* lightpress cms (Nov 19 2016)
	* https://github.com/codeeverywhereca/lightpresscms
	* Copyright 2016, http://codeeverywhere.ca
	* Licensed under the MIT license.
	*/
session_start();

if( !isset($_SESSION['auth']) or $_SESSION['auth'] !== 'randomauthkey12345') {
	echo '{ "status" : "error", "message" : "not authenticated" }';
	exit();
}

class media {
	
	private $sizes = null;
	
	public function __construct() {
		$this->sizes = array( // max 999,999
			"half"	=> array(305, 305),
			"full"	=> array(610, 610),
		);
	}
	
	public function getMedia() {
		$scan = scandir( dirname(__FILE__) . "/uploads");
		$media = array();
		for( $x=0; $x<count($scan); $x++ )
			if( preg_match('/-(j|g|p)([0-9]{3}x[0-9]{3})\.jpg$/i', substr($scan[$x], -13) ) && substr($scan[$x], -11) !== "150x150.jpg" )
				$media[] = $scan[$x];
		return $media;
	}
	
	public function printUploadForm() {
		echo "<form action=\"#\" method=\"post\" enctype=\"multipart/form-data\">";
		echo "  <table>";
		foreach( $this->sizes as $name => $dimensions ) {
			echo "<tr>";
			echo "  <td><input type=\"checkbox\" checked=\"checked\" name=\"size[]\" value=\"" . $dimensions[0] . "x" . $dimensions[1] . "\"></td>";
			echo "  <td>$name</td>";
			echo "  <td>". $dimensions[0] . "x" . $dimensions[1] . "</td>";
			echo "  <td>[image:uploads/filename-EXTx". $dimensions[0] . "x" . $dimensions[1] .".jpg]</td>";
			echo "</tr>";
		}
		echo "  </table>";
		echo "  <div>";
		echo "    <input type=\"file\" name=\"image\" />";
		echo "    <input type=\"submit\" name=\"submit\" value=\"upload\"/>";
		echo "</div>";
		echo "</form>";
	}
	
	public function printGalleryList() {
		echo "<table>";
		$media = $this->getMedia();
		for( $x=0; $x<count($media); $x++ ) {
			$preview = substr( $media[$x], 0, -11 ) . "150x150.jpg";
			echo "<tr>";
			echo "  <td><img src=\"uploads/$preview\" /></td>";
			echo "  <td><div>{$media[$x]}</div><div><input type=\"text\" value=\"[image:uploads/{$media[$x]}]\" /></div><div></td>";
			echo "</tr>";
		}
		echo "</table>";
	}
	
	public function upload() {
		$filename = strip_tags($_FILES['image']['name']);
		$filename = str_replace(' ', '-', $filename);
		$ext = "";
		$sizes = $_POST['size'];
		$tempname = $_FILES['image']["tmp_name"];
		$filetype = $_FILES['image']['type'];
		$filesize = round($_FILES['image']['size']/1024, 3);	
		$types = array('image/gif', 'image/jpg', 'image/jpeg', 'image/png', 'image/JPG', 'image/pjpeg', 'image/GIF');
		
		if( file_exists("uploads/$filename") ) {
			echo "<h2 class=\"red\">file exists, not uploaded!</h2>";			
		} else {
			
			if( $size = getimagesize($tempname) and $size and in_array($size['mime'], $types) ) {
				
				switch($size['mime']) {
					case 'image/jpg': case 'image/jpeg': case 'image/pjpeg': case 'image/JPG':
						$ext = "jpg";
						$src = imagecreatefromjpeg($tempname);
						$filename = str_ireplace('.jpg', '',   $filename);
		                $filename = str_ireplace('.jpeg', '',   $filename);
		                $filename = str_ireplace('.pjpg', '',   $filename);
					break;
					case 'image/gif': case 'image/GIF':
						$ext = "gif";
						$src = imagecreatefromgif($tempname);
						$filename = str_ireplace('.gif', '',   $filename);
					break;
					case 'image/png':
						$ext = "png";
						$src = imagecreatefrompng($tempname);
						$filename = str_ireplace('.png', '',   $filename);
					break;
				}
				
				$sizes[] = "150x150"; // the preview
				
				for($x=0; $x<count($sizes); $x++) {
					
					$target_size = explode('x', $sizes[$x]);
					$width = $size[0];
					$height = $size[1];
					
					$maxwidth = (int)($target_size[0]);
					$maxheight = (int)$target_size[1];
					
					if( $width < $maxwidth ) $maxwidth = $width;
					if( $height < $maxheight ) $maxheight = $height;
													
					while(($height/$width)*$maxwidth>$maxheight+1){ $maxwidth = $maxwidth - 1; }
					$newwidth = $maxwidth;
					$newheight=($height/$width)*$maxwidth;
					$tmp=imagecreatetruecolor($newwidth,$newheight);
					imagealphablending($tmp, false);
					imagesavealpha($tmp, true);
					imagecopyresampled($tmp,$src,0,0,0,0,$newwidth,$newheight,$width,$height);
					imagejpeg($tmp, "uploads/$filename-{$ext[0]}{$sizes[$x]}.jpg", 100);
					imagedestroy($tmp);
				}
				
				move_uploaded_file($tempname, "uploads/$filename.$ext");
				
				echo "<h2 class=\"green\">file uploaded successfully</h2>";
				echo "<img src=\"uploads/$filename-{$ext[0]}150x150.jpg\" />";			
				echo "<table>";
				$sizes = $_POST['size'];
				foreach($sizes as $dimensions) {
					echo "<tr><td>$dimensions</td><td><input type=\"text\" value=\"[image:uploads/$filename-{$ext[0]}{$dimensions}.jpg]\" /></td></tr>";
				}
				echo "</table>";
			} else {
				echo "<h2 class=\"red\">bad file</h2>";
			}
		}
		
	}
	
}

$m = new media();

?>
<!DOCTYPE html>
<html>
	<head>
		<title>LightPress CMS - Media</title>
		<style>
			* { margin: 0; padding: 0; }
			body { margin: 25px; }			
			h2 { text-transform: capitalize; margin-top: 45px; font-size: 24px; border-top: 2px solid #EBEBEB; padding-top: 25px; }
			.red { color: #ee5a4f; }
			.green { color: #58C026; }
			img { border: 1px solid #acacac; padding: 3px; margin-top: 5px; }
			input { padding: 8px; border-radius: 2px; border: 1px solid rgb(224, 224, 224); font-size: 18px; transition: 750ms; }
			input:hover { border-color: rgb(117, 117, 117); }
			form input[type="submit"]{ background: #fff; cursor: pointer; text-transform: capitalize; color: rgb(88, 88, 88); }
			table { border-collapse: collapse; border-spacing: 0; border: 1px solid #cccccc; margin: 15px 0 15px 0; }
			table tr th, table tr td { padding: 8px 16px 8px 16px; border-bottom: 1px solid #ccc; }
			table tr td { border-left: 1px solid #ccc; }
			table tr td:first-child { background: #f4f4f4; }
			table div { padding: 5px; }
			table a { color: red; text-transform: capitalize; }
		</style>
	</head>
	<body>
		<?php
		if( isset($_POST['submit']) and !empty($_FILES['image']) and !empty($_POST['size']) ):
			$m->upload();
		endif;
		?>
			
		<h2>upload a new image (JPG, PNG, GIF Only)</h2>
		<?php $m->printUploadForm(); ?>

		<h2>image gallery</h2>
		<?php $m->printGalleryList(); ?>
	</body>
</html>
