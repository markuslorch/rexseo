<?php
/**
 * RexSEO Addon
 *
 * @link http://gn2-code.de/projects/rexseo/
 * @link https://github.com/gn2netwerk/rexseo
 *
 * @author dh[at]gn2-netwerk[dot]de Dave Holloway
 * @author code[at]rexdev[dot]de jeandeluxe
 *
 * Based on url_rewrite Addon by
 * @author markus.staab[at]redaxo[dot]de Markus Staab
 *
 * @package redaxo4.2.x/4.3.x
 * @version 1.4
 * @version svn:$Id$
 */

// GET PARAMS
////////////////////////////////////////////////////////////////////////////////
$mypage    = rex_request('page'   , 'string');
$subpage   = rex_request('subpage', 'string');
$minorpage = rex_request('minorpage', 'string');
$func      = rex_request('func'   , 'string');
$id        = rex_request('id', 'int');
$myroot    = $REX['INCLUDE_PATH'].'/addons/'.$mypage;


// SETTINGS
/////////////////////////////////////////////////////////////////////////////////
$table      = $REX['TABLE_PREFIX'].'rexseo_redirects';
$pagination = 15;


// BATCH SUBMIT
/////////////////////////////////////////////////////////////////////////////////
if($func=='batch-submit')
{
  $batch = rex_request('redirects','string','false');
  if($batch!='false')
  {
    $db = new rex_sql;
    $qry = 'INSERT INTO `'.$table.'` (`id`, `createdate`, `updatedate`, `expiredate`, `creator`, `status`, `from_url`, `to_article_id`, `to_clang`, `http_status`) VALUES';
    $batch = rexseo_301_2_array($batch);
    $date = time();
    $expire = $date + ($REX['ADDON']['rexseo']['settings']['default_redirect_expire']*24*60*60);
    foreach($batch as $k=>$v)
    {
      $qry .= PHP_EOL.'(\'\', \''.$date.'\', \''.$date.'\', \''.$expire.'\', \''.$REX['USER']->getValue('login').'\', 1, \''.$k.'\', '.$v['article_id'].', '.$v['clang'].', 301),';
    }
    $qry = rtrim($qry,',').';';

    if($db->setQuery($qry))
      echo rex_info('Weiterleitungen wurden in Tabelle gespeichert.');

    if(rexseo_htaccess_update_redirects())
      echo rex_info('Weiterleitungen wurden in die .htaccess geschrieben.');
  }
}


