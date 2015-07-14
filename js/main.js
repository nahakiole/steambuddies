var app = angular.module('steamBuddies', []);

app.controller('mainController', ['steamBuddies', '$scope', function (steamBuddies, $scope) {
  $scope.LOCAL_MODE = 'local';
  $scope.ONLINE_MODE = 'online';
  $scope.MAX_PLAYER_NUMBER = 8;

  $scope.activeSlide = 0;
  $scope.game = '';
  $scope.error = '';

  $scope.playerNumber = 1;
  $scope.playerNumberString = [
    'Zero',
    'One',
    'Two',
    'Three',
    'Four',
    'Five',
    'Six',
    'Seven',
    'Eight',
    'Nine',
    'Ten'
  ];

  $scope.players = [];
  $scope.currentPlayerName = "";


  $scope.mode = $scope.LOCAL_MODE;

  $scope.setMode = function (mode) {
    if (mode == $scope.LOCAL_MODE) {
      $scope.goToSlide(2);
    }
    else {
      $scope.nextSlide();
    }
  };

  $scope.setPlayers = function (players) {
    $scope.playerNumber = players;
    $scope.nextSlide();
  };

  $scope.addPlayer = function (player) {
    $scope.players.push(player);
    $scope.currentPlayerName = "";
    if ($scope.players.length == $scope.playerNumber) {
      $scope.nextSlide();
      steamBuddies.getGames($scope.players).then(function (d) {
        $scope.game = d;
      }, function (rejected) {
        $scope.error = rejected;
      });
    }
  };

  $scope.nextSlide = function () {
    $scope.goToSlide($scope.activeSlide + 1);
  };

  $scope.goToSlide = function (slide) {
    $scope.activeSlide = slide;
  };


}]).factory('steamBuddies', function ($http, $q) {
  return {
    getGames: function (friends) {
      return $q(function(resolve, reject) {
        $http.post('/findmatches', {steam: friends}).then(function (response) {
          console.log(response);
          if (response.data.status == 'error'){
            reject(response.data.response);
          }
          else {
            resolve(response.data.response);
          }
        });
      });
    }
  };
});