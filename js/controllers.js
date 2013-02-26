'use strict';

/* Controllers */
function ChatCtrl($scope, $http, $timeout) {
	var state = null;

	$scope.startChat = function() {
		$timeout(function updateChat(){
			$http({method: 'GET', url: 'http://theyunges.com/scrape/angular-seed/app/api/index.php/chat/' + state}).
			  success(function(data, status, headers, config) {
			  	$scope.chatMessages = data;
			  	$timeout(updateChat, 1000);
			  }).
			  error(function(data, status, headers, config) {
				$timeout(updateChat, 1000);
			  });
	    },1000);
	};

    $scope.sendMessage = function () {
    	// create object to send to service
    	$scope.chatMessage = {
    		email: 'kent.yunge@gmail.com',
    		message: $scope.message
    	};

    	// set message textbox to ''
    	$scope.message = '';

    	// insert new chat message
		$http({method: 'POST', url: 'http://theyunges.com/scrape/angular-seed/app/api/index.php/chat', data: angular.toJson($scope.chatMessage)});
    };

    // onLoad run the function to grab the max insert date
	(function(){
		$http({method: 'GET', url: 'http://theyunges.com/scrape/angular-seed/app/api/index.php/status'}).
		  success(function(data, status, headers, config) {
	    	state = data[0].state;
	    	$scope.startChat();
		  });
	})();
}
