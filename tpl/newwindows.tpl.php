<html>
<head>
<title><?=$rus['fullres_title']?></title>
<meta http-equiv="Content-Type" content="text/html; charset=<?=CHARSET?>">
<link rel="stylesheet" type="text/css" href="styles.css">
</head>
<body>
<? if (my_sizeof($tpl->result[$_GET['id']])) : ?>
<table border=0 width="100%">
<? $bgcolor1 = '#FFFFFF'; $bgcolor2 = '#A7A2D5'; $bgcolor2 = '#BFD8E6'; $flag=0; ?> 
<?   foreach ($tpl->result[$_GET['id']] as $value) : ?>
<? if ($flag==0) : $bgcolor=$bgcolor1; $flag=1;
else : $bgcolor=$bgcolor2; $flag=0;
endif;?>
<?     if (strlen(trim($value['value']))) : ?>
  <tr bgcolor="<?=$bgcolor?>">
    <td><b><?=$value['field']?></b></td>
    <td><?=$value['value']?></td>
  </tr>
<?     endif; ?>
<?   endforeach; ?>
</table>
<? endif; ?>
<div align="center"><a href="#" onClick="window.close(this);"><?=$rus['close']?></a></div>
</body>
</html>