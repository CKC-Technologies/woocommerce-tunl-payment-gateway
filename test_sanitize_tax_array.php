<?php
function sanitize_tax_array($jsonString = ""){
    $taxArray = json_decode($jsonString, true);
    if (!is_array($taxArray)) return 0;

    $refundSumsArray = array_map(function($refundArray){
        if (!is_array($refundArray)) return 0;
        $sanitizedArray = array_filter($refundArray, 'is_numeric');
        return array_sum($sanitizedArray);
    }, $taxArray);
    
    return array_sum($refundSumsArray);
}
$test = array();

// testing bad inputs (should all pass with sanitize_tax_array returning 0)
$test[] = 0 === sanitize_tax_array("[1,2,3]");
$test[] = 0 === sanitize_tax_array("['asdf', 'qwer', 'zxcv']");
$test[] = 0 === sanitize_tax_array("[[],[],[]]");
$test[] = 0 === sanitize_tax_array("");
$test[] = 0 === sanitize_tax_array(null);
$test[] = 0 === sanitize_tax_array(false);
$test[] = 0 === sanitize_tax_array(true);
$test[] = 0 === sanitize_tax_array("");

// testing a partial bad input, should discard invalid values and only return numbers
$test[] = 6 === sanitize_tax_array('[["SELECT * FROM something"],[1,2,3]]');

// testing good inputs
$test[] = 6 === sanitize_tax_array('[[1,2,3]]');
$test[] = 6 === sanitize_tax_array('[[1,2,3],[]]');
$test[] = 6 === sanitize_tax_array('[null,true,[1,2,3],[],[],false]');
$test[] = 6 === sanitize_tax_array('[[1,2,3],[],[],true]');
$test[] = 6 === sanitize_tax_array('[[1,2,3],[],[],null]');

$test[] = 9 === sanitize_tax_array('[[1,2,3],[1,2]]');
$test[] = 15 === sanitize_tax_array('[[1,2,3],[1,2],[6]]');
$test[] = 15 === sanitize_tax_array('[[3,2,1],[1,2],[6]]');

// show any failed tests
var_dump(array_filter($test, function ($i){return $i === false;}));

?>