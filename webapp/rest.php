<?php
	require_once('third-party/mongodb/Client.php');
	require_once('third-party/mongodb/Database.php');
	require_once('third-party/mongodb/Collection.php');
	require_once('third-party/mongodb/functions.php');
	require_once('third-party/mongodb/Operation/Executable.php'); // Interface
	require_once('third-party/mongodb/Operation/InsertOne.php');
	require_once('third-party/mongodb/InsertOneResult.php');
	require_once('third-party/mongodb/Operation/Explainable.php'); // Interface
	require_once('third-party/mongodb/Operation/Find.php');
	require_once('third-party/mongodb/Model/BSONArray.php');
	require_once('third-party/mongodb/Model/BSONDocument.php');
	require_once('third-party/mongodb/Operation/DeleteOne.php');
	require_once('third-party/mongodb/Operation/Delete.php');
	require_once('third-party/mongodb/DeleteResult.php');
	

	function addDir($params) {
		global $mongoCollection;
		global $data;
		error_log("addDir: ".json_encode($params));
		if(isset($params['name'])) {
			try {
				$mongoCollection->insertOne(['name' => $_POST['name']]);
			}
			catch ( Exception $e ) {
				$duplicateError = "E11000 duplicate key error collection";
				if(substr($e->getMessage(), 0, strlen($duplicateError)) === $duplicateError) {
					$data['message'] = "Directory already exists";
					error_log("Directory ".$_POST['name']." already exists.");
				} else {
					$data['error'] = $e->getMessage();
				}
			}
		}
	}

	function getDir($params) {
		global $mongoCollection;
		global $data;
		$dataset = $mongoCollection->find();

		foreach($dataset as $document) {
			//array_push($data, $document['name']);
			$data['directories'][] = $document['name'];
		}
	}

	function remDir($params) {
		global $mongoCollection;
		global $data;
		if(isset($params['name'])) {
			$mongoCollection->deleteOne(['name' => $params['name']]);
			$data['message'] = "ok";
		} else {
			$data['message'] = "error";
		}
		error_log("remDir: ".json_encode($params));
	}


	try {
	    $mongo = new MongoDB\Client("mongodb://localhost:27017"); // connect
	    $mongoCollection = $mongo->adff->directory;
	}
	catch ( MongoConnectionException $e )
	{
	    echo '<p>Couldn\'t connect to mongodb, is the "mongo" process running?</p>';
	    exit();
	}
	// /etc/php/7.2/apache2/php.ini

	$data = [];

	$functions = ["getDir", "addDir", "remDir"];

	switch($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			$data['method'] = 'GET';
			if(isset($_GET['function']) && in_array($_GET['function'], $functions)) {
				call_user_func($_GET['function'], $_GET);
			} else {
				$data['message'] = 'Error: Invalid data';
			}
			break;
		case 'HEAD':
			$data['method'] = 'HEAD';
			break;
		case 'POST':
			$data['method'] = 'POST';
			if(isset($_POST['function']) && in_array($_POST['function'], $functions)) {
				call_user_func($_POST['function'], $_POST);
			} else {
				$data['message'] = 'Error: Invalid data';
			}
			break;
		case 'PUT':
			$data['method'] = 'PUT';
			break;
		case 'DELETE':
			$data['method'] = 'DELETE';
			error_log("DELETE".json_encode($_POST));
			break;
		default:
			$data['method'] = 'unknown: '.$_SERVER['REQUEST_METHOD'];
			break;
	}
	
	echo json_encode($data, JSON_PRETTY_PRINT);
?>