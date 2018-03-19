.. _databases:

Vendor support and Extensions
=============================

=========== ========= ======== ============
Vendor      Support   PDO      Dependency
=========== ========= ======== ============
MySQL       Full      mysql:   native, PDO
SQLite      Full      sqlite:  native, PDO
Oracle      Untested  oci:     native, PDO
PostgreSQL  Untested  pgsql:   native, PDO
MSSQL       Untested  mssql:   native, PDO
=========== ========= ======== ============

.. note::

  Most PDO vendors should work out of the box

Other Interesting Drivers
-------------------------

===================== ========= ======== ============
Class                 Support   PDO      Dependency
===================== ========= ======== ============
Connection_Dumper     Full      dumper:  native, Proxy
Connection_Counter    Full      counter: native, Proxy
===================== ========= ======== ============


3rd party vendor support
-------------------------

===================== ========= =========  ============================
Class                 Support   PDO        Dependency
===================== ========= =========  ============================
Connection_MyVendor   Full      myvendor:  http://github/test/myvendor
===================== ========= =========  ============================

See :ref:`new_vendor` for more details on how to add support for your driver.
