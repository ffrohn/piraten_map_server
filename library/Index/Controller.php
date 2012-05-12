<?php
class Index_Controller extends Controller
{
  public function index()
  {
    if ($this->isMobileDevice()) {
      $this->view = 'Index_View_Mobile';
    } else {
      $this->view = 'Index_View_Desktop';
    }

    $this->display();
  }

  public function getInitialPosition()
  {
    $result = array('lat'   => $_SESSION['deflat'] ? $_SESSION['deflat'] : System::getConfig('start_lat'),
                    'lon'   => $_SESSION['deflon'] ? $_SESSION['deflon'] : System::getConfig('start_lon'),
                    'zoom'  => $_SESSION['defzoom'] ? $_SESSION['defzoom'] : System::getConfig('start_zoom'));
    foreach (array_keys($result) as $key)
    {
      if ($this->getGetParameter($key, FILTER_SANITIZE_NUMBER_FLOAT)) {
        $result[$key] = $this->getGetParameter($key, FILTER_SANITIZE_NUMBER_FLOAT);
      }
    }
    return $result;
  }

  public function getPosterFlags()
  {
    GLOBAL $options;
    return $options;
  }
  
  protected function sendHeaders()
  {
    header('content-type: text/html; charset=utf-8');
  }

  private function isMobileDevice()
  {
    return (bool) strpos($_SERVER['HTTP_USER_AGENT'],"iPhone") ||
                  strpos($_SERVER['HTTP_USER_AGENT'],"Android") ||
                  strpos($_SERVER['HTTP_USER_AGENT'],"iPod") ||
                  strpos($_SERVER['HTTP_USER_AGENT'],"iPad") ||
                  strpos($_SERVER['HTTP_USER_AGENT'],"webOS");
  }
}