// LIST & FORM
/////////////////////////////////////////////////////////////////////////////////
if($func == '' || $func=='batch-submit')
{
   echo '<div class="rex-addon-output">
   <h2 class="rex-hl2">Redirects <span style="color:silver;font-size:12px;">(DB Tabelle: '.$table.')</span></h2>';

  $query = 'SELECT `id`, `from_url`, `status`, `to_article_id`, `to_clang`, `http_status`, `expiredate`, `createdate`, `updatedate`, `creator` FROM '.$table.' ORDER BY `createdate` DESC';
  $list = new rex_list($query,$pagination,'data');
  $list->debug = false;


  $imgHeader = '<a href="'. $list->getUrl(array('func' => 'add')) .'"><img src="media/metainfo_plus.gif" alt="add" title="add" /></a>';
  $list->addColumn($imgHeader,'<img src="media/metainfo.gif" alt="field" title="field" />',0,array('<th class="rex-icon">###VALUE###</th>','<td class="rex-icon">###VALUE###</td>'));
  $list->setColumnParams($imgHeader,array('func' => 'edit', 'id' => '###id###'));


  $list->removeColumn     ('id'            );
  $list->setColumnSortable('from_url'      );
  $list->setColumnSortable('status'        );

  $list->addColumn        ('target','',3);

  $list->setColumnSortable('to_article_id' );
  $list->setColumnSortable('to_clang'      );
  $list->setColumnSortable('http_status'   );
  $list->removeColumn     ('createdate'    );
  $list->removeColumn     ('updatedate'    );
  $list->setColumnSortable('expiredate'    );
  $list->removeColumn     ('creator'       );


  $list->setColumnLabel('id'            ,'ID');
  $list->setColumnLabel('from_url'      ,'alte URL');
  $list->setColumnLabel('status'        ,'Status');
  $list->setColumnLabel('expiredate'    ,'Expire');
  $list->setColumnLabel('target'        ,'Ziel-Artikel');
  $list->setColumnLabel('to_article_id' ,'ID');
  $list->setColumnLabel('to_clang'      ,'CLANG');
  $list->setColumnLabel('http_status'   ,'HTTP Status');
  $list->setColumnLabel('creator'       ,'Erzeuger');

  function list_status()
  {
    global $list;
    $str = $list->getValue('status')==1 ? '<span style="color:#107C2C;">aktiv</span>' : '<span style="color:#EA1144;">inaktiv</span>';
    return $str;
  }
  $list->setColumnFormat('status'  ,'custom', 'list_status');

  function list_target()
  {
    global $list;
    return urldecode(rex_getUrl($list->getValue('to_article_id'),$list->getValue('to_clang')));
  }
  $list->setColumnFormat('target'  ,'custom', 'list_target');

  function list_from_url()
  {
    global $list;
    return urldecode($list->getValue('from_url'));
  }
  $list->setColumnFormat('from_url'  ,'custom', 'list_from_url');

  function list_expiredate()
  {
    global $list;
    return date('d.m.y',$list->getValue('expiredate'));
  }
  $list->setColumnFormat('expiredate'  ,'custom', 'list_expiredate');

  $list->setColumnParams('id'            ,array('func' => 'edit', 'id' => '###id###'));
  $list->setColumnParams('from_url'      ,array('func' => 'edit', 'id' => '###id###'));
  $list->setColumnParams('status'        ,array('func' => 'edit', 'id' => '###id###'));
  $list->setColumnParams('target'        ,array('func' => 'edit', 'id' => '###id###'));
  $list->setColumnParams('expiredate'    ,array('func' => 'edit', 'id' => '###id###'));
  $list->setColumnParams('to_article_id' ,array('func' => 'edit', 'id' => '###id###'));
  $list->setColumnParams('to_clang'      ,array('func' => 'edit', 'id' => '###id###'));
  $list->setColumnParams('http_status'   ,array('func' => 'edit', 'id' => '###id###'));
  $list->show();

  echo '</div>';

// BATCH SUBMIT FORM
////////////////////////////////////////////////////////////////////////////////
echo '

<div class="rex-addon-output">
  <div class="rex-form">

  <form action="index.php" method="post">
    <input type="hidden" name="page"                   value="rexseo" />
    <input type="hidden" name="subpage"                value="redirects" />
    <input type="hidden" name="func"                   value="batch-submit" />

      <fieldset class="rex-form-col-1">
        <legend style="font-size: 1.333em;color: #336699;">Batch Submit</legend>
        <div class="rex-form-wrapper">

          <div class="rex-form-row">
            <p class="rex-form-col-a rex-form-select">
              <label for="robots" class="helptopic">Weiterleitungen:<br /> <br /><em style="color:gray;font-size:10px;">url article_id clang<br />z.B. foo/bar.html 4 0</em></label>
              <textarea id="rexseo_redirects" name="redirects"></textarea>
            </p>
          </div><!-- /rex-form-row -->

        </div><!-- /rex-form-wrapper -->

        <div class="rex-form-wrapper">

          <div class="rex-form-row rex-form-element-v2">
            <p class="rex-form-submit">
              <input class="rex-form-submit" type="submit" id="sendit" name="sendit" value="Weiterleitungen einfügen" />
            </p>
          </div><!-- /rex-form-row -->

        </div><!-- /rex-form-wrapper -->
      </fieldset>

  </form>
  </div><!-- /rex-addon-output -->
</div><!-- /rex-form -->
';



}

