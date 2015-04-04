<form method="post" enctype="multipart/form-data" onsubmit="return validate();">
<input type="hidden" name="do_search" value="1">
<input type="hidden" name="search_type" value="<?=$_GET['type']?>">
<input type="hidden" name="subsearch" value="0">
<? if ($_POST['do_search'] && my_sizeof($tpl->result)) : ?>
<input type="hidden" name="sub_ids" value="<?=join(',', array_keys($tpl->result))?>">
<? endif; ?>

<? switch ($_GET['type']) : ?>
<?   case 'simple': ?>

<?     if ($_POST['do_search'] && my_sizeof($tpl->result)) : ?>
<div class="style4">
  <?=$rus['simple_search_title']?>. <?=$rus['result']?>
  (<?=$rus['found']?> <?=sizeof($tpl->result)?> <?=$tpl->ChangeCase(sizeof($tpl->result), $rus['record'])?>)
</div>
<?     else : ?>
<div class="style4"><?=$rus['simple_search_title']?></div>
<?     endif; ?>
<?   break; ?>

<?   case 'adv': ?>

<?     if ($_POST['do_search'] && my_sizeof($tpl->result)) : ?>
<div class="style4">
  <?=$rus['adv_search_title']?>. <?=$rus['result']?>
  (<?=$rus['found']?> <?=sizeof($tpl->result)?> <?=$tpl->ChangeCase(sizeof($tpl->result), $rus['record'])?>)
</div>
<?     else : ?>
<div class="style4"><?=$rus['adv_search_title']?></div>
<?     endif; ?>
<?   break; ?>

<? endswitch; ?>

<table width="50%" border=0>
  <tr>
    <td class="maintext" width="100%"><?=$rus['lang_field']?></td>
    <td class="maintext" align="right">
      <select name="lang_select">
<!--        <option value="-1"><?=$rus['lang_choice']?></option>-->

<? if ($_POST['lang_select'] == 'all') : ?>
        <option value="all" selected><?=$rus['each']?></option>
<? else : ?>
        <option value="all"><?=$rus['each']?></option>
<? endif; ?>

<? foreach ($tpl->lang as $key => $value) : ?>
<?   if ($_POST['lang_select'] === ''.$key) : ?>
        <option value="<?=$key?>" selected><?=$value?></option>
<?   else : ?>
        <option value="<?=$key?>"><?=$value?></option>
<?   endif; ?>
<? endforeach; ?>

      </select>
    </td>
  </tr>
</table>

<? if ($_GET['type'] == 'adv') : ?>
<?   for ($i=0; $i<NUM_FIELDS; $i++) : ?>
<table width="50%" border=0>
  <tr>
    <td class="maintext">
      <select name="field_select[<?=$i?>]" onchange="setCond(this.value, <?=$i?>, true, 0)">
        <option value="-1"><?=$rus['where']?></option>
<?     foreach ($rus['search_fields'] as $key => $value) : ?>
<?       if ($_POST['field_select'][$i] == $key) : ?>
        <option value="<?=$key?>" selected><?=$value?></option>
<?       else : ?>
        <option value="<?=$key?>"><?=$value?></option>
<?       endif; ?>
<?     endforeach; ?>
      </select>
    </td>
	
<? if ($_POST['s_string'][$i] && $_POST['s_string'][$i] != $rus['what']) : ?>
    <td class="maintext"><input onkeyup="setCond(document.forms[0].elements['field_select[<?=$i?>]'].value, <?=$i?>, false, this.value.length)" onfocus="selectedField=<?=$i?>;" type="text" size=60 id="s_string[<?=$i?>]" name="s_string[<?=$i?>]" value="<?=$_POST['s_string'][$i]?>"></td>
<? else : ?>
    <td class="maintext"><input onkeyup="setCond(document.forms[0].elements['field_select[<?=$i?>]'].value, <?=$i?>, false, this.value.length)" onfocus="if(this.value=='<?=$rus['what']?>'){this.value='';}selectedField=<?=$i?>;" type="text" value="<?=$rus['what']?>" size=60 id="s_string[<?=$i?>]" name="s_string[<?=$i?>]"></td>
<? endif; ?>
    <td class="maintext">
      <div id="div_cond_select_<?=$i?>">
      <select name="cond_select[<?=$i?>]" onchange="if(this.value>-1){document.forms[0].elements['s_string[<?=$i?>]'].value=this.options[selectedIndex].text}">
        <option value="-1"><?=$rus['how']?></option>
<? if ($_POST['field_select'][$i] == 'header' || $_POST['field_select'][$i] == 'cat') : ?>
<?   foreach ($rus['cond_types'] as $key => $value) : ?>
<?     if ($_POST['cond_select'][$i] == $key) : ?>
        <option value="<?=$key?>" selected><?=$value?></option>
<?     else : ?>
        <option value="<?=$key?>"><?=$value?></option>
<?     endif; ?>
<?   endforeach; ?>
<? endif; ?>
      </select>
      </div>
    </td>
  </tr>
</table>


<?     if ($i < NUM_FIELDS-1) : ?>
<table width="100%" border=0>
  <tr>
    <td class="maintext">
      <select name="logic[<?=$i?>]">
<? if ($_POST['logic'][$i] == 'AND') : ?>
        <option value='AND' selected><?=$rus['AND']?></option>
<? else : ?>
        <option value='AND'><?=$rus['AND']?></option>
