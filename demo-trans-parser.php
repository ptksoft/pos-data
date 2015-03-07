#!/usr/bin/php -q
<?php
$fileName = '100904-043-007-00001.esv';
echo 'Demo Parse Transaction FILE [',$fileName,"]\n";

$data = ParseTransactionFile($fileName);
if (empty($data)) {
    echo 'Error in Parse Transaction FILE !!!', "\n";
}
else {
    echo 'Parse Transaction File Success',"\n";
    print_r($data);
}


//========================================================================
function ParseTransactionFile($fileName) {  
    if (! file_exists($fileName)) return(array());  // File Dont' exists
    $fp = @fopen($fileName, 'r');
    if ($fp===false) return(array());   // Cannot Open file for read

    $header = array();
    $member = array();
    $details = array();
    $promotions = array();
    $payments = array();
    $summary = array();
        
    $lineCount = 0;
    $lineCountToCheck = 0;

    $isPacketBegin = false;
    $isPacketEnd = false;
    
    while ($line = fgets($fp)) {
        $lineCount++;

        $esv = _ParseESV($line);
        if (empty($esv)) return(array());   // Empty ESV! impossible
        if (! array_key_exists('!', $esv)) return(array());     // Empty '!' KEY, something wrong

        if ($lineCount==1) {
            // First Line Must Start Transaction
            if ($esv['!']!='{') return(array());       // Start Line, line-type must be '{'
            $isPacketBegin = true;                  // Start Packet ready
        }
        else {
            if (! $isPacketBegin) return(array());      // Packet not begin but found other line-type, something wrong
            if ($isPacketEnd) return(array());      // Packet already end, but what fucking line now?
            
            switch ($esv['!']) {
                case 'header':
                    _MergeEsvToArray($esv, $header);
                    break;

                case 'member':
                    _MergeEsvToArray($esv, $member);
                    break;

                case 'detail':
                    if (false===_MergeEsvToArray2D($esv, $details)) return(array());  // Something Wrong
                    break;

                case 'promotion':
                    if (false===_MergeEsvToArray2D($esv, $promotions)) return(array());  // Something Wrong
                    break;

                case 'payment':
                    if (false===_MergeEsvToArray2D($esv, $payments)) return(array());    // Something Wrong
                    break;

                case 'summary':
                    _MergeEsvToArray($esv, $summary);
                    break;

                case '}':
                    // Packet IS END Now
                    $isPacketEnd = true;
                    $lineCountToCheck = (int)$esv['%'];
                    break;

                default:
                    // Unknow What type of this line, Dont' care it
                    // Lets other program that know this type use it
                    break;
            }
        }
    }
    @fclose($fp);
    if ($lineCount != $lineCountToCheck) return(array());   // Invalid Line in Packet, Something Wrong

    // NOW Return Perfect Result, Every thing OK
    return(array(
       'header'=>$header,
       'member'=>$member,
       'details' =>$details,
       'promotions'=>$promotions,
       'payments'=>$payments,
       'summary'=>$summary
    ));
}
//========================================================================
function _MergeEsvToArray($esv, &$arrTarget) {
    unset($esv['!']);   // Remove line-type KEY from ESV
    foreach ($esv as $k => $v) {
        $arrTarget[$k] = $v;
    }
}
//========================================================================
function _MergeEsvToArray2D($esv, &$arrTarget2D) {
    unset($esv['!']);   // Remove line-type KEY from ESV
    if (! isset($esv['line'])) return(false);   // Need 'line' Keyword for 2D-Dimension array
    $lineNum = (int)$esv['line'];
    if ($lineNum < 1) return(false);    // Invalid line value
    unset($esv['line']);    // No need this key any more
    $arrTarget2D[$lineNum] = $esv;  // Push all remain key->value to Target
}
//========================================================================
function _ParseESV($esvLine) {
    if (strpos($esvLine, '=')===false) return(array());     // ESV must have at least 1 '=' char
    $parts = explode(chr(27), $esvLine);
    $arrData = array();
    foreach ($parts as $onePart) {
        if (strpos($onePart, '=')===false) continue;    // Not found '=' char
        list($k, $v) = explode('=', $onePart, 2);      // Extract and limit part to 2
        $arrData[trim($k)] = $v;        // Push key=>value structure
    }
    return($arrData);
}
//========================================================================
//========================================================================
?>
