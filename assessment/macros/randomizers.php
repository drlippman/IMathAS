<?php

// core randomizers macros

array_push(
    $GLOBALS['allowedmacros'],
    'rand',
    'rrand',
    'rands',
    'rrands',
    'randfrom',
    'randsfrom',
    'jointrandfrom',
    'diffrandsfrom',
    'nonzerorand',
    'nonzerorrand',
    'nonzerorands',
    'nonzerorrands',
    'diffrands',
    'diffrrands',
    'nonzerodiffrands',
    'nonzerodiffrrands',
    'singleshuffle',
    'jointshuffle',
    'randpythag',
    'randcities',
    'randcountries',
    'randstates',
    'randstate',
    'randcity',
    'randcountry',
    'randnames',
    'randmalenames',
    'randfemalenames',
    'randname',
    'randnamewpronouns',
    'randmalename',
    'randfemalename'
);

function rrand($min, $max, $p = 0) {
    if (func_num_args() != 3) {
        echo "Error: rrand expects 3 arguments";
        return $min;
    }
    if ($p <= 0) {
        echo "Error with rrand: need to set positive step size";
        return false;
    }
    list($min, $max) = checkMinMax($min, $max, false, 'rrand');

    $rn = max(0, getRoundNumber($p), getRoundNumber($min));
    $out = round($min + $p * $GLOBALS['RND']->rand(0, floor(($max - $min) / $p + 1e-12)), $rn);
    if ($rn == 0) {
        $out = (int) $out;
    }
    return ($out);
}


function rands($min, $max, $n = 0, $ord = 'def') {
    if (func_num_args() < 3) {
        echo "rands expects 3 arguments";
        return $min;
    }
    list($min, $max) = checkMinMax($min, $max, true, 'rands');
    $n = floor($n);
    if ($n <= 0) {
        echo "rands: need n &gt; 0";
    }
    if ($n > 1e4) {
        echo 'Error with diffrrands: $n too large';
        return [];
    }
    $r = [];
    for ($i = 0; $i < $n; $i++) {
        $r[$i] = $GLOBALS['RND']->rand($min, $max);
    }
    if ($ord == 'inc') {
        sort($r);
    } else if ($ord == 'dec') {
        rsort($r);
    }
    return $r;
}


function rrands($min, $max, $p = 0, $n = 0, $ord = 'def') {
    if (func_num_args() < 4) {
        echo "rrands expects 4 arguments";
        return $min;
    }
    if ($p <= 0) {
        echo "Error with rrands: need to set positive step size";
        return false;
    }
    list($min, $max) = checkMinMax($min, $max, false, 'rrands');

    $rn = max(0, getRoundNumber($p), getRoundNumber($min));
    $n = floor($n);
    if ($n <= 0) {
        echo "rrands: need n &gt; 0";
    }
    if ($n > 1e4) {
        echo 'Error with diffrrands: $n too large';
        return [];
    }
    $r = [];
    $maxi = floor(($max - $min) / $p + 1e-12);
    for ($i = 0; $i < $n; $i++) {
        $r[$i] = round($min + $p * $GLOBALS['RND']->rand(0, $maxi), $rn);
        if ($rn == 0) {
            $r[$i] = (int) $r[$i];
        }
    }
    if ($ord == 'inc') {
        sort($r);
    } else if ($ord == 'dec') {
        rsort($r);
    }
    return $r;
}


function randfrom($lst) {
    if (func_num_args() != 1) {
        echo "randfrom expects 1 argument";
        return 1;
    }
    if (!is_array($lst)) {
        $lst = listtoarray($lst);
    }
    if (count($lst) == 0) {
        echo 'cannot pick randfrom empty list';
        return '';
    }
    return $lst[$GLOBALS['RND']->rand(0, count($lst) - 1)];
}


function randsfrom($lst, $n, $ord = 'def') {
    if (func_num_args() < 2) {
        echo "randsfrom expects 2 arguments";
        return 1;
    }
    if (!is_array($lst)) {
        $lst = listtoarray($lst);
    }
    $n = floor($n);
    if ($n <= 0) {
        echo "randsfrom: need n &gt; 0";
    }
    $r = [];
    for ($i = 0; $i < $n; $i++) {
        $r[$i] = $lst[$GLOBALS['RND']->rand(0, count($lst) - 1)];
    }
    if ($ord == 'inc') {
        sort($r);
    } else if ($ord == 'dec') {
        rsort($r);
    }
    return $r;
}


function jointrandfrom() {
    $args = func_get_args();
    if (count($args) < 2) {
        echo "jointrandfrom expects at least 2 arguments";
        return array(1, 1);
    }
    $min = 1e12;
    foreach ($args as $k => $arg) {
        if (!is_array($arg)) {
            $args[$k] = listtoarray($arg);
        }
        $min = min($min, count($args[$k]) - 1);
    }
    $l = $GLOBALS['RND']->rand(0, $min);
    $out = array();
    foreach ($args as $k => $arg) {
        $out[] = $arg[$l];
    }
    return $out;
}


function diffrandsfrom($lst, $n, $ord = 'def') {
    if (func_num_args() < 2) {
        echo "diffrandsfrom expects 2 arguments";
        return array();
    }
    if (!is_array($lst)) {
        $lst = listtoarray($lst);
    }
    $n = floor($n);
    if ($n <= 0) {
        echo "diffrandsfrom: need n &gt; 0";
    }
    $GLOBALS['RND']->shuffle($lst);
    $r = array_slice($lst, 0, $n);
    if ($ord == 'inc') {
        sort($r);
    } else if ($ord == 'dec') {
        rsort($r);
    }
    return $r;
}


