<?

class DBInterface {

  private $link;
  private $charset=false;
  
  function __construct($host, $login, $password, $database) {
    $this->link = new COM("ADODB.Connection", null, DB_CHARSET) or die('Cannot connect to database');
    $dsn = "PROVIDER=SQLOLEDB.1; DRIVER={SQL Server}; SERVER={".$host."};UID={".$login."};PWD={".$password."}; DATABASE={".$database."};";
    $this->link->Open($dsn);
    if (defined('CHARSET')) {
      $this->charset = CHARSET;
    }
    return true;
  }

  public function change_db($database) {
    $this->link->Execute('use database '.$database) or die('Change: Cannot select DB');
    return true;
  }
  
  private function array_query($query, $singular=0, $sing_col=0) {
    $array = array();
//    echo $query."<br>";
//    die($query);
    if (!$result = &$this->link->Execute($query)) {
      echo 'Array query: Query error';
      return false;
    }
    $num_columns = $result->Fields->Count();
//    com_print_typeinfo($result->Fields);
    while (!$result->EOF) {
      $row = array();
      $j = 0;
      for ($i=0; $i < $num_columns; $i++) {
        $row[$j] = $result->Fields[$i]->Value;
        $j++;
        $row[$result->Fields[$i]->Name] = $result->Fields[$i]->Value;
      }
      $array[] = $row;
      $result->MoveNext();
    }
    if ($singular) {
      if ($array[0]) {
        return $array[0];
      } else {
        return $array;
      }
    } elseif ($sing_col) {
      if (my_sizeof($array)) {
        foreach ($array as $this_value) {
          $new_arr[] = $this_value[0];
        }
        return $new_arr;
      } else {
        return $array;
      }
    } else {
      return $array;
    }
  }

  private function mssql_slashes($str) {
    $str = str_replace('\'', '\'\'', $str);
    return $str;
  }

  public function simple_get($query, $sing_row=0, $sing_col=0, $database=false) {
    if ($database) {
      $this->link->Execute('use '.$database) or die('Simple get: Cannot select DB');
    }
    if (!stristr($query, 'select')) {
      return false;
    }
    $result = $this->array_query($query, $sing_row, $sing_col);
    if (!is_array($result)) {
      die('Simple get: Bad query');
    }
    return stripslashes_deep($result);
  }

  public function get($tbl_name, $fields, $condition=false, $order='id', $single=0, $lim_start=false, $lim_end=false, $database=false) {
    if ($database) {
      $this->link->Execute('use '.$database) or die('Get: Cannot select DB');
    }
    $query = 'select ';
    $query .= join(', ', $fields);
    $query .= ' from '.$tbl_name.' where 1';
//    $condition = addslashes_deep($condition);
    if (my_sizeof($condition)) {
      $condition_tmp = array();
      foreach ($condition as $key => $value) {
        if (!is_int($value) && $value != 'null') {
          $value[0] = $this->mssql_slashes($value[0]);
          $condition_tmp[$key] = $key.' '.$value[1].' \''.$value[0].'\'';
        } else {
          $condition_tmp[$key] = $key.' '.$value[1].' '.$value[0];
        }
      }
    }
    if (my_sizeof($condition_tmp)) {
      $new_condition = join(' and ', $condition_tmp);
    }
    $query .= ' order by '.$order;
/*    if (!isset($lim_start)) {
      $lim_start = false;
    }
    if (!isset($lim_end)) {
      $lim_end = false;
    }
    if ($lim_start !== false && $lim_end !== false) {
      $query .= ' limit '.$lim_start.' , '.$lim_end;
    }*/
//    echo $query;
    $result = $this->array_query($query, $single);
    return stripslashes_deep($result);
  }

  public function add($args, $tbl_name, $database=false) {
    if ($database) {
      $this->link->Execute('use '.$database) or die('Add: Cannot select DB');
    }
    $fields = join(', ', array_keys($args));
    foreach ($args as $key => $this_arg) {
      $args[$key] = '\''.addslashes($this_arg).'\'';
    }
    $values = join(', ', $args);
    $query = 'insert into '.$tbl_name.' ('.$fields.') values ('.$values.')';
//    die($query);
    $this->link->Execute($query) or die('Add: Bad query');
    return true;
  }
  
  public function edit($args, $tbl_name, $column='id', $database=false) { 
    if ($database) {
      $this->link->Execute('use '.$database) or die('Edit: Cannot select DB');
    }
    foreach ($args as $id => $this_arg) {
      if (!my_sizeof($this_arg)) {
        return false;
      }
      foreach ($this_arg as $field => $value) {
        if (!$value) {
          $query_string[] = $field.'=null';
        } else {
          $query_string[] = $field.'=\''.addslashes($value).'\'';
        }
//        $query_string[] = $field.'=\''.addslashes($value).'\'';
      }
      $values = join(', ', $query_string);
      $query = 'update '.$tbl_name.' set '.$values.' where '.$column.'=\''.$id.'\'';
//    die($query);
      $this->link->Execute($query) or die('Edit: Bad query');
    }
    return true;
  }

  public function delete($id, $tbl_name, $column='id', $database=false) {
    if ($database) {
      $this->link->Execute('use '.$database) or die('Delete: Cannot select DB');
    }
    $query = 'delete from '.$tbl_name.' where '.$column.'='.$id;
    $this->link->Execute($query) or die('Delete: Bad query');
    return true;
  }

  public function if_exists($field, $value, $tbl_name, $database=false) {
    if ($database) {
      $this->link->Execute('use '.$database) or die('If exists: Cannot select DB');
    }
    $query = 'select * from '.$tbl_name.' where '.$field.'=\''.$value.'\'';
    $result = $this->array_query($query, 1) or die('Bad query: if exists');
    if (my_sizeof($result)) {
      return $result;
    }
    return false;
  }

  public function num_rows($tbl_name, $condition='', $database=false) {
    if ($database) {
      $this->link->Execute('use '.$database) or die('Num_rows: Cannot select DB');
    }
    $query = 'select count(id) as count from '.$tbl_name;
    if (my_sizeof($condition)) {
      foreach ($condition as $key => $this_condition) {
        $condition[$key] = str_replace('=', '=\'', $this_condition);
        $condition[$key] .= '\'';
      }
    } elseif ($condition) {
      $condition = str_replace('=', '=\'', $condition).'\'';
    }
    if (my_sizeof($condition)) {
      $condition = join(' and ', $condition);
    }
    if ($condition) {
      $query .= ' where '.$condition;
    }
    $result = $this->array_query($query, 1) or die('Bad query: num_rows');
    if (my_sizeof($result)) {
      return $result['count'];
    }
    return false;
  }

}

?>