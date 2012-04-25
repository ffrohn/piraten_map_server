<?php
/**
 * Singleton containing system whide important information.
 */
class System
{
  /**
   * @var System
   */
  private static $instance = null;

  private $configuration = array();
  
  /**
   * @var mysqli
   */
  private $db = null;

  public static function getInstance()
  {
    if (!$instance) {
      self::$instance = new System();
    }

    return self::$instance;
  }

  public static function init()
  {
    return self::getInstance();
  }

  public static function __callStatic($method, $arguments)
  {
    if (!is_callable(array(self::getInstance(), $method))) {
      throw new Exception('Method ' . $method . ' does not exist.');
    }

    return call_user_func(array(self::getInstance(), $method), $arguments);
  }

  public static function autoload($classname)
  {
    $path = dirname(__FILE__);
    $path .= str_replace('_', '/', $classname) . '.php';
    if (!file_exists($path)) {
      throw new Exception('Class ' . $classname . ' not Found');
    }

    include_once($path);
  }

  private function __construct()
  {
    spl_autoload_register('System::autoload');
    $this->readConfiguration();
    $this->initDatabase();
  }

  private function readConfiguration()
  {
    include 'settings.php';

    $this->configuration = get_defined_vars();
  }

  private function initDatabase()
  {
    $this->db = new mysqli($this->getConfig('mysql_server'), $this->getConfig('mysql_user'), $this->getConfig('mysql_password'), $this->getConfig('mysql_database'));
  }
  
  public function getConfig($varname)
  {
    return $this->configuration[$varname];
  }

  public function query($query, $types = '', $arguments = null)
  {
    $statement = $this->db->prepare($query);
    if ($sequence && $arguments) {
      call_user_func_array(array($statement, 'bind_param'), array_merge(array($types), (array) $arguments));
    }
    if (!$statement->execute()) {
      throw new Exception('Query failed');
    }

    return $statement->get_result();
  }

  public function getDb()
  {
    return $this->db;
  }
}