function nonzerorand($min, $max) {
    if (func_num_args() != 2) {
        echo "nonzerorand expects 2 arguments";
        return $min;
    }
    list($min, $max) = checkMinMax($min, $max, true, 'nonzerorand');
    if ($min == 0 && $max == 0) {
        return 0;
    }
    do {
        $ret = $GLOBALS['RND']->rand($min, $max);
    } while ($ret == 0);
    return $ret;
}


function nonzerorrand($min, $max, $p = 0) {
    if (func_num_args() != 3) {
        echo "nonzerorrand expects 3 arguments";
        return $min;
    }
    list($min, $max) = checkMinMax($min, $max, false, 'nonzerorrand');
    if ($min == 0 && $max == 0) {
        return 0;
    }
    if ($p <= 0) {
        echo "Error with nonzerorrand: need to set positive step size";
        return $min;
    }
    $maxi = floor(($max - $min) / $p + 1e-12);
    if ($maxi == 0) {
        return $min;
    }

    $rn = max(0, getRoundNumber($p), getRoundNumber($min));
    $cnt = 0;
    do {
        $ret = round($min + $p * $GLOBALS['RND']->rand(0, $maxi), $rn);
        $cnt++;
        if ($cnt > 1000) {
            echo "Error in nonzerorrand - not able to find valid value";
            break;
        }
    } while (abs($ret) < 1e-14);
    if ($rn == 0) {
        $ret = (int) $ret;
    }
    return $ret;
}


function nonzerorands($min, $max, $n = 0, $ord = 'def') {
    if (func_num_args() < 3) {
        echo "nonzerorands expects 3 arguments";
        return $min;
    }
    list($min, $max) = checkMinMax($min, $max, true, 'nonzerorands');
    if ($min == 0 && $max == 0) {
        return 0;
    }
    $n = floor($n);
    if ($n <= 0) {
        echo "nonzerorands: need n &gt; 0";
    }
    if ($n > 1e4) {
        echo 'Error with diffrrands: $n too large';
        return [];
    }
    $r = [];
    for ($i = 0; $i < $n; $i++) {
        do {
            $r[$i] = $GLOBALS['RND']->rand($min, $max);
        } while ($r[$i] == 0);
    }
    if ($ord == 'inc') {
        sort($r);
    } else if ($ord == 'dec') {
        rsort($r);
    }
    return $r;
}


function nonzerorrands($min, $max, $p = 0, $n = 0, $ord = 'def') {
    if (func_num_args() < 4) {
        echo "nonzerorrands expects 4 arguments";
        return $min;
    }
    $n = floor($n);
    list($min, $max) = checkMinMax($min, $max, false, 'nonzerorrands');

    if ($p <= 0) {
        echo "Error with nonzerorrands: need to set positive step size";
        return array_fill(0, $n, $min);
    }
    $maxi = floor(($max - $min) / $p + 1e-12);
    if ($maxi == 0) {
        return array_fill(0, $n, $min);
    }

    $rn = max(0, getRoundNumber($p), getRoundNumber($min));
    $n = floor($n);
    $r = [];
    if ($n <= 0) {
        echo "nonzerorrands: need n &gt; 0";
    }
    if ($n > 1e4) {
        echo 'Error with diffrrands: $n too large';
        return [];
    }
    for ($i = 0; $i < $n; $i++) {
        $cnt = 0;
        do {
            $r[$i] = round($min + $p * $GLOBALS['RND']->rand(0, $maxi), $rn);
            if ($rn == 0) {
                $r[$i] = (int) $r[$i];
            }
            $cnt++;
            if ($cnt > 1000) {
                echo "Error in nonzerorrands - not able to find valid value";
                break;
            }
        } while (abs($r[$i]) < 1e-14);
    }
    if ($ord == 'inc') {
        sort($r);
    } else if ($ord == 'dec') {
        rsort($r);
    }
    return $r;
}


function diffrands($min, $max, $n = 0, $ord = 'def') {
    if (func_num_args() < 3) {
        echo "diffrands expects 3 arguments";
        return $min;
    }
    list($min, $max) = checkMinMax($min, $max, true, 'diffrands');
    if ($max == $min) {
        echo "diffrands: Need min&lt;max";
        return array_fill(0, $n, $min);
    }
    /*if ($n > $max-$min+1) {
		if ($GLOBALS['myrights']>10) {
			echo "diffrands: min-max not far enough for n requested";
		}
	}*/

    $n = floor($n);
    if ($n <= 0) {
        echo "diffrands: need n &gt; 0";
    }
    if ($n > 1e4) {
        echo 'Error with diffrrands: $n too large';
        return [];
    }
    if ($n < .1 * ($max - $min)) {
        $out = array();
        $cnt = 0;
        while (count($out) < $n) {
            $x = $GLOBALS['RND']->rand($min, $max);
            if (!in_array($x, $out)) {
                $out[] = $x;
            }
            $cnt++;
            if ($cnt > 2000) {
                echo "Error in diffrands - not able to find valid values";
                break;
            }
        }
    } else {
        $r = range($min, $max);
        while ($n > count($r)) {
            $r = array_merge($r, $r);
        }
        $GLOBALS['RND']->shuffle($r);
        $out = array_slice($r, 0, $n);
    }
    if ($ord == 'inc') {
        sort($out);
    } else if ($ord == 'dec') {
        rsort($out);
    }
    return $out;
}


