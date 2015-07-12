var app = angular.module('steamBuddies', []);
app.controller('mainController', [ 'steamBuddies', '$scope', function(steamBuddies,$scope) {
    $scope.LOCAL_MODE = 'local';
    $scope.ONLINE_MODE = 'online';
    $scope.MAX_PLAYER_NUMBER = 8;

    $scope.activeSlide = 0;
    $scope.gamename = '';

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

    $scope.setMode = function(mode) {
        if (mode == $scope.LOCAL_MODE){
            $scope.goToSlide(2);
        }
        else {
            $scope.nextSlide();
        }
    };

    $scope.setPlayers = function(players){
        $scope.playerNumber = players;
        $scope.nextSlide();
    };

    $scope.addPlayer = function(player){
        $scope.players.push(player);
        $scope.currentPlayerName = "";
        if ($scope.players.length == $scope.playerNumber){
            $scope.nextSlide();
            steamBuddies.getGames($scope.players).then(function(d) {
                $scope.gamename = d.name;
            });
        }
    };

    $scope.nextSlide = function(){
        $scope.activeSlide++;
    };

    $scope.goToSlide = function(slide){
        $scope.activeSlide = slide;
    };


}] ).factory('steamBuddies', function($http) {
    return {
        getGames: function (friends) {
            return $http.post('/findmatches', {steam: friends}).then(function (response) {
              // The then function here is an opportunity to modify the response
              console.log(response);
              // The return value gets picked up by the then in the controller.
              return response.data;
            });
        }
    };
});;