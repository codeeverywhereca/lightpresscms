<?php
	require('lightpress.class.php');
	$lp = new lightpress();
	$pages = $lp->listPosts('date', 'G')['posts'];
	$post = $lp->get($_GET['id'])['post'];	
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?= $post['title'] ?></title>
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
							$selected = ( $post['title'] == $page['title'] ) ? ' class="selected"' : '';
							$url_title = str_replace(' ', '-', $page['title']);
							echo "<li$selected><a href=\"post.php?id={$page['post_id']}#$url_title\">{$page['title']}</a></li>";
						}
					?>
				</ul>
				
				<div class="clear"></div>
			</div>
		</div>		
		
		<div class="wrapper">
			
			<!-- post -->
			<div class="post">
				<?php if( $post['type'] == "P" ): ?>
					<h1><?= $post['title'] ?></h1>
					<div class="date">posted on <?= date("F j, Y, g:i a", $post['date']) ?> in <span class="tags"><?= $post['tags'] ?></span></div>
				<?php endif; ?>
				<div class="body">
					<?= $post['html_content'] ?>
				</div>
			</div>				
			
			<!-- recent posts -->
			<?php if( $post['type'] == "P" ): ?>
				<div class="recent">
					<h1>recent posts</h1>
					<?php
						foreach( $lp->listPosts('date', 'P', 3)['posts'] as $post ) {
							$url_title = str_replace(' ', '-', $post['title']);
							$date = date("F d, Y", $post['date']);
							echo "<ul><li><a href=\"post.php?id={$post['post_id']}#$url_title\">{$post['title']}</a><span>$date</span></li></ul>";
						}
					?>
				</div>
			<?php endif; ?>
			
			<a href="./" class="back">&lt; back</a>
			
			<div class="footer">
				<p>like to stay up to date? join our <a href="#">mailing list</a></p>
				<p>quarterly. no spam! we promise.</p>
			</div>
		
		</div>
	</body>
</html>
