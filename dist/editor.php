<?php
	/*
	* lightpress cms (Nov 19 2016)
	* https://github.com/codeeverywhereca/lightpresscms
	* Copyright 2016, http://codeeverywhere.ca
	* Licensed under the MIT license.
	*/
  
  require('lightpress.class.php');
	session_start();
	if(isset($_POST['submit']) && $_POST['pass'] == lp_password){ $_SESSION['auth'] = 'randomauthkey12345'; }
	if(isset($_GET['logout']) || $_SESSION['auth'] != 'randomauthkey12345'){ $_SESSION['auth'] = false; session_destroy(); }
	if(isset($_SESSION['auth']) and $_SESSION['auth'] == 'randomauthkey12345'):
?>

<!DOCTYPE html>
<html ng-app="lightpress">
	<head>
		<title>LightPress CMS</title>
		<link href="https://fonts.googleapis.com/css?family=PT+Serif" rel="stylesheet">
		<style type="text/css">
			*{ margin: 0; padding: 0; }
			img{ border: 0; }
			body{ font-size: 14px; font-family: 'PT Serif', serif; }
			p { margin: 5px 0 5px 0; }
			
			.lp-header { background: #000; color: #fff; border-bottom: 1px solid #d9d9d9; padding: 10px; font-size: x-large; text-transform: capitalize; }
			.lp-header a { color: #c3c3c3; margin: 0 8px 0 8px; }
		
			.lp-dash { font-size: 18px; }
			.lp-dash h1 { font-size: 28px; }
			.lp-dash h2 { font-size: 24px; margin-top: 25px; text-transform: capitalize; }			
			
			.lp-tags li { list-style-type: none; display: inline-block; margin-left: 5px; margin-top: 5px; background: #f6f6f6; 
				border: 1px solid rgb(227, 227, 227); padding: 2px 6px 2px 6px; }
			.lp-tags li span { color: rgb(120, 120, 120); text-transform: capitalize; }
			
			.lp-left{ float: left; }
			.lp-right{ float: right; }
			.lp-left, .lp-right{ width: 310px; }
			.lp-txt-right{ text-align: right; }
			.lp-txt-trunc{ overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
			.lp-clear{ clear: both; }
			.lp-grey { color: #717171; margin-left: 5px; }
			.lp-empty { text-transform: capitalize; padding: 45px; text-align: center; color: #2e2e2e; font-weight: bold; }
			
			.lp-posts{ width: 320px; padding: 16px; background: #fff; max-height: 600px; overflow-y: scroll; }
			.lp-posts-heading{ padding: 8px; border-bottom: 1px solid #e1e1e1; font-weight: bold;
				display: block; color: #e6e6e6; text-transform: capitalize; color: #000; font-size: large; }
			.lp-posts > div:nth-child(1) { margin-bottom: 25px; }
			.lp-posts-list a { display: block; margin: 4px 0 4px 0; cursor: pointer; text-transform: capitalize; padding: 8px; border-bottom: 1px solid #f7f7f7; }
			.lp-posts-list a:hover { background: #eaeaea; }
			
			.lp-posts-heading a:first-child { margin-left: 155px; }
			.lp-posts-heading a { color: #000; margin-left: 10px; font-weight: normal; }
			
			.H { border-left: 4px solid #ffe430; }
			.D { border-left: 4px solid #2473FA; }

			.lp-preview{ width: 640px; margin: 5px 25px 5px 25px; position: relative; }

			.lp-options{ /* width: 400px; */ margin: 20px 0 20px 0; }
			.lp-options a{ margin-left: 10px; color: #151515; font-size: large; text-transform: capitalize; }
			.lp-red { color: #FC4349 !important; }

			.lp-title input, .lp-content textarea, .lp-content > div { background: #fff; font-size: large; padding: 8px; border: 1px solid #d4d4d4; border-radius: 2px; }
			.lp-content > div { overflow: auto; }
			.lp-preview h3 { margin-top: 24px; margin-bottom: 4px; text-transform: capitalize; }
			
			.lp-link { padding: 8px 0px 0 5px; color: #757575; font-size: 15px; }
			.lp-link a { color: #a1a1a1; }
									
			.lp-border{ border: none; outline: none; -webkit-box-shadow: none; -moz-box-shadow: none; box-shadow: none; }
			.lp-textarea{ font-size: 14px; }
			
			.lp-expanded .lp-preview { position: fixed; width: auto; overflow-y: scroll; border: 1px solid #5c5c5c; padding: 25px;
				top: 15px; bottom: 15px; right: 15px; left: 15px; background: #fff; box-shadow: 0px 0px 50px 0px #8a8a8a;
			}
			.lp-expanded .lp-textarea { width: 98% !important; }
			
			.lp-expand-btn { display: inline-block; padding: 5px; color: rgb(161, 161, 161); text-transform: capitalize; position: absolute; top: 15px; right: 15px; }
			
			.lp-datemodifier { padding: 15px; border: 1px solid red; margin-top: 10px; margin-bottom: 10px; }
			.lp-datemodifier input { padding: 5px; width: 100px; font-size: 14px; text-align: center; margin-right: 5px; }
		</style>
		<script src="https://ajax.googleapis.com/ajax/libs/angularjs/1.3.0-beta.8/angular.min.js"></script>
		<script type="text/javascript">
			var app = angular.module("lightpress", []);
			app.controller('lp', ['$scope', '$http', '$sce', function($scope, $http, $sce) {
				
				$scope.APICall = function(API, data, cb) {
					var http_data;
					if( data === false ) http_data = { method: 'GET', url: API };
					else http_data = { method: 'POST', url: API, data : data };
					
					$http(http_data)
						.success(function(data, status, headers, config) {
							if(data.status == 200) cb(data);
							else alert('Problem: ' + data.message.toUpperCase());
						})
						.error(function(data, status, headers, config) {
							alert('Error:' + data.message);
						})
					;
				};
				
				$scope.posts = [];
				$scope.action = 'dash'; //dash, new, view, edit
				$scope.post = { title : '', content : ' ', tags : '', type : '', date : 0 };
				$scope.dateModifier = { display : false, value : 0 };
				$scope.pageViews = 0;				
				$scope.wordCount = function(){ return $scope.post.content.split(' ').length; };
				$scope.readTime = function(){ return Math.ceil($scope.wordCount() / 250); };
				
				$scope.list = function() {
					$scope.APICall('controller.php?method=list', false, function(data) {
						$scope.posts = data.posts;
						$scope.pageViews = 0;
						for( var x=0; x < data.posts.length; x++ )
							$scope.pageViews += parseInt(data.posts[x].views);
					});
				};
				
				$scope.newPost = function( type ) {
					$scope.action = 'new';
					$scope.post = { post_id : -1, title : '', content : ' ', tags : '', type : type, date : Math.floor( new Date() / 1000 ) }	
				};
				
				$scope.get = function(id) {
					$scope.APICall('controller.php?method=get&id=' + id, false, function(data) {
						$scope.action = 'view';
						$scope.dateModifier.display = false;
						$scope.post = data.post;
						$scope.post.html_content = $sce.trustAsHtml(data.post.html_content);
						$scope.post.link = "post.php?id=" + data.post.post_id + "#" + data.post.title.replace(/\s/g, '-');
					});
				};
				
				$scope.del = function(post_id) {
					$scope.APICall('controller.php?method=delete&id=' + post_id, false, function(data) {
						alert('Post Deleted');
						$scope.action = 'dash';
						$scope.list();
					});
				};
				
				$scope.save = function() {
					var post_data = { id : $scope.post.post_id, title : $scope.post.title, content : $scope.post.content, type : $scope.post.type, date : $scope.post.date };
					$scope.APICall('controller.php?method=save', post_data, function(data) {
						alert('post saved as ' + data.post.newedit);
						$scope.action = 'view';
						$scope.post = data.post;
						$scope.post.html_content = $sce.trustAsHtml(data.post.html_content);
						$scope.post.link = "post.php?id=" + data.post.post_id + "#" + data.post.title.replace(/\s/g, '-');
						$scope.list();
					});
				};
				
				$scope.APICall('controller.php?method=tags', false, function(data) {
					$scope.tags = data.tags.split(',');
				});
				
				$scope.list();
				
				$scope.toggleTitle = "expanded view";
				$scope.toggleExpandedView = function() {
					var el = angular.element( document.getElementsByTagName('body')[0] );
					if( el.hasClass('lp-expanded') ) {
						el.removeClass('lp-expanded');
						$scope.toggleTitle = "expanded view";
					} else {
						el.addClass('lp-expanded');
						$scope.toggleTitle = "collapse";
					}
				};
				
				$scope.unixToString = function( unix ) {
					var d = new Date( unix * 1000 );
					return d.getDate() + '-' + (d.getMonth()+1) + '-' + d.getFullYear();
				};
				$scope.stringToUnix = function( str ) {
					str = str.split("-");
					if( str.length == 3 ) {
						var d = new Date( parseInt(str[2]) , parseInt(str[1]) - 1, parseInt( str[0] ) );
						return Math.floor( d / 1000 ) + "";
					} else {
						return Math.floor( new Date() / 1000 ) + "";
					}
				};
			}]);
		</script>
	</head>
	<body ng-controller="lp">
		<div class="lp-header">
			<div class="lp-left">lightPress CMS</div>
			<div class="lp-right lp-txt-right">
				<a href="media.php" target="_blank">media gallery</a>
				<a href="editor.php?logout">Sign Out</a>
			</div>
			<div class="lp-clear"></div>
		</div>
		
		<!-- post list -->
		<div class="lp-left lp-posts" ng-init="types = [ ['page', 'G', 'GH'], ['post', 'D', 'DP'] ]">
			<div ng-repeat="type in types">
				<div class="lp-posts-heading">{{ type[0] }}s <a href="#" ng-click="newPost( type[1] )">new {{ type[0] }}</a></div>
				<div class="lp-posts-list">
					<a class="lp-txt-trunc" ng-repeat="post in posts" ng-if="type[2].indexOf( post.type ) > -1" ng-click="get( post.post_id )" ng-class="post.type">
						{{ post.title }} <span class="lp-grey">{{ post.date * 1000 | date : 'MMM d, yyyy' }}</span>
					</a>
					<div class="lp-empty" ng-if="posts.length == 0">no {{ type[0] }}s yet!</div>
				</div>
			</div>
		</div>
		
		<!-- content -->
		<div class="lp-left lp-preview">
			
			<a href="#/" class="lp-expand-btn" ng-click="toggleExpandedView()">{{ toggleTitle }}</a>
			
			<div ng-switch="action">

				<div ng-switch-when="dash" class="lp-dash">
					<h1>Welcome to LightPress</h1>
					<p>A lightweight blog engine, written in AngularJS, PHP and SQLite.</p>
					
					<h2>stats</h2>
					<ul class="lp-tags">
						<li>{{ posts.length }} <span>posts</span></li>
						<li>{{ pageViews | number }} <span>page views</span></li>
					</ul>
					
					<h2>tags</h2>
					<ul class="lp-tags">
						<li ng-repeat="tag in tags">{{ tag }}</li>
					</ul>
					
					<h2>guide</h2>
					<p>To add heading use <strong>#heading</strong></p>
					<p>To add bold text use <strong>**some text**</strong></p>
					<p>To add images use <strong>[image:uploads/image-link.jpg]</strong></p>
					<p>To add tags use <strong>[tags:aaa, bbb, ccc]</strong></p>
					<p>To add code use <strong>```( php | sql | js-inc | css-inc | html | js | css | terminal ) ... ```</strong></p>
					<p>Special note, 'js-inc' and 'css-inc' will include the code on your page.</p>
					<p>To add a quote use <strong>```quote ... ```</strong></p>
					<p>To add a table use <strong>```table ... ```</strong></p>
					<p>To add a skip block use <strong>```skip ... ```</strong></p>
					
					<h2>about</h2>
					<p>LightPress CMS v1.0, a <a href="http://codeeverywhere.ca">code everywhere</a> project.</p>
					<p>Visit the <a href="https://github.com/codeeverywhereca/lightpresscms">GitHub</a> page for details.</p>
				</div>
								
				<div ng-switch-when="new">					
					<div class="lp-title">
						<h3>title</h3>
						<input type="text" style="width: 620px;" name="title" placeholder="type your title here" ng-model="post.title" >
					</div>
					<div class="lp-content">
						<h3>content</h3>
						<textarea class="lp-border lp-textarea" ng-model="post.content" style="width: 620px; height: 400px;"></textarea>
					</div>
				</div>
					
				<div ng-switch-when="view">
					<div class="lp-title">
						<h3>title</h3>
						<input type="text" style="width: 620px;" name="title" ng-model="post.title" disabled="disabled">
						<p class="lp-link">Link <a ng-href="{{ post.link }}" target="_blank">{{ post.link }}</a></p>
					</div>
					<div class="lp-content">
						<h3>Preview <a href="#/" ng-click="$parent.action='edit'">edit</a></h3>
						<div ng-bind-html="post.html_content"></div>
					</div>					
				</div>
					
				<div ng-switch-when="edit">
					<div class="lp-title">
						<h3>title</h3>
						<input type="text" style="width: 620px;" name="title" placeholder="type your title here" ng-model="post.title">
						<p class="lp-link">Link <a ng-href="{{ post.link }}" target="_blank">{{ post.link }}</a></p>
					</div>
					<div class="lp-content">
						<h3>content</h3>
						<textarea class="lp-border lp-textarea" ng-model="post.content" style="width: 620px; height: 400px;"></textarea>
					</div>
				</div>
					
			</div>
						
			<div ng-show="action != 'dash'">
				
				<div class="lp-link">
					<select ng-model="post.type"  ng-selected="post.type" ng-disabled="action == 'view'">
						<option value="D">Draft</option>
						<option value="P">Post</option>
						<option value="G">Page</option>
						<option value="H">Hidden Page</option>
					</select>
					{{ wordCount() | number }} words, {{ readTime() }} min read | 
					{{ post.date * 1000 | date : 'medium' }} <a href="#/" ng-show="action=='edit'" ng-click="dateModifier.display = !dateModifier.display">modify</a>
					<span ng-if="post.tags.length > 0"> | Tags {{ post.tags }}</span>
				</div>
				
				<div class="lp-txt-right lp-options">
					<div ng-if="dateModifier.display" class="lp-datemodifier">
						<input type="text" ng-init="dateModifier.value = unixToString( post.date );" ng-model="dateModifier.value"/>
						{{ stringToUnix( dateModifier.value ) * 1000 | date : 'medium' }}
						<a href="#/" ng-click="dateModifier.display = false;">cancel</a>
						<a href="#/" ng-click="post.date = stringToUnix(dateModifier.value); dateModifier.display = false;">set</a>
					</div>
					<a href="#/" class="lp-red" ng-show="action=='edit'" ng-click="del(post.post_id)">delete</a>
					<a href="#/" ng-click="action='dash'">cancel</a>
					<a href="#/" ng-show="action == 'edit' || action == 'new'" ng-click="save()">save</a>
					<a href="#/" ng-show="action=='view'" ng-click="action='edit'">edit</a>					
				</div>

			</div>
			
		</div>
		<div class="lp-clear"></div>
	</body>
</html>

<?php else: ?>

<!DOCTYPE html>
<html>
	<head>
		<link href="https://fonts.googleapis.com/css?family=PT+Serif" rel="stylesheet">
		<title>login...</title>
		<style type="text/css">
			*{ margin: 0; padding: 0; }
			body{ background: #f8f8f8; font-family: 'PT Serif', serif; }
			.lp-wrapper{ width: 500px; margin: 125px auto; }
			.lp-green, .lp-red { font-size: 18px; margin-bottom: 15px; text-transform: capitalize; }
			.lp-green{ color: #40EA36; }
			.lp-red{ color: #FC4349; }
			.lp-login{ padding: 25px; background: #ffffff; text-align: center; }
			.lp-login input { padding: 8px; border-radius: 2px; border: 1px solid rgb(224, 224, 224); font-size: 18px; transition: 750ms; }
			.lp-login input:hover { border-color: #000; }
			.lp-login input[type='submit'] {  background: #fff; cursor: pointer; }
		</style>
	</head>
	<body>
		
		<div class="lp-wrapper">
			<form action="editor.php" method="post" class="lp-login">
				<?php
					if(isset($_GET['logout'])) echo '<div class="lp-green">you have successfully signed out</div>';
					if(isset($_POST['submit'])) echo '<div class="lp-red">your password is incorrect</div>';
					if(isset($_GET['login'])) echo '<div class="lp-red">your must be signed in to access this page</div>';
				?>
				<input type="password" name="pass" size="32" placeholder="Password" />				
				<input type="submit" name="submit" value="Sign In" />
			</form>
		</div>
		
	</body>
</html>

<?php endif; ?>
