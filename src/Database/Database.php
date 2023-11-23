<?php

namespace Tuples\Database;

/**
 * Simple PDO wrapper designed for projects that require basic database operations without extensive code management.
 * Provides a basic insert/update/delete/select builder compatible with major relational database management systems (RDBMS).
 * Use the query(...) method for more complex and RDBMS-specific statements.
 *
 * This "module" (\Tuples\Database) is not intended to reinvent the wheel. For more comprehensive database management, ORM, or ActiveRecord capabilities,
 * consider using tools like Doctrine and registering them with the App container to share across your application.
 */
class Database
{
    public function __construct(private \PDO $pdo)
    {
    }

    /**
     * Execute multiple instruction in the same transaction
     * inside the callback use first parameter as Database object
     *
     * @param \Closure $callback
     * @return void
     */
    public function batch(\Closure $callback)
    {
        try {
            $this->begin();
            $callback($this);
            $this->commit();
        } catch (\Throwable $th) {
            $this->rollback();
            throw new \Error($th->getMessage(), $th->getCode(), $th);
        }
    }

    /**
     * Start transaction
     *
     * @return boolean
     */
    public function begin(): bool
    {
        return $this->pdo->beginTransaction();
    }

    /**
     * Rollback transaction
     *
     * @return boolean
     */
    public function rollback(): bool
    {
        return $this->pdo->rollBack();
    }

    /**
     * Confirm transaction
     *
     * @return boolean
     */
    public function commit(): bool
    {
        return $this->pdo->commit();
    }

    /**
     * Simple key-value INSERT, for more complex insert statement use query()
     *
     * @param string $table
     * @param array $values
     * @return int|string
     */
    public function insert(string $table, array $values): int|string
    {
        $columns = array_keys($values);
        $placeholders = array_fill(0, count($values), "?");
        $rawValues = array_values($values);

        $query = "INSERT INTO $table (" . implode(",", $columns) . ") VALUES (" . implode(",", $placeholders) . ")";

        $res = $this->query($query, $rawValues);

        // lastInserted id
        return $res[2];
    }

    /**
     * Simple key-value UPDATE, for more complex update statement use query()
     *
     * @param string $table
     * @param array $where
     * @param array $values
     * @return int
     */
    public function update(string $table, array $where, array $values): int
    {
        // cast allways as multidimensional array
        $wheres = isset($where[0]) && is_array($where[0]) ? $where : [$where];

        $parameters = [];
        $sets = [];
        $whereArray = [];

        foreach ($values as $column => $value) {
            $sets[] = $column . " = ?";
            $parameters[] = $value;
        }

        foreach ($wheres as $where) {
            list($column, $value, $operator) = $where;
            $auxComparation = empty($operator) ? "=" : $operator;
            $whereArray[] = " $column $auxComparation ?";
            $parameters[] = $value;
        }

        $query = "UPDATE $table SET " . implode(", ", $sets) . " WHERE " . implode(" AND ", $whereArray);

        $res = $this->query($query, $parameters);

        // affectedRows
        return $res[1];
    }

    /**
     * Query shorcuct. Executes query() and return only the rows
     *
     * @param string $query
     * @param array|null|null $parameters
     * @param int $mode
     * @return array
     */
    public function select(string $query, array|null $parameters = null, int $mode = \PDO::FETCH_OBJ): array
    {
        if (strtolower(substr($query, 0, 6)) !== 'select') {
            throw new \Error("Using select() on non-SELECT statement");
        }

        $res = $this->query($query, $parameters, $mode);

        // rows
        return $res[0];
    }

    /**
     * Executes a Query on the object connection
     *
     * @param string $query
     * @param array|null|null $parameters
     * @param int $mode
     * @return array | rows, affectedRows and lastInsertedId in each case
     */
    public function query(string $query, array|null $parameters = null, int $mode = \PDO::FETCH_OBJ): array
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute($parameters);

        $lastInsertedId = null;
        $rows = null;

        if (strtolower(substr($query, 0, 6)) === 'insert') {
            $lastInsertedId = $this->pdo->lastInsertId();
        }

        if (strtolower(substr($query, 0, 6)) === 'select') {
            $rows = $stmt->fetchAll($mode);
        }

        $affectedRows = $stmt->rowCount();

        return [$rows, $affectedRows, $lastInsertedId];
    }

    /**
     * Simple key-value (where) DELETE, for more complex delete statement use query()
     *
     * @param string $table
     * @param array $where
     * @return int
     */
    public function delete(string $table, array $where)
    {
        $parameters = [];
        $whereArray = [];

        // cast allways as multidimensional array
        $wheres = isset($where[0]) && is_array($where[0]) ? $where : [$where];

        foreach ($wheres as $where) {
            list($column, $value, $operator) = $where;
            $auxComparation = empty($operator) ? "=" : $operator;
            $whereArray[] = " $column $auxComparation ?";
            $parameters[] = $value;
        }

        $query = "DELETE FROM $table WHERE " . implode(" AND ", $whereArray);

        $res = $this->query($query, $parameters);

        // affectedRows
        return $res[1];
    }

    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
}