function diffrrands($min, $max, $p = 0, $n = 0, $ord = 'def', $nonzero = false) {
    if (func_num_args() < 4) {
        echo "diffrrands expects 4 arguments";
        return $min;
    }
    $n = floor($n);
    list($min, $max) = checkMinMax($min, $max, false, 'diffrrands');
    if ($n > 1e4) {
        echo 'Error with diffrrands: $n too large';
        return [];
    }

    if ($p <= 0) {
        echo "Error with diffrrands: need to set positive step size";
        return array_fill(0, $n, $min);
    }
    $n = floor($n);
    if ($n <= 0) {
        echo "diffrrands: need n &gt; 0";
    }

    $maxi = floor(($max - $min) / $p + 1e-12);

    if ($maxi == 0) {
        echo "Error with diffrrands: step size is greater than max-min";
        return array_fill(0, $n, $min);
    }

    $rn = max(0, getRoundNumber($p), getRoundNumber($min));

    if ($n < .1 * $maxi) {
        $out = array();
        $cnt = 0;
        while (count($out) < $n) {
            $x = round($min + $p * $GLOBALS['RND']->rand(0, $maxi), $rn);
            if ($rn == 0) {
                $x = (int) $x;
            }
            if (!in_array($x, $out) && (!$nonzero || abs($x) > 1e-14)) {
                $out[] = $x;
            }
            $cnt++;
            if ($cnt > 2000) {
                echo "Error in diffrrands - not able to find valid values";
                break;
            }
        }
        $r = $out;
    } else {
        $r = range(0, $maxi);
        if ($nonzero) {
            if ($min <= 0 && $max >= 0) {
                array_splice($r, -1 * round($min / $p), 1);
            }
        }
        while ($n > count($r)) {
            $r = array_merge($r, $r);
        }

        $GLOBALS['RND']->shuffle($r);
        $r = array_slice($r, 0, $n);
        for ($i = 0; $i < $n; $i++) {
            $r[$i] = round($min + $p * $r[$i], $rn);
            if ($rn == 0) {
                $r[$i] = (int) $r[$i];
            }
        }
    }
    if ($ord == 'inc') {
        sort($r);
    } else if ($ord == 'dec') {
        rsort($r);
    }
    return $r;
}


function nonzerodiffrands($min, $max, $n = 0, $ord = 'def', $nowarn = false) {
    if (func_num_args() < 3) {
        echo "nonzerodiffrands expects 3 arguments";
        return $min;
    }
    list($min, $max) = checkMinMax($min, $max, true, 'nonzerodiffrands');
    if ($max == $min) {
        echo "nonzerodiffrands: Need min&lt;max";
        return array_fill(0, $n, $min);
    }
    $n = floor($n);
    if ($n <= 0) {
        echo "nonzerodiffrands: need n &gt; 0";
    }
    if ($n > $max - $min + 1 || ($min * $max <= 0 && $n > $max - $min)) {
        if ($GLOBALS['myrights'] > 10 && !$nowarn) {
            echo "nonzerodiffrands: min-max not far enough for n requested";
        }
    }
    if ($n > 1e4) {
        echo 'Error with diffrrands: $n too large';
        return [];
    }

    if ($n < .1 * ($max - $min)) {
        $out = array();
        $cnt = 0;
        while (count($out) < $n) {
            $x = $GLOBALS['RND']->rand($min, $max);
            if ($x != 0 && !in_array($x, $out)) {
                $out[] = $x;
            }
            $cnt++;
            if ($cnt > 2000) {
                echo "Error in nonzerodiffrrands - not able to find valid values";
                break;
            }
        }
    } else {
        $r = range($min, $max);
        if ($min <= 0 && $max >= 0) {
            array_splice($r, -1 * $min, 1);
        }
        while ($n > count($r)) {
            $r = array_merge($r, $r);
        }
        $GLOBALS['RND']->shuffle($r);
        $out = array_slice($r, 0, $n);
    }
    if ($ord == 'inc') {
        sort($out);
    } else if ($ord == 'dec') {
        rsort($out);
    }
    return $out;
}


function nonzerodiffrrands($min, $max, $p = 0, $n = 0, $ord = 'def') {
    return diffrrands($min, $max, $p, $n, $ord, true);
}


function singleshuffle($a) {
    if (!is_array($a)) {
        $a = listtoarray($a);
    }
    $GLOBALS['RND']->shuffle($a);
    if (func_num_args() > 1) {
        $n = func_get_arg(1);
        return array_slice($a, 0, $n);
    } else {
        return $a;
    }
}


function jointshuffle($a1, $a2) {  //optional third & fourth params $n1 and $n2
    if (!is_array($a1)) {
        $a1 = listtoarray($a1);
    }
    if (!is_array($a2)) {
        $a2 = listtoarray($a2);
    }
    if (count($a1) != count($a2)) {
        echo "jointshuffle should be called with two arrays of equal length";
        return [$a1, $a2];
    }
    $r = $GLOBALS['RND']->array_rand($a1, count($a1));
    $GLOBALS['RND']->shuffle($r);
    for ($j = 0; $j < count($r); $j++) {
        $ra1[$j] = $a1[$r[$j]];
        $ra2[$j] = $a2[$r[$j]];
    }
    if (func_num_args() > 2) {
        $n = func_get_arg(2);
        if (func_num_args() > 3) {
            $n2 = func_get_arg(3);
        } else {
            $n2 = $n;
        }
        return array(array_slice($ra1, 0, $n), array_slice($ra2, 0, $n2));
    } else {

        return array($ra1, $ra2);
    }
}

