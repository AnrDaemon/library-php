<?php

namespace AnrDaemon\Utility;

class Path
{
  /** Creates fixed pathinfo structure
  *
  * Meaning, "$dirname/$filename$extension" eq. rtrim($path, "\\/").
  *
  * Contrary to the {@see \pathinfo() pathinfo()}, all members of the structure always set.
  *
  * @param string $path The path to get info on.
  * @return array Fixed pathinfo structure.
  */
  public static function info($path)
  {
    $p = pathinfo($path);
    if(empty($p["filename"]))
    {
      $p["filename"] = $p["basename"];
      unset($p["extension"]);
    }
    if(!empty($p["extension"]))
    {
      $p["extension"] = ".{$p["extension"]}";
    }

    return $p + ["extension" => ""];
  }

  /** Path normalizer part examinator.
  * @internal
  * @throws \UnexpectedValueException if relative path is trying to escape above current directory, unless explicitly allowed.
  */
  protected static function examine($part, array &$array, $path_relative, $allow_escape = false)
  {
    if($part === '.')
    {
      return;
    }

    if($part !== '..')
    {
      $array[] = $part;
      return;
    }

    // $part == '..', handle escaping.
    $last = end($array);
    if($last === '..')
    { // Escaping is allowed and we're already on the run.
      $array[] = $part;
      return;
    }

    if($last !== false)
    { // $last element exists - move up the stack.
      array_pop($array);
      return;
    }

    if(!$path_relative)
    { // Path is not relative - skip updir.
      return;
    }

    if(!$allow_escape)
      throw new \UnexpectedValueException('Attempt to traverse outside the root directory.');

    $array[] = $part;
  }

  /** Normalize path string, removing '.'/'..'/empty components.
  *
  * Warning: This function is NOT intended to handle URL's ("//host/path")!
  * Please use {@see \parse_url() parse_url()} first.
  *
  * @param string $path The path to normalize.
  * @param bool $allow_escape Is the path relative? Defaults to autodetect. Paths declared explicitly relative get slightly different treatment.
  * @param string $directory_separator Output directory separator. Defaults to DIRECTORY_SEPARATOR.
  * @return string The normalized string.
  * @throws \UnexpectedValueException if relative path is trying to escape above current directory, unless explicitly allowed.
  */
  public static function normalize($path, $allow_escape = false, $directory_separator = DIRECTORY_SEPARATOR)
  {
    $path = (string)$path;
    if($path === '')
      return $path;

    $disk = null;
    $path_relative = false;

    // If path is not explicitly relative, test if it's an absolute and possibly Windows path
    // Convert first byte to uppercase.
    $char = ord($path[0]) & 0xdf;
    if($char & 0x80)
    { // Multibyte character - path is relative
      $path_relative = true;
    }
    // Windows disk prefix "{A..Z}:"
    elseif(strlen($path) > 1 && $char > 0x40 && $char < 0x5b && $path[1] === ':')
    {
      if(strlen($path) === 2)
        return $path;

      $disk = substr($path, 0, 2);
      $path = substr($path, 2);
    }

    if($path[0] !== "/" && $path[0] !== "\\")
    { // First byte is not a slash
      $path_relative = true;
    }

    $ta = [];
    $part = strtok($path, "/\\");
    while(false !== $part)
    {
      static::examine($part, $ta, $path_relative, $allow_escape);
      $part = strtok("/\\");
    }

    return $disk . ($path_relative ? '' : $directory_separator) . join($directory_separator, $ta);
  }
}
