<?php

use SteamCondenser\Community\SteamGame;
use SteamCondenser\Community\SteamId;

require_once __DIR__ . '/vendor/autoload.php';


$app = new Silex\Application();
// production environment - false; test environment - true
$app['debug'] = true;

$app->get(
    '/', function () {
    return new \Symfony\Component\HttpFoundation\Response(file_get_contents('index.html'));
}
);

$app->get(
    '/category', function () {

    $id = SteamId::create('Raubritter');
    $gameCollections[] = $id->getGames();
    return '';
}
);



$app->post(
    '/findmatches', function (\Silex\Application $app) {
    try {

        $post = json_decode(file_get_contents("php://input"), true);

        if (!isset($post['steam'])){
            return new \Symfony\Component\HttpFoundation\JsonResponse('Provide at least one player!');
        }
        $users = $post['steam'];

        $gameCollections = [];
        foreach ($users as $user) {
            $id = SteamId::create($user);
            $gameCollections[] = $id->getGames();
        }

        $gameCollections[]
            = function ($a, $b) {
            /**
             * @var $a SteamGame
             * @var $b SteamGame
             */

            if ($a instanceof SteamGame && $b instanceof SteamGame) {
                return !($a->getId() === $b->getId());
            }
            return false;

        };

        $games3 = call_user_func_array('array_uintersect_assoc', $gameCollections);
        $randomGame = $games3[array_rand($games3)];

        return new \Symfony\Component\HttpFoundation\JsonResponse(array(
            'name'  => $randomGame->getName(),
            'store' => $randomGame->getStoreUrl(),
            'image' => $randomGame->getLogoUrl()
        ));
    } catch (Exception $e) {

        return new \Symfony\Component\HttpFoundation\JsonResponse('Error');

    }
}
);

$app->run();