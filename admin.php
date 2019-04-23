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

    const DEFAULT_LANG = 'en'; // fallback language

    /** return sort order for position in admin menu */
    function getMenuSort() { return 20; }

    /**
     * langdelete Output function
     *
     * print a table with all found language folders
     *
     * @author  Taggic <taggic@t-online.de>
     */
    function html() {
        global $conf;                   // access DW configuration array

        // langdelete__intro
        echo $this->locale_xhtml('intro');

        // input anchor
        echo '<a name="langdelete_inputbox"></a>'.NL;
        echo $this->locale_xhtml('guide');
        // input form
        $this->_html_form();

        $langs = $this->list_languages();

        if (!array_key_exists ('submit', $_REQUEST)) {
            /* Show available languages */
            echo '<section class="langdelete__text">';
            echo '<p>Available languages:';
            $this->html_print_langs($langs);
            echo '</p>';
            echo '</section>';

        } else {
			/* Process form */

			/* Check token */
			if (!checkSecurityToken()) {
				echo "<p> Invalid security token</p>";
				return;
			}

			/* Grab form data */
			$lang_str = $_REQUEST['langdelete_w'];
			$dryrun = $_REQUEST['dryrun'];

            /* Figure out what languages to keep */
            if (strlen ($lang_str) > 0) {
                $lang_keep = explode(',', $lang_str);
            }
            $lang_keep[] = self::DEFAULT_LANG; // add 'en', the fallback
            $lang_keep[] = $conf['lang'];      // add current lang
            $lang_keep = array_unique($lang_keep);

            $langs_to_delete = $this->_filter_out_lang ($langs, $lang_keep);

			/* Display text */
            echo '<h2>'.$this->getLang('h2_output').'</h2>'.NL;

            if ($dryrun) {
                /* Display what will be deleted */
                msg($this->getLang('langdelete_willmsg'), 2);

                echo '<section class="langdelete__text">';
                $this->html_print_langs($langs_to_delete);
                echo '</section>';
                
                msg($this->getLang('langdelete_attention'), 2);
                echo '<a href="#langdelete_inputbox">'.$this->getLang('backto_inputbox').'</a>'.NL;

            } else {
				/* Delete and report what was deleted */
                msg($this->getLang('langdelete_delmsg'), 0);

                echo '<section class="langdelete__text">';
                $this->html_print_langs($langs_to_delete);
                echo '</section>';

				echo '<pre>';
				$this->remove_langs($langs_to_delete);
				echo '</pre>';
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
        echo     '<input type="hidden" name="page" value="'.$this->getPluginName().'" />'.NL;
        formSecurityToken();

        echo     '<fieldset class="langdelete__fieldset"><legend>'.$this->getLang('i_legend').'</legend>'.NL;

        echo         '<label class="formTitle">'.$this->getLang('i_using').':</label>';
        echo         '<div class="box">'.$conf['lang'].'</div>'.NL;

        echo         '<label class="formTitle" for="langdelete_w">'.$this->getLang('i_shouldkeep').':</label>';
        echo         '<input type="text" name="langdelete_w" class="edit" value="'.hsc($_REQUEST['langdelete_w']).'" />'.NL;

        echo         '<label class="formTitle" for="option">'.$this->getLang('i_runoption').':</label>';
        echo         '<div class="box">'.NL;
        echo             '<input type="checkbox" name="dryrun" checked="checked" /> ';
        echo             '<label for="dryrun">'.$this->getLang('i_dryrun').'</label>'.NL;
        echo         '</div>'.NL;

        echo         '<button name="submit">'.$this->getLang('btn_start').'</button>'.NL;

        echo     '</fieldset>'.NL;
        echo '</form>'.NL;
        echo '</div>'.NL;
    }

    /** Display the languages in $langs for each module as a HTML list */
    function html_print_langs ($langs) {
        echo '<ul>';

        // Core
        echo '<li>'.$this->getLang('dokuwiki_core');
        echo     '<ul class="languages">';
        foreach ($langs['core'] as $val) {
            echo '<li>'.$val.'</li>';
        }
        echo     '</ul>';
        echo '</li>';

        // Templates
        echo '<li>'.$this->getLang('templates');
        echo     '<ul>';
        foreach ($langs['templates'] as $name => $l) {
            echo '<li>'.$name.':';
            echo     '<ul class="languages">';
            foreach ($l as $val) {
                echo '<li>'.$val.'</li>';
            }
            echo     '</ul>';
            echo '</li>';
        }
        echo     '</ul>';
        echo '</li>';

        // Plugins
        echo '<li>'.$this->getLang('plugins');
        echo     '<ul>';
        foreach ($langs['plugins'] as $name => $l) {
            echo '<li>'.$name.':';
            echo     '<ul class="languages">';
            foreach ($l as $val) {
                echo '<li>'.$val.'</li>';
            }
            echo     '</ul>';
            echo '</li>';
        }
        echo     '</ul>';
        echo '</li>';

        echo '</ul>';
    }

    function list_languages () {
        // See https://www.dokuwiki.org/devel:localization
        /** List subfolders of $dir */
        function dir_subfolders ($dir) {
            $sub = scandir($dir);
            $sub = array_filter ($sub, function ($e) use ($dir) {
                return is_dir ("$dir/$e")
                       && !in_array ($e, array('.', '..')); 
            } );
            return $sub;
        }

        function list_templates () {
            return dir_subfolders (DOKU_INC."lib/tpl");
        }

        function array_prefix ($arr, $prefix) {
            return array_map (
                function ($p) use ($prefix) { return $prefix.$p; },
                $arr);
        }

        /** List languages available for the module (core, template or plugin)
         * given its $root directory
         */
        function list_langs ($root) {
            $dir = "$root/lang";
            if (!is_dir ($dir)) return;

            return dir_subfolders ($dir);
        }

        global $plugin_controller;
        $plugins = $plugin_controller->getList();
        $templates = list_templates();

        $dirs = array(
            "core" => list_langs (DOKU_INC."inc"),
            "templates" => array_combine ($templates,
                array_map (list_langs,
                    array_prefix ($templates, DOKU_INC."lib/tpl/"))),
            "plugins" => array_combine ($plugins,
                array_map (list_langs,
                    array_prefix ($plugins, DOKU_PLUGIN)))
        );
        return $dirs;
    }

	/** Remove $lang_keep from &$e as return by $this->list_languages() */
	function _filter_out_lang (&$e, $lang_keep) {
		if (count ($e) > 0 && is_array (array_values($e)[0])) {
			foreach ($e as $k => $elt) {
				$out[$k] = $this->_filter_out_lang ($elt, $lang_keep);
			}
			return $out;

		} else {
			return array_filter ($e, function ($v) use ($lang_keep) {
				return !in_array ($v, $lang_keep);
			});
		}
	}

	/** Delete the languages from the modules as specified by $langs */
	function remove_langs($langs) {
		foreach ($langs['core'] as $l) {
			$this->rrm(DOKU_INC."inc/lang/$l");
		}

		foreach ($langs['templates'] as $tpl => $arr) {
			foreach ($arr as $l) {
				$this->rrm(DOKU_INC."lib/tpl/$tpl/lang/$l");
			}
		}

		foreach ($langs['plugins'] as $plug => $arr) {
			foreach ($arr as $l) {
				$this->rrm(DOKU_INC."lib/plugins/$plug/lang/$l");
			}
		}
	}

    /** Recursive file removal with reporting */
    function rrm ($path) {
        if (is_dir ($path)) {
            $objects = scandir ($path);
            foreach ($objects as $object) {
                if (!in_array ($object, array('.', '..'))) {
                    $this->rrm("$path/$object");
                }
            }
            $sucess = @rmdir ($path);
            if (!$sucess) { echo "Failed to delete $path/\n"; }
			else echo "Delete $path\n";
        } else {
            $sucess = @unlink ($path);
            if (!$sucess) { echo "Failed to delete $path\n"; }
			else echo "Delete $path\n";
        }
    }
}
