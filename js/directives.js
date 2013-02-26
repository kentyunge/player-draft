'use strict';

/* Directives */
angular.module('playerDraft.directives', []).
  directive('scrollTo', function() {
    return function(scope, elm, attrs) {
		if ((document.getElementById('chatTable').scrollHeight - document.getElementById('chatDiv').scrollTop) > 280) {
			document.getElementById('chatDiv').scrollTop = document.getElementById('chatTable').scrollHeight - 280;
		} 
    };
  });