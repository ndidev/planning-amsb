<?php

namespace App\Core\Logger;

use App\Core\Database\MySQL;

use function App\Core\Functions\array_stringify;

class RequestLogger
{
    public static function log(): void
    {
        $logFile = "/app/log/requests.log";

        $mysql = new MySQL();

        if (isset($_COOKIE["planning-uid"])) {
            try {
                $user = $mysql->query("SELECT login, nom FROM admin_users WHERE uid = '{$_COOKIE['planning-uid']}'")->fetch();
            } catch (\Throwable $e) {
                ErrorLogger::log($e);
            }
        } else {
            $user = [
                "nom" => "Inconnu",
                "login" => "Pas de login/uid"
            ];
        }

        $sid = $_COOKIE["planning-sid"] ?? "Pas de session";

        $data = "[" . (new \DateTime())->format("Y-m-d H:i:s") . "]" . PHP_EOL .
            array_stringify(
                [
                    "utilisateur" => "{$user['nom']} ({$user['login']})",
                    "session" => $sid,
                    'REQUEST_URI' => $_SERVER["REQUEST_URI"]
                ]
            ) . PHP_EOL;

        try {
            $fh = fopen($logFile, "a");
            fwrite($fh, $data);
        } catch (\Throwable $e) {
            ErrorLogger::log($e);
        }
    }
}
