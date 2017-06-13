------------------------------------------------------------------------
r658 | anrdaemon | 2017-06-13 18:05:30 +0300 (Вт, 13 июн 2017) | 3 lines

* Force convert all errors to exceptions for connection phase.
  PDO will attach them as PDOException::previous.

------------------------------------------------------------------------
