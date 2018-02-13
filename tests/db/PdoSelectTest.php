<?php

namespace atk4\dsql\tests\db;

use atk4\dsql\Connection;

class PdoSelectTest extends SelectTest
{
    public function __construct()
    {
        $this->c = Connection::connect(new \PDO($GLOBALS['DB_DSN'], $GLOBALS['DB_USER'], $GLOBALS['DB_PASSWD']));
        $this->pdo = $this->c->connection();

        $this->pdo->query(
            'CREATE TEMPORARY TABLE employee (id int not null, name text, surname text, retired bool, PRIMARY KEY (id))'
        );
    }
}
