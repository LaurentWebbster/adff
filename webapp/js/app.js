var app = angular.module('adff', []);
var url = "rest.php";

app.controller('MainController', function($scope, $http) {

	$scope.getCrawl = function() {
		$http.get(url + '?function=getCrawl').then(function(response) {
			$scope.crawls = response.data['crawls'];
		});
	};

	$scope.getDir = function() {
		$http.get(url + '?function=getDir').then(function(response) {
			$scope.directories = response.data['directories'];
		});
	};

	$scope.getDup = function() {
		$http.get(url + '?function=getDup').then(function(response) {
			$scope.duplicates = response.data['duplicates'];
		});
	};

	$scope.clrCrawlsAndDups = function() {
		var data = $.param({ function: 'clrCrawlsAndDups' });
		$http({
			url: url,
			method: 'POST',
			data: data,
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		})
		.then(function(response) {
			$scope.getCrawl();
			$scope.getDup();
		});
	}

	$scope.addCrawl = function(crawl) {
		var data = $.param({ function: 'addCrawl', name: crawl });
		$http({
			url: url,
			method: 'POST',
			data: data,
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		})
		.then(function(response) {
			if(response.data.message == "already_there") {
				alert("Crawling is already scheduled for this path.");
			} else {
				$scope.getCrawl();
			}
		})
		return false;
	};

	$scope.addDir = function() {
		var data = $.param({ function: 'addDir', name: $scope.newDirectory });
		$http({
			url: url,
			method: 'POST',
			data: data,
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		})
		.then(function(response) {
			$scope.getDir();
		})
		document.getElementById('newDirectory').value = '';
		return false;
	};

	$scope.rmDir = function(directory) {
		var data = $.param({ function: 'remDir', name: directory });
		$http({
			url: url,
			method: 'POST',
			data: data,
			headers: {'Content-Type': 'application/x-www-form-urlencoded'}
		})
		.then(function(response) {
			$scope.getDir();
		});
	}

	// Initial load
	$scope.getDir();
	$scope.getCrawl();
	$scope.getDup();
});