<? endif; ?>
<? if ($_POST['logic'][$i] == 'OR') : ?>
        <option value='OR' selected><?=$rus['OR']?></option>
<? else : ?>
        <option value='OR'><?=$rus['OR']?></option>
<? endif; ?>
<? if ($_POST['logic'][$i] == 'AND NOT') : ?>
        <option value='AND NOT' selected><?=$rus['AND NOT']?></option>
<? else : ?>
        <option value='AND NOT'><?=$rus['AND NOT']?></option>
<? endif; ?>
<? if ($_POST['logic'][$i] == 'OR NOT') : ?>
        <option value='OR NOT' selected><?=$rus['OR NOT']?></option>
<? else : ?>
        <option value='OR NOT'><?=$rus['OR NOT']?></option>
<? endif; ?>
      </select>
    </td>
  </tr>
</table>

<?     endif; ?>
<?   endfor; ?>

<? else : ?>
<table width="50%" border=0>
  <tr>
<? if ($_POST['s_string'][0]) { ?>
    <td class="maintext">
	  <input onfocus="selectedField=0;" type="text" value="<?=$_POST['s_string'][0]?>" size=60 id="s_string[0]" name="s_string[0]" style="width:100%">
	</td>
<? } else { ?>
    <td class="maintext">
	  <input onfocus="if(this.value=='<?=$rus['what']?>'){ this.value=''; } selectedField=0;" type="text" value="<?=$rus['what']?>" size=60 id="s_string[0]" name="s_string[0]" style="width:100%">
	 </td>
<? } ?>
  </tr>
</table>
<? endif; ?>

<? if ($_GET['type'] == 'adv') : ?>
<table width="50%" border=0>
  <tr>
    <td class="maintext"><?=$rus['period']?></td>
    <td class="maintext"><?=$rus['from']?></td>
    <td>
      <select name="from_month">
        <option value="-1"><?=$rus['period_month']?></option>
<? foreach ($rus['months'] as $key => $value) : ?>
<?   if ($_POST['from_month'] == $key) : ?>
        <option value="<?=$key?>" selected><?=$value?></option>
<?   else : ?>
        <option value="<?=$key?>"><?=$value?></option>
<?   endif; ?>
<? endforeach; ?>
      </select>
    </td>
<? if ($_POST['from_year']) : ?>
    <td><input type="text" name="from_year" value="<?=$_POST['from_year']?>" size=4 maxlength=4 onfocus="if(this.value=='<?=$rus["period_year"]?>'){this.value='';}"></td>
<? else : ?>
    <td><input type="text" name="from_year" value="<?=$rus['period_year']?>" size=4 maxlength=4 onfocus="if(this.value=='<?=$rus["period_year"]?>'){this.value='';}"></td>
<? endif; ?>
    <td class="maintext"><?=$rus['upto']?></td>
    <td>
      <select name="upto_month">
        <option value="-1"><?=$rus['period_month']?></option>
<? foreach ($rus['months'] as $key => $value) : ?>
<?   if ($_POST['upto_month'] == $key) : ?>
        <option value="<?=$key?>" selected><?=$value?></option>
<?   else : ?>
        <option value="<?=$key?>"><?=$value?></option>
<?   endif; ?>
<? endforeach; ?>
      </select>
    </td>
<? if ($_POST['upto_year']) : ?>
    <td><input type="text" name="upto_year" value="<?=$_POST['upto_year']?>" size=4 maxlength=4 onfocus="if(this.value=='<?=$rus["period_year"]?>'){this.value='';}"></td>
<? else : ?>
    <td><input type="text" name="upto_year" value="<?=$rus['period_year']?>" size=4 maxlength=4 onfocus="if(this.value=='<?=$rus["period_year"]?>'){this.value='';}"></td>
<? endif; ?>
  </tr>
</table>
<? endif; ?>


<table width="50%" border=0>
  <tr>
    <td class="maintext"><?=$rus['diacritic']?></td>
    <td class="maintext" align="right">
      <select name="vk_select" onchange="if(this.value>-1){doLoad(this.value)}">
        <option value="-1"><?=$rus['region_choice']?></option>
<? foreach ($tpl->diacr as $key => $value) : ?>
        <option value="<?=$key?>"><?=$value?></option>
<? endforeach; ?>
      </select>
    </td>
  </tr>
</table>
<div id="vk"></div>
<? if ($_POST['do_search'] && my_sizeof($tpl->result)) : ?>
<table width="50%" border=0>
<tr><td colspan="2" bgcolor="#CCCCCC" height="1px"></td></tr>
  <tr>
    <td width="30%" class="maintext"><?=$rus['subsearch']?></td>
    <td  width="70%" class="maintext" align="left"><input type="checkbox" name="subsearch" value="1"></td>
  </tr>
</table>
<? endif; ?>


<table width="50%" border=0>
  <tr>
    <td width="30%" align="left"><br />
	 <BUTTON name="submit" value="<?=$rus['submit']?>" type="submit" title="<?=$rus['submit']?>">
	 <?=$rus['submit']?>
	</BUTTON>
	</td>
    <td align="left"><br />
	<BUTTON name="submit" type="button" value="<?=$rus['reset']?>" onclick="reset_action()" title="<?=$rus['reset']?>">
	 <?=$rus['reset']?>
	</BUTTON>
	</td>
  </tr>
</table>
</form>
