<?php

require_once('../headerTree.php');


class TestOfHeaderTreeSimple extends UnitTestCase {
    protected $input = 
        '<div>
         <h1>title 1</h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p>
         <h1>title 1</h1>
         <p>paragraph 3</p>
         <p>paragraph 4</p>
         </div>';
    protected $expected =
        '<div>
         <div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p>
         </div><div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 3</p>
         <p>paragraph 4</p>
         </div></div>';
    protected $rules = null;

    function testSimple() {
        $h     = new HeaderTree();
        $input = $this->input;
        $h->apply($this->rules, null, $input);
        $this->assertEqual($input, $this->expected, 
                           "<!-- DISPLAY PASS -->
                            <div class='headerTree-example'>
                            <div class='given'><h2 style='text-align: center'>Given</h2>{$this->input}</div>
                            <div class='expected'><h2 style='text-align: center'>Expected</h2>{$this->expected}</div>
                            <div class='received'><h2 style='text-align: center'>Received</h2>{$input}</div>
                            </div>");
    }
}

class TestOfHeaderTreeFragment extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>title 1</h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p>
         <h1>title 1</h1>
         <p>paragraph 3</p>
         <p>paragraph 4</p>';
    protected $expected =
        '<div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p>
         </div><div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 3</p>
         <p>paragraph 4</p></div>';
}


class TestOfHeaderTreeTree extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>title 1</h1>
         <p>paragraph 1</p>
         <h2>title 2</h2>
         <p>paragraph 2</p>
         <h1>title 1</h1>
         <p>paragraph 3</p>';
    protected $expected =
        '<div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 1</p>
         <div class="headerTree headerTree-h2"><h2>title 2</h2>
         <p>paragraph 2</p>
         </div></div><div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 3</p></div>';
}


class TestOfHeaderTreeTree2 extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>title 1</h1>
         <p>paragraph 1</p>
         <h2>title 2</h2>
         <p>paragraph 2</p>
         <h3>title 3</h3>
         <p>paragraph 3</p>
         <h4>title 4</h4>
         <h2>title 2</h2>
         <p>paragraph 4</p>
         <h1>title 1</h1>
         <p>paragraph 5</p>';
    protected $expected =
        '<div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 1</p>
         <div class="headerTree headerTree-h2"><h2>title 2</h2>
         <p>paragraph 2</p>
         <div class="headerTree headerTree-h3"><h3>title 3</h3>
         <p>paragraph 3</p>
         <div class="headerTree headerTree-h4"><h4>title 4</h4>
         </div></div></div><div class="headerTree headerTree-h2"><h2>title 2</h2>
         <p>paragraph 4</p>
         </div></div><div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 5</p></div>';
}

?>