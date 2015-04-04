<?

// this class forms the sql query

class Query {

  private $condition;
  private $paradigm;
  private $priority;
  private $super_condition;
  private $table;
  private $column;


  
  function __construct($table, $column) {
    $this->table = $table;
    $this->column = $column;
  }

  
  
  public function AddCondition($str_sql, $table=false) {
    if ($table) {
      $this->condition[] = array('str'=>$str_sql, 'tbl'=>$table);
    } else {
      $this->condition[] = array('str'=>$str_sql);
    }
    return true;
  }



  public function AddSuperCondition($str_sql) {
    $this->super_condition[] = $str_sql;
    return true;
  }


  
  public function AddLogic($logic) {
    if ($logic != 'AND' && $logic != 'OR' && $logic != 'AND NOT' && $logic != 'OR NOT') {
      return false;
    }
    $this->paradigm[] = $logic;
    return true;
  }

  
  
  private function SetPriority($p_type='first') {
    if (!my_sizeof($this->condition)) {
      return false;
    }
    if (!my_sizeof($this->paradigm)) {
      return true;
    }
    if (!in_array('AND', $this->paradigm) && !in_array('AND NOT', $this->paradigm)) {
      return true;
    }
    if (!in_array('OR', $this->paradigm) && !in_array('OR NOT', $this->paradigm)) {
      return true;
    }
    $opens = array();
    $closes = array();
    foreach ($this->condition as $key => $value) {
      switch ($p_type) {
        case 'first':
          if ($key > 0 && $key < sizeof($this->condition)-1) {
            $this->priority[] = array(1,0);
            $closes[sizeof($this->condition)-1]++;
          } else {
            $this->priority[] = array(0,0);
          }
        break;
        case 'last':
          if ($key > 0 && $key < sizeof($this->condition)-1) {
            $this->priority[] = array(0,1);
            $opens[0]++;
          } else {
            $this->priority[] = array(0,0);
          }
        break;
        case 'and':
          if ($key == 0 && ($this->paradigm[$key] == 'OR' || $this->paradigm[$key] == 'OR NOT')) {
            $this->priority[] = array(1,0);
          } elseif ($key == sizeof($this->condition)-1 && ($this->paradigm[$key-1] == 'OR' || $this->paradigm[$key-1] == 'OR NOT')) {
            $this->priority[] = array(0,1);
          } elseif (($this->paradigm[$key-1] == 'AND' || $this->paradigm[$key-1] == 'AND NOT') && ($this->paradigm[$key] == 'OR' || $this->paradigm[$key] == 'OR NOT')) {
            $this->priority[] = array(1,0);
          } elseif (($this->paradigm[$key-1] == 'OR' || $this->paradigm[$key-1] == 'OR NOT') && ($this->paradigm[$key] == 'AND' || $this->paradigm[$key] == 'AND NOT')) {
            $this->priority[] = array(0,1);
          } else {
            $this->priority[] = array(0,0);
          }
        break;
        case 'or':
          if ($key == 0 && ($this->paradigm[$key] == 'AND' || $this->paradigm[$key] == 'AND NOT')) {
            $this->priority[] = array(1,0);
          } elseif ($key == sizeof($this->condition)-1 && ($this->paradigm[$key-1] == 'AND' || $this->paradigm[$key-1] == 'AND NOT')) {
            $this->priority[] = array(0,1);
          } elseif (($this->paradigm[$key-1] == 'OR' || $this->paradigm[$key-1] == 'OR NOT') && ($this->paradigm[$key] == 'AND' || $this->paradigm[$key] == 'AND NOT')) {
            $this->priority[] = array(1,0);
          } elseif (($this->paradigm[$key-1] == 'AND' || $this->paradigm[$key-1] == 'AND NOT') && ($this->paradigm[$key] == 'OR' || $this->paradigm[$key] == 'OR NOT')) {
            $this->priority[] = array(0,1);
          } else {
            $this->priority[] = array(0,0);
          }
        break;
        default:
          return false;
        break;
      }
    }
    foreach ($closes as $key => $value) {
      $this->priority[$key][1] += $value;
    }
    foreach ($opens as $key => $value) {
      $this->priority[$key][0] += $value;
    }
    return true;
  }


  
  public function SetQuery($search_col) {
    $query = "select distinct ".$this->column." from ".$this->table." where 1=1";

    if (my_sizeof($this->super_condition)) {
      foreach ($this->super_condition as $value) {
        $query .= " and ".$this->column." in (select ".$this->column." from ".$this->table." where ".$value.")";
      }
    }

    if (my_sizeof($this->paradigm)) {
      $this->SetPriority(DEF_PRIORITY);
    }
    
    if (my_sizeof($this->condition)) {
      $query .= " and (";
      foreach ($this->condition as $key => $value) {
        $value['str'] = $this->AddPattern($value['str'], $search_col);
        if ($this->paradigm[$key-1]) {
          if ($this->paradigm[$key-1] == 'AND' || $this->paradigm[$key-1] == 'AND NOT') {
            $query .= ' and ';
          } else {
            $query .= ' or ';
          }
        }

        if ($this->priority[$key][0]) {
          for ($i=0; $i<$this->priority[$key][0]; $i++) {
            $query .= '(';
          }
        }

        $query .= $this->column;
        if ($this->paradigm[$key-1] && ($this->paradigm[$key-1] == 'AND NOT' || $this->paradigm[$key-1] == 'OR NOT')) {
          $query .= " not in ";
        } else {
          $query .= " in ";
        }
        $query .= "(select ".$this->column." from ";
        if ($value['tbl']) {
          $query .= $value['tbl'];
        } else {
          $query .= $this->table;
        }
        $query .= " where ".$value['str'].")";

        if ($this->priority[$key][1]) {
          for ($i=0; $i<$this->priority[$key][1]; $i++) {
            $query .= ')';
          }
        }
      }
      $query .= ')';
    }
    return $query;
  }



