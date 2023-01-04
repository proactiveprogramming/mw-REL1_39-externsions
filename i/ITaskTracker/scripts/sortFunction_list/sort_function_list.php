<?php
function curPageURL() {
 $pageURL = 'http'; 
 if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}


function removeUrlvalue($strselected,$strurl){

$lnurl=strlen($strurl);
$lnselected=strlen($strselected);
$strbegin="";
$strend="";
for($i=0;$i<$lnurl;$i++){
$strend=$strurl;
   if ($strurl[$i]=="&"){
        if ($strbegin=="" && ($i+$lnselected+1)<$lnurl){
                        if (substr($strurl,$i+1,$lnselected)==$strselected && substr($strurl,$i+$lnselected+1,1)=="="){
                $strbegin=substr($strurl,0,$i);

                }
                }
                elseif ($strbegin!="")
                {
                        $strend=substr($strurl,$i,($lnurl-($i)));
                        break;
                }

   }

}
return $strbegin.$strend;

}


//SORTING FUNCTION

//function for Numbering
function cmpString_Issue_num_ac($a, $b)
{
    return strcmp($a[num], $b[num]);
}
function cmpString_Issue_num_dc($a, $b)
{
    return (strcmp($a[num], $b[num]) * -1);
}

//function for title
function cmpString_Issue_title_ac($a, $b)
{
    return strcmp($a[title], $b[title]);
}
function cmpString_Issue_title_dc($a, $b)
{
    return (strcmp($a[title], $b[title]) * -1);
}

//function for start_date
function cmpDate_Issue_start_date_ac($a, $b)
{
   $start_date_a = date("Ymd", strtotime($a[start_date]));
   $start_date_b = date("Ymd", strtotime($b[start_date]));

//   echo "<br />*".$start_date_a . " - " . $start_date_b;

   if ($start_date_a == $start_date_b) {
        return 0;
   }

   return ($start_date_a > $start_date_b) ? 1 : -1;

}
function cmpDate_Issue_start_date_dc($a, $b)
{
   $start_date_a = date("Ymd", strtotime($a[start_date]));
   $start_date_b = date("Ymd", strtotime($b[start_date]));

  // echo "<br />*".$start_date_a . " - " . $start_date_b;

   if ($start_date_a == $start_date_b) {
        return 0;
   }

   return ($start_date_a > $start_date_b) ? -1 : 1;
}

//function for due_date
function cmpDate_Issue_due_date_ac($a, $b)
{
   $due_date_a = date("Ymd", strtotime($a[due_date]));
   $due_date_b = date("Ymd", strtotime($b[due_date]));

//   echo "<br />*".$due_date_a . " - " . $due_date_b;

   if ($due_date_a == $due_date_b) {
        return 0;
   }

   return ($due_date_a > $due_date_b) ? 1 : -1;
}
function cmpDate_Issue_due_date_dc($a, $b)
{
   $due_date_a = date("Ymd", strtotime($a[due_date]));
   $due_date_b = date("Ymd", strtotime($b[due_date]));

  // echo "<br />*".$due_date_a . " - " . $due_date_b;

   if ($due_date_a == $due_date_b) {
        return 0;
   }

   return ($due_date_a > $due_date_b) ? -1 : 1;
}
//function for perc_complete
function cmpInt_Issue_perc_complete_ac($a, $b)
{
   if ($a[perc_complete]=='')
        $a[perc_complete]=-1;
   if ($b[perc_complete]=='')
        $b[perc_complete]=-1;

   if ((int)($a[perc_complete]) == (int)($b[perc_complete])) {
        return 0;
   }

   return ((int)($a[perc_complete]) > (int)($b[perc_complete])) ? 1 : -1;
}
function cmpInt_Issue_perc_complete_dc($a, $b)
{
   if ($a[perc_complete]=='')
        $a[perc_complete]=-1;
   if ($b[perc_complete]=='')
        $b[perc_complete]=-1;

   if ((int)($a[perc_complete]) == (int)($b[perc_complete])) {
        return 0;
   }

   return ((int)($a[perc_complete]) > (int)($b[perc_complete])) ? -1 : 1;
}
//function for targ_accom
function cmpDate_Issue_targ_accom_ac($a, $b)
{
   $targ_accom_a = date("Ymd", strtotime($a[targ_accom]));
   $targ_accom_b = date("Ymd", strtotime($b[targ_accom]));

//   echo "<br />*".$targ_accom_a . " - " . $targ_accom_b;

   if ($targ_accom_a == $targ_accom_b) {
        return 0;
   }

   return ($targ_accom_a > $targ_accom_b) ? 1 : -1;
}
function cmpDate_Issue_targ_accom_dc($a, $b)
{
   $targ_accom_a = date("Ymd", strtotime($a[targ_accom]));
   $targ_accom_b = date("Ymd", strtotime($b[targ_accom]));

  // echo "<br />*".$targ_accom_a . " - " . $targ_accom_b;

   if ($targ_accom_a == $targ_accom_b) {
        return 0;
   }

   return ($targ_accom_a > $targ_accom_b) ? -1 : 1;
}
//function for priority
function cmpInt_Issue_priority_ac($a, $b)
{
   if ($a[priority]=='')
        $a[priority]=-1;
   if ($b[priority]=='')
        $b[priority]=-1;

   if ((int)($a[priority]) == (int)($b[priority])) {
        return 0;
   }

   return ((int)($a[priority]) > (int)($b[priority])) ? 1 : -1;
}
function cmpInt_Issue_priority_dc($a, $b)
{
   if ($a[priority]=='')
        $a[priority]=-1;
   if ($b[priority]=='')
        $b[priority]=-1;

   if ((int)($a[priority]) == (int)($b[priority])) {
        return 0;
   }

   return ((int)($a[priority]) > (int)($b[priority])) ? -1 : 1;
}

//function for status
function cmpString_Issue_status_id_ac($a, $b)
{
    return strcmp($a[status_id], $b[status_id]);
}
function cmpString_Issue_status_id_dc($a, $b)
{
    return (strcmp($a[status_id], $b[status_id]) * -1);
}

//function for owner
function cmpString_Issue_owned_by_ac($a, $b)
{
    return strcmp($a[owned_by], $b[owned_by]);
}
function cmpString_Issue_owned_by_dc($a, $b)
{
    return (strcmp($a[owned_by], $b[owned_by]) * -1);
}

//function for approv_by
function cmpString_Issue_approv_by_ac($a, $b)
{
    return strcmp($a[approv_by], $b[approv_by]);
}
function cmpString_Issue_approv_by_dc($a, $b)
{
    return (strcmp($a[approv_by], $b[approv_by]) * -1);
}
//function for summary
function cmpString_Issue_summary_ac($a, $b)
{
    return strcmp($a[summary], $b[summary]);
}
function cmpString_Issue_summary_dc($a, $b)
{
    return (strcmp($a[summary], $b[summary]) * -1);
}
?>





