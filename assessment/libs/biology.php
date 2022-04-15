<?php
//A collection of biology routines
//
//Version 0.2 April 15, 2022

global $allowedmacros;
array_push($allowedmacros,"bio_randcodon","bio_getcodonname","bio_anticodon","bio_translation", "bio_splitcodons");


// bio_randcodon
// fetches a random codon returns an array of (code, 3 letter abbreviation, long name)
function bio_randcodon() {
    global $bio_codons, $RND;
    $out = array();
    $max = count($bio_codons);
    $out = $bio_codons[$RND->rand(0,$max-1)];
    return $out;
}

// bio_getcodonname
// returns an array of (3 letter abbreviation, long name) of a codon
function bio_getcodonname($codonLetters){
    global $bio_codons, $bio_codons_to_number;
    $in = strtoupper($codonLetters);
    $codon_number = $bio_codons_to_number[$in];
    $out = array($bio_codons[$codon_number][1], $bio_codons[$codon_number][2]);
    return $out;
}

// bio_anticodon
// returns the anticodon of the codon
function bio_anticodon($DNA_string){
    $input = trim($DNA_string);
    $input = str_split($input);
    $output = "";
    foreach ($input as $letter) {
        if ($letter == "A") {
            $output .= "T";
        } elseif ($letter == "T") {
            $output .= "A";
        } elseif ($letter == "C") {
            $output .= "G";
        } elseif ($letter == "G") {
            $output .= "C";
        } elseif ($letter == "U") {
            $output .= "A";
        }
    } 
    return $output;
}

// bio_translation
// returns the translation of the codon
function bio_translation($DNA_string){
    $input = trim($DNA_string);
    $input = str_split($input);
    $output = "";
    foreach ($input as $letter) {
        if ($letter == "A") {
            $output .= "U";
        } elseif ($letter == "T") {
            $output .= "A";
        } elseif ($letter == "C") {
            $output .= "G";
        } elseif ($letter == "G") {
            $output .= "C";
        }
    } 
    return $output;
}


// bio_splitcodons
// splits up a string into sets of 3 base codons
function bio_splitcodons($DNA_string){
    $input = trim($DNA_string);
    $DNA_len = strlen($input);
    $input = str_split($input);
    $output= array();
    for ($x = 0; $x <= $DNA_len; $x++) {
        $string = "";
        if ($x % 3 == 1) {
            $index = $x / 3;
            $string .= $input[$x-1];
            $string .= $input[$x];
            $string .= $input[$x+1];
            $output[$index] = $string;
        }
    }
    return $output;
}

