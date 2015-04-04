<?

// this class does primary parsing of form data and calls the "Query" class

class Post extends GenQueries {

  private $post;
  private $cond;
  private $supercond;
  private $query_obj;
  private $MAIN_TBL='DATAEXT';
  public $ids_main;
  public $words;
  

  
  function __construct() {
    parent::__construct();
    $this->post = $this->Check($_POST) or die('Bad query conditions');
    $this->post = $this->RemoveEmpty($this->post);
    $this->post = $this->DateFormat($this->post);
    $this->query_obj = new Query('DATAEXT', 'IDMAIN');
    $this->Refine();
    if ($this->post['search_type'] == 'simple') {
      $query = $this->query_obj->SetQuery('VALUE');
    } 
    if ($this->post['search_type'] == 'adv') {
      $query = $this->query_obj->SetQuery('PLAIN');
    }
//    die($query);
    if ($query) {
      $this->ids_main = $this->db->simple_get($query, 0, 1, 'BIBLIOJET');
    }
  }


  
  private function Check($post) {
    if (!my_sizeof($post)) {
      return false;
    }
    if (!$post['do_search']) {
      return false;
    }
    if (!$post['search_type']) {
      return false;
    }
    if (!$post['from_year'] && !$post['upto_year'] && !my_sizeof($post['s_string'])) {
      return false;
    }
    if (!isset($post['lang_select'])) {
      return false;
    }
    return $post;
  }
  

  
  private function RemoveEmpty($new_post) {
    global $rus;
    if (!is_array($new_post)) {
      return false;
    }
    if (sizeof($new_post)) {
      foreach ($new_post as $key => $value) {
        if ($value == '-1') {
          unset($new_post[$key]);
        }
      }
    }
    if (my_sizeof($new_post['field_select']) && $new_post['search_type'] == 'adv') {
      foreach ($new_post['field_select'] as $key => $value) {
        if ($value == '-1' || !strlen($new_post['s_string'][$key]) || $new_post['s_string'][$key] == $rus['what']) {
          unset($new_post['field_select'][$key]);
          unset($new_post['s_string'][$key]);
          unset($new_post['cond_select'][$key]);
          unset($new_post['logic'][$key-1]);
        }
      }
      $invalid = 0;
      for ($i=0; $i<NUM_FIELDS; $i++) {
        if ($invalid) {
          unset($new_post['field_select'][$i]);
          unset($new_post['s_string'][$i]);
          unset($new_post['cond_select'][$i]);
          unset($new_post['logic'][$i-1]);
        }
        if (!$new_post['field_select'][$i]) {
          $invalid = 1;
        }
      }
    }
    if ($new_post['from_year'] == $rus['period_year']) {
      unset($new_post['from_year']);
    }
    if ($new_post['upto_year'] == $rus['period_year']) {
      unset($new_post['upto_year']);
    }
    if ($new_post['from_month'] && !$new_post['from_year']) {
      unset($new_post['from_month']);
    }
    if ($new_post['upto_month'] && !$new_post['upto_year']) {
      unset($new_post['upto_month']);
    }
    if (strlen($new_post['from_year']) != 4) {
      unset($new_post['from_year']);
    }
    if (strlen($new_post['upto_year']) != 4) {
      unset($new_post['upto_year']);
    }
    return $new_post;
  }


  
  private function DateFormat($new_post) {
    if ($new_post['from_month'] && $new_post['from_month'] < 10) {
      $new_post['from_month'] = '0'.$new_post['from_month'];
    }
    if ($new_post['upto_month'] && $new_post['upto_month'] < 10) {
      $new_post['upto_month'] = '0'.$new_post['upto_month'];
    }
    if ($new_post['from_year']) {
      if ($new_post['from_month']) {
        $new_post['date_from'] = $new_post['from_year'].$new_post['from_month'].'01 00:00:00';
      } else {
        $new_post['date_from'] = $new_post['from_year'].'0101 00:00:00';
      }
    }
    if ($new_post['upto_year']) {
      if ($new_post['upto_month']) {
        $new_post['date_upto'] = $new_post['upto_year'].$new_post['upto_month'].cal_days_in_month(CAL_GREGORIAN, $new_post['upto_month'], $new_post['upto_year']).' 23:59:59';
      } else {
        $new_post['date_upto'] = $new_post['upto_year'].'1231 23:59:59';
      }
    }
    return $new_post;
  }
  
  
  