function randpythag($min = 1, $max = 100) {
    list($min, $max) = checkMinMax($min, $max, true, 'randpythag');
    $m = $GLOBALS['RND']->rand(ceil(sqrt($min + 1)), floor(sqrt($max - 1)));
    $n = $GLOBALS['RND']->rand(1, floor(min($m - 1, sqrt($m * $m - $min), sqrt($max - $m * $m))));
    $v = array($m * $m - $n * $n, 2 * $m * $n, $m * $m + $n * $n);
    sort($v);
    return $v;
}


$namearray[0] = ['Aaron', 'Aarón', 'Aarush', 'Abraham', 'Adam', 'Aden', 'Adewale', 'Adrian', 'Adriel', 'Agustín', 'Ahanu', 'Ahmed', 'Ahmod', 'Aidan', 'Aiden', 'Alan', 'Alang', 'Alejandro', 'Alex', 'Alexander', 'Alexei', 'Alexis', 'Alfonso', 'Alfredo', 'Alo', 'Alonso', 'Alonzo', 'Alphonso', 'Álvaro', 'Alvin', 'Amari', 'Amir', 'Anakin', 'Anderson', 'Andre', 'Andrei', 'Andres', 'Andrés', 'Andrew', 'Ángel', 'Angelo', 'Anthony', 'Antoine', 'Antonio', 'Arjun', 'Armando', 'Arno', 'Arturo', 'Arun', 'Asaad', 'Asher', 'Ashton', 'Atharv', 'Austin', 'Autry', 'Áxel', 'Ayden', 'Azarias', 'Bastián', 'Bautista', 'Ben', 'Benicio', 'Benjamin', 'Bill', 'Billy', 'Blake', 'Booker', 'Braden', 'Bradley', 'Brady', 'Brandon', 'Brayden', 'Brendan', 'Bret', 'Brian', 'Brody', 'Bruno', 'Bryan', 'Bryce', 'Bryson', 'Caden', 'Cai', 'Caleb', 'Calian', 'Calvin', 'Cameron', 'Canon', 'Carlos', 'Carlton', 'Carson', 'Carter', 'Casey', 'Cavanaugh', 'Cayden', 'Cesar', 'Chad', 'Chan', 'Chance', 'Charles', 'Chase', 'Cheick', 'Cheng', 'Chris', 'Christian', 'Christopher', 'CJ', 'Cody', 'Colan', 'Colby', 'Cole', 'Collin', 'Colton', 'Conner', 'Connor', 'Cooper', 'Corey', 'Cornell', 'Courri', 'Craig', 'Cristian', 'Curtis', 'Dajon', 'Dak', 'Dakota', 'Dale', 'Dalton', 'Damian', 'Damien', 'Daniel', 'Danny', 'Dante', 'Daran', 'Dario', 'Darius', 'Darnell', 'Darrell', 'Darryl', 'David', 'Daymond', 'Deandre', 'DeAndre', 'Deion', 'Demetrius', 'Denali', 'Derek', 'Deshawn', 'Devante', 'Devin', 'Devon', 'Devonte', 'Diego', 'Dion', 'Dmitry', 'Dominic', 'Dominique', 'Donald', 'Donnel', 'Donovan', 'Duan', 'Dustin', 'Dwayne', 'Dwight', 'Dylan', 'Edgar', 'Eduardo', 'Edward', 'Edwin', 'Eli', 'Elías', 'Elijah', 'Emanuel', 'Emiliano', 'Emilio', 'Emmanuel', 'Emmett', 'Enrique', 'Enzo', 'Eric', 'Erick', 'Erik', 'Ermias', 'Ervin', 'Esteban', 'Ethan', 'Evan', 'Facundo', 'Farrell', 'Farzad', 'Felipe', 'Fernando', 'Finn', 'Foluso', 'Forest', 'Francisco', 'Franco', 'Frank', 'Frederick', 'Furnell', 'Gabriel', 'Gael', 'Gage', 'Garlin', 'Garrett', 'Gavin', 'George', 'Gerardo', 'Giovanni', 'Gonzalo', 'Grant', 'Gregory', 'Guyton', 'Hakeem', 'Hampton', 'Hao', 'Harrison', 'Hayden', 'Hector', 'Henry', 'Herold', 'Hopi', 'Hu', 'Hudson', 'Hugo', 'Hunter', 'Ian', 'Ibrahim', 'Ibram', 'Ignacio', 'Iker', 'Isaac', 'Isaiah', 'Israel', 'Ivan', 'Izaak', 'Izan', 'Jabulani', 'Jace', 'Jack', 'Jackson', 'Jacob', 'Jacy', 'Jaden', 'Jahkil', 'Jaime', 'Jake', 'Jalen', 'Jamaal', 'Jamal', 'James', 'Jamison', 'Jared', 'Jason', 'Javier', 'Jay', 'Jayceon', 'Jayden', 'Jaylen', 'Jayson', 'Jeff', 'Jeffrey', 'Jeremiah', 'Jeremy', 'Jermaine', 'Jerome', 'Jerónimo', 'Jesse', 'Jessie', 'Jimmy', 'Joaquín', 'Joel', 'Joey', 'Johan', 'John', 'John Carlo', 'John Lloyd', 'John Mark', 'John Michael', 'John Paul', 'John Rey', 'Jon', 'Jonah', 'Jonathan', 'Jordan', 'Jorge', 'Jose', 'José', 'Joseph', 'Josh', 'Joshua', 'Josiah', 'Juan', 'Juan José', 'Juan Martín', 'Juan Pablo', 'Julian', 'Julián', 'Julio', 'Justice', 'Justin', 'Juwan', 'Kaden', 'Kai', 'Kaiden', 'Kaleb', 'Kareem', 'Karlus', 'Kayden', 'Keegan', 'Kehinde', 'Kele', 'Ken', 'Kendrick', 'Kenneth', 'Kenny', 'Kerel', 'Kevin', 'Keyon', 'Kim', 'King', 'Kirk', 'Kosumi', 'Krishna', 'Kwame', 'Kwan', 'Kyle', 'Kyrie', 'Lamont', 'Landon', 'LeBron', 'Lee', 'Lennon', 'Leo', 'León', 'Leonardo', 'Leonel', 'Lester', 'Levi', 'Lewis', 'Li', 'Lian', 'Lloyd', 'Logan', 'Londell', 'Lorenzo', 'Louis', 'Loyiso', 'Luan', 'Luca', 'Lucas', 'Luciano', 'Luis', 'Luke', 'Luther', 'Major', 'Malachi', 'Malcolm', 'Malik', 'Mamadou', 'Mandla', 'Manny', 'Manuel', 'Marcel', 'Marcelo', 'Marco', 'Marcos', 'Marcus', 'Mario', 'Mark', 'Marquis', 'Marshall', 'Martin', 'Martín', 'Mason', 'Mateo', 'Matías', 'Mato', 'Matt', 'Matthew', 'Maurice', 'Mauricio', 'Max', 'Maxim', 'Maximiliano', 'Maxwell', 'Mayowa', 'Meng', 'Micah', 'Michael', 'Miguel', 'Mika', 'Mikhail', 'Miles', 'Mitchell', 'Mohamed', 'Mondaire', 'Montana', 'Moussa', 'Nahele', 'Nasir', 'Nate', 'Nathan', 'Nathaniel', 'Nayati', 'Neel', 'Nicholas', 'Nick', 'Nicolás', 'Nihad', 'Noah', 'Nodin', 'Noel', 'Nolan', 'Nova', 'Nuka', 'Nyeeam', 'Oliver', 'Omar', 'Oscar', 'Otis', 'Owen', 'Pablo', 'Parker', 'Parnell', 'Parvez', 'Patrick', 'Paul', 'Paulo', 'Pedro', 'Perry', 'Peter', 'Peyton', 'Phillip', 'Pierre', 'Porter', 'Pranav', 'Preston', 'Qasim', 'Quan', 'Quinn', 'Rafael', 'Rahquez', 'Ramogi', 'Randall', 'Raymond', 'Recardo', 'Reggie', 'Reginald', 'Ren', 'Reza', 'Ricardo', 'Richard', 'Ricky', 'Riley', 'Rippy', 'Ritchie', 'Robert', 'Roberto', 'Rodney', 'Rodrigo', 'Roman', 'Roscoe', 'Ross', 'Roy', 'Russell', 'Ryan', 'Salvador', 'Sam', 'Samuel', 'Santiago', 'Santino', 'Santos', 'Scott', 'Sean', 'Sebastián', 'Seni', 'Sergio', 'Seth', 'Shane', 'Shaun', 'Shaurya', 'Shawn', 'Simón', 'Skyler', 'Spencer', 'Stacey', 'Stephen', 'Sterling', 'Steven', 'Tadeo', 'Takoda', 'Ta-Nehisi', 'Tanner', 'Tarell', 'Tauri', 'Taylor', 'Terrance', 'Terrell', 'Terrence', 'Tevin', 'Thiago', 'Thomas', 'Timothy', 'Tobias', 'Todd', 'Tokala', 'Tom', 'Tomás', 'Tony', 'Tran', 'Travis', 'Tremanie', 'Trent', 'Trenton', 'Trevante', 'Trevion', 'Trevon', 'Trevor', 'Trey', 'Treyvon', 'Tristan', 'Trymaine', 'Tyler', 'Tyrone', 'Valentino', 'Van', 'Vicente', 'Victor', 'Vihaan', 'Vincent', 'Wade', 'Walter', 'Warren', 'Wayne', 'Wayra', 'Wesley', 'William', 'Willie', 'Wiyot', 'Wyatt', 'Xavier', 'Yan', 'Yane', 'Yoni', 'Zach', 'Zachary', 'Zaire', 'Zeke', 'Zephan', 'Zhang', 'Zion'];
$namearray[1] = ['Aaliyah', 'Abby', 'Abigail', 'Abril', 'Addison', 'Adriana', 'Adrianna', 'Agustina', 'Ainhoa', 'Aisha', 'Aitana', 'Aiyana', 'Akilah', 'Alana', 'Alba', 'Alecia', 'Alejandra', 'Aleshya', 'Alexa', 'Alexandra', 'Alexandria', 'Alexia', 'Alexis', 'Alexus', 'Alice', 'Alicia', 'Alina', 'Aliyah', 'Allison', 'Alma', 'Alondra', 'Althea', 'Alyce', 'Alyson', 'Alyssa', 'Amahle', 'Amanda', 'Amani', 'Amber', 'Amelia', 'Amina', 'Aminata', 'Amy', 'Ana', 'Ana Paula', 'Ananya', 'Anastasia', 'Andrea', 'Angel', 'Angela', 'Angelica', 'Angelina', 'Angeline', 'Anika', 'Aniyah', 'Anna', 'Antonella', 'Antonia', 'Anushka', 'Anya', 'Aponi', 'April', 'Ariana', 'Arianna', 'Ashley', 'Ashlyn', 'Ashton', 'Asia', 'Aubrey', 'Audrey', 'Aurora', 'Autumn', 'Ava', 'Averie', 'Avery', 'Avni', 'Bailey', 'Banu', 'Bao', 'Bea', 'Bella', 'Betty', 'Bianca', 'Bisa', 'Braelin', 'Breanna', 'Brenda', 'Breonna', 'Bria', 'Brianna', 'Brigeth', 'Brittany', 'Brooke', 'Brooklyn', 'Caitlyn', 'Camila', 'Candela', 'Capria', 'Carie', 'Carissa', 'Carla', 'Carlota', 'Carmen', 'Carolina', 'Caroline', 'Carolyn', 'Carrie', 'Cassandra', 'Cassidy', 'Catalina', 'Catherine', 'Catori', 'Cecilia', 'Cedrica', 'Charlotte', 'Chasity', 'Chee', 'Chelsea', 'Cheyenne', 'Chloe', 'Christina', 'Christine', 'Christy', 'Ciara', 'Claire', 'Clara', 'Claudia', 'Colleen', 'Constanza', 'Cori', 'Courtney', 'Cristina', 'Crystal', 'Daisy', 'Dallas', 'Dana', 'DaNeeka', 'Daniela', 'Danielle', 'Danna', 'Dawn', 'Daysha', 'Dazzline', 'Deborah', 'Deja', 'Delaney', 'Delfina', 'Delia', 'DeShuna', 'Destiny', 'Diamond', 'Diana', 'Dyani', 'Ebony', 'Edith', 'Elana', 'Elena', 'Elisa', 'Elizabeth', 'Ella', 'Ellie', 'Elu', 'Emilia', 'Emily', 'Emma', 'Enola', 'Erica', 'Erika', 'Erin', 'Esmeralda', 'Eva', 'Eve', 'Evelyn', 'Ezra', 'Faith', 'Fan', 'Fatema', 'Fatoumata', 'Fayth', 'Fernanda', 'Francesca', 'Gabriela', 'Gabriella', 'Gabrielle', 'Gail', 'Genesis', 'Gianna', 'Giselle', 'Grace', 'Gracie', 'Guadalupe', 'Gwen', 'Hailey', 'Haley', 'Halona', 'Hanita', 'Hanna', 'Hannah', 'Hazzell', 'Heather', 'Heaven', 'Hillary', 'Himari', 'Holly', 'Hope', 'Icema', 'Ida', 'Imani', 'Indigo', 'Isabel', 'Isabella', 'Isabelle', 'Isfa', 'Issa', 'Istas', 'Ivanna', 'Jacqueline', 'Jada', 'Jade', 'Jamie', 'Janai', 'Jane', 'Janelle', 'Janet', 'Janice', 'Jashanna', 'Jasmin', 'Jasmine', 'Jayla', 'Jazmin', 'Jeanette', 'Jenna', 'Jennifer', 'Jenny', 'Jessa Mae', 'Jessica', 'Jia', 'Jillian', 'Jocelyn', 'Johnetta', 'Joni', 'Jordan', 'Jordyn', 'Josefa', 'Josefina', 'Juana', 'Julia', 'Juliana', 'Julie', 'Julieta', 'Kaileika', 'Kaitlyn', 'Kamala', 'Karen', 'Karina', 'Karissa', 'Karla', 'Kasa', 'Kassandra', 'Kate', 'Katelyn', 'Kateri', 'Katherine', 'Kathryn', 'Katie', 'Katrice', 'Kayla', 'Kaylee', 'Kelly', 'Kelsey', 'Kelsi', 'Kendall', 'Kendra', 'Kenita', 'Kennedy', 'Keyanna', 'Kiana', 'Kiara', 'Kiersten', 'Kimani', 'Kimberlé', 'Kimberly', 'Kimi', 'Kimora', 'Kira', 'Kisha', 'Kizzmekia', 'Kori', 'Kristel', 'Kristen', 'Kristina', 'Kristyn', 'Krystal', 'Kyla', 'Kylee', 'Kylie', 'Lacee', 'Lafyette', 'Laia', 'Laila', 'Latasha', 'Lateefah', 'LaTosha', 'Laura', 'Lauren', 'Laverne', 'Layla', 'Leah', 'Leanne', 'Leilani', 'Leire', 'Lena', 'Leslie', 'Liana', 'Liliana', 'Lillian', 'Lily', 'Linda', 'Lindsay', 'Lindsey', 'Lola', 'Lomasi', 'London', 'Lu', 'Lucía', 'Luciana', 'Lucy', 'Luna', 'Lydia', 'Lynda', 'Mackenzie', 'Madelaine', 'Madeline', 'Madelyn', 'Madison', 'Maggie', 'Maia', 'Maisha', 'Maite', 'Maji', 'Makayla', 'Makenzie', 'Manuela', 'Margaret', 'Mari', 'María', 'María Fernanda', 'María Victoria', 'Mariah', 'Mariam', 'Mariana', 'Marie', 'Mariel', 'Maris', 'Marissa', 'Marley', 'Marsai', 'Martina', 'Mary Grace', 'Mary Joy', 'Maxine', 'Maya', 'Maylin', 'Maymay', 'Mckenzie', 'Megan', 'Mei', 'Melanie', 'Melique', 'Melissa', 'Mellody', 'Melynda', 'Meredith', 'Merryll', 'Mia', 'Michelle', 'Mika', 'Mikayla', 'Mini', 'Miracle', 'Miranda', 'Misty', 'Mitena', 'Molly', 'Monique', 'Morgan', 'Mya', 'Na’estse', 'Nadia', 'Nadine', 'Nakala', 'Nandi', 'Naomi', 'Natalia', 'Natalie', 'Natasha', 'Navaeh', 'Navya', 'Neichelle', 'Nevaeh', 'Neveah', 'Nia', 'Nicole', 'Nikole', 'Nina', 'Noa', 'Noelle', 'Nylah', 'Odina', 'Olivia', 'Opal', 'Orenda', 'Orlena', 'Paige', 'Palesa', 'Pamela', 'Pari', 'Paris', 'Patricia', 'Patriciana', 'Patrisse', 'Paula', 'Paulina', 'Pavati', 'Payton', 'Peyton', 'Pilar', 'Precious', 'Prisha', 'Priya', 'Quetta', 'Rachael', 'Rachel', 'Rachelle', 'Rafaela', 'Raquel', 'Rashida', 'Raven', 'Reagan', 'Rebecca', 'Regina', 'Renata', 'Renee', 'Reshanda', 'Rhianna', 'Riley', 'Rita', 'Riya', 'Romina', 'Rosa', 'Rosetta', 'Roya', 'Ruby', 'Rylee', 'Saada', 'Sabrina', 'Sadie', 'Sadiqa', 'Sahana', 'Sakari', 'Salomé', 'Samantha', 'Samira', 'Sara', 'Sarah', 'Savannah', 'Scarlett', 'Scherita', 'Serena', 'Serenity', 'Shani', 'Shania', 'Shanice', 'Shannon', 'Shante', 'Shantel', 'Sharlee', 'Shelby', 'Sheniqua', 'Sierra', 'Skylar', 'Sloane', 'Sofía', 'Sonia', 'Sonya', 'Sophia', 'Sophie', 'Soraya', 'Soyala', 'Stacey', 'Stacy', 'Stephanie', 'Summer', 'Sunny', 'Sybil', 'Sydney', 'Tabria', 'Tallulah', 'Tamika', 'Tanya', 'Tara', 'Tarana', 'Tatiana', 'Tayen', 'Taylor', 'Teresa', 'Teyonah', 'Thandiwe', 'Thulile', 'Tia', 'Tiana', 'Tiara', 'Tierra', 'Tiffany', 'Tiva', 'Tomi', 'Tracee', 'Tracey', 'Trashia', 'Treasure', 'Trinidad', 'Trinity', 'Umbrosia', 'Urika', 'Valentina', 'Valeria', 'Valerie', 'Vanessa', 'Veronica', 'Victoria', 'Violeta', 'Vivian', 'Wei', 'Wendy', 'Whitney', 'Winona', 'Ximena', 'Yara', 'Yolanda', 'Yvette', 'Zari', 'Zhao', 'Zheng', 'Zoe', 'Zoey', 'Zuri'];

