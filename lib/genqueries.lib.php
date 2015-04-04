<?

abstract class GenQueries {


  
  public $lang;
  public $db;


  
  function __construct() {
    global $db;
    $this->db = $db;
  }
  

  
  protected function GetLang($field) {
    $this->lang = array();
    $query = "select ".$field." from LIST_1 order by NAME";
    $this->lang = $this->db->simple_get($query, 0, 1, 'BIBLIOJET');
    return true;
  }


  
  protected function GetTranslit($string) {
    for ($i=0; $i < mb_strlen($string); $i++) {
      $chars[] = mb_substr($string, $i, 1);
    }
    if (!my_sizeof($chars)) {
      return false;
    }
    $new_string = '';
    foreach ($chars as $value) {
     if ($value == ' ') {
      $new_string .= $value;
     } else {
      $arr_tmp = array();
      $query = "select LOWER(PODSTAN) as PODSTANL from TRANSLIT where UNI = N'".$value."'";
      $arr_tmp = $this->db->simple_get($query, 1, 0, 'OPAC');
      if (sizeof($arr_tmp)) {
        $new_string .= $arr_tmp['PODSTANL'];
      } else {
        $new_string .= $value;
      }
     }
    }
    if ($new_string == $string) {
      return false;
    } else {
      return $new_string;
    }
  }



  protected function GetField($field) {
    global $DATA_FIELDS;
    $query = "select MNFIELD, MSFIELD from FIELDS where HEADER = '".$DATA_FIELDS[$field]."' and MSFIELD != '-1'";
    return $this->db->simple_get($query, 1, 0, 'BIBLIOJET');
  }



}

?>