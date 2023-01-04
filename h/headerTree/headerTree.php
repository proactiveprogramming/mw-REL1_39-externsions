<?php

require_once('spyc.php');

class HeaderTree {
    function apply($rules_file, $parser, &$text) {
        if('' == $text) return;
        $text_obj              = new TextTagIterator($text);
        $stack_current_nesting = array();

        if(file_exists($rules_file)) {
            HeaderOpeningTag::setAttrRules(Spyc::YAMLLoad($rules_file));
        }

        foreach($text_obj as $ii => $partial_text) {
            $tag = Tag::NewTag($partial_text);
            if(!is_null($tag)) {
                $tag->updateText($stack_current_nesting, $text_obj);
            }
        }

        foreach(array_reverse($stack_current_nesting) as $open_tag_name) {
            if(Tag::isContainerName($open_tag_name)) {
                $text_obj->closeContainer();
            }
        }

        $text = $text_obj->getText();
    }
}



/**
 * Used to iterate through all opening and closing tags of the HTML
 * and insert the containers' opening and closing tags.
 * 
 */
class TextTagIterator implements Iterator {
    private $containerOpeningTag = '<div %s>';
    private $containerClosingTag = '</div>';
    private $text;
    private $pointer;


    public function __construct($text) {
        $this->text    = $text;
        $this->pointer = 0;
    }


    public function getText() { return $this->text; }


    public function rewind()  { $this->pointer = 0; }

    
    public function current() { return substr($this->text, $this->pointer); }


    public function key()     { return $this->pointer; }


    public function valid()   { return false !== $this->pointer; }


    public function next() {
        if(false === $this->pointer) return $this->pointer;
        ++$this->pointer;
        $this->pointer = strpos($this->text, '<', $this->pointer);
        $this->current();
    }

    public function openContainer($attributes) {
        $this->insertAtPointer(sprintf($this->containerOpeningTag, $attributes));
    }

    public function closeContainer() {
        $this->insertAtPointer($this->containerClosingTag);
    }


    private function insertAtPointer($to_insert) {
        assert(is_string($to_insert));
        if(false === $this->pointer) {
            $this->text = $this->text . $to_insert;
            return;
        }
        $this->text =
            substr($this->text, 0, $this->pointer) .
            $to_insert . 
            substr($this->text, $this->pointer);
        $this->pointer += strlen($to_insert);
    }
}



abstract class Tag {
    static protected $alphaNum  = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    static protected $headers   = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');
    static private   $containerNamePrefix = 'HeaderTree:';

    private static function getTagName($partial_text) {
        $ii = 1;
        $tag_name = '';
        while(false !== strpos(Tag::$alphaNum, $partial_text[$ii])) {
            $tag_name .= $partial_text[$ii];
            ++$ii;
        }
        return $tag_name;
    }


    public static function NewTag($partial_text) {
        if(false !== strpos(self::$alphaNum, $partial_text[1])) {
            $tag_name = self::getTagName($partial_text);

            return in_array($tag_name, self::$headers) ? 
                new HeaderOpeningTag($tag_name) :
                new OpeningTag($tag_name) ;

        } else if($partial_text[1] == '/') {
            return new ClosingTag(self::getTagName(substr($partial_text, 1)));
        }
    }


    public static function isContainerName($tag_name) {
        return 0 === strpos($tag_name, self::$containerNamePrefix);
    }

    protected $tagName;


    protected function __construct($tag_name) {
        $this->tagName = $tag_name;
    }


    protected function makeContainerName($header) {
        return self::$containerNamePrefix . $header;
    }


    abstract public function updateText(array &$stack, TextTagIterator $text_obj);
}



class OpeningTag extends Tag {
    public function updateText(array &$stack, TextTagIterator $text_obj) {
        $stack[] = $this->tagName;
    }
}



class HeaderOpeningTag extends OpeningTag {
    private static $attrRules           = array();
    private static $ruleMatchingCounter = array();


    public static function setAttrRules(array $attr_rules) {
        self::$attrRules = $attr_rules;
    }

    private function extractTitleText($partial_text) {
        $end_opening_tag = 1+strpos($partial_text, '>');
        $title_text = substr($partial_text, 
                             $end_opening_tag,
                             strpos($partial_text, "</{$this->tagName}>") - $end_opening_tag);
        $title_text = preg_replace('/<[\/'.Tag::$alphaNum.']+[^<]*>/', '', $title_text);
        $title_text = preg_replace('/\[\s*edit\s*\]/', '', $title_text);
        return trim($title_text);
    }

    private function makeAttr($partial_text) {
        $classes         = array('headerTree', 'headerTree-'.$this->tagName);
        $attrs           = array();
        $title_text      = $this->extractTitleText($partial_text);

        foreach(self::$attrRules as $rule_name => $rule) {
            $is_matched = 
                (array_key_exists('is',    $rule) && trim($rule['is']) == trim($title_text)) ||
                (array_key_exists('match', $rule) && preg_match("/{$rule['match']}/", trim($title_text), $matches));
            if($is_matched) {
                if(!array_key_exists($rule_name, self::$ruleMatchingCounter)) self::$ruleMatchingCounter[$rule_name] = 0;
                self::$ruleMatchingCounter[$rule_name]++;
                foreach($rule['attr'] as $name => $value) {
                    $value = str_replace('[COUNTER]', self::$ruleMatchingCounter[$rule_name], $value);
                    if(array_key_exists('match', $rule)) {
                        $value = preg_replace("/{$rule['match']}/", $value, $title_text);
                    }
                    if('class' == $name) {
                        $classes[] = $value;
                    } else {
                        $attrs[$name]   = $value;
                    }
                }
            }
        }

        $attrs['class'] = count($classes) ? implode(' ', $classes) : '';
        $attrs_text = array();
        foreach($attrs as $name => $value) {
            $attrs_text[] = "$name=\"$value\"";
        }

        return implode(' ', $attrs_text);
    }


    public function updateText(array &$stack, TextTagIterator $text_obj) {
        $container_tag_name = $this->makeContainerName($this->tagName);

        while(count($stack) &&
              Tag::isContainerName($stack[count($stack)-1]) &&
              $container_tag_name <= $stack[count($stack)-1]) {
            array_pop($stack);
            $text_obj->closeContainer();
        }

        $attributes = $this->makeAttr($text_obj->current());
        $text_obj->openContainer($attributes);
        $stack[] = $container_tag_name;

        parent::updateText($stack, $text_obj);
    }
}



class ClosingTag extends Tag {
    public function updateText(array &$stack, TextTagIterator $text_obj) {
        if(!in_array($this->tagName, $stack)) return;
        while($stack[count($stack)-1] != $this->tagName) {
            $tag_name = array_pop($stack);
            if(Tag::isContainerName($tag_name)) {
                $text_obj->closeContainer();
            }
        }
        array_pop($stack);
    }
}

?>