$cityarray_US = explode(',', 'Los Angeles,Dallas,Houston,Atlanta,Detroit,San Francisco,Minneapolis,St. Louis,Baltimore,Pittsburgh,Cincinnati,Cleveland,San Antonio,Las Vegas,Milwaukee,Oklahoma City,New Orleans,Tucson,New York City,Chicago,Philadelphia,Miami,Boston,Phoenix,Seattle,San Diego,Tampa,Denver,Portland,Sacramento,Orlando,Kansas City,Nashville,Memphis,Hartford,Salt Lake City');
$cityarray_CA = explode(',', 'Toronto,Montreal,Calgary,Ottawa,Edmonton,Mississauga,Winnipeg,Vancouver,Brampton,Hamilton,Québec City,Surrey,Laval,Halifax,London,Gatineau,Saskatoon,Kitchener,Burnaby,Windsor,Regina,Victoria,Richmond,Fredericton,Saint John,Yellowknife,Sydney,Iqaluit,Charlottetown,Whitehorse');

$countryarray = explode(',', 'Afghanistan,Albania,Algeria,Andorra,Angola,Antigua & Deps,Argentina,Armenia,Australia,Austria,Azerbaijan,Bahamas,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bhutan,Bolivia,Bosnia Herzegovina,Botswana,Brazil,Brunei,Bulgaria,Burkina,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Central African Rep,Chad,Chile,China,Colombia,Comoros,Congo,Congo,Costa Rica,Croatia,Cuba,Cyprus,Czech Republic,Denmark,Djibouti,Dominica,Dominican Republic,East Timor,Ecuador,Egypt,El Salvador,Equatorial Guinea,Eritrea,Estonia,Ethiopia,Fiji,Finland,France,Gabon,Gambia,Georgia,Germany,Ghana,Greece,Grenada,Guatemala,Guinea,Guinea-Bissau,Guyana,Haiti,Honduras,Hungary,Iceland,India,Indonesia,Iran,Iraq,Ireland,Israel,Italy,Ivory Coast,Jamaica,Japan,Jordan,Kazakhstan,Kenya,Kiribati,North Korea,South Korea,Kosovo,Kuwait,Kyrgyzstan,Laos,Latvia,Lebanon,Lesotho,Liberia,Libya,Liechtenstein,Lithuania,Luxembourg,Macedonia,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Marshall Islands,Mauritania,Mauritius,Mexico,Micronesia,Moldova,Monaco,Mongolia,Montenegro,Morocco,Mozambique,Myanmar,Namibia,Nauru,Nepal,Netherlands,New Zealand,Nicaragua,Niger,Nigeria,Norway,Oman,Pakistan,Palau,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Poland,Portugal,Qatar,Romania,Russia,Rwanda,St Kitts & Nevis,St Lucia,Saint Vincent & the Grenadines,Samoa,San Marino,Sao Tome & Principe,Saudi Arabia,Senegal,Serbia,Seychelles,Sierra Leone,Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,South Sudan,Spain,Sri Lanka,Sudan,Suriname,Swaziland,Sweden,Switzerland,Syria,Taiwan,Tajikistan,Tanzania,Thailand,Togo,Tonga,Trinidad & Tobago,Tunisia,Turkey,Turkmenistan,Tuvalu,Uganda,Ukraine,United Arab Emirates,United Kingdom,United States,Uruguay,Uzbekistan,Vanuatu,Vatican City,Venezuela,Vietnam,Yemen,Zambia,Zimbabwe');

function randcities($n = 1, $country = "USA") {
    global $cityarray_US, $cityarray_CA;

    if ($country == "Canada") {
        $cityarray = $cityarray_CA;
    } elseif ($country == "USA") {
        $cityarray = $cityarray_US;
    } else {
        echo "randcity only accepts 'USA' and 'Canada' at the moment.";
        return "";
    }


    $c = count($cityarray);
    if ($n == 1) {
        return $cityarray[$GLOBALS['RND']->rand(0, $c - 1)];
    } else {
        $out = $cityarray;
        $GLOBALS['RND']->shuffle($out);
        return array_slice($out, 0, $n);
    }
}

function randcountries($n = 1) {
    global $countryarray;
    $c = count($countryarray);
    if ($n == 1) {
        return $countryarray[$GLOBALS['RND']->rand(0, $c - 1)];
    } else {
        $out = $countryarray;
        $GLOBALS['RND']->shuffle($out);
        return array_slice($out, 0, $n);
    }
}

function randstates($n = 1, $country = "USA") {

    if ($country == "Canada") {
        $states = array("Alberta", "British Columbia", "Manitoba", "New Brunswick", "Newfoundland and Labrador", "Northwest Territories", "Nova Scotia", "Nunavut", "Ontario", "Prince Edward Island", "Quebec", "Saskatchewan", "Yukon");
    } elseif ($country == "USA") {
        $states = array("Alabama", "Alaska", "Arizona", "Arkansas", "California", "Colorado", "Connecticut", "Delaware", "Dist. of Columbia", "Florida", "Georgia", "Hawaii", "Idaho", "Illinois", "Indiana", "Iowa", "Kansas", "Kentucky", "Louisiana", "Maine", "Maryland", "Massachusetts", "Michigan", "Minnesota", "Mississippi", "Missouri", "Montana", "Nebraska", "Nevada", "New Hampshire", "New Jersey", "New Mexico", "New York", "North Carolina", "North Dakota", "Ohio", "Oklahoma", "Oregon", "Pennsylvania", "Rhode Island", "South Carolina", "South Dakota", "Tennessee", "Texas", "Utah", "Vermont", "Virginia", "Washington", "West Virginia", "Wisconsin", "Wyoming");
    } else {
        echo "randstate only accepts 'USA' and 'Canada'.";
        return "";
    }

    $c = count($states);
    if ($n == 1) {
        return $states[$GLOBALS['RND']->rand(0, $c - 1)];
    } else {
        $GLOBALS['RND']->shuffle($states);
        return array_slice($states, 0, $n);
    }
}

