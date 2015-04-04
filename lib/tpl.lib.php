<?

// the class forms all arrays needed for templates

class Tpl extends GenQueries {

  public $diacr;
  public $vk;
  public $condlist;
  public $result;
  public $words;
  
  function __construct($mode, $id=false) {
    global $post_obj;
    parent::__construct();
    switch ($mode) {
      case 'cond':
        $this->GetCondList($_REQUEST['field_select'], $_REQUEST['s_string']);
      break;
      case 'vk':
        $this->GetVK();
      break;
      case 'result':
        $this->GetResult($post_obj);
        $this->words = $post_obj->words;
      case 'main':
        $this->GetDiacr();
        $this->GetLang('NAME');
      break;
      case 'full_res':
        if ($id) {
          $this->GetResult(null, $id);
        } else {
          die;
        }
      break;
    }
  }

  private function GetDiacr() {
    $this->diacr = array();
    $query = "select NAZV from NAZV_REG_TAB order by NAZV";
    $this->diacr = $this->db->simple_get($query, 0, 1, 'OPAC');
    return true;
  }

  private function GetVK() {
    if ($_REQUEST['vk_select'] === false) {
      return false;
    }
    $this->vk = array();
    $arr_tmp = array();
    $query = "select NAZV, IM_TABL, USLOVIE from NAZV_REG_TAB order by NAZV";
    $arr_tmp = $this->db->simple_get($query, 0, 0, 'OPAC');
    $query = "select UNI from ".$arr_tmp[$_REQUEST['vk_select']]['IM_TABL']." where ".$arr_tmp[$_REQUEST['vk_select']]['USLOVIE']." order by c1";
    $this->vk = $this->db->simple_get($query, 0, 1, 'OPAC');
    return true;
  }

  private function GetCondList($field, $val) {
    $val = mb_strtolower($val);
    switch ($field) {
      case 'author':
        $query = "select distinct PLAIN from AFNAMESVAR where LOWER(PLAIN) like '".$val."%' order by PLAIN";
        $this->condlist = $this->db->simple_get($query, 0, 1, 'BIBLIOJET');
      break;
      case 'org':
        $query = "select distinct PLAIN from AFORGSVAR where LOWER(PLAIN) like '".$val."%' order by PLAIN";
        $this->condlist = $this->db->simple_get($query, 0, 1, 'BIBLIOJET');
      break;
      case 'UDK':
      case 'theme':
      case 'type':
      case 'place':
        $indexes = array();
        $indexes = $this->GetField($field);
        $query = "select distinct DATAEXTPLAIN.PLAIN from DATAEXTPLAIN inner join DATAEXT on DATAEXTPLAIN.IDDATAEXT = DATAEXT.ID where DATAEXT.MNFIELD = '".$indexes['MNFIELD']."' and DATAEXT.MSFIELD='".$indexes['MSFIELD']."' and LOWER(DATAEXTPLAIN.PLAIN) like '".$val."%' order by DATAEXTPLAIN.PLAIN";
        $this->condlist = $this->db->simple_get($query, 0, 1, 'BIBLIOJET');
      break;
      case 'level':
        $query = "select distinct DATAEXTPLAIN.PLAIN from DATAEXTPLAIN inner join MAIN on MAIN.ID = DATAEXTPLAIN.IDMAIN inner join DATAEXT on DATAEXTPLAIN.IDDATAEXT = DATAEXT.ID where DATAEXT.MNFIELD = 200 and DATAEXT.MSFIELD = '\$a' and MAIN.IDLEVEL < 0 and LOWER(DATAEXTPLAIN.PLAIN) like '".$val."%' order by DATAEXTPLAIN.PLAIN";
        $this->condlist = $this->db->simple_get($query, 0, 1, 'BIBLIOJET');
      break;
    }
    return true;
  }

