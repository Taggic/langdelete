<?php
/**
 * Delete unnecessary languages -> administration function
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Taggic <taggic@t-online.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();


/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_langdelete extends DokuWiki_Admin_Plugin {

    const DEFAULT_LANG = 'en'; // this must be kept

    /**
     * return sort order for position in admin menu
     */
    function getMenuSort() {
        return 20;
    }

    /**
     * handle user request
     *
     * Initializes internal vars and handles modifications
     *
     * @author Taggic <taggic@t-online.de>
     */
    function handle() {
        global $ID;
    }

    /**
     * langdelete Output function
     *
     * print a table with all found lanuage folders
     *
     * @author  Taggic <taggic@t-online.de>
     */
    function html() {
        global $ID;
        global $conf;                   // access DW configuration array

        // langdelete__intro
        echo $this->locale_xhtml('intro');

        // input guidance
        echo '<a name="langdelete_inputbox"></a>'.NL;
        echo $this->locale_xhtml('guide');
        echo '<br />'.NL;
        // input form
        $this->_html_form();
        echo '<br />'.NL;

        $lang_keep = $_REQUEST['langdelete_w'];
        $dryrun = $_REQUEST['dryrun'];
        // language given?
        if (!empty($lang_keep)) {

            echo '<h2>'.$this->getLang('h2_output').'</h2>'.NL;

            if ($dryrun==true) {
                msg($this->getLang('langdelete_willmsg'), 2);
            } else {
                msg($this->getLang('langdelete_delmsg'), 0);
            }
            echo '<br />'.NL;

            $arr_langs = explode(',', $lang_keep);
            $arr_langs[] = self::DEFAULT_LANG; // add 'en'
            $arr_langs[] = $conf['lang'];      // add current lang
            // print_r($arr_langs);
            $lang_keep = array_unique($arr_langs);

            echo '<div class="langdelete__result">';
            $this->_list_language_dirs(DOKU_INC.'inc', 0, $lang_keep, $dryrun);
            $this->_list_language_dirs(DOKU_INC.'lib', 0, $lang_keep, $dryrun);
            echo '</div>';

            if ($dryrun==true) {
                echo '<br />'.NL;
                msg($this->getLang('langdelete_attention'), 2);
                echo '<br />'.NL;
                echo '<a href="#langdelete_inputbox">'.$this->getLang('backto_inputbox').'</a>'.NL;
            }
        }
    }

    /**
     * Display the form with input control to let the user specify,
     * which languages to be kept beside en
     *
     * @author  Taggic <taggic@t-online.de>
     */
    function _html_form(){
        global $ID, $conf;

        echo '<div id="langdelete__form">'.NL;
        echo '<form action="'.wl($ID).'" method="post">';
        echo     '<input type="hidden" name="do" value="admin" />'.NL;
        echo     '<input type="hidden" name="page" value="langdelete" />'.NL;
        echo     '<input type="hidden" name="sectok" value="'.getSecurityToken().'" />'.NL;

        echo     '<fieldset class="langdelete__fieldset"><legend>'.$this->getLang('i_legend').'</legend>'.NL;

        echo         '<label class="formTitle">'.$this->getLang('i_using').':</label>';
        echo         '<div class="box">'.$conf['lang'].'</div>'.NL;

        echo         '<label class="formTitle" for="langdelete_w">'.$this->getLang('i_shouldkeep').':</label>';
        echo         '<input type="text" name="langdelete_w" class="edit" value="'.hsc($_REQUEST['langdelete_w']).'" />'.NL;

        echo         '<label class="formTitle" for="option">'.$this->getLang('i_runoption').':</label>';
        echo         '<div class="box">'.NL;
        echo             '<input type="checkbox" name="dryrun" checked="checked" /> ';
        echo             '<label for "dryrun">'.$this->getLang('i_dryrun').'</label>'.NL;
        echo         '</div>'.NL;

        echo         '<input type="submit" value="'.$this->getLang('btn_start').'" class="button"/>'.NL;

        echo     '</fieldset>'.NL;
        echo '</form>'.NL;
        echo '</div>'.NL;
    }

  /**
   * This function will read the full structure of a directory. 
   * It's recursive becuase it doesn't stop with the one directory, 
   * it just keeps going through all of the directories in the folder you specify.
   */
    function _list_language_dirs($path, $level,$lang_keep,$dryrun){
        // misleading variable $file was replaced by $dir due to foreach ist searching directories only here  
        // Directories to ignore when listing output. Many hosts 
        // will deny PHP access to the cgi-bin.
        $ignore = array( 'cgi-bin', '.', '..' );
        // Open the directory to the handle $dh
        $dh = @opendir( $path );

        // Loop through the directory
        while( false !== ($dir = readdir($dh)) ){
            if( !in_array( $dir, $ignore ) ){
                // Check that this file is not to be ignored
                if( is_dir( "$path/$dir" ) ){
                    // Its a directory, so we need to keep reading down...
                    $cFlag = false;
                    foreach ($lang_keep as $f) {
                        $tst  = strtoupper(substr("$path/$dir",strlen("$path/$dir")-strlen("/lang/".trim($f))));
                        // do not delete the audio folders within language directories to be kept (e.g. captcha/en/audio)
                        $tst2 = strtoupper(substr("$path/$dir",strlen("$path/$dir")-strlen("/lang/".trim($f)."/audio")));       
                        if (($tst === strtoupper ("/lang/".trim($f))) || ($tst2 === strtoupper ("/lang/".trim($f)."/audio"))) {
                            $cFlag = true;
                            break;
                        }
                    }

                    if ((stripos("$path/$dir",'lang/')>0) && ($cFlag == false)) {
                        $dir = $path.'/'.$dir;
                        if ($dryrun==true) {
                            echo '<strong>'.substr($dir,strlen(DOKU_INC),strlen($dir)-strlen(DOKU_INC)).'</strong><br />';
                        } else {
                            // now delete the lanuage sub-folder
                            $this->rrmdir($dir);
                        }
                    }  else {
                        // Re-call this same function but on a new directory.
                        // this is what makes function recursive.
                        $this->_list_language_dirs( "$path/$dir", ($level+1), $lang_keep,$dryrun );
                    }
                }
            }
        }
        // Close the directory handle
        closedir( $dh );
    }

    /**
     * This function will delete all folders and files which are in the specified directory.
     * This is necessary due to only empty directories can be deleted.
     */
    function rrmdir($dir) {
        // replace "//" in $dir if existing
        $dir = str_replace('//', '/', $dir);

        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') {
                        $this->rrmdir($dir.'/'.$object);
                        echo '<strong>'.$dir.'/'.$object.' -> empty directory removed</strong><br />';
                    } else {
                       chmod($dir.'/'.$object, 0755);
                       $result = @unlink($dir.'/'.$object);
                       if($result === true) echo $dir.'/'.$object.' -> file deleted<br />';
                       else echo $dir.'/'.$object.'<span style="color:red;"><b> -> file not deleted</b></span><br />';
                    }
                }
            }
            reset($objects);
            @rmdir($dir); 
        }
    }
}