// TODO: Deal with Stop Codons
$GLOBALS['bio_codons'] = array(
    1=> array("UUU", "Phe", "Phenylalanine"),
        2=> array("UUC", "Phe", "Phenylalanine"),
        3=> array("UUA", "Leu", "Leucine"),
        4=> array("UUG", "Leu", "Leucine"),
        5=> array("CUU", "Leu", "Leucine"),
        6=> array("CUC", "Leu", "Leucine"),
        7=> array("CUA", "Leu", "Leucine"),
        8=> array("CUG", "Leu", "Leucine"),
        9=> array("AUU", "Ile", "Isoleucine"),
        10=>array("AUC", "Ile", "Isoleucine"),
        11=>array("AUA", "Ile", "Isoleucine"),
        12=>array("AUG", "Met", "Methionine"),
        13=>array("GUU", "Val", "Valine"),
        14=>array("GUC", "Val", "Valine"),
        15=>array("GUA", "Val", "Valine"),
        16=>array("GGG", "Val", "Valine"),
        17=>array("UCU", "Ser", "Serine"),
        18=>array("UCC", "Ser", "Serine"),
        19=>array("UCA", "Ser", "Serine"),
        20=>array("UCG", "Ser", "Serine"),
        21=>array("CCU", "Pro", "Proline"),
        22=>array("CCC", "Pro", "Proline"),
        23=>array("CCA", "Pro", "Proline"),
        24=>array("CCG", "Pro", "Proline"),
        25=>array("ACU", "Thr", "Threonine"),
        26=>array("ACC", "Thr", "Threonine"),
        27=>array("ACA", "Thr", "Threonine"),
        28=>array("ACG", "Thr", "Threonine"),
        29=>array("GCU", "Ala", "Alanine"),
        30=>array("GCC", "Ala", "Alanine"),
        31=>array("GCA", "Ala", "Alanine"),
        32=>array("GCG", "Ala", "Alanine"),
        33=>array("UAU", "Tyr", "Tyrosine"),
        34=>array("UAC", "Tyr", "Tyrosine"),
        35=>array("UAA", "Stop", "Stop"),
        36=>array("UAG", "Stop", "Stop"),
        37=>array("CAU", "His", "Histidine"),
        38=>array("CAC", "His", "Histidine"),
        39=>array("CAA", "Gln", "Glutamine"),
        40=>array("CAG", "Gln", "Glutamine"),
        41=>array("AAU", "Asn", "Asparagine"),
        42=>array("AAC", "Asn", "Asparagine"),
        43=>array("AAA", "Lys", "Lysine"),
        44=>array("AAG", "Lys", "Lysine"),
        45=>array("GAU", "Asp", "Aspartic Acid"),
        46=>array("GAC", "Asp", "Aspartic Acid"),
        47=>array("GAA", "Glu", "Glutamic Acid"),
        48=>array("GAG", "Glu", "Glutamic Acid"),
        49=>array("UGU", "Cys", "Cysteine"),
        50=>array("UGC", "Cys", "Cysteine"),
        51=>array("UGA", "Stop", "Stop"),
        52=>array("UGG", "Trp", "Tryptophan"),
        53=>array("CGU", "Arg", "Arginine"),
        54=>array("CGC", "Arg", "Arginine"),
        55=>array("CGA", "Arg", "Arginine"),
        56=>array("CGG", "Arg", "Arginine"),
        57=>array("AGU", "Ser", "Serine"),
        58=>array("AGC", "Ser", "Serine"),
        59=>array("AGA", "Arg", "Arginine"),
        60=>array("AGG", "Arg", "Arginine"),
        61=>array("GGU", "Gly", "Glycine"),
        62=>array("GGC", "Gly", "Glycine"),
        63=>array("GGA", "Gly", "Glycine"),
        64=>array("GGG", "Gly", "Glycine"),
    );

$GLOBALS['bio_codons_to_number'] = array(
    "UUU"=>1, "UUC"=>2,"UUA"=>3,"UUG"=>4,"CUU"=>5,"CUC"=>6,"CUA"=>7,"CUG"=>8,"AUU"=>9,"AUC"=>10,
    "AUA"=>11,"AUG"=>12,"GUU"=>13,"GUC"=>14,"GUA"=>15,"GGG"=>16,"UCU"=>17,"UCC"=>18,"UCA"=>19,"UCG"=>20,
    "CCU"=>21,"CCC"=>22,"CCA"=>23,"CCG"=>24,"ACU"=>25,"ACC"=>26,"ACA"=>27,"ACG"=>28,"GCU"=>29,"GCC"=>30,
    "GCA"=>31,"GCG"=>32,"UAU"=>33,"UAC"=>34,"UAA"=>35,"UAG"=>36,"CAU"=>37,"CAC"=>38,"CAA"=>39,"CAG"=>40,
    "AAU"=>41,"AAC"=>42,"AAA"=>43,"AAG"=>44,"GAU"=>45,"GAC"=>46,"GAA"=>47,"GAG"=>48,"UGU"=>49,"UGC"=>50,
    "UGA"=>51,"UGG"=>52,"CGU"=>53,"CGC"=>54,"CGA"=>55,"CGG"=>56,"AGU"=>57,"AGC"=>58,"AGA"=>59,"AGG"=>60,
    "GGU"=>61,"GGC"=>62,"GGA"=>63,"GGG"=>64,
    );