  private function AddPattern($cond, $col=SEARCH_COL, $symbol=PAT_SYMBOL) {
    $cond = str_replace(' = ', '=', $cond);
    if (preg_match('/'.$col.'=\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $cond)) {
      $cond = preg_replace('/'.$col.'=\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $col.' LIKE \'%$1%\'', $cond);
    } elseif (preg_match('/'.$col.'\\)=\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $cond)) {
      $cond = preg_replace('/'.$col.'\\)=\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $col.') LIKE \'%$1%\'', $cond);
    } elseif (preg_match('/'.$col.'=N\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $cond)) {
      $cond = preg_replace('/'.$col.'=N\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $col.' LIKE N\'%$1%\'', $cond);
    } elseif (preg_match('/'.$col.'\\)=N\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $cond)) {
      $cond = preg_replace('/'.$col.'\\)=N\\\'\\'.$symbol.'(.*?)\\'.$symbol.'\\\'/isx', $col.') LIKE N\'%$1%\'', $cond);
    } else {
      $cond = str_replace($col.'=\''.$symbol, $col.' LIKE \'%', $cond);
      $cond = str_replace($col.')=\''.$symbol, $col.') LIKE \'%', $cond);
      $cond = str_replace($col.'=N\''.$symbol, $col.' LIKE N\'%', $cond);
      $cond = str_replace($col.')=N\''.$symbol, $col.') LIKE N\'%', $cond);
      $cond = preg_replace('/'.$col.'=\\\'(.*?)\\'.$symbol.'\\\'/isx', $col.' LIKE \'$1%\'', $cond);
      $cond = preg_replace('/'.$col.'\\)=\\\'(.*?)\\'.$symbol.'\\\'/isx', $col.') LIKE \'$1%\'', $cond);
      $cond = preg_replace('/'.$col.'=N\\\'(.*?)\\'.$symbol.'\\\'/isx', $col.' LIKE N\'$1%\'', $cond);
      $cond = preg_replace('/'.$col.'\\)=N\\\'(.*?)\\'.$symbol.'\\\'/isx', $col.') LIKE N\'$1%\'', $cond);
    }
    return $cond;
  }

}


?>