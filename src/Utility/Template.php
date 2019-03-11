<?php
/** "Simple PHP template" implementation
*
* Idea by {@see http://chadminick.com/articles/simple-php-template-engine.html Chad Minick }
* Interface inspired by {@see https://www.smarty.net/ Smarty }.
*
* @version SVN: $Id: Template.php 999 2019-03-09 21:11:29Z anrdaemon $
*/

namespace AnrDaemon\Utility;

class Template
{
  private $templateDir = null;
  private $template = null;
  private $params = [];

  private function protect($_template)
  {
    $store = [];

    foreach($GLOBALS as $k => $v)
    {
      if($k === "GLOBALS")
        continue;

      $store[$k] = $v;
    }

    try
    {
      return $this->wrap($_template);
    }
    finally
    {
      foreach($store as $k => $v)
      {
        $GLOBALS[$k] = $v;
      }
    }
  }

  private function wrap($_template)
  {
    $caller = function(array $_vars)
    use($_template)
    {
      extract($_vars, EXTR_SKIP);
      $_vars = isset($_vars["_vars"]) ? $_vars["_vars"] : null;

      include $_template;
    };

    $caller = $caller->bindTo(null, null);

    try
    {
      set_error_handler(
        function($s, $m, $f, $l, $c = null)
        {
          throw new \ErrorException($m, 0, $s, $f, $l);
        },
        ~(E_NOTICE | E_STRICT)
      );

      ob_start();
      $caller($this->params);
    }
    finally
    {
      restore_error_handler();
      $result = ob_get_clean();
    }

    return $result;
  }

  public function assign($vars, $value = null)
  {
    if(is_scalar($vars))
    {
      $vars = [$vars => $value];
    }

    if(!(is_array($vars) || is_object($vars) && $vars instanceof \Traversable))
    {
      throw new \BadMethodCallException("Dataset is not \\Traversable.");
    }

    foreach($vars as $name => $value)
    {
      $this->params[$name] = $value;
    }

    return $this;
  }

  public function createTemplate($_template)
  {
    $self = clone $this;
    $self->template = $_template;

    return $self;
  }

  public function getTemplateDir()
  {
    return $this->templateDir;
  }

  public function getTemplateVars($name = null)
  {
    if(!isset($name))
      return $this->params;

    return $this->params[$name];
  }

  public function isCached()
  {
    return false;
  }

  public function display($_template = null)
  {
    print $this->fetch($_template);
  }

  public function fetch($_template = null)
  {
    if(!isset($_template))
    {
      $_template = $this->template;
    }

    if(!strlen($_template))
    {
      throw new \InvalidArgumentException("No template file specified.");
    }

    if(($_template[0] === "/" || $_template[0] === "\\") && !strlen($this->templateDir))
    {
      $_template = "/{$_template}";
    }

    return $this->protect("{$this->templateDir}/{$_template}");
  }

  public function setTemplateDir($path)
  {
    if(empty($path) || !is_dir($path))
    {
      throw new \UnexpectedValueException("Templates path must be an existing directory.");
    }

    $this->templateDir = realpath($path);

    return $this;
  }

// Magic!

  public function __construct() {}

  public function __get($name)
  {
    return $this->getTemplateVars($name);
  }

  public function __set($name, $value)
  {
    $this->assign($name, $value);
  }

  public function __isset($name)
  {
    return isset($this->params[$name]);
  }
}
