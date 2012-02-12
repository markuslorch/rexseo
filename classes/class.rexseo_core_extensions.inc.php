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

class rex_var_link_exender extends rex_var_link
{
  function getMultilangLinkButton($id, $article_id, $category = '')
  {
    global $REX, $I18N;

    $art_name = '';
    $clang = '';
    $art = OOArticle :: getArticleById($article_id);

    // Falls ein Artikel vorausgewählt ist, dessen Namen anzeigen und beim öffnen der Linkmap dessen Kategorie anzeigen
    if (OOArticle :: isValid($art))
    {
      $art_name = $art->getName();
      $category = $art->getCategoryId();
    }

    $open_params = '&clang=' . $REX['CUR_CLANG'];
    if ($category != '')
      $open_params .= '&category_id=' . $category;

    $open_class   = 'rex-icon-file-open rex-icon-file-open-inactive';
    $delete_class = 'rex-icon-file-delete rex-icon-file-delete-inactive';
    $open_func    = '';
    $delete_func  = '';
    if ($REX['USER']->hasStructurePerm())
    {
      $open_class   = 'rex-icon-file-open';
      $delete_class = 'rex-icon-file-delete';
      $lang_links = '';
      foreach($REX['CLANG'] as $clang=>$name)
      {
        $lang_links .= '  <a title="Link auswählen" clang="'.$clang.'" onclick="openLinkMap(\'LINK_'.$id.'\', \'&amp;clang='.$clang.'&amp;category_id='.$category.'\');return false;" class="rex-icon-file-open open-clang-linkmap" href="#" '. rex_tabindex() .'>'.$clang.'</a>'.PHP_EOL;
      }
      $delete_func  = 'deleteREXLink(' . $id . ');';
    }

    $media = '
  <div class="rex-widget">
    <div class="rex-widget-link">
      <p class="rex-widget-field">
        <input type="hidden" name="LINK[' . $id . ']" id="LINK_' . $id . '" value="'. $article_id .'" />
        <input type="text" size="30" name="LINK_NAME[' . $id . ']" value="' . htmlspecialchars($art_name) . '" id="LINK_' . $id . '_NAME" readonly="readonly" />
      </p>
       <p class="rex-widget-icons rex-widget-1col">
        <span class="rex-widget-column rex-widget-column-first">
          <span style="float:left;margin-top:3px;">clang:</span>
          '.$lang_links.'
          <a href="#" class="'. $delete_class .'" onclick="'. $delete_func .'return false;" title="'. $I18N->msg('var_link_delete') .'"'. rex_tabindex() .'></a>
        </span>
      </p>
    </div>
  </div>
  <div class="rex-clearer"></div>';

    return $media;
  }


} // end class


class rex_form_widget_multilanglinkmap_element extends rex_form_element
{
  var $category_id = 0;

  // 1. Parameter nicht genutzt, muss aber hier stehen,
  // wg einheitlicher Konstrukturparameter
  function rex_form_widget_multilanglinkmap_element($tag = '', &$table, $attributes = array())
  {
    parent::rex_form_element('', $table, $attributes);
  }

  function setCategoryId($category_id)
  {
    $this->category_id = $category_id;
  }

  function formatElement()
  {
    static $widget_counter = 1;

    $html = rex_var_link_exender::getMultilangLinkButton($widget_counter, $this->getValue(), $this->category_id);
    $html = str_replace('LINK['. $widget_counter .']', $this->getAttribute('name'), $html);

    $widget_counter++;
    return $html;
  }
} // end class


class rexseo_rex_form extends rex_form
{
  /**
   * Fuegt dem Formular ein Feld hinzu mit dem die Struktur-Verwaltung angebunden werden kann.
   * Es kann nur ein Element aus der Struktur eingefuegt werden.
   */
  /*public*/ function &addMultilangLinkmapField($name, $value = null, $attributes = array())
  {
    $attributes['internal::fieldClass'] = 'rex_form_widget_multilanglinkmap_element';
    $field =& $this->addField('', $name, $value, $attributes, true);
    return $field;
  }
} // end class