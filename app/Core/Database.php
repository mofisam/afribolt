<?php
namespace App\Core;

use mysqli;
use mysqli_stmt;

/** Thin MySQLi wrapper. Every query uses prepared statements. */
final class Database
{
    private static ?mysqli $conn = null;

    public static function conn(): mysqli
    {
        if (self::$conn === null) {
            mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions
            $c = Config::get('database');
            $db = new mysqli($c['host'], $c['user'], $c['pass'], $c['name'], $c['port']);
            $db->set_charset($c['charset']);
            $db->query("SET time_zone = '+00:00'"); // all DATETIME values are UTC
            self::$conn = $db;
        }
        return self::$conn;
    }

    public static function run(string $sql, array $params = []): mysqli_stmt
    {
        $stmt = self::conn()->prepare($sql);
        if ($params) {
            $types = '';
            $values = [];
            foreach ($params as $p) {
                if (is_bool($p)) { $p = (int) $p; }
                $types .= is_int($p) ? 'i' : (is_float($p) ? 'd' : 's');
                $values[] = $p;
            }
            $stmt->bind_param($types, ...$values);
        }
        $stmt->execute();
        return $stmt;
    }

    public static function all(string $sql, array $params = []): array
    {
        $stmt = self::run($sql, $params);
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    public static function one(string $sql, array $params = []): ?array
    {
        $rows = self::all($sql, $params);
        return $rows[0] ?? null;
    }

    public static function value(string $sql, array $params = []): mixed
    {
        $row = self::one($sql, $params);
        return $row ? array_values($row)[0] : null;
    }

    /** INSERT/UPDATE/DELETE. Returns affected rows. */
    public static function execute(string $sql, array $params = []): int
    {
        $stmt = self::run($sql, $params);
        $n = $stmt->affected_rows;
        $stmt->close();
        return $n;
    }

    public static function insertId(): int { return (int) self::conn()->insert_id; }

    public static function begin(): void { self::conn()->begin_transaction(); }
    public static function commit(): void { self::conn()->commit(); }
    public static function rollback(): void { self::conn()->rollback(); }

    /** Run $fn inside a transaction; rolls back and rethrows on any exception. */
    public static function transaction(callable $fn): mixed
    {
        self::begin();
        try {
            $result = $fn();
            self::commit();
            return $result;
        } catch (\Throwable $e) {
            self::rollback();
            throw $e;
        }
    }
}