  private function GetResult($post_obj=false, $id=0) {
    global $rus, $DATA_FIELDS;
    if (!$id && !my_sizeof($post_obj->ids_main)) {
      return false;
    }
    $query = "select DATAEXT.IDMAIN, DATAEXTPLAIN.PLAIN, DATAEXT.SORT, FIELDS.NAME 
              from DATAEXTPLAIN inner join DATAEXT on DATAEXTPLAIN.IDDATAEXT = DATAEXT.ID inner join FIELDS on 
              (DATAEXT.MNFIELD = FIELDS.MNFIELD and DATAEXT.MSFIELD = FIELDS.MSFIELD)";
    if (!$id) {
      $ids_main = join(',', $post_obj->ids_main);
      $query .= " where DATAEXT.IDMAIN in (".$ids_main.")
                 order by DATAEXT.SORT";
    } else {
      $query .= " where DATAEXT.IDMAIN = ".$id;
    }
    $result_tmp = $this->db->simple_get($query, 0, 0, 'BIBLIOJET');
    if (!my_sizeof($result_tmp)) {
      return false;
    }
    $level = 0;
    foreach ($result_tmp as $value) {
      if ($value['NAME'] == $DATA_FIELDS['level'] && (int)$value['PLAIN'] > 0) {
        $query = "select DATAEXTPLAIN.PLAIN from DATAEXTPLAIN inner join DATAEXT on DATAEXT.ID=DATAEXTPLAIN.IDDATAEXT where DATAEXT.IDMAIN=".$value['PLAIN']." and DATAEXT.MNFIELD=200 and DATAEXT.MSFIELD='\$a'";
        $level_tmp = $this->db->simple_get($query, 1, 1, 'BIBLIOJET');
        $level = $level_tmp[0];
        $this->result[$value['IDMAIN']][] = array('field' => $value['NAME'], 'value' => $level);
      } else {
        $this->result[$value['IDMAIN']][] = array(
                                                'field' => $value['NAME'],
                                                'value' => $value['PLAIN'],
                                               );
      }
    }
    if ($id && !$level) {
      $query = "select DATAEXT.IDMAIN, DATAEXTPLAIN.PLAIN from DATAEXTPLAIN inner join MAIN on MAIN.ID = DATAEXTPLAIN.IDMAIN inner join DATAEXT on DATAEXTPLAIN.IDDATAEXT = DATAEXT.ID where DATAEXT.MNFIELD = 200 and DATAEXT.MSFIELD = '\$a' and MAIN.IDLEVEL < 0 and MAIN.ID = ".$id;
      unset($result_tmp);
      $result_tmp = $this->db->simple_get($query, 1, 0, 'BIBLIOJET');
      if (my_sizeof($result_tmp)) {
        foreach ($this->result[$result_tmp['IDMAIN']] as $key => $value) {
          if ($value['field'] == $rus['level_head']) {
            $this->result[$result_tmp['IDMAIN']][$key]['value'] = $result_tmp['PLAIN'];
          }
        }
      }
    }
    if (!$id) {
      $this->result = $this->ResultTrans($this->result);
    }
    return true;
  }



  private function ResultTrans($arr) {
    global $DATA_FIELDS;
    $new_arr = array();
    foreach ($arr as $key => $value) {
      $new_arr[$key] = array();
      foreach ($value as $key2 => $value2) {
        $arr_search = array_search($value2['field'], $DATA_FIELDS);
        if ($arr_search !== false)  {
          if (isset($new_arr[$key][$arr_search])) {
            $new_arr[$key][$arr_search] .= ', '.$value2['value'];
          } else {
            $new_arr[$key][$arr_search] = $value2['value'];
          }
        }
      }
    }
    return $new_arr;
  }



  public function ChangeCase($number, $str) {
    $root_str = mb_substr($str, 0, -1);
    if ($number % 10 == 1 && $number % 100 != 11) {
      $root_str .= iconv('cp1251', 'utf-8', 'ь');
    } elseif (($number % 10 == 2 || $number % 10 == 3 || $number % 10 == 4) && ($number %100 != 12 && $number % 100 != 13 && $number % 100 != 14)) {
      $root_str .= iconv('cp1251', 'utf-8', 'и');
    } else {
      $root_str .= iconv('cp1251', 'utf-8', 'ей');
    }
    return $root_str;
  }

}

?>