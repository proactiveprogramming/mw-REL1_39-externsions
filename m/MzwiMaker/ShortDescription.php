<?php

/*
 * Copyright (c) 2022 Sergei Chekanov
 *
 * This script is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */


class ShortDescription
{
    /** Original MediaWiki record. */
    private $record_wiki = '';
    private $record_txt = '';

    /**
     * Constructor.
     * @param  string $wiki  Wiki Text
     * @param  string $txt plain text
     */
    public function __construct($record_wiki, $record_txt)
    {
        $this->record_wiki = $record_wiki;
        $this->record_txt = $record_txt;

    }




    /**
     * Get  first sentance . 
     *
     * @param  string $txt plain text 
     * @return string first long sentance 
     */

     protected function get_first_sentence($string) {
    
     $array = preg_split("/\r\n|\n|\r/", $string);
     // split lines on long chunks with more than 5 words
     // No more than 30 lines 
     $xsum=""; $n=0;
     foreach ($array as &$value) {
	     if (str_word_count($value, 0)>4) {
		     $xsum=$xsum." ". trim($value);
                     $n=$n+1; 
                     if ($n>30) break;
	     } }

    $xsum=trim($xsum);
    //print("OK=".$xsum);

    // split into sentances
    $sentences = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $xsum);
    //print_r($sentences);

    //take a sentance with at least 3 words
    $xsum=""; 
    foreach ($sentences as &$value) {
             if (str_word_count($value, 0)>3) {
                     $xsum=$xsum." ". trim($value);
                     break;
             } }

     return trim($xsum);
}

    // trim and remove full dot.
    protected function mytrim($string){
        $string=trim($string);
	$string = rtrim($string,'.');
	return trim($string);
    }

    /**
     * Get short description of the article.
     *
     * @return string short description
     */

    public function getDescription() {


	$wiki=$this->record_wiki;
	$txt=$this->record_txt;
   
	$description="";

        // EnHub style
        if (preg_match('/{{abstract\|(.*?)}}/i', $wiki, $match) == 1) {
                  $description=$match[1];
         }
        if (str_word_count($description, 0)>3) return $this->mytrim($description);

        // Wikipedia style
        if (preg_match('/{{short description\|(.*?)}}/i', $wiki, $match) == 1) {
                $description=$match[1];
        }
        if (str_word_count($description, 0)>3) return $this->mytrim($description); 


       // if nothing is found in templates, use plain text.  
       $description=$this->get_first_sentence($txt);

	return $this->mytrim($description); 

}


} // end class



// some debugging
//$wiki="Test {{Abstract|EncycloReader is a  web application designed to search multiple online encyclopedias at once}}
//'''EncycloReader''' is a web application designed to search multiple online encyclopedias at once and read articles in a unified representation  {{Author|S.V.Chekanov}}";
//$txt="{shs = ss}\n || \n Test. This  is a  web application designed to search multiple online encyclopedias at once.
//EncycloReader is a web application designed to search multiple online encyclopedias at once and read articles in a unified representation";
//$DESC = new ShortDescription($wiki, $txt);
//print($DESC->getDescription());
//print_r(get_first_sentence($txt));


