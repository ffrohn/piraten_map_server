<?php
class Login_Controller extends Controller
{
  public function index()
  {
    //currently redirect to login, but in future, this should display the login dialogue
    return $this->login();
  }

  public function logout()
  {
      global $snoopy, $apiPath, $_SESSION;
      if ($_SESSION['wikisession']) {
          $snoopy->cookies = $_SESSION['wikisession'];
          
          $request_vars = array('action' => 'logout', 'format' => 'php');
          if (!$snoopy->submit($apiPath, $request_vars))
              die("Snoopy error: {$snoopy->error}");
      }
      $loginok = 0;
      unset($_SESSION['siduser']);
      unset($_SESSION['wikisession']);
      unset($_SESSION['sidip']);
    $this->displayMessage("Logout OK", true);
  }
  
  public function login()
  {
    global $apiPath, $snoopy, $_SESSION;
    $username = $this->getPostParameter('username');
    $password = $this->getPostParameter('password');
    $mail     = $this->getPostParameter('mail');
    if (!$username || !$password) {
      throw new UserException('Please provide Username and Password to login.');
    }

    $search = Data_User::find('username', $username);
    if ($search->num_rows == 0) {
      return $this->createAccount($username, $password, $mail);
    }

    $user = Data_User::login($username, $password);

    $_SESSION['siduserid'] = $user->getId();
    $_SESSION['siduser'] = $user->getUsername();
    $_SESSION['user'] = serialize($user);
    $_SESSION['sidip'] = $_SERVER["REMOTE_ADDR"];
      
    // Try to get the users location...
    if ($_SESSION['siduser']) {
      $request_vars = array('action' => 'query', 'prop' => 'categories', 'titles' => 'Benutzer:' . $_SESSION['siduser'], 'format' => 'php');
      if ($snoopy->submit($apiPath, $request_vars)) {
        $array = unserialize($snoopy->results);
        $categories = array('Germany');
        if (($array) && ($array['query']) && ($array['query']['pages'])) {
          $pages = $array['query']['pages'];
          reset($pages);
          while (list($key, $val) = each($pages)) {
            if (($val) && ($val['categories'])) {
              $cats = $val['categories'];
              reset($cats);
              while (list($k, $cat) = each($cats)) {
                if (($cat) && ($cat['title']))
                  $categories[] = $cat['title'];
              }
            }
          }
        }
        $query = "SELECT lat, lon,zoom FROM " . System::getConfig('tbl_prefix') . "regions WHERE category in (?" . str_repeat(', ?', count($categories) - 1) . ") order by zoom desc limit 1";
        $res = System::query($query, $categories);
        if ($res->num_rows == 1) {
          $entry = $res->fetch_assoc();
          $_SESSION['deflat'] = $entry['lat'];
          $_SESSION['deflon'] = $entry['lon'];
          $_SESSION['defzoom'] = $entry['zoom'];
        }
      }
    }
    $this->displayMessage("Login OK", true);
  }
  
  public function createAccount($username, $password, $mail)
  {
    if (!strstr($mail, '@piraten')) return $this->displayMessage("eMail-Addresse muss @piraten enthalten");
    $res = Data_User::find('username', $username);
    if ($res->num_rows > 0) return $this->displayMessage("Username already exists");

    $user = new Data_User();
    $user->setUsername($username);
    $user->setPassword($password);
    $user->save();
    $header = 'From: noreply@piratenpartei-aachen.de';
    if (mail($mail, "Piraten Karte Account", "Hallo, um die Registrierung auf http://pk.piratenpartei-nrw.de abzuschliessen, klicke bitte auf den Link unten. Bei Fragen bitte zuerst im Wiki schauen: http://wiki.piratenpartei.de/Plakatekarte_NRW. \r\n".
      $_SERVER["SERVER_NAME"].$_SERVER['PHP_SELF']."?action=activate&hash=".$user->getHash()."&username=".$user->getUsername(), $header)) {
      return $this->displayMessage("Account created");
    }

    return $this->displayMessage("Delivering mail failed");
  }
  
  public function activate(){
    if (Data_User::activate($this->getGetParameter('username'), $this->getGetParameter('hash'))) {
      $this->displayMessage("Account activated");
    }
  }

  private function displayMessage($msg, $success = false)
  {
    if (!System::getConfig('use_controllers')) {
      header('Location: /?message=' . urlencode($msg));
      die;
    }
    print json_encode(array('message' => $msg, 'success' => $success));
  }
}
