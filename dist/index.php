<?php
	require('lightpress.class.php');
	$lp = new lightpress();
	$pages = $lp->listPosts('date', 'G')['posts'];
	$listByYear = $lp->listByYear()['posts'];
?>
<!DOCTYPE html>
<html>
	<head>
		<title>my awesome blog</title>
		<link href="https://fonts.googleapis.com/css?family=PT+Serif" rel="stylesheet">
		<link href="style.css" rel="stylesheet">
	</head>
	<body>
		<div class="header">
			<div class="wrapper">
				
				<!-- page title -->				
				<div class="title">my awesome blog</div>
				
				<!-- navigation links -->
				<ul class="nav">
					<li><a href="./">archive</a></li>
					<?php
						foreach( $pages as $page ) {
							$url_title = str_replace(' ', '-', $page['title']);
							echo "<li><a href=\"post.php?id={$page['post_id']}#$url_title\">{$page['title']}</a></li>";
						}
					?>
				</ul>
				
				<div class="clear"></div>
			</div>
		</div>
		
		<!-- post list -->
		<div class="wrapper">
			<div class="archive">
				<?php
					foreach( $listByYear as $year => $posts) {
						echo "<h1>$year</h1>";
						for( $x=0; $x< count($posts); $x++ ) {
							$url_title = str_replace(' ', '-', $posts[$x]['title']);
							echo "<div class=\"group\">";
							echo "<div class=\"title\"><a href=\"post.php?id={$posts[$x]['post_id']}#$url_title\">{$posts[$x]['title']}</a></div>";
							echo "<div class=\"date\">" . date("F j, Y", $posts[$x]['date']) . "</div>";
							echo "<div class=\"clear\"></div>";
							echo "</div>";
						}
					}
				?>
			</div>
			
			<div class="footer">
				<p>like to stay up to date? join our <a href="#">mailing list</a></p>
				<p>quarterly. no spam! we promise.</p>
			</div>
			
		</div>
	</body>
</html>
