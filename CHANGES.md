------------------------------------------------------------------------
r602 | anrdaemon | 2016-07-20 04:07:22 +0300 (Ср, 20 июл 2016) | 12 lines

# Misc\Coordinate3D
* Class renamed to a shorter (and more sensible) name.
* Import used class names into local space.
- Removed bogus ::fromGPS() fabric. It IS a "GPS" coordinate class, already...
  What more?
- Renamed ::fromVector() fabric to ::fromPolar() as a more appropriate name.
+ Improved ::format() to allow both raw printing and custom formatting.
- Made ::__set() to not throw exception on success.
- Overall make methods more conformant to future language improvements.
+ ::__debugInfo();
+ Simplified ArrayAccess assignment.
+ Some comments.

------------------------------------------------------------------------
r601 | anrdaemon | 2016-07-20 02:48:26 +0300 (Ср, 20 июл 2016) | 2 lines

+ Import coordinate class from SoH lib.

------------------------------------------------------------------------
r533 | anrdaemon | 2016-07-19 23:05:21 +0300 (Вт, 19 июл 2016) | 2 lines

+ Fix classloader for good.

------------------------------------------------------------------------
