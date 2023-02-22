<?php
/**
 * Created by PhpStorm.
 * User: lluis
 * Date: 2/25/15
 * Time: 8:16 PM
 */

namespace App\DependencyInjection\Commons;



use Symfony\Component\DependencyInjection\ContainerInterface;

class UserChecker{

    const IDENTITY_CARD_NOT_VALID = "Identity card not valid";
    const DOCUMENT_NOT_VALID = "CIF not valid";
    const DOCUMENT_CONTAINS_WHITESPACES = "Document contains whitespaces";

    /** @var ContainerInterface $container */
    private $container;

    function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function validateUserIdentification($identification){
        if ($this->containsWhitespaces($identification))
            return ['result' => false, 'data' => [], 'errors' => [$this::DOCUMENT_CONTAINS_WHITESPACES]];
        $identification = strtoupper($identification);
        if($this->isValidDni($identification) || $this->isValidNie($identification))
            return ['result' => true, 'data' => [], 'errors' => []];
        return ['result' => false, 'data' => [],'errors' =>  [$this::IDENTITY_CARD_NOT_VALID]];
    }

    public function validateCompanyIdentification($identification){
        if ($this->containsWhitespaces($identification))
            return ['result' => false, 'data' => [], 'errors' => [$this::DOCUMENT_CONTAINS_WHITESPACES]];
        $identification = strtoupper($identification);
        if($this->isValidCif($identification) || $this->isValidDni($identification) || $this->isValidNie($identification))
            return ['result' => true, 'data' => [], 'errors' => []];
        return ['result' => false, 'data' => [],'errors' =>  [$this::DOCUMENT_NOT_VALID]];
    }

    public function containsWhitespaces($identification){
        return (strpos($identification, " "));
    }

    public function isValidDni($dni){
        $nie_letter = array('X','Y','Z');
        $nie_letter_number = array('0','1','2');
        $letter = substr($dni, -1);
        $number = substr($dni, 0, -1);
        if (!is_numeric($number)) return false;
        $number = str_replace($nie_letter, $nie_letter_number, $number);
        return ( substr("TRWAGMYFPDXBNJZSQVHLCKE", $number%23, 1) == $letter && strlen($letter) == 1 && strlen ($number) == 8 );
    }

    public function isValidNie($nie){
        if (preg_match('/^[XYZT][0-9][0-9][0-9][0-9][0-9][0-9][0-9][A-Z0-9]/', $nie)) {
            for ($i = 0; $i < 9; $i ++){
                $num[$i] = substr($nie, $i, 1);
            }
            if ($num[8] == substr('TRWAGMYFPDXBNJZSQVHLCKE', substr(str_replace(array('X','Y','Z'), array('0','1','2'), $nie), 0, 8) % 23, 1)) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }

    public function isValidCif($cif){
        $cif = strtoupper($cif);
        $cif_codes = 'JABCDEFGHI';

        $sum = (string) $this->getCifSum ($cif);
        $n = (10 - substr ($sum, -1)) % 10;
        $result = false;
        if (preg_match ('/^[ABCDEFGHJNPQRSUVW]{1}/', $cif)) {
            if (in_array ($cif[0], array ('A', 'B', 'E', 'H'))) {
                $result = ($cif[8] == $n);
            } elseif (in_array ($cif[0], array ('K', 'P', 'Q', 'S'))) {
                $result = ($cif[8] == $cif_codes[$n]);
            } else {
                if (is_numeric ($cif[8])) {
                    $result = ($cif[8] == $n);
                } else {
                    $result = ($cif[8] == $cif_codes[$n]);
                }
            }
        }
        return $result;
    }

    function getCifSum($cif) {
        $sum = $cif[2] + $cif[4] + $cif[6];

        for ($i = 1; $i<8; $i += 2) {
            $tmp = (string) (2 * $cif[$i]);
            $tmp = $tmp[0] + ((strlen ($tmp) == 2) ?  $tmp[1] : 0);
            $sum += $tmp;
        }
        return $sum;
    }
}
