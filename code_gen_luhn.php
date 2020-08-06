<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class codeGen {
    private $arrValidChars;
    private $lenValidChars;
    private $codeLength;
    private $lastError;
    private $reqPrefix;
    private $uniqeCodes;

    const VERBOSE = false;

    function __construct($codeLen, $valid_chars = "234689ACDEFHJKMNPRTVWXY", $required_prefix = null) {
        // A String of valid characters to use
        $this->validChars       = $valid_chars;

        // The size of the valid chars array
        $this->lenValidChars    = strlen($valid_chars);

        // An array of valid characters to use, with index starting at 0
        $this->arrValidChars    = str_split($valid_chars);

        // A flipped array of valid chars leading to their index (with value min at 0)
        $this->arrValidKeys     = array_flip($this->arrValidChars);

        // The length of the codes for this codeGen (this includes a checksum)
        $this->codeLength       = $codeLen;

        //required prefix, will taked up one character space
        $this->reqPrefix        = $required_prefix;    

        // store generated codes into persistant store (mem wont handle too large a value, and will need to carry over to subsequent runs) to squash dupes
        $this->uniqeCodes       = array();
    }

    /**
     * Randomly create a new code (needs to be verified against code database for uniqueness)
     * @return string
     */
    public function getCode() {
        $codebody   = $this->getRandomSeq($this->codeLength-2);
        $prefix     = $this->reqPrefix;
        $newcode    = $prefix.$codebody;
        $checkdig   = $this->calcCheckDigit($newcode);
        $returncode = $newcode.$checkdig;

        if(in_array($returncode, $this->uniqeCodes)){
            // if exists, recurse, careful here... can't have too much recursion or take php down with "Maximum function nesting level of '256' reached" error
            $this->getCode();
        }else{
            array_push($this->uniqeCodes, $returncode);
        }

        return $returncode;
    }


    /**
     * Validate a code's format
     * @param $code
     * @return bool
     */
    public function validateCodeFormat($code) {
        $numDigits      = strlen($code);

        // Verify length
        if ($numDigits !== $this->codeLength) {
            $this->lastError = "Invalid Code Length";
            return false;
        }

        // Verify all characters are valid EXCEPT THE CHECKDIGIT
        $arrChars       = str_split($code);
        array_pop($arrChars); 
        $invalidChars   = array_diff($arrChars, $this->arrValidChars);
        if (!empty($invalidChars)) {
            $this->lastError = "Code contains invalid characters: " . implode(",",$invalidChars);
            return false;
        }

        // Break off the checksum digit
        list($payload, $checkDigit) = str_split($code, $numDigits - 1);

        // Check Digit should = mod of result of algo
        $actualCheckDigit = $this->calcCheckDigit($payload);
        if (intval($actualCheckDigit) !== intval($checkDigit)) {
            $this->lastError = "Invalid CheckDigit for $code = $payload + $actualCheckDigit";
            return false;
        }

        return true;
    }

    /**
     * Return the number of codes available in this space
     * @return string
     */
    public function getSpace() {
        // Take off for the checksum and see the total space
        $size = pow($this->lenValidChars, $this->codeLength-1);
        return "A code of [$this->codeLength] characters including a check digit has space of " . number_format($size) . "<br>";
    }


    /**
     * Calculate the modulus of the code using the base of the number of characters present
     * @param $payload
     * @return string
     */
    private function calcCheckDigit($payload) {
        // Convert each character to base x and sum.
        $arrChars       = str_split($payload);

        // Verify all characters are valid
        $invalidChars   = array_diff($arrChars, $this->arrValidChars);
        if (!empty($invalidChars)) {
            $this->lastError = "Code contains invalid characters: " . implode(",",$invalidChars);
            return false;
        }

        // then reverse and go left to right 
        $arrChars       = array_reverse($arrChars);

        // Luhn algo variation
        $checkSum       = 0;
        foreach ($arrChars as $i => $char) {
            // get Ascii Value - 48
            $prep_ord_digit = ord($char) - 48;

            // from "right to left" even positioned character values get weighting
            if($i%2 == 0){
                //this will effectively double the value for int (then digits are summed if value > 9, unless derived from alpha value then use as is) 
                $weight = (2 * $prep_ord_digit) - floor($prep_ord_digit / 5) * 9;  
            }else{
                //use ascii value even if > 10 (for alpha values)
                $weight = $prep_ord_digit; 
            }
            $checkSum   += $weight;

            if (self::VERBOSE){
                print_r( ($i%2 ? "even" : "odd" ) .  " : $char => $weight" .  "<br>");
            }
        }
        $checkSum   = abs($checkSum) + 10; //handle sum < 10 if characters < 0 are allowed
        $checkDigit = floor((10 - ($checkSum%10)) % 10); //check digit is amount needed to reach next number divisible by ten

        if (self::VERBOSE){
            print_r("$checkSum % 10 = $checkDigit <br>");
        }

        return $checkDigit; //this will return var type "double" so make sure to intval it before comparison
    }

    /**
     * Generate a random code of length specified
     * @param $len
     * @return string
     */
    private function getRandomSeq($len) {
        $r = [];
        for ($i = 0; $i < $len; $i++) {
            $r[] = $this->arrValidChars[rand(0, $this->lenValidChars - 1)];
        }
        return implode("", $r);
    }
}

# 23 characters are valid
$validChars         = "234689ACDEFHJKMNPRTVWXY";

# Length of code to be created
$codeLen            = 8;
$required_prefix    = "V";
$cg                 = new codeGen($codeLen, $validChars, $required_prefix);

# Debug space given length:
// $space      = $cg->getSpace() ;
// echo "\nSpace: $space <br>";

# Get a code & # Validate a code
// $code       = $cg->getCode(true);
// $isValid    = $cg->validateCodeFormat($code);
// echo "Is example code [$code] Valid? " . ($isValid ? "true" : "false") . "<br>";

# Validate an invalid code
// $char       = substr($validChars,5,1);
// $badcode    = str_pad($char,$codeLen,$char);
// $badcode    = "4689WXY5";
// $isValid    = $cg->validateCodeFormat($badcode);
// echo "Is bad code [$badcode] Valid? " . ($isValid ? "true" : "false") . "<br>";

echo "<ol>";
echo "<li>8 character code length</li>";
echo "<li>First Character Must be '$required_prefix'</li>";
echo "<li>Code portion can only consist of '$validChars'</li>";
echo "<li>Use Luhn algo variation to be the checkdigit</li>";
echo "<li>Need to store (if generating many, will need persistant store, in case script times out) created codes, and skip dupes</li>";
echo "<pre>";

$codes = [];
for($i=0; $i < 100; $i++) {
    $codes[] = $cg->getCode();
}
echo "<pre>";
print_r($codes);
?>

<script>
function validateCodeFormat(code) {
    var validChars  = "234689ACDEFHJKMNPRTVWXY";
    code            = code.toUpperCase().trim().split("").reverse(); //prep code for luhn algo UPPERCASe, TRIM , REVERSE

    // will match this with result of Luhn algo below, and remove from code array
    var verifyDigit = code.shift(); 
    var checkSum    = 0;

    // make sure code portion consists of valid chars
    // TODO, double check browser requirements may need to rewrite in older JS for browser compatability
    var checkvalid  = code.filter(char => validChars.indexOf(char) == -1);
    if(checkvalid.length){
        console.log("Invalid Character(s) in Code");
        return false;
    }

    // apply algo to code reversed "right to left"
    for (var i in code) {
        var char = code[i];
        var prep_ord_digit = char.charCodeAt(0) - 48;

        var weight;
        if (i % 2 == 0) {
          weight = (2 * prep_ord_digit) - parseInt(prep_ord_digit / 5) * 9;
        } else {
          weight = prep_ord_digit;
        }
        checkSum += weight;
    }

    checkSum        = Math.abs(checkSum) + 10;
    var checkDigit  = (10 - (checkSum % 10)) % 10;

    return checkDigit == verifyDigit;
}

var code        = "4689WXY5";
var checkDigit  = validateCodeFormat(code);
console.log(code, checkDigit);
</script>