  private function RemoveArticles($str) {
    $words = explode(' ', $str);
    $query = "select SLOVO from STOP_SLOV order by SLOVO";
    $articles = $this->db->simple_get($query, 0, 1, 'OPAC');
    if (my_sizeof($articles)) {
      foreach ($articles as $value) {
        foreach ($words as $key2 => $value2) {
          if ($value == $value2) {
            unset($words[$key2]);
          }
        }
      }
    }
    $str = join(' ', $words);
    return $str;
  }



  private function Refine() {
    $this->GetLang('SHORTNAME');

    if ($this->post['search_type'] == 'simple') {
      if (!$this->post['s_string'][0]) {
        return false;
      }
      $search_str = mb_strtolower($this->post['s_string'][0]);
//      $search_str = mb_ereg_replace('[[:punct:]]', '', $search_str);
      $search_str = mb_str_ireplace('\'', '\'\'', $search_str);
      $search_str = $this->RemoveArticles($search_str);
      $search_trans = $this->GetTranslit($search_str);
      $words = explode(' ', $search_str);
      $this->words = $words;
      if ($search_trans) {
        $words_trans = explode(' ', $search_trans);
      }
      $str_sql = "IDWORDS in (select ID from UNIWORDS_1 where ";
      $i = 0;
      foreach ($words as $key => $value) {
        $i++;
        $str_sql .= "(LOWER(VALUE) = N'".$value."'";
        if ($search_trans && $words_trans[$key] != $value) {
          $str_sql .= " or LOWER(VALUE) = N'".$words_trans[$key]."')";
        } else {
          $str_sql .= ")";
        }
        if ($i < sizeof($words)) {
          $str_sql .= " or ";
        }
      }
      $str_sql .= ")";
      $this->query_obj->AddCondition($str_sql, 'UNIWORDSEXT_1');
      $this->query_obj->AddLogic('OR');

      $str_sql = "IDWORDS in (select ID from UNIWORDS_AZ where ";
      $i = 0;
      foreach ($words as $key => $value) {
        $i++;
        $str_sql .= "(LOWER(VALUE) = N'".$value."'";
        if ($search_trans && $words_trans[$key] != $value) {
          $str_sql .= " or LOWER(VALUE) = N'".$words_trans[$key]."')";
        } else {
          $str_sql .= ")";
        }
        if ($i < sizeof($words)) {
          $str_sql .= " or ";
        }
      }
      $str_sql .= ")";
      $this->query_obj->AddCondition($str_sql, 'UNIWORDSEXT_AZ');
      $this->query_obj->AddLogic('OR');

      $str_sql = "IDWORDS in (select ID from UNIWORDS_RU where ";
      $i = 0;
      foreach ($words as $key => $value) {
        $i++;
        $str_sql .= "(LOWER(VALUE) = N'".$value."'";
        if ($search_trans && $words_trans[$key] != $value) {
          $str_sql .= " or LOWER(VALUE) = N'".$words_trans[$key]."')";
        } else {
          $str_sql .= ")";
        }
        if ($i < sizeof($words)) {
          $str_sql .= " or ";
        }
      }
      $str_sql .= ")";
      $this->query_obj->AddCondition($str_sql, 'UNIWORDSEXT_RU');

      if (isset($this->post['lang_select']) && $this->post['lang_select'] != 'all') {
        $this->query_obj->AddSuperCondition("IDMAIN in (select IDMAIN from DATAEXTPLAIN where PLAIN = N'".$this->lang[$this->post['lang_select']]."')");
//"
        $this->query_obj->AddSuperCondition("IDMAIN in (select IDMAIN from DATAEXT where MNFIELD=101 or (MNFIELD=200 and MSFIELD='\$z'))");
      }
      if ($this->post['subsearch'] && $this->post['sub_ids']) {
        $this->query_obj->AddSuperCondition("IDMAIN in (".$this->post['sub_ids'].")");
      }
    }

    if ($this->post['search_type'] == 'adv') {
      if (!$this->post['date_from'] && !$this->post['date_upto'] && !my_sizeof($this->post['field_select'])) {
        return false;
      }
      foreach ($this->post['field_select'] as $key => $value) {
        if ($key) {
          if ($this->post['logic'][$key-1]) {
            $this->query_obj->AddLogic($this->post['logic'][$key-1]);
          } else {
            $this->query_obj->AddLogic(DEF_LOGIC);
          }
        }
        $this->post['s_string'][$key] = trim($this->post['s_string'][$key]);
        $search_str = mb_strtolower($this->post['s_string'][$key]);
/*        if ($value != 'level' && $value != 'place') {
          $search_str = mb_ereg_replace('[[:punct:]]', '', $search_str);
        }*/
        $search_str = mb_str_ireplace('\'', '\'\'', $search_str);
        if ($this->post['cond_select'][$key] != 'strict') {
          $search_str = $this->RemoveArticles($search_str);
        }
        $search_trans = $this->GetTranslit($search_str);
        switch ($value) {
          case 'level':
            $words = explode(' ', $search_str);
            if ($search_trans) {
              $words_trans = explode(' ', $search_trans);
            }
            $str_sql = "IDMAIN in (select MAIN.ID from MAIN inner join DATAEXT on MAIN.ID = DATAEXT.IDMAIN inner join DATAEXTPLAIN on DATAEXTPLAIN.IDDATAEXT = DATAEXT.ID where MAIN.IDLEVEL < 0 and DATAEXT.MNFIELD = 200 and DATAEXT.MSFIELD = '\$a' and ";
            if (sizeof($words) == 1) {
              $str_sql .= "(LOWER(DATAEXTPLAIN.PLAIN) = N'".$words[0]."'";
              if ($search_trans) {
                $str_sql .= " or LOWER(DATAEXTPLAIN.PLAIN) = N'".$words_trans[0]."')";
              } else {
                $str_sql .= ")";
              }
            } else {
            if ($this->post['cond_select'][$key] == 'all') {
              $i = 0;
              $str_sql .= "(";
              foreach ($words as $key2 => $value2) {
                $i++;
                $str_sql .= "(".$this->PatIndex($value2, "DATAEXTPLAIN.PLAIN");
                if ($search_trans && $words_trans[$key2] != $value2) {
                  $str_sql .= " or (".$this->PatIndex($words_trans[$key2], "DATAEXTPLAIN.PLAIN")."))";
                } else {
                  $str_sql .= ")";
                }
                if ($i < sizeof($words)) {
                  $str_sql .= " and ";
                }
              }
              $str_sql .= ")";
            } elseif ($this->post['cond_select'][$key] == 'any') {
              $i = 0;
              $str_sql .= "(";
              foreach ($words as $key2 => $value2) {
                $i++;
                $str_sql .= "(".$this->PatIndex($value2, "DATAEXTPLAIN.PLAIN");
                if ($search_trans && $words_trans[$key2] != $value2) {
                  $str_sql .= " or (".$this->PatIndex($words_trans[$key2], "DATAEXTPLAIN.PLAIN")."))";
                } else {
                  $str_sql .= ")";
                }
                if ($i < sizeof($words)) {
                  $str_sql .= " or ";
                }
              }
              $str_sql .= ")";
            } else {
              $str_sql .= "(".$this->PatIndex($search_str, "DATAEXTPLAIN.PLAIN");
              if ($search_trans) {
                $str_sql .= " or ".$this->PatIndex($search_trans, "DATAEXTPLAIN.PLAIN").")";
              } else {
                $str_sql .= ")";
              }
            }
            }
            $str_sql .= ")";
            $this->query_obj->AddCondition($str_sql);
          break;
          case 'theme':
          case 'type':
          case 'place':
            $field_ids = $this->GetField($value);
            $this->query_obj->AddCondition("IDMAIN in (select DATAEXT.IDMAIN from DATAEXT inner join DATAEXTPLAIN on DATAEXT.ID = DATAEXTPLAIN.IDDATAEXT where LOWER(DATAEXTPLAIN.PLAIN) = '".$search_str."' and DATAEXT.MNFIELD=".$field_ids['MNFIELD']." and DATAEXT.MSFIELD='".$field_ids['MSFIELD']."')");
          break;
          case 'UDK':
            $field_ids = $this->GetField($value);
            $this->query_obj->AddCondition("IDMAIN in (select DATAEXT.IDMAIN from DATAEXT inner join DATAEXTPLAIN on DATAEXT.ID = DATAEXTPLAIN.IDDATAEXT where LOWER(DATAEXTPLAIN.PLAIN) = '".mb_str_ireplace('\'', '\'\'', $this->post['s_string'][$key])."' and DATAEXT.MNFIELD=".$field_ids['MNFIELD']." and DATAEXT.MSFIELD='".$field_ids['MSFIELD']."')");
          break;
          case 'cat':
            $words = explode(' ', $search_str);
            $str_sql = "IDMAIN in (select DATAEXT.IDMAIN from DATAEXT inner join DATAEXTPLAIN on DATAEXT.ID = DATAEXTPLAIN.IDDATAEXT where ";
            if (sizeof($words) == 1) {
              $str_sql .= "(LOWER(DATAEXTPLAIN.PLAIN) = N'".$words[0]."'";
              if ($search_trans) {
                $str_sql .= " or LOWER(DATAEXTPLAIN.PLAIN) = N'".$words_trans[0]."')";
              } else {
                $str_sql .= ")";
              }
            } else {
            if ($this->post['cond_select'][$key] == 'all') {
              $i = 0;
              $str_sql .= "(";
              foreach ($words as $value2) {
                $i++;
                $str_sql .= $this->PatIndex($value2, "DATAEXTPLAIN.PLAIN");
                if ($i < sizeof($words)) {
                  $str_sql .= " and ";
                }
              }
              $str_sql .= ")";
            } elseif ($this->post['cond_select'][$key] == 'any') {
              $i = 0;
              $str_sql .= "(";
              foreach ($words as $value2) {
                $i++;
                $str_sql .= $this->PatIndex($value2, "DATAEXTPLAIN.PLAIN");
                if ($i < sizeof($words)) {
                  $str_sql .= " or ";
                }
              }
              $str_sql .= ")";
            } else {
              $str_sql .= $this->PatIndex($search_str, "DATAEXTPLAIN.PLAIN");
            }
            }
            $str_sql .= " and (DATAEXT.MNFIELD=600 or DATAEXT.MNFIELD=606))";
            $this->query_obj->AddCondition($str_sql);
          break;
          case 'header':
            $words = explode(' ', $search_str);
            if ($search_trans) {
              $words_trans = explode(' ', $search_trans);
            }
            $str_sql = "IDMAIN in (select DATAEXT.IDMAIN from DATAEXT inner join DATAEXTPLAIN on DATAEXT.ID = DATAEXTPLAIN.IDDATAEXT where ";
            if (sizeof($words) == 1) {
              $str_sql .= "(LOWER(DATAEXTPLAIN.PLAIN) = N'".$words[0]."'";
              if ($search_trans) {
                $str_sql .= " or LOWER(DATAEXTPLAIN.PLAIN) = N'".$words_trans[0]."')";
              } else {
                $str_sql .= ")";
              }
            } else {
            if ($this->post['cond_select'][$key] == 'all') {
              $i = 0;
              $str_sql .= "(";
              foreach ($words as $key2 => $value2) {
                $i++;
                $str_sql .= "(".$this->PatIndex($value2, "DATAEXTPLAIN.PLAIN");
                if ($search_trans && $words_trans[$key2] != $value2) {
                  $str_sql .= " or (".$this->PatIndex($words_trans[$key2], "DATAEXTPLAIN.PLAIN")."))";
                } else {
                  $str_sql .= ")";
                }
                if ($i < sizeof($words)) {
                  $str_sql .= " and ";
                }
              }
              $str_sql .= ")";
            } elseif ($this->post['cond_select'][$key] == 'any') {
              $i = 0;
              $str_sql .= "(";
              foreach ($words as $key2 => $value2) {
                $i++;
                $str_sql .= "(".$this->PatIndex($value2, "DATAEXTPLAIN.PLAIN");
                if ($search_trans && $words_trans[$key2] != $value2) {
                  $str_sql .= " or (".$this->PatIndex($words_trans[$key2], "DATAEXTPLAIN.PLAIN")."))";
                } else {
                  $str_sql .= ")";
                }
                if ($i < sizeof($words)) {
                  $str_sql .= " or ";
                }
              }
              $str_sql .= ")";
            } else {
              $str_sql .= "(".$this->PatIndex($search_str, "DATAEXTPLAIN.PLAIN");
              if ($search_trans) {
                $str_sql .= " or ".$this->PatIndex($search_trans, "DATAEXTPLAIN.PLAIN").")";
              } else {
                $str_sql .= ")";
              }
            }
            }
            $str_sql .= " and DATAEXT.MNFIELD=200 and DATAEXT.MSFIELD='\$a')";
            $this->query_obj->AddCondition($str_sql);
          break;
          case 'date':
            $field_ids = $this->GetField($value);
            if (mb_strstr($this->post['s_string'][$key], '-')) {
              $years = explode('-', $this->post['s_string'][$key]);
              $years[0] = trim($years[0]);
              $years[1] = trim($years[1]);
              if ($years[0] && (int)$years[0] == $years[0] && strlen($years[0]) == 4) {
                $str_cond = "DATAEXTPLAIN.PLAIN >= '".$years[0]."'";
                if ($years[1] && (int)$years[1] == $years[1] && strlen($years[1]) == 4) {
                  $str_cond .= " and DATAEXTPLAIN.PLAIN <= '".$years[1]."'";
                }
              } elseif ($years[1] && (int)$years[1] == $years[1] && strlen($years[1]) == 4) {
                $str_cond = "DATAEXTPLAIN.PLAIN <= '".$years[1]."'";
              }
              if ($str_cond) {
                $str_cond .= " and DATAEXT.MNFIELD=".$field_ids['MNFIELD']." and DATAEXT.MSFIELD='".$field_ids['MSFIELD']."'";
                $this->query_obj->AddCondition("IDMAIN in (select DATAEXT.IDMAIN from DATAEXT inner join DATAEXTPLAIN on DATAEXT.ID = DATAEXTPLAIN.IDDATAEXT where ".$str_cond.")");
              }
            } else {
              if ((int)$search_str == $search_str && strlen($search_str) == 4) {
                $this->query_obj->AddCondition("IDMAIN in (select DATAEXT.IDMAIN from DATAEXT inner join DATAEXTPLAIN on DATAEXT.ID = DATAEXTPLAIN.IDDATAEXT where DATAEXTPLAIN.PLAIN = N'".$search_str."' and DATAEXT.MNFIELD=".$field_ids['MNFIELD']." and DATAEXT.MSFIELD='".$field_ids['MSFIELD']."')");
              }
            }
          break;
          case 'ISBN':
            $isbn = str_replace('-', '', $search_str);
            if (strlen($isbn) == 10 || strlen($isbn) == 13) {
              $this->query_obj->AddCondition("REPLACE(ISBN, '-', '') = '".$isbn."'", 'ISBN');
            }
          break;
          case 'author':
            $str_sql = "AFLINKID in (select IDINAF from AFWORDSEXT where IDWORDS in (select ID from AFWORDS where (";
            $words = explode(' ', $search_str);
            $i = 0;
            foreach ($words as $value2) {
              $i++;
              $str_sql .= "LOWER(VALUE) = N'".$value2."'";
              if ($i < sizeof($words)) {
                $str_sql .= " or ";
              }
            }
            if ($search_trans) {
              $words_trans = explode(' ', $search_trans);
              foreach ($words_trans as $key2 => $value2) {
                if ($value2 != $words[$key2]) {
                  $str_sql .= " or LOWER(VALUE) = N'".$value2."'";
                }
              }
            }
            $str_sql .= "))) and (MNFIELD=700 or MNFIELD=701) and MSFIELD='\$3'";
            $this->query_obj->AddCondition($str_sql);
          break;
          case 'org':
            $str_sql = "AFLINKID in (select IDAF from AFORGSVAR where LOWER(PLAIN) = N'".$search_str."'";
            if ($search_trans) {
              $str_sql .= " or LOWER(PLAIN) = N'".$search_trans."'";
            }
            $str_sql .= ") and (MNFIELD=210 or MNFIELD=710)";
            $this->query_obj->AddCondition($str_sql);
          break;
        }
      }

      if (isset($this->post['lang_select']) && $this->post['lang_select'] != 'all') {
        $this->query_obj->AddSuperCondition("IDMAIN in (select DATAEXT.IDMAIN from DATAEXT inner join DATAEXTPLAIN on DATAEXT.ID = DATAEXTPLAIN.IDDATAEXT where DATAEXTPLAIN.PLAIN = N'".$this->lang[$this->post['lang_select']]."' and (DATAEXT.MNFIELD=101 or (DATAEXT.MNFIELD=200 and DATAEXT.MSFIELD='\$z')))");
//"
      }
      if ($this->post['date_from']) {
        $this->query_obj->AddSuperCondition("Created is null or Created >= '".$this->post['date_from']."'");
//"
      }
      if ($this->post['date_upto']) {
        $this->query_obj->AddSuperCondition("Created is null or Created <= '".$this->post['date_upto']."'");
//"
      }
      if ($this->post['subsearch'] && my_sizeof($this->post['sub_ids'])) {
        $this->query_obj->AddSuperCondition("IDMAIN in (".$this->post['sub_ids'].")");
      }
    }
    return true;
  }



  private function PatIndex($str, $field) {
    $new_str = "PATINDEX('%".$str."%', LOWER(".$field.")) > 0 
                             AND
                             (
                              (
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) - 1, 1) = ' ' 
                               OR
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) - 1, 1) = ',' 
                               OR
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) - 1, 1) = ':' 
                               OR
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) - 1, 1) = '.' 
                              )
                              AND
                              (
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) + LEN('".$str."'), 1) = ' '
                               OR 
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) + LEN('".$str."'), 1) = ','
                               OR 
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) + LEN('".$str."'), 1) = '.'
                               OR 
                               SUBSTRING(LOWER(".$field."), PATINDEX('%".$str."%', LOWER(".$field.")) + LEN('".$str."'), 1) = ':'
                              ) 
                             )";
    return $new_str;
  }



}

?>