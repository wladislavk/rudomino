<? $k = 0; ?>
<? if (my_sizeof($tpl->vk)) : ?>
<table border=1 bgcolor="#FFFFFF">
  <tr>
<?  foreach ($tpl->vk as $value) : ?>
<?   if (strlen(trim($value))) : ?>
<?    $k++; ?>
    <td class="keyboard" onclick="vk_insert('<?=$value?>', selectedField);setCond(document.forms[0].elements['field_select['+selectedField+']'].value, selectedField, false, 1);"><?=$value?></td>
<?    if ($k % VK_COLS == 0) : ?>
  </tr>
  <tr>
<?    endif; ?>
<?   endif; ?>
<?  endforeach; ?>
<?  if ($k % VK_COLS != 0) : ?>
<?   for ($i=0; $i < VK_COLS - $k%VK_COLS; $i++) : ?>
    <td>&nbsp;</td>
<?   endfor; ?>
<?  endif; ?>
  </tr>
</table>
<? endif; ?>
