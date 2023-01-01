<?php
class SummaryRequired
{
    function SummaryRequired() {
        wfLoadExtensionMessages( 'SummaryRequired' );
    }

    function execute( $par ) {
        global $wgRequest, $wgOut;

        $this->setHeaders();
        
        # display form
        self::displayBody($wgOut);
        
        return true;
    }
    
    public static function fnMyHook(&$catpage)
    {
	    # must be declared
	    global $wgOut;
	    
	    # add custom javascript
	    $wgOut->addHTML(self::HandleClick());
	    
	    return true;
    }
    
    /**
     * React to click event
     * Prepare click listener
     */
    public static function HandleClick() {
	    global $wgScriptPath;
	    
	    $myPath = "$wgScriptPath/extensions/SummaryRequired/includes";
	    	
    	return "<script type='text/javascript' src='$myPath/jquery-1.6.4.min.js'></script><script type='text/javascript' src='$myPath/SummaryRequired.js'></script>";
    }
}


