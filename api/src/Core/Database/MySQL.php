<?php

// Path: api/src/Core/Database/MySQL.php

declare(strict_types=1);

namespace App\Core\Database;

use App\Core\Exceptions\Server\DB\DBConnectionException;
use App\Core\Array\Environment;

/**
 * Connection to MySQL.
 */
class MySQL extends \PDO
{
    /**
     * @param string|null $database Optional. Name of the database.
     * @return void 
     */
    public function __construct(string $database = null)
    {
        $host = Environment::getString('DB_HOST');
        $port = Environment::getInt('DB_PORT', 3306);
        $base = $database ?? Environment::getString('DB_BASE');
        $user = Environment::getString('DB_USER');
        $pass = Environment::getString('DB_PASS');

        try {
            parent::__construct(
                "mysql:host=$host;port=$port;dbname=$base;charset=utf8mb4",
                $user,
                $pass,
                [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
                    \PDO::MYSQL_ATTR_FOUND_ROWS => true,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                ]
            );
        } catch (\PDOException $pdo_exception) {
            throw new DBConnectionException(previous: $pdo_exception);
        }
    }

    /**
     * Vérifie si une entrée existe dans la base de données.
     * 
     * @param string     $table    Nom de la table.
     * @param int|string $id       Identifiant de l'entrée.
     * @param string     $idColumn Optional. Nom de la colonne de l'identifiant.
     */
    public function exists(string $table, int|string $id, string $idColumn = "id"): bool
    {
        $statement = "SELECT EXISTS (SELECT * FROM `$table` WHERE `$idColumn` = :id)";

        $request = $this->prepare($statement);
        $request->execute(["id" => $id]);

        $exists = (bool) $request->fetch(\PDO::FETCH_COLUMN);

        return $exists;
    }
}
