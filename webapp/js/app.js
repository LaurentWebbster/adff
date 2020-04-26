var app = angular.module('adff', []);
var url = "rest.php";

app.controller('MainController', function($scope, $http) {
	
	$scope.getDirectories = function() {
		$http.get(url + '?function=getDir').then(function(response) {
			$scope.directories = response.data['directories'];
		});
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
			$scope.getDirectories();
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
			$scope.getDirectories();
		});
	}

	// Initial load
	$scope.getDirectories();

});