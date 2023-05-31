<?php

require_once __DIR__ . '/vendor/digitalstars/simplevk/autoload.php';

use DigitalStars\SimpleVK\{Bot, Message, Store, SimpleVK as vk};

$token = '';

$vk = vk::create($token, '5.131')->setConfirm('9f1e47ee');
$bot = Bot::create($vk);

$vk->setUserLogError(582127671);

$vk->initVars($peer_id, $user_id);
$ctx = Store::load($user_id);

$servers = [
    ['Phoenix'], ['Tucson'], ['Scottdale'], ['Chandler'], ['Brainburg']
];

$orgs = [
    ['LSPD'], ['RCSD'], ['FBI'], ['SFPD'], ['LSMed'], ['Government']
];


$bot->cmd('start', ['меню', 'Привет', 'Начать', 'начать'])->func(function (Message $msg) {
    global $servers;
    $msg->text("Ку, выбери сервер")->kbd($servers);
});

$bot->btn('first', $vk->buttonText("Назад", 'red'))->func(function (Message $msg) {
    global $servers;
    $msg->text("Ку, выбери сервер")->kbd($servers);
});


foreach ($servers as $key => $server) {
    foreach ($server as $serv) {
        $bot->btn($serv, $serv)->func(function (Message $message) use ($serv) {
            $message->text('Вы выбрали - ' . $serv)->kbd([
                [$serv . '_online'], [$serv . '_members'], [$serv . '_old'], [$serv . '_rich'], ['first']
            ]);
        });
        $bot->btn($serv . '_online', 'Узнать онлайн')->func(function (Message $message) use ($serv, $key) {
            $message->text("Онлайн сервера $serv составляет - " . getOnline($key) . " человек");
        });
        $bot->btn($serv . '_old', 'Узнать самых старых игроков')->func(function (Message $message) use ($serv, $key) {
            $players = getOldestPlayer($key + 1);
            $players_array = [];

            foreach ($players as $player) {
                $players_array[] = "НикНейм: " . $player->name . "\n" . "Уровень " . $player->level;
            }

            $message->text(implode("<br><br>", $players_array));
        });
        $bot->btn($serv . '_rich', 'Узнать самых богатых игроков')->func(function (Message $message) use ($serv, $key) {
            $players = getRichPlayer($key + 1);
            $players_array = [];

            foreach ($players as $player) {
                $players_array[] = "НикНейм: " . $player->name . "\n";
            }

            $message->text(implode("", $players_array));
        });

        $bot->btn($serv . '_members', 'Узнать онлайн организации')->func(function (Message $message) use ($serv, $key, $ctx, $orgs) {
            $ctx->sset('server_id', $key + 1);
            $message->text('Выбери организацию')->kbd($orgs, true);
        });
    }
}

foreach ($orgs as $key => $org) {
    foreach ($org as $organization) {
        $bot->btn($organization, $organization)->func(function (Message $message) use ($ctx, $organization, $key) {
            if ($ctx->get('server_id')) {
                $members = getMembers($ctx->get('server_id'), $key + 1);
                $members_array = [];

                foreach ($members->items as $key => $member) {
                    if ($key < 15)
                        $members_array[] = "НикНейм: " . $member->name . "<br>" . "Ранг: " . $member->rank . "<br>" . "Название ранга: " . $member->rankLabel;
                }

                $message->text(implode('<br><br>', $members_array));
            }
        });
    }
}
function getOnline(int $server_id)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://backend.arizona-rp.com/server/get-all");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Referer: https://arizona-rp.com/',
    ]);

    $response = curl_exec($ch);
    $response = json_decode($response);

    curl_close($ch);

    return $response[$server_id]->players;
}

function getOldestPlayer(int $server_id)
{
    $url = 'https://backend.arizona-rp.com/rating?type=oldest-players&serverId=' . $server_id;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Referer: https://arizona-rp.com/',
    ]);

    $response = curl_exec($ch);
    $response = json_decode($response);

    curl_close($ch);

    return $response->items;
}

function getRichPlayer(int $server_id)
{
    $url = 'https://backend.arizona-rp.com/rating?type=richest-players&serverId=' . $server_id;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Referer: https://arizona-rp.com/',
    ]);

    $response = curl_exec($ch);
    $response = json_decode($response);

    curl_close($ch);

    return $response->items;
}

function getMembers(int $server_id, int $fraction_id)
{
    $url = "https://backend.arizona-rp.com/fraction/get-players?serverId=$server_id&fractionId=$fraction_id";
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Referer: https://arizona-rp.com/',
    ]);

    $response = curl_exec($ch);
    $response = json_decode($response);

    curl_close($ch);
    return $response;
}


$bot->run();