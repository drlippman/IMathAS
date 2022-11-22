<?php

/*
 * Row reduce a matrix specified by the array of values $A with $N rows
 */
function matrix_scorer_rref($A, $N)
{
    $M = count($A) / $N;
    $r = 0;
    $c = 0;
    while ($r < $N && $c < $M) {
        if ($A[$r * $M + $c] == 0) { //swap only if there's a 0 entry
            $max = $r;
            for ($i = $r + 1; $i < $N; $i++) {
                if (abs($A[$i * $M + $c]) > abs($A[$max * $M + $c])) {
                    $max = $i;
                }
            }
            if ($max != $r) { // swap rows
                for ($j = 0; $j < $M; $j++) {
                    $temp = $A[$r * $M + $j];
                    $A[$r * $M + $j] = $A[$max * $M + $j];
                    $A[$max * $M + $j] = $temp;
                }
            }
        }
        if (abs($A[$r * $M + $c]) < 1e-10) {
            $c++;
            continue;
        }
        //scale pivot row
        $div = $A[$r * $M + $c];
        for ($j = $c; $j < $M; $j++) {
            $A[$r * $M + $j] = $A[$r * $M + $j] / $div;
        }

        //get zeros above/below
        for ($i = 0; $i < $N; $i++) {
            if ($i == $r) {continue;}
            $mult = $A[$i * $M + $c];
            if ($mult == 0) {continue;}
            for ($j = $c; $j < $M; $j++) {
                $A[$i * $M + $j] = $A[$i * $M + $j] - $mult * $A[$r * $M + $j];
            }
        }
        $r++;
        $c++;
    }
    return $A;
}

/*
 * Sort rows in a matrix specified by the array of values $A with $N rows
 */
function matrix_scorer_roworder($A, $N)
{
    $rows = array_chunk($A, round(count($A)/$N));

    usort($rows, function($a,$b) {
        for ($i=0;$i<count($a);$i++) {
            if ($a[$i]==$b[$i]) { continue; }
            return ($a[$i] - $b[$i]);
        }
        return 0;
    });

    return array_merge(...$rows);
}
