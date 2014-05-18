<?php
/**
 * Delete unused languages -> administration function
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
    /**
     * return some info
     */
    function getInfo(){
        return array(
            'author' => 'Taggic',
            'email'  => 'taggic@t-online.de',
            'date'   => '2011-11-25',
            'name'   => 'langdelete',
            'desc'   => 'Delete unused language files and folders to reduce space consumption',
            'url'    => 'http://dokuwiki.org/plugin:langdelete',
        );
    }

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

        echo '<div id="langdelete__intro">'.NL;
        echo $this->locale_xhtml('intro');
        echo '</div>'.NL;

        // inut guidance
        echo '<p>'.$this->getLang('i_choose').'</p>'.NL;

        // input form
        $this->_html_form();
        echo '<br />'.NL;

        $lang_keep = $_REQUEST['langdelete_w'];
        $dryrun = $_REQUEST['dryrun'];
        // language given?
        if (!empty($lang_keep)) {
            if ($dryrun==true) {
                msg($this->getLang('langdelete_willmsg'), 2);
            } else {
                msg($this->getLang('langdelete_delmsg'), 0);
            }
            echo '<br />'.NL;

            $lang_keep = $lang_keep.',en';
            $lang_keep = explode(',', $lang_keep);

            echo '<div class="level4">';
            $this->_list_language_dirs(DOKU_INC.'inc', 0, $lang_keep, $dryrun);
            $this->_list_language_dirs(DOKU_INC.'lib', 0, $lang_keep, $dryrun);
            echo '</div>';

            if ($dryrun==true) {
                echo '<br />'.NL;
                msg($this->getLang('langdelete_attention'), 2);
                echo '<br />'.NL;
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
        echo         '<input type="text" name="langdelete_w" class="edit" value="'.$_REQUEST['langdelete_w'].'" />'.NL;

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
        // Directories to ignore when listing output. Many hosts 
        // will deny PHP access to the cgi-bin.
        $ignore = array( 'cgi-bin', '.', '..' );
        // Open the directory to the handle $dh
        $dh = @opendir( $path );

        // Loop through the directory
        while( false !== ($file = readdir($dh)) ){
            if( !in_array( $file, $ignore ) ){
                // Check that this file is not to be ignored
                if( is_dir( "$path/$file" ) ){
                    // Its a directory, so we need to keep reading down...
                    $cFlag = false;
                    foreach ($lang_keep as $f) {
                        if (stripos("$path/$file",$f)>0) {
                            $cFlag = true;
                            break;
                        }
                    }
                    if ((stripos("$path/$file",'/lang/')>0) && ($cFlag == false)) {
                        $dir = $path.'/'.$file;
                        if ($dryrun==true) {
                            echo '<strong>'.substr($dir,strlen(DOKU_INC),strlen($dir)-strlen(DOKU_INC)).'</strong><br />';
                        } else {
                            // now delete the lanuage sub-folder
                            $this->rrmdir($dir);
                        }
                    }  else {
                        // Re-call this same function but on a new directory.
                        // this is what makes function recursive.
                        $this->_list_language_dirs( "$path/$file", ($level+1), $lang_keep,$dryrun );
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
                        echo '<strong>'.$dir.'/'.$object.' ->directory removed</strong><br />';
                    } else {
                       unlink($dir.'/'.$object);
                       echo $dir.'/'.$object.' -> file deleted<br />';
                    }
                }
            }
            reset($objects);
            rmdir($dir); 
        }
    }
}
