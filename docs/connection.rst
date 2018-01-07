
.. _connect:

==========
Connection
==========

DSQL supports various database vendors natively but also supports 3rd party
extensions.
For current status on database support see: :ref:`databases`.


.. php:class:: Connection

Connection class is handy to have if you plan on building and executing
queries in your application. It's more appropriate to store
connection in a global variable or global class::

    $app->db = atk4\dsql\Connection::connect($dsn, $user, $pass);


.. php:staticmethod:: connect($dsn, $user = null, $password = null, $args = [])

    Determine which Connection class should be used for specified $dsn,
    create new object of this connection class and return.

    :param string $dsn: DSN, see http://php.net/manual/en/ref.pdo-mysql.connection.php
    :param string $user: username
    :param string $password: password
    :param array  $args: Other default properties for connection class.
    :returns: new Connection


This should allow you to access this class from anywhere and generate either
new Query or Expression class::

    $query = $app->db->dsql();

    // or

    $expr = $app->db->expr('show tables');


.. php:method:: dsql($args)

    Creates new Query class and sets :php:attr:`Query::connection`.

    :param array  $args: Other default properties for connection class.
    :returns: new Query

.. php:method:: expr($template, $args)

    Creates new Expression class and sets :php:attr:`Expression::connection`.

    :param string  $args: Other default properties for connection class.
    :param array  $args: Other default properties for connection class.
    :returns: new Expression


Here is how you can use all of this together::


    $dsn = 'mysql:host=localhost;port=3307;dbname=testdb';

    $c = atk4\dsql\Connection::connect($dsn, 'root', 'root');
    $expr = $c -> expr("select now()");

    echo "Time now is : ". $expr;

:php:meth:`connect` will determine appropriate class that can be used for this
DSN string. This can be a PDO class or it may try to use a 3rd party connection
class.

Connection class is also responsible for executing queries. This is only used
if you connect to vendor that does not use PDO.

.. php:method:: execute(Expression $expr)

    Creates new Expression class and sets :php:attr:`Expression::connection`.

    :param Expression  $expr: Expression (or query) to execute
    :returns: PDOStatement, Iterable object or Generator.
