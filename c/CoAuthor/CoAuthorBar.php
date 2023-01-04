<?php 
/*
 * Copyright (c) 2010 University of Macau
 *
 * Licensed under the Educational Community License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License. You may
 * obtain a copy of the License at
 *
 * http://www.osedu.org/licenses/ECL-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an "AS IS"
 * BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
 * or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

global $wgRequest;

$widthHeight = 6;
$height = 14;
$color = array(50, 50, 240);


header("Content-type: image/png");

$percentage = $_REQUEST['percentage'];
$width  = max(1, $widthHeight * $percentage);

$image = imagecreate($width, $height);
imagecolorallocate($image, $color[0], $color[1], $color[2]);
imagepng($image);
imagedestroy($image);

?>
