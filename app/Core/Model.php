<?php
namespace App\Core;

/**
 * Base model. Child classes set $table (and $softDeletes if the table has deleted_at).
 * Models only talk to the database - business rules belong in app/Services.
 */
abstract class Model
{
    protected static string $table = '';
    protected static bool $softDeletes = false;

    protected static function ident(string $name): string
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/i', $name)) {
            throw new \InvalidArgumentException("Invalid identifier: {$name}");
        }
        return '`' . $name . '`';
    }

    protected static function liveClause(): string
    {
        return static::$softDeletes ? ' AND `deleted_at` IS NULL' : '';
    }

    public static function find(int $id): ?array
    {
        return Database::one('SELECT * FROM ' . static::ident(static::$table) . ' WHERE id = ?' . static::liveClause() . ' LIMIT 1', [$id]);
    }

    public static function findBy(string $column, mixed $value): ?array
    {
        return Database::one('SELECT * FROM ' . static::ident(static::$table) . ' WHERE ' . static::ident($column) . ' = ?' . static::liveClause() . ' LIMIT 1', [$value]);
    }

    public static function all(string $orderBy = 'id', string $direction = 'DESC', int $limit = 100, int $offset = 0): array
    {
        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        return Database::all(
            'SELECT * FROM ' . static::ident(static::$table) . ' WHERE 1=1' . static::liveClause()
            . ' ORDER BY ' . static::ident($orderBy) . ' ' . $dir . ' LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    public static function count(): int
    {
        return (int) Database::value('SELECT COUNT(*) FROM ' . static::ident(static::$table) . ' WHERE 1=1' . static::liveClause());
    }

    public static function create(array $data): int
    {
        $cols = array_map(fn($c) => static::ident($c), array_keys($data));
        $marks = implode(',', array_fill(0, count($data), '?'));
        Database::execute('INSERT INTO ' . static::ident(static::$table) . ' (' . implode(',', $cols) . ') VALUES (' . $marks . ')', array_values($data));
        return Database::insertId();
    }

    public static function update(int $id, array $data): int
    {
        $set = implode(',', array_map(fn($c) => static::ident($c) . ' = ?', array_keys($data)));
        return Database::execute('UPDATE ' . static::ident(static::$table) . ' SET ' . $set . ' WHERE id = ?', [...array_values($data), $id]);
    }

    public static function delete(int $id): int
    {
        if (static::$softDeletes) {
            return Database::execute('UPDATE ' . static::ident(static::$table) . ' SET deleted_at = UTC_TIMESTAMP() WHERE id = ?', [$id]);
        }
        return Database::execute('DELETE FROM ' . static::ident(static::$table) . ' WHERE id = ?', [$id]);
    }
}
