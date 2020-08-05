<?php

class codeGen
{
    private $arrValidChars;
    private $lenValidChars;
    private $codeLength;
    private $lastError;

    const VERBOSE = false;

    function __construct($codeLen, $valid_chars = "234689ACDEFHJKMNPRTVWXY")
    {
        // A String of valid characters to use
        $this->validChars = $valid_chars;

        // The size of the valid chars array
        $this->lenValidChars = strlen($valid_chars);

        // An array of valid characters to use, with index starting at 0
        $this->arrValidChars = str_split($valid_chars);

        // A flipped array of valid chars leading to their index (with value min at 0)
        $this->arrValidKeys = array_flip($this->arrValidChars);

        // The length of the codes for this codeGen (this includes a checksum)
        $this->codeLength = $codeLen;
    }


    /**
     * Randomly create a new code (needs to be verified against code database for uniqueness)
     * @return string
     */
    public function getCode() {
        $prefix = $this->getRandomSeq($this->codeLength-1);
        $checksum = $this->calcCheckDigit($prefix);
        return $prefix.$checksum;
    }


    /**
     * Validate a code's format
     * @param $code
     * @return bool
     */
    public function validateCodeFormat($code)
    {
        $numDigits = strlen($code);

        // Verify length
        if ($numDigits !== $this->codeLength) {
            $this->lastError = "Invalid Code Length";
            return false;
        }

        // Verify all characters are valid
        $arrChars = str_split($code);
        $invalidChars = array_diff($arrChars, $this->arrValidChars);
        if (!empty($invalidChars)) {
            $this->lastError = "Code contains invalid characters: " . implode(",",$invalidChars);
            return false;
        }

        // Break off the checksum digit
        list($payload, $checkDigit) = str_split($code, $numDigits - 1);

        // Get checksum from pre-code
        $actualCheckDigit = $this->calcCheckDigit($payload);
        if ($actualCheckDigit !== $checkDigit) {
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
        return "A code of {$this->codeLength} characters including a check digit has space of " . number_format($size) . "\n";
    }


    /**
     * Calculate the modulus of the code using the base of the number of characters present
     * @param $payload
     * @return string
     */
    private function calcCheckDigit($payload)
    {
        // Convert each character to base x and sum.
        $arrChars = str_split($payload);

        // Verify all characters are valid
        $invalidChars = array_diff($arrChars, $this->arrValidChars);
        if (!empty($invalidChars)) {
            $this->lastError = "Code contains invalid characters: " . implode(",",$invalidChars);
            return false;
        }

        $idxSum = 0;
        foreach ($arrChars as $i => $char) {
            $idxSum   = $idxSum + $this->arrValidKeys[$char];
            if (self::VERBOSE) print_r((string) $i . " -- $char => " . $this->arrValidKeys[$char] . " [" . $idxSum . "]\n");
        }
        $mod = $idxSum % $this->lenValidChars;
        $checkDigit = $this->arrValidChars[$mod];

        if (self::VERBOSE) print_r("$idxSum mod {$this->lenValidChars} = $mod which corresponds to $checkDigit");
        return $checkDigit;
    }

    /**
     * Generate a random code of length specified
     * @param $len
     * @return string
     */
    private function getRandomSeq($len)
    {
        $r = [];
        for ($i = 0; $i < $len; $i++) {
            $r[] = $this->arrValidChars[rand(0, $this->lenValidChars - 1)];
        }
        return implode("", $r);
    }
}

# 23 characters are valid
$validChars = "234689ACDEFHJKMNPRTVWXY";

# Length of code to be created
$codeLen = 6;

$cg = new codeGen($codeLen, $validChars);

# Debug space given length:
$space = $cg->getSpace();
echo "\nSpace: $space";

# Get a code
$code = $cg->getCode(true);
echo "\nExample Code: $code";

$codes = [];
for($i=0; $i < 100; $i++) {
    $codes[] = $cg->getCode();
}
// echo "\n" . implode("\n", $codes);


# Validate a code
$isValid = $cg->validateCodeFormat($code);
echo "\nIs $code Valid? " . ($isValid ? "true" : "false") . "\n\n";

# Validate an invalid code
$char = substr($validChars,5,1);
$code = str_pad($char,$codeLen,$char);

$isValid = $cg->validateCodeFormat($code);
echo "\nIs $code Valid? " . ($isValid ? "true" : "false") . "\n\n";



