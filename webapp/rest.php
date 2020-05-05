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
	require_once('third-party/mongodb/Operation/Aggregate.php');
	require_once('third-party/mongodb/Operation/DropCollection.php');


	function addCrawl($params) {
		global $mongoDB;
		global $data;
		error_log("addCrawl: ".json_encode($params));
		if(isset($params['name'])) {
			try {
				$dataset = $mongoDB->crawl->find();
			}
			catch ( Exception $e ) {
				$dataset = [];
			}
			$already_there = false;
			foreach($dataset as $document) {
				if ($document['name'] == $params['name']) {
					$already_there = true;
				}
			}
			if ($already_there) {
				$data['message'] = 'already_there';
			} else {
				$data['message'] = 'ok';
				try {
					$mondo_date = new MongoDB\BSON\UTCDateTime(new DateTime());
					$mongoDB->crawl->insertOne(['name' => $_POST['name'], 'created' => $mondo_date, 'status' => 'new']);
				}
				catch ( Exception $e ) {
					$data['error'] = $e->getMessage();
				}
			}
		}
	}

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

	function clrCrawlsAndDups($params) {
		error_log("clrCrawlsAndDups: ".json_encode($params));
		global $mongoDB;
		$mongoDB->crawl->drop();
		$mongoDB->file->drop();
	}

	function getCrawl($params) {
		error_log("getCrawl: ".json_encode($params));
		global $mongoDB;
		global $data;
		try {
			$dataset = $mongoDB->crawl->find();
		}
		catch ( Exception $e ) {
			$dataset = [];
		}

		foreach($dataset as $document) {
			foreach(["created", "started", "finished"] as $key) {
				if(isset($document[$key])) {
					$document[$key] = date('Y-m-d H:i:s', intval(strval($document[$key])) / 1000);
				}
			}
			$data['crawls'][] = $document;
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

	function getDup($params) {
		error_log("getDup: ".json_encode($params));
		$query = 'db.file.aggregate([ {$group: { _id: {MD5: "$checksum_MD5"}, cnt: {$sum: 1}, name: {$addToSet:"$name"}, path: {$addToSet: "$path"} } }, {$match: {cnt: {"$gt": 1}}}, {$sort: {cnt: -1} } ]);';
		global $mongoDB;
		global $data;


		$dataset = $mongoDB->file->aggregate([
			['$group' => [
				"_id" => ["MD5" => '$checksum_MD5'],
				"cnt" => ['$sum' => 1],
				"name" =>  ['$addToSet' => '$name'],
				"path" => ['$addToSet' => '$path']
			]],
			['$match' => ["cnt" => ['$gt' => 1]]],
			['$sort' => ["cnt" => -1]]
		]);

		foreach($dataset as $document) {
			//array_push($data, $document['name']);
			$data['duplicates'][] = $document;
		}
	}

	function remCrawl($params) {
		global $mongoCollection;
		global $data;
		if(isset($params['name'])) {
			$mongoCollection->deleteOne(['name' => $params['name']]);
			$data['message'] = "ok";
		} else {
			$data['message'] = "error";
		}
		error_log("remCrawl: ".json_encode($params));
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
			$mongoDB = $mongo->adff;
			$mongoCollection = $mongo->adff->directory;
	}
	catch ( MongoConnectionException $e )
	{
	    echo '<p>Couldn\'t connect to mongodb, is the "mongo" process running?</p>';
	    exit();
	}

	$data = [];

	$functions = [
		"getDir", "addDir", "remDir",
		"getCrawl", "addCrawl", "remCrawl",
		"getDup", "clrCrawlsAndDups"
	];

	switch($_SERVER['REQUEST_METHOD']) {
		case 'GET':
			$data['method'] = 'GET';
			if(isset($_GET['function']) && in_array($_GET['function'], $functions)) {
				call_user_func($_GET['function'], $_GET);
			} else {
				$data['message'] = 'Error: Invalid data';
				error_log("Invalid function in GET request: ".json_encode($_GET));
			}
			break;
		case 'HEAD':
			$data['method'] = 'HEAD';
			break;
		case 'POST':
			$data['method'] = 'POST';
			if(isset($_POST['function']) && in_array($_POST['function'], $functions)) {
				error_log("Valid function in POST request: ".json_encode($_POST));
				call_user_func($_POST['function'], $_POST);
			} else {
				$data['message'] = 'Error: Invalid data';
				error_log("Invalid function in POST request: ".json_encode($_POST));
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
			error_log("Unknown HTML method: ".$_SERVER['REQUEST_METHOD']);
			break;
	}

	echo json_encode($data, JSON_PRETTY_PRINT);
?>
