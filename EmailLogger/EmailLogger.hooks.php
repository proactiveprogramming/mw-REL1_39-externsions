<?php

class EmailLoggerHooks {
    public static function onAlternateUserMailer( $headers, $to, $from, $subject, $body ) {
        global $wgEmailLoggerLogFilePath, $wgEmailLoggerFailSilently;

        // Get time
        $timestamp = date('Y-m-d G:i:s T');

        // Log info in CSV format
        // By default, just log the to and subject
        // fputcsv() does not always add double quotes, so we are not using it
        $destinations = "[";
        foreach ($to as $destination) {
            $destinations .= str_replace('"', '', $destination) . ",";
        }
        $destinations = substr($destinations, 0, -1) . "]";
        $escaped_subject = addslashes($subject);

        $log_info = "\"$timestamp\",\"$destinations\",\"$escaped_subject\"";
        // if desired, log the email contents as well
        // temporarily disabled because that will not work well with multiline bodies, as almost
        // all emails are in MediaWiki
        // if ($wgEmailLoggerLogBody) {
        //     $log_info .= ",$body";
        // }
        $log_info .= "\n";

        // Establish connection to the log file path
        $file_handler = fopen($wgEmailLoggerLogFilePath, 'a');
        $write_result = fwrite($file_handler, $log_info);

        // If there was an error writing to the file, and shouldn't fail silently, then
        // return the error message
        if ($wgEmailLoggerFailSilently === false && $write_result !== strlen($log_info)) {
            return '[EmailLogger] Error with logging; fwrite returned: ' . $write_result;
        }

        // need to return true to indicate continuing to send email
        return true;
    }
}
