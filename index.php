<?php

use SteamCondenser\Community\SteamGame;
use SteamCondenser\Community\SteamId;

require_once __DIR__ . '/vendor/autoload.php';

set_time_limit(0);
$app = new Silex\Application();
mkdir(__DIR__ . '/cache');
// production environment - false; test environment - true
$app['debug'] = true;

$app->get(
    '/', function () {
    return new \Symfony\Component\HttpFoundation\Response(file_get_contents('index.html'));
});

$app->get(
    '/warmup', function () {
    ob_end_flush();
    $allGames = json_decode( file_get_contents('http://api.steampowered.com/ISteamApps/GetAppList/v0001/'), true);
    foreach($allGames['applist']['apps']['app'] as $game){
        echo $game['name'].'<br/>';
        SteamCache::get($game['appid']);
    }
    return 'done';
});

$app->post(
    '/findmatches', function (\Silex\Application $app) {
    try {

        $post = json_decode(file_get_contents("php://input"), true);

        if (!isset($post['steam'])) {
            return new \Symfony\Component\HttpFoundation\JsonResponse('Provide at least one player!');
        }
        $users = $post['steam'];

        if (count($users) == 1) {
            $id = SteamId::create(current($users));

            /**
             * @var $singleGames SteamGame[]
             */
            $singleGames = $id->getGames();
            $localMultiplayerGames = array_filter($singleGames, function ($game) {
                return SteamCache::hasLocalMultiplayer($game->getAppId());
            });

            $randomGame = $localMultiplayerGames[array_rand($localMultiplayerGames)];
            return new \Symfony\Component\HttpFoundation\JsonResponse(array('status' => 'success',
                'response' => array(
                    'name' => $randomGame->getName(),
                    'store' => $randomGame->getStoreUrl(),
                    'image' => $randomGame->getLogoUrl()
                )));
        }

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
                if (($a->getAppId() === $b->getAppId())) {
                    if (SteamCache::hasOnlineMultiplayer($a->getAppId())) {
                        return 0;
                    }
                }
            }
            return 1;

        };

        $games3 = call_user_func_array('array_uintersect_assoc', $gameCollections);
        $randomGame = $games3[array_rand($games3)];

        return new \Symfony\Component\HttpFoundation\JsonResponse(
            array('status' => 'success',
                'response' => array(
                    'name' => $randomGame->getName(),
                    'store' => $randomGame->getStoreUrl(),
                    'image' => $randomGame->getLogoUrl()
                )));
    } catch (Exception $e) {
        return new \Symfony\Component\HttpFoundation\JsonResponse(
            array('status' => 'error',
                'response' => $e->getMessage()
            )
        );
    }
}
);

class SteamCache
{
    const CATEGORY_LOCAL_MULTIPLAYER = 24;
    const CATEGORY_ONLINE_MULTIPLAYER = 1;

    private static $cache = array();
    private static $curl;

    private static function getCurl()
    {
        if (isset(self::$curl)){
            return self::$curl;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        return self::$curl = $ch;
    }

    public static function get($steamID)
    {
        if (isset(self::$cache[$steamID])) {
            return self::$cache[$steamID];
        }
        if (file_exists(__DIR__ . '/cache/' . $steamID)) {
            $response = file_get_contents(__DIR__ . '/cache/' . $steamID);
        } else {
            $ch = self::getCurl();
            curl_setopt($ch, CURLOPT_URL,'http://store.steampowered.com/api/appdetails/?appids=' . $steamID);
            $response = curl_exec($ch);
            file_put_contents(__DIR__ . '/cache/' . intval($steamID) , $response);
        }
        return self::$cache[$steamID] = json_decode($response, true)[$steamID]['data'];
    }

    public static function hasLocalMultiplayer($steamID)
    {
        self::get($steamID);
        foreach (self::$cache[$steamID]['categories'] as $category) {
            if ($category['id'] == self::CATEGORY_LOCAL_MULTIPLAYER) {
                return true;
            }
        }
        return false;
    }

    public static function hasOnlineMultiplayer($steamID)
    {
        self::get($steamID);
        foreach (self::$cache[$steamID]['categories'] as $category) {
            if ($category['id'] == self::CATEGORY_ONLINE_MULTIPLAYER) {
                return true;
            }
        }
        return false;
    }

}


$app->run();