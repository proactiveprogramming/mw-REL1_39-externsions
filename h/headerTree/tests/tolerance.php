<?php
class TestOfNestedHeaders extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>title 1</h1>
         <p>paragraph 1</p>
         <div>
           <h2>title 2</h2>
           <p>paragraph 2</p>
         </div>
         <p>paragraph 3</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 1</p>
         <div>
           <div class="headerTree headerTree-h2"><h2>title 2</h2>
           <p>paragraph 2</p>
         </div></div>
         <p>paragraph 3</p></div>';         
}

class TestOfRemoveEditLink extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1><span class="editsection">[<a href="..." title="...">edit</a>]</span> <span class="mw-headline">my special title</span></h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1 special"><h1><span class="editsection">[<a href="..." title="...">edit</a>]</span> <span class="mw-headline">my special title</span></h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p></div>';
}


class TestOfHeaderWithMarkup extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1><span class="stuff">my <strong>special</strong> title</span></h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1 special"><h1><span class="stuff">my <strong>special</strong> title</span></h1>
         <p>paragraph 1</p>
         <p>paragraph 2</p></div>';
}


/* Not most desirable outcome, but it is the best 
   I can do without using a real parser, such as TagSoup */
class TestOfUnclosedTags extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>title 1</h1>
         <p>paragraph 1
         <h1>title 1</h1>
         <p>paragraph 2';
    protected $expected = 
        '<div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 1
         <div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 2</div></div>';
}

class TestOfUnclosedHeader extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>title 1
         <p>paragraph 1</p>
         <h1>title 1</h1>
         <p>paragraph 2</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1"><h1>title 1
         <p>paragraph 1</p>
         <div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragraph 2</p></div></div>';
}

class TestOfLessThanSigns extends TestOfHeaderTreeSimple {
    protected $input = 
        '<h1>title < 1</h1>
         <p>paragraph 1</p>
         <h1>title 1</h1>
         <p>paragr <aph 2</p>';
    protected $expected = 
        '<div class="headerTree headerTree-h1"><h1>title < 1</h1>
         <p>paragraph 1</p>
         </div><div class="headerTree headerTree-h1"><h1>title 1</h1>
         <p>paragr <aph 2</p></div>';
}


?>