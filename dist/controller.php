<?php
	session_start();
	
	if( !isset($_SESSION['auth']) || $_SESSION['auth'] !== 'randomauthkey12345') {
		echo '{ "status" : "error", "message" : "not authenticated" }';
		exit();
	}
	
	require('lightpress.class.php');
	
	$lp = new lightpress();
	
	$json = json_decode( file_get_contents("php://input") );
	
	header('Content-Type: application/json');
	
	switch( $_GET['method'] ) {
	
		case "list":
			echo json_encode( $lp->listAllPosts() );
		break;
		
		case "save":
			echo json_encode( $lp->save($json->id, $json->title, $json->content, $json->type, $json->date) );
		break;
		
		case "delete":
			echo json_encode( $lp->delete( $_GET['id'] ) );
		break;
		
		case "get":
			echo json_encode( $lp->get( $_GET['id'], false) );
		break;
		
		case "tags":
			echo json_encode( $lp->tags() );
		break;
				
		default:
			echo '{ "status" : "error", "message" : "unknown method" }';
	}
?>
