<?php
/**
 * indexnumber-Plugin: Create independent, referencable counters on a page
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Gabriel Birke <gb@birke-software.de>
 */


if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_indexnumber extends DokuWiki_Syntax_Plugin {

    protected $idxnumbers = array();

    protected $tag_stack;

    public function __construct(){
        $this->tag_stack = new SplStack();
    }

    /**
     * What about paragraphs?
     */
    function getPType(){
        return 'block';
    }

    /**
     * What kind of syntax are we?
     */
    function getType(){
        return 'container';
    }

    /**
     * Where to sort in?
     */
    function getSort(){
        return 200;
    }

    function getAllowedTypes() { return array('container', 'substition', 'protected', 'disabled', 'formatting', 'paragraphs'); }

    /**
     * Connect pattern to lexer
     */
    function connectTo($mode) {
        $this->Lexer->addEntryPattern('<idxnum .*?>',$mode,'plugin_indexnumber');
    }

    function postConnect() {
        $this->Lexer->addExitPattern('</idxnum>', 'plugin_indexnumber');
    }

    /**
     * Handle the match
     */
    function handle($match, $state, $pos, &$handler) {
        if($state == DOKU_LEXER_ENTER && preg_match('/<idxnum ([^#]+)(?:#(\d+)(.*))?>/', $match, $matches)) {
            error_log(var_export($matches, true));
            $idxId = trim($matches[1]);
            if(empty($this->idxnumbers[$idxId])) {
                $this->idxnumbers[$idxId] = 1;
            }
            else {
                $this->idxnumbers[$idxId]++;
            }
            $description = trim($matches[3], '"');
            if($matches[2] !== '') {
                $data = array(
                    'idxId'  => $idxId,
                    'number' => $this->idxnumbers[$idxId],
                    'ref'    => trim($matches[2], '#'),
                    'text'   => $description
                );
                trigger_event("PARSER_IDXNUM_OPEN", $data);
            }
            $tagData = array($state, $idxId, $this->idxnumbers[$idxId], $matches[2], $description);
            if($this->tag_stack->isEmpty()) {
                $this->tag_stack->push($tagData);
                return $tagData;
            }
        }
        elseif($state == DOKU_LEXER_EXIT) {
            if(!$this->tag_stack->isEmpty()) {
                $tagData = $this->tag_stack->pop();
                $tagData[0] = $state;
                return $tagData;
            }
        }
        elseif($state == DOKU_LEXER_UNMATCHED) {
            return array($state, $match);
        }

        // Ignore errors
        return array();

    }

    /**
     * Create output
     */
    function render($format, &$R, $data) {
        if($format != 'xhtml'){
            return false;
        }
        if($data[0] == DOKU_LEXER_ENTER) {
            $anchor = preg_replace('/[^a-z]/i', '_', $data[1]).'_'.$data[2];
            $R->doc .= '<div id="'.$anchor.'" class="idxnum_container">';
            return true;
        }
        elseif($data[0] == DOKU_LEXER_EXIT) {
            $R->doc .= '<p class="idxnum">'.$data[1].' '.$data[2].$data[4].'</p></div>';
            return true;
        }
        elseif($data[0] == DOKU_LEXER_UNMATCHED) {
            $R->doc .= $R->_xmlEntities($data[1]);
        }
        return false;
    }

}