function randstate($country = "USA") {
    return randstates(1, $country);
}
function randcity($country = "USA") {
    return randcities(1, $country);
}
function randcountry() {
    return randcountries(1);
}

function randnames($n = 1, $gender = 2) {
    global $namearray;
    $n = floor($n);
    if ($n == 1) {
        if ($gender == 2) {
            $gender = $GLOBALS['RND']->rand(0, 1);
        }
        $maxNameIndex = count($namearray[$gender]) - 1;
        return $namearray[$gender][$GLOBALS['RND']->rand(0, $maxNameIndex)];
    } else {
        $out = array();
        $maxNameIndex = [count($namearray[0]) - 1, count($namearray[1]) - 1];
        // use a step to avoid adjacent names to avoid too-similar names
        $step = max(1, min(20, floor(min($maxNameIndex) / $n)));
        $locs = [];
        $locs[0] = diffrrands(0, $maxNameIndex[0], $step, $n);
        $locs[1] = diffrrands(0, $maxNameIndex[1], $step, $n);
        $thisgender = $gender;
        for ($i = 0; $i < $n; $i++) {
            if ($gender == 2) {
                $thisgender = $GLOBALS['RND']->rand(0, 1);
            }
            $out[] = $namearray[$thisgender][$locs[$thisgender][$i]];
        }
        return $out;
    }
}

function randmalenames($n = 1) {
    return randnames($n, 0);
}
function randfemalenames($n = 1) {
    return randnames($n, 1);
}
function randname() {
    return randnames(1, 2);
}
function randnamewpronouns($g = 2) {
    $gender = $GLOBALS['RND']->rand(0, 1);

    if ($g == 2) {
        if ($gender == 0) { //male
            return array(randnames(1, 0), _('he'), _('him'), _('his'), _('his'), _('himself'));
        } else {
            return array(randnames(1, 1), _('she'), _('her'), _('her'), _('hers'), _('herself'));
        }
    } elseif ($g == 'neutral') {
        if ($gender == 0) { //male
            return array(randnames(1, 0), _('they'), _('them'), _('their'), _('theirs'), _('themself'));
        } else {
            return array(randnames(1, 1), _('they'), _('them'), _('their'), _('theirs'), _('themself'));
        }
    }
}

function randmalename() {
    return randnames(1, 0);
}

function randfemalename() {
    return randnames(1, 1);
}
