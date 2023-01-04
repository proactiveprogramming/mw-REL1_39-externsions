<?php

class TestOfRuleExact extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>my special title</h1>
         <p>paragraph 1</p>
         <h1>my other title</h1>
         <p>paragraph 2</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1 special"><h1>my special title</h1>
         <p>paragraph 1</p>
         </div><div class="headerTree headerTree-h1"><h1>my other title</h1>
         <p>paragraph 2</p></div>';
    protected $rules = 'basic.yml';
}


class TestOfRulePattern extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>Question 2</h1>
         <p>paragraph 1</p>
         <h1>my other title</h1>
         <p>paragraph 2</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1 question"><h1>Question 2</h1>
         <p>paragraph 1</p>
         </div><div class="headerTree headerTree-h1"><h1>my other title</h1>
         <p>paragraph 2</p></div>';
    protected $rules = 'basic.yml';
}

class TestOfRuleCounter extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>my title with id</h1>
         <p>paragraph 1</p>
         <h1>my title with id</h1>
         <p>paragraph 2</p>';
    protected $expected = 
        '<div id="with-id-1" class="headerTree headerTree-h1"><h1>my title with id</h1>
         <p>paragraph 1</p>
         </div><div id="with-id-2" class="headerTree headerTree-h1"><h1>my title with id</h1>
         <p>paragraph 2</p></div>';
    protected $rules = 'basic.yml';
}


class TestOfRulePatternFragment extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>Code blue</h1>
         <p>paragraph 1</p>
         <h1>Code cyan</h1>
         <p>paragraph 2</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1 code-blue"><h1>Code blue</h1>
         <p>paragraph 1</p>
         </div><div class="headerTree headerTree-h1 code-cyan"><h1>Code cyan</h1>
         <p>paragraph 2</p></div>';
    protected $rules = 'basic.yml';
}


?>