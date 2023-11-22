<?php

namespace Tuples\Database;

class DatabasePool
{
    /**
     * Array of Database instances
     *
     * @var Database[]
     */
    private array $instances;

    /**
     * Construct with default connection
     *
     * @param Database $pdo
     */
    public function __construct(Database $db)
    {
        $this->instances['default'] = $db;
    }

    /**
     * Add database connection
     *
     * @param string $name
     * @param Database $db
     * @return void
     */
    public function add(string $name, Database $db): void
    {
        $this->instances[$name] = $db;
    }

    /**
     * Get database connection
     *
     * @param string $name
     * @return Database
     */
    public function get(string $name): Database
    {
        if (!isset($this->instances[$name])) {
            throw new \Error("Database $name doesnt exists in pool");
        }

        return $this->instances[$name];
    }
}