// ADD/EDIT FORM
////////////////////////////////////////////////////////////////////////////////
elseif($func == 'edit' || $func == 'add')
{
  require_once $myroot.'/classes/class.rexseo_core_extensions.inc.php';

  echo '<div class="rex-addon-output">';

  if($func == 'edit')
  {
    echo '<h2 class="rex-hl2">Redirect bearbeiten <span style="color:silver;font-size:12px;">(ID: '.$id.')</span></h2>';
  }
  else
  {
    echo '<h2 class="rex-hl2">Neuen Datensatz anlegen</h2>';
  }


  $form = new rexseo_rex_form($table,'Redirect','id='.$id,'post',false);

  $field =& $form->addSelectField('status');
  $field->setLabel('Status');
  $select =& $field->getSelect();
  $select->setSize(1);
  $select->addOption('aktiv',1);
  $select->addOption('inaktiv',0);

  $field = &$form->addTextField('from_url');
  $field->setLabel('alte URL');

  //$field = &$form->addLinkmapField('to_article_id');
  $field = &$form->addMultilangLinkmapField('to_article_id');
  $field->setLabel('umleiten nach');


  $field =& $form->addSelectField('http_status');
  $field->setLabel('HTTP Status');
  $select =& $field->getSelect();
  $select->setSize(1);
  $select->addOption('301 Moved Permanently',301);
  $select->addOption('302 Found',302);
  $select->addOption('303 See Other',303);
  $select->addOption('307 Temporary Redirect',307);

  $field = &$form->addTextField('to_clang',null,array('style'=>'display:none;'));
  $stored_clang = $form->getElement('Redirect','to_clang')->value;

  $form->addFieldset('Infos');

  if($func == 'edit')
  {
    $field = &$form->addReadOnlyField('createdate',null,array('class'=>'rex-form-read unix-date'));
    $field->setLabel('Erstellungsdatum');

    $field = &$form->addReadOnlyField('updatedate',null,array('class'=>'rex-form-read unix-date'));
    $field->setLabel('Änderungsdatum');
  }

  $expire = ($func == 'edit') ? null : time() + ($REX['ADDON']['rexseo']['settings']['default_redirect_expire']*24*60*60);

  $field = &$form->addTextField('expiredate',$expire,array('class'=>'rex-form-text unix-date-picker'));
  $field->setLabel('Verfallsdatum');

  if($func == 'edit')
  {
    $field = &$form->addReadOnlyField('creator');
    $field->setLabel('Ersteller');
  }


  if($func == 'edit')
  {
    $form->addParam('id', $id);
    $form->addHiddenField('updatedate', time());
  }

  if($func == 'add')
  {
    $form->addHiddenField('creator', $REX['USER']->getValue('login'));
    $form->addHiddenField('updatedate', time());
    $form->addHiddenField('createdate', time());
  }

  $form->show();

  echo '</div>';
}

?>
<script type="text/javascript">
<!--
jQuery(function($) {


  jQuery(document).ready(function() {

    // UNIX TIMESTRING TO HUMAN DATE
    $("span.rex-form-read.unix-date").each(function() {
      d = new Date($(this).html() * 1000);
      $(this).html(d.getDate()+"."+(d.getMonth()+1)+"."+d.getFullYear()+" - "+d.getHours()+":"+d.getMinutes()+"h");
    });

    // HIDE ACTUAL EXIRE DATE INPUT
    $("#rex_rexseo_redirects_Infos_expiredate").css("display","none");

    // CONVERT UNIX DATE FROM HIDDEN INPUT TO HUMAN DATE FOR DATEPICKER
    v = $("#rex_rexseo_redirects_Infos_expiredate").val();
    d = new Date(v * 1000);
    $(".unix-date-picker").append('<input type="text" style="width:100px" id="formated-date" value="'+d.getDate()+"."+(d.getMonth()+1)+"."+d.getFullYear()+'" /> (D.M.YYYY)');

  }); //jQuery(document).ready(function()


  // UPDATE HIDDEN INPUT ON USER CHANGE OF EXPIRE DATE
  $('input#formated-date').change(function(){
    u = $(this).val().split(".");
    if(u[0]<10) u[0]="0"+u[0];
    if(u[1]<10) u[1]="0"+u[1];
    d = new Date(u[2],u[1]-1,u[0]);
    $("#rex_rexseo_redirects_Infos_expiredate").val(d.getTime()/1000);
  });

  // VALIDATE NOT EMPTY ON SAVE
  $("#rex_rexseo_redirects_Redirect_save").click(function(){
    if($("#rex_rexseo_redirects_Redirect_from_url").val()==""){
      alert("Alte URL definieren!");
      return false;
    }
    if($("#LINK_1").val()==0){
      alert("Umleitungs URL definieren!");
      return false;
    }
  });

  // VALIDATE NOT EMPTY ON UPDATE
  $("#rex_rexseo_redirects_Redirect_apply").click(function(){
    if($("#rex_rexseo_redirects_Redirect_from_url").val()==""){
      alert("Alte URL definieren!");
      return false;
    }
    if($("#LINK_1").val()==0){
      alert("Umleitungs URL definieren!");
      return false;
    }
  });

}); // jQuery(function($)
//-->
</script>