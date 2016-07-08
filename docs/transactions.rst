
============
Transactions
============

When you work with the DSQL, you can work with transactions. There are 2 enhancement
to the standard functionality of transactinos in DSQL:

1. You can start nested transactions.

2. You can use atomic() which has a nicer syntax.

I recommend that you only use atomic() in your code.

.. php:class:: Connection


.. php:method:: atomic($callback)


    Execute callback within the SQL transaction. If callback entounters an
    excetpion, transaction will be automatically rolled back::


        $c->atomic(function() use($c) {
            $c->dsql('user')->set('balance=balance+10')->where('id', 10)->update();
            $c->dsql('user')->set('balance=balance-10')->where('id', 14)->update();
        });

    atomic() can be nested, the completion of a top-most method will commit everything.

.. php:method:: beginTransaction

    Start new transaction. If already started, will do nothing but will increase
    "transaction_depth"

.. php:method:: commit

    Will commit transaction, however if begiTransaction was executed more than once,
    will only transaction_depth.

.. php:method:: inTransaction

    Returns true if transaction is currently active. There is no need for you to ever
    use this method.

.. php:method:: rollBack

    roll-back the transaction.




.. warning:: If you roll-back internal transaction and commit external transaction, then
    result might be unpredictable. Please discuss this https://github.com/atk4/dsql/issues/89
