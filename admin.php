<?php
/**
 * Delete unnecessary languages -> administration function
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Taggic <taggic@t-online.de>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/** Implicit data type:
 *
 * ^Lang is an array that looks like the following
 * { "core": [ $lang... ],
 *      "templates": [ $tpl_name: [ $lang... ], ... ],
 *      "plugins": [ $plugin_name: [ $lang... ], ... ]
 * }
 *     where $lang is a DokuWiki language code
 *           $tpl_name is the template name
 *           $plugin_name is the plugin name
 *  The $lang arrays are zero-indexed
 */

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_langdelete extends DokuWiki_Admin_Plugin {

    /** Fallback language */
    const DEFAULT_LANG = 'en';
    /** data variable for ->handle() => ->html() */
    private $d;

    /** return sort order for position in admin menu */
    function getMenuSort() { return 20; }

    /** Called when dispatching the DokuWiki action;
     * Puts the required data for ->html() in $->d */
    function handle() {
        $d =& $this->d;
        $d = new stdClass; // reset

        $d->submit = isset($_REQUEST['submit']);
        $submit =& $d->submit;

        $d->langs = $this->list_languages();
        $langs =& $d->langs;
        // $u_langs is in alphabetical (?) order because directory listing
        $d->u_langs = $this->lang_unique($langs);
        $u_langs =& $d->u_langs;
        $lang_keep[] = self::DEFAULT_LANG; // add 'en', the fallback
        $lang_keep[] = $conf['lang'];      // add current lang

        if ($submit) {
            $valid =& $d->valid;
            $valid = True;
            if (!checkSecurityToken()) {
                $valid = False;
                return;
            }
            /* Process form */

            /* Grab form data */
            $lang_str = $_REQUEST['langdelete_w'];
            $d->dryrun = $_REQUEST['dryrun'];

            /* Add form data to languages to keep */
            if (strlen ($lang_str) > 0) {
                $lang_keep = array_merge ($lang_keep, explode(',', $lang_str));
            }
        } else {
            // Keep every language on first run
            $lang_keep = $u_langs;
        }

        $lang_keep = array_values(array_filter(array_unique($lang_keep)));
        $d->lang_keep =& $lang_keep;

        if ($submit) {
            /* Grab checkboxes */
            $d->shortlang = array_keys ($_REQUEST['shortlist']);
            $shortlang =& $d->shortlang;

            /* Prevent discrepancy between shortlist and text form */
            if (array_diff ($lang_keep, $shortlang)
                || array_diff ($shortlang, $lang_keep))
            {
                $d->discrepancy = True;
            }

            $d->langs_to_delete = $this->_filter_out_lang ($langs, $lang_keep);
        } else {
            // Keep every language on first run
            $d->shortlang = $u_langs;
        }
    }

    /**
     * langdelete Output function
     *
     * Prints a table with all found language folders.
     * HTML and data processing are done here at the same time
     *
     * @author  Taggic <taggic@t-online.de>
     */
    function html() {
        global $conf; // access DW configuration array
        $d =& $this->d; // from ->handle()

        // langdelete__intro
        echo $this->locale_xhtml('intro');

        // input anchor
        echo '<a name="langdelete_inputbox"></a>'.NL;
        echo $this->locale_xhtml('guide');
        // input form
        $this->_html_form($d);


        $langs = $this->list_languages();
        $u_langs = $this->lang_unique($langs);


        /* Switch on form submission state */
        if (!$d->submit) {
            /* Show available languages */
            echo '<section class="langdelete__text">';
            echo $this->getLang('available_langs');
            $this->print_shortlist ($d);
            $this->html_print_langs($d->langs);
            echo '</p>';
            echo '</section>';

        } else {
            /* Process form */

            /* Check token */
            if (!$d->valid) {
                echo "<p>Invalid security token</p>";
                return;
            }

            if ($d->discrepancy) {
                msg($this->getLang('discrepancy_warn'), 2);
            }

            echo '<h2>'.$this->getLang('h2_output').'</h2>'.NL;

            if ($d->dryrun) {
                /* Display what will be deleted */
                msg($this->getLang('langdelete_willmsg'), 2);
                echo '<section class="langdelete__text to-delete">';
                echo $this->getLang('available_langs');
                $this->print_shortlist ($d);
                $this->html_print_langs($d->langs, $d->lang_keep);
                echo '</section>';

                msg($this->getLang('langdelete_attention'), 2);
                echo '<a href="#langdelete_inputbox">'.$this->getLang('backto_inputbox').'</a>'.NL;

            } else {
                /* Delete and report what was deleted */
                msg($this->getLang('langdelete_delmsg'), 0);

                echo '<section class="langdelete__text to-delete">';
                $this->html_print_langs($d->langs_to_delete);
                echo '</section>';

                echo '<pre>';
                $this->remove_langs($d->langs_to_delete);
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
    private function _html_form (&$d) {
        global $ID, $conf;

        echo '<form id="langdelete__form" action="'.wl($ID).'" method="post">';
        echo     '<input type="hidden" name="do" value="admin" />'.NL;
        echo     '<input type="hidden" name="page" value="'.$this->getPluginName().'" />'.NL;
        formSecurityToken();

        echo     '<fieldset class="langdelete__fieldset"><legend>'.$this->getLang('i_legend').'</legend>'.NL;

        echo         '<label class="formTitle">'.$this->getLang('i_using').':</label>';
        echo         '<div class="box">'.$conf['lang'].'</div>'.NL;

        echo         '<label class="formTitle" for="langdelete_w">'.$this->getLang('i_shouldkeep').':</label>';
        echo         '<input type="text" name="langdelete_w" class="edit" value="'.hsc(implode(',', $d->lang_keep)).'" />'.NL;

        echo         '<label class="formTitle" for="option">'.$this->getLang('i_runoption').':</label>';
        echo         '<div class="box">'.NL;
        echo             '<input type="checkbox" name="dryrun" checked="checked" /> ';
        echo             '<label for="dryrun">'.$this->getLang('i_dryrun').'</label>'.NL;
        echo         '</div>'.NL;

        echo         '<button name="submit">'.$this->getLang('btn_start').'</button>'.NL;

        echo     '</fieldset>'.NL;
        echo '</form>'.NL;
    }

    /** Print the language shortlist and cross-out those not in $keep */
    function print_shortlist (&$d) {
        $shortlang =& $d->shortlang;

        echo '<ul id="langshortlist" class="languages">';
        # As the disabled input won't POST
        echo '<input type="hidden" name="shortlist['.self::DEFAULT_LANG.']"'
            .' form="langdelete__form" />';
        foreach ($d->u_langs as $l) {
            echo '<li'.(in_array ($l, $shortlang) ? ' class="enabled"' : '').'>';
            echo '<input type="checkbox" id="shortlang-'.$l.'" name="shortlist['.$l.']"'
                .' form="langdelete__form"'
                .(in_array($l, $shortlang) || $l == self::DEFAULT_LANG
                    ? ' checked'
                    : '')
                .($l == self::DEFAULT_LANG ? ' disabled' : '')
                .' />';
            echo '<label for="shortlang-'.$l.'">';
            if (in_array ($l, $shortlang) || $l == self::DEFAULT_LANG) {
                echo $l;
            } else {
                echo '<del>'.$l.'</del>';
            }
            echo '</label>';
            echo '</li>';
        }
        echo '</ul>';
    }


    /** Display the languages in $langs for each module as a HTML list;
     * Cross-out those not in $keep
     *
     * Signature: ^Lang, Array => () */
    private function html_print_langs ($langs, $keep = null) {
        /* Print language list, $langs being an array;
         * Cross out those not in $keep */
        $print_lang_li = function ($langs) use ($keep) {
            echo '<ul class="languages">';
            foreach ($langs as $val) {
                echo '<li val="'.$val.'"'
                    .(is_null($keep) || in_array ($val, $keep) ? ' class="enabled"' : '')
                    .'>';
                if (is_null($keep) || in_array ($val, $keep)) {
                    echo $val;
                } else {
                    echo '<del>'.$val.'</del>';
                }
                echo '</li>';
            }
            echo '</ul>';
        };


        echo '<ul id="langlonglist">';

        // Core
        echo '<li><span class="module">'.$this->getLang('dokuwiki_core').'</span>';
        $print_lang_li ($langs['core']);
        echo '</li>';

        // Templates
        echo '<li>'.$this->getLang('templates');
        echo     '<ul>';
        foreach ($langs['templates'] as $name => $l) {
            echo '<li><span class="module">'.$name.':</span>';
            $print_lang_li ($l);
            echo '</li>';
        }
        echo     '</ul>';
        echo '</li>';

        // Plugins
        echo '<li>'.$this->getLang('plugins');
        echo     '<ul>';
        foreach ($langs['plugins'] as $name => $l) {
            echo '<li><span class="module">'.$name.':</span>';
            $print_lang_li ($l);
            echo '</li>';
        }
        echo     '</ul>';
        echo '</li>';

        echo '</ul>';
    }

    /** Returns the available languages for each module
     * (core, template or plugin)
     *
     * Signature: () => ^Lang
     */
    private function list_languages () {
        // See https://www.dokuwiki.org/devel:localization

        /* Returns the subfolders of $dir as an array */
        $dir_subfolders = function ($dir) {
            $sub = scandir($dir);
            $sub = array_filter ($sub, function ($e) use ($dir) {
                return is_dir ("$dir/$e")
                       && !in_array ($e, array('.', '..'));
            } );
            return $sub;
        };

        $list_templates = function () use ($dir_subfolders) {
            return $dir_subfolders (DOKU_INC."lib/tpl");
        };

        /* Return an array of languages available for the module
         * (core, template or plugin) given its $root directory */
        $list_langs = function ($root) use ($dir_subfolders) {
            $dir = "$root/lang";
            if (!is_dir ($dir)) return;

            return $dir_subfolders ($dir);
        };

        global $plugin_controller;
        $plugins = $plugin_controller->getList();
        $templates = $list_templates();

        return array(
            "core" => $list_langs (DOKU_INC."inc"),
            "templates" => array_combine ($templates,
                array_map ($list_langs,
                    array_prefix ($templates, DOKU_INC."lib/tpl/"))),
            "plugins" => array_combine ($plugins,
                array_map ($list_langs,
                    array_prefix ($plugins, DOKU_PLUGIN)))
        );
    }

    /** Remove $lang_keep from the module languages $e
     *
     * Signature: ^Lang, Array => ^Lang */
    private function _filter_out_lang ($e, $lang_keep) {
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

    /** Return an array of the languages in $l
     *
     * Signature: ^Lang => Array */
    private function lang_unique ($l) {
        foreach ($l['core'] as $lang) {
            $count[$lang]++;
        }
        foreach ($l['templates'] as $tpl => $arr) {
            foreach ($arr as $lang) {
                $count[$lang]++;
            }
        }
        foreach ($l['plugins'] as $plug => $arr) {
            foreach ($arr as $lang) {
                $count[$lang]++;
            }
        }

        return array_keys ($count);
    }

    /** Delete the languages from the modules as specified by $langs
     *
     * Signature: ^Lang => () */
    private function remove_langs($langs) {
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

    /** Recursive file removal of $path with reporting */
    private function rrm ($path) {
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

/** Returns an array with each element of $arr prefixed with $prefix */
function array_prefix ($arr, $prefix) {
    return array_map (
        function ($p) use ($prefix) { return $prefix.$p; },
        $arr);
}
