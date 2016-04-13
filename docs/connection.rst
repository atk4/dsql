
==========
Connection
==========

DSQL works just fine if you specify
`PDO <http://php.net/manual/en/class.pdo.php>`_ class as your
connection. But there are some database vendors that cannot be
connected through PDO 
(`PDO Drivers <http://php.net/manual/en/pdo.drivers.php>`_)

For other vendors, you can extend of :php:class:`Connection` class
and implement querying logic yourself. 

In order to help you integrate your own vendor implementation,
our default Connection class supports vendor mapping for DSQL
implementation::


    $dsn = 'mysql:host=localhost;port=3307;dbname=testdb';

    $c = DSQL\Connection::connect($dsn, $user, $pass);
    $expr = $c -> expr("select now()");

    echo "Time now is : ". $expr;

:php:meth:`connect` will return appropriate class name that
you can use for a specified driver. This can be a PDO class
or it may try to use a 3rd party connection class.

.. todo::
write more
