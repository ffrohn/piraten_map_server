<?php
/**
 * Singleton containing system whide important information.
 */
class System
{
  /**
   * @var bool
   */
  private static $initiated = false;

  /**
   * @var array
   */
  private static $configuration = array();
  
  /**
   * @var mysqli
   */
  private static $db = null;

  private static $lastRequest = null;

  public static function init()
  {
    if (self::$initiated) {
      return;
    }

    spl_autoload_register('System::autoload');
    setlocale(LC_ALL, 'de_DE.UTF-8');
    self::readConfiguration();
    self::initDatabase();
    self::$initiated = true;
  }

  public static function autoload($classname)
  {
    $path = dirname(__FILE__) . '/' . str_replace('_', '/', $classname) . '.php';
    if (!file_exists($path)) {
      throw new Exception('Class ' . $classname . ' not Found in ' . $path);
    }

    include_once($path);
  }

  private function __construct()
  {
  }

  private static function readConfiguration()
  {
    include 'settings.php';

    self::$configuration = get_defined_vars();
  }

  private static function initDatabase()
  {
    self::$db = new mysqli(self::getConfig('mysql_server'), self::getConfig('mysql_user'), self::getConfig('mysql_password'), self::getConfig('mysql_database'));
    self::$db->query('SET NAMES utf8');
  }
  
  public static function getConfig($varname)
  {
    return self::$configuration[$varname];
  }

  /**
   * Executes a query and delivers the statement object
   * 
   * @param string $query
   * @param mixed $arguments
   * @return mysqli_stmt
   */
  public static function query($query, $arguments = array())
  {
    $type = '';
    if (stripos($query, 'insert') === 0) {
      $type = 'insert';
    } else if (stripos($query, 'update') === 0 || stripos($query, 'delete') === 0) {
      $type = 'modify';
    }
    $query = self::prepareQuery($query, (array) $arguments);

    self::$lastRequest = $query;

    $result = self::$db->query($query);

    if (!$result) {
      throw new Exception(self::$db->error);
    } else if ($result === true) {
      if ($type == 'insert') {
        return self::$db->insert_id;
      } else if ($type == 'modify') {
        return self::$db->affected_rows;
      }
    }

    return $result;
  }

  public static function getLastRequest()
  {
    return self::$lastRequest;
  }

  public static function getDb()
  {
    return self::$db;
  }

  private static function prepareQuery($query,array $data_arr)
  {
    preg_match_all("/[\?]/",$query,$matches,PREG_OFFSET_CAPTURE);

    if (sizeof($matches[0])!=sizeof($data_arr))
    {
      throw new Exception('Query arguments missmatch');
    }

    $offset=0;
    foreach (array_values($data_arr) as $index => $value) {
      $pos  = $matches[0][$index][1];

      if (is_array($value)) {
        $replace="";
        if (sizeof($value)>0) {
          foreach ($value as $data) {
            $replace.=self::escape($data).",";
          }
          $replace=substr($replace,0,-1);
        }
      } else {
        $replace=self::escape($value);
      }

      $query   = substr_replace($query,$replace,$pos + $offset, 1);
      $offset += strlen($replace)-1;
    }
    return $query;
  }

  private static function escape($value)
  {
    if (is_string($value)) {
      $replace="'". self::$db->real_escape_string($value)."'";
    }
    elseif (is_int($value) || is_float($value))
      $replace=$value;
    elseif (is_bool($value))
      $replace=(int) $value;
    elseif (is_null($value))
      $replace="NULL";
    else
      $replace=self::$db->real_escape_string((string) $value);
    return $replace;
  }
}
