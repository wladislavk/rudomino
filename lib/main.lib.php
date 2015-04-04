<?

// this file contains some useful functions

function my_sizeof($array) { 
// returns size if the argument is an array AND it contains some elements, false 
// otherwise. native PHP sizeof() returns 1 if the argument is scalar
  if (!is_array($array)) {
    return false;
  }
  if (!sizeof($array)) {
    return false;
  }
  return sizeof($array);
}

function addslashes_deep($value) { // for making addslashes on every element of n-dimensional array
  $value = is_array($value) ?
               array_map('addslashes_deep', $value) :
               addslashes($value);
  return $value;
}

function stripslashes_deep($value) { // from php.net, for making stripslashes on every element of n-dimensional array
  $value = is_array($value) ?
               array_map('stripslashes_deep', $value) :
               stripslashes($value);
  return $value;
}

function br2nl($text) {
  return preg_replace("/<br\\s*?\\/??>/i", "\n", $text); // from php.net, reverse to nl2br()
}

function br2void($text) {
  return preg_replace("/<br\\s*?\\/??>/i", "", $text); // for textarea fields, eliminates <br> tags
}

function noscript($text) {
  return preg_replace('/<script(.*?)>(.*?)<\\/script>/isx', '', $text);
}

function nl2div($text) {
  $text = rtrim($text);
  $text = preg_replace('/(\\n)+?/x', "</div>\n\n<div class=maintext>", $text);
  $text = "<div class=maintext>".$text."</div>";
  return $text;
}

function div2nl($text) { // </div> is followed by endline
  $text = preg_replace("/<br\\s*?\\/??>/i", "<br>", $text); 
  $text = preg_replace("/<\/div>\\n+?/i", "</div>\n", $text);
  $text = preg_replace("/<\/div>\\s+?<div/i", "</div>\n\n<div", $text);
  $text = preg_replace("/<\/div>\\s+?<a/i", "</div>\n\n<a", $text);
  $text = preg_replace("/<\/ul>\\n+?/i", "</ul>\n", $text);
  $text = preg_replace("/<\/ul>\\s+?<ul/i", "</ul>\n\n<ul", $text);
  $text = preg_replace("/<\/ul>\\s+?<div/i", "</ul>\n\n<div", $text);
  $text = preg_replace("/<\/div>\\s+?<ul/i", "</div>\n\n<ul", $text);
  $text = preg_replace("/<\/ul>\\s+?<a/i", "</ul>\n\n<a", $text);
  return $text;
}

function div2nl2($text) { // <div> is replaced by endline
  $text = preg_replace("/<div.*?>(.*?)<\\/div>/is", '$1', $text);
  $text = preg_replace("/(\\s)*(\\n)+(\\s)*/is", "\n\n", $text);
  return $text;
}

function num2case($number, $cases) { // sets russian endings for numbers
  $last = $number % 10;
  $last2 = $number % 100;
  if ($last2 >= 11 && $last2 <= 14) {
    return $cases[2];
  }
  if ($last == 1) {
    return $cases[0];
  }
  if ($last >=2 && $last <= 4) {
    return $cases[1];
  }
  return $cases[2];
}

function my_preg_split($regexp, $string) { 
// returns a 2D array with parts of preg_splitted string and delimiters
// useful for distinguishing between whitespace and endline
  $new_string = array();
  $string_tmp = preg_split($regexp, $string, -1, PREG_SPLIT_OFFSET_CAPTURE);
  $counter = 0;
  foreach ($string_tmp as $key => $this_string_tmp) {
    $counter++;
    $new_string[$key][0] = $this_string_tmp[0];
    if ($counter != sizeof($string_tmp)) {
      $new_string[$key][1] = substr($string, $this_string_tmp[1] + strlen($this_string_tmp[0]), 1);
    }
  }
  return $new_string;
}

// believe it or not but some programs make two different types of whitespaces
// that are not equal. i did not find a way to make them equal to each other
// except using entities
function whitespace_decode($string) {
  $string = htmlentities($string);
  $string = str_replace('&nbsp;', ' ', $string);
  $string = html_entity_decode($string);
  return $string;
}

// for recursive trimming of different characters from string
function my_rtrim($string, $cutlist) {
  if (!my_sizeof($cutlist)) {
    return false;
  }
  $string = rtrim($string);
  $string = whitespace_decode($string);
  foreach ($cutlist as $this_cut) {
    if (substr($string, -1 * strlen($this_cut)) == $this_cut) {
      $string = substr($string, 0, strlen($string) - strlen($this_cut) - 1);
      $string = my_rtrim($string, $cutlist);
    }
  }
  return $string;
}

if (!function_exists ('mime_content_type')) {
  function mime_content_type($f) {
    return exec(trim('file -bi '.escapeshellarg($f)));
  }
} 

function strtoupper_cyr($str, $lower, $upper) {
  for ($i=0; $i<strlen($str); $i++) {
    if ($this_char = strpos($lower, substr($str, $i, 1))) {
      $str = str_replace(substr($lower, $this_char, 1), substr($upper, $this_char, 1),$str);
    }
  }
  return strtoupper($str);
}

function strtolower_cyr($str, $lower, $upper) {
  for ($i=0; $i<strlen($str); $i++) {
    if ($this_char = strpos($upper, substr($str, $i, 1))) {
      $str = str_replace(substr($upper, $this_char, 1), substr($lower, $this_char, 1),$str);
    }
  }
  return strtolower($str);
}

function kill_yo($str) {
  $str = str_replace('ё', 'е', $str);
  $str = str_replace('Ё', 'Е', $str);
  return $str;
}

function mb_str_ireplace($co, $naCo, $wCzym)
{
    $wCzymM = mb_strtolower($wCzym);
    $coM    = mb_strtolower($co);
    $offset = 0;
    
    while(($poz = mb_strpos($wCzymM, $coM, $offset)) !== false)
    {
        $offset = $poz + mb_strlen($naCo);
        $wCzym = mb_substr($wCzym, 0, $poz). $naCo .mb_substr($wCzym, $poz+mb_strlen($co));
        $wCzymM = mb_strtolower($wCzym);
    }
    
    return $wCzym;
}
?>