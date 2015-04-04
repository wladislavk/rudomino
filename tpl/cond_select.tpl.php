<option value="-1"><?=$rus['how']?></option>
<? if (my_sizeof($tpl->condlist)) : ?>
<?   foreach ($tpl->condlist as $key => $value) : ?>
<option value="<?=$key?>"><?=$value?></option>
<?   endforeach; ?>
<? endif; ?>