<div class="style4"><?=$rus['search_cond']?></div>
<br />

<? switch ($_GET['type']) : ?>
<?   case 'simple': ?>
<?     if ($_POST['lang_select'] == 'all') : ?>
<div class="textusl"><?=$rus['language']?> - <strong><?=$rus['each']?></strong> <?=$rus['AND']?> 
<?=$rus['str']?> - <strong><?=join(' '.$rus['AND'].' ', $tpl->words)?></strong></div>

<?     else : ?>
<div class="textusl"><?=$rus['language']?> - <strong><?=$tpl->lang[$_POST['lang_select']]?></strong> 
<?=$rus['AND']?> <?=$rus['str']?> - <strong><?=join(' '.$rus['AND'].' ', $tpl->words)?></strong></div>
<?     endif; ?>
<?   break; ?>

<?   case 'adv': ?>
<div class="textusl">
  [ <?=$rus['language']?> = <strong>
<?     if ($_POST['lang_select'] == 'all') : ?>
  <?=$rus['each']?> 
<?     else : ?>
  <?=$tpl->lang[$_POST['lang_select']]?> 
<?     endif; ?>
</strong>]
<?     if (($_POST['from_year'] && $_POST['from_year'] != $rus['period_year']) || ($_POST['upto_year'] && $_POST['upto_year'] != $rus['period_year'])) : ?>
  <?=$rus['AND']?> [ <?=$rus['get_period']?> = <strong>
<?       if ($_POST['from_year'] && $_POST['from_year'] != $rus['period_year']) : ?>
  <?=$rus['from']?>
<?         if ($_POST['from_month'] != '-1') : ?>
  <?=$_POST['from_month']?> /
<?         endif; ?>
  <?=$_POST['from_year']?>
<?       endif; ?>
<?       if ($_POST['upto_year'] && $_POST['upto_year'] != $rus['period_year']) : ?>
  <?=$rus['upto']?>
<?         if ($_POST['upto_month'] != '-1') : ?>
  <?=$_POST['upto_month']?> /
<?         endif; ?>
  <?=$_POST['upto_year']?>
<?       endif; ?>
 </strong> ]
<?     endif; ?>

  <?=$rus['AND']?>
<?     for ($i=0; $i < NUM_FIELDS; $i++) : ?>
<?       if ($_POST['field_select'][$i] != '-1' && $_POST['s_string'][$i] && $_POST['s_string'][$i] != $rus['what']) : ?>
<?         if ($_POST['logic'][$i-1]) : ?>
  <?=$rus[$_POST['logic'][$i-1]]?>
<?         endif; ?>
  [ <?=$rus['search_fields'][$_POST['field_select'][$i]]?> = <strong><?=$_POST['s_string'][$i]?></strong> 
<?         if ($_POST['field_select'][$i] == 'header' || $_POST['field_select'][$i] == 'cat' || $_POST['field_select'][$i] == 'level') : ?>
<?           if ($_POST['cond_select'][$i] > -1) : ?>
    ( <?=$rus['cond_types'][$_POST['cond_select'][$i]]?> )
<?           else : ?>
    ( <?=$rus['cond_types'][DEF_COND_TYPE]?> )
<?           endif; ?>
<?         endif; ?>
  ]
<?       endif; ?>
<?     endfor; ?>
</div>
<?   break; ?>
<? endswitch; ?>

<div class="textresalt"><br /><?=$rus['search_result']?>
  \ <span class="lcase">(<?=$rus['found']?> <?=sizeof($tpl->result)?> <?=$tpl->ChangeCase(sizeof($tpl->result), $rus['record'])?>)</span>
 </div>
<? if (my_sizeof($tpl->result)) : ?>
<table border=0 width="100%" cellpadding="0" cellspacing="5">
  <tr>
    <th><?=$rus['number']?></th>
    <th><?=$rus['author']?></th>
    <th><?=$rus['header']?></th>
    <th><?=$rus['pub_place']?></th>
    <th><?=$rus['publisher']?></th>
    <th><?=$rus['pub_date']?></th>
  </tr>
<? $bgcolor1 = '#FFFFFF'; $bgcolor2 = '#A7A2D5'; $bgcolor2 = '#BFD8E6'; $flag=0; ?> 
<? foreach ($tpl->result as $key => $value) : ?>
<? if ($flag==0) : $bgcolor=$bgcolor1; $flag=1;
else : $bgcolor=$bgcolor2; $flag=0;
endif;?>
  <tr bgcolor="<?=$bgcolor?>">
    <td><a href="#" onclick="window.open('full_res.php?id=<?=$key?>', 'full_res', 'toolbar=no, location=no, directories=no, status=no, menubar=no, resizable=yes, scrollbars=yes, width=500, height=400');return false;"><?=$key?></a></td>
<?   if ($value['author']) : ?>
    <td><?=$value['author']?></td>
<?   else : ?>
    <td>&nbsp;</td>
<?   endif; ?>
<?   if ($value['header']) : ?>
    <td><?=$value['header']?></td>
<?   else : ?>
    <td>&nbsp;</td>
<?   endif; ?>
<?   if ($value['place']) : ?>
    <td><?=$value['place']?></td>
<?   else : ?>
    <td>&nbsp;</td>
<?   endif; ?>
<?   if ($value['org']) : ?>
    <td><?=$value['org']?></td>
<?   else : ?>
    <td>&nbsp;</td>
<?   endif; ?>
<?   if ($value['date']) : ?>
    <td><?=$value['date']?></td>
<?   else : ?>
    <td>&nbsp;</td>
<?   endif; ?>
  </tr>
<? endforeach; ?>
</table>
<? else : ?>
<div align="center"><br /><?=$rus['no_result']?></div>
<? endif; ?>