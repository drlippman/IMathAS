<?php
//a collection of social choice routines
//
//Version 1.0 Sept 27, 2012

//potential source of Banzhaf: http://www.hippasus.com/resources/socialsoftware/banzhaf.py

                  
global $allowedmacros;
array_push($allowedmacros, 'apportion','apportion_info', 'banzhafpower', 'shapleyshubikpower');

//apportion(populations, seats, method)
// populations: array or list of state populations
// seats: number of seats to apportion
// method: method to use: "hamilton", "jefferson", "adams", "webster", 
//                        "huntington", "lowndes"
// returns an array giving the apportionment to each state
function apportion($pop, $seats, $method, $md = 0) {
	if (!is_array($pop)) {
		$pop = explode(',',$pop);
	}
	list($md, $quotas, $chopped, $other, $outdiv) = apportion_info($pop, $seats, $method);

	if ($method=='hamilton' || $method=='lowndes') {
		$toadd = $seats - array_sum($chopped);
		if ($toadd>0) {
			arsort($other);
			$i = 0;
			foreach ($other as $state=>$dec) {
				$chopped[$state]++;
				$i++;
				if ($i==$toadd) { break;}
			}
		}
		return $chopped;
	} else if ($method=='jefferson' || $method=='adams' || $method=='webster') {
		if ($outdiv==='fail') {
			return 'fail';
		}
		$outdiv = explode(',',substr($outdiv,1,-1));
		$moddiv = ($outdiv[0]+$outdiv[1])/2;
		foreach ($pop as $s=>$p) {
			if ($method=='jefferson') {
				$chopped[$s] = floor($p/$moddiv);
			} else if ($method=='adams') {
				$chopped[$s] = ceil($p/$moddiv);
			} else if ($method=='webster') {
				$chopped[$s] = round($p/$moddiv);
			}
		}
		return $chopped;
	} else if ($method=='huntington') {
		if ($outdiv==='fail') {
			return 'fail';
		}
		$outdiv = explode(',',substr($outdiv,1,-1));
		$moddiv = ($outdiv[0]+$outdiv[1])/2;
		foreach ($pop as $s=>$p) {
			$q = $p/$moddiv;
			$chopped[$s] = floor($q);
			if ($q>sqrt($chopped[$s]*($chopped[$s]+1))) {$chopped[$s]++;}
		}
		return $chopped;
	} 
	
	
}

//apportion_info(populations, seats, method)
// populations: array or list of state populations
// seats: number of seats to apportion
// method: method to use: "hamilton", "jefferson", "adams", "webster", 
//                        "huntington", "lowndes"
// returns an array of:
//  0) the divisor
//  1  an array of quotas (decimal values), 
//  2) an array of initial lower quotas (hamilton, jefferson, lowndes)
//     an array of initial upper quotas (adams)
//     an array of initial rounded quotas (webster)
//     an array of initial allocations (huntington)
//  3) an array of geometric means (huntington)
//     an array of decimal/whole ratios (lowndes)
//     an array of decimal remainders (hamilton)
//     nothing (others)
//  4) valid modified divisors, in interval notation (jefferson, adams, webster)
function apportion_info($pop, $seats, $method) {
	if (!is_array($pop)) {
		$pop = explode(',',$pop);
	}
	$divisor = array_sum($pop)/$seats;
	$quotas = array();
	foreach ($pop as $s=>$p) {
		$quotas[$s] = $p/$divisor;
	}
	
	$outdiv = '';
	$luq = array();
	$other = array();
	if ($method=='hamilton') {
		foreach ($quotas as $s=>$q) {
			$luq[$s] = floor($q);
			$other[$s] = $q - $luq[$s];
		}
		$totest = array_values($other);
		sort($totest);
		$last = -1;
		foreach ($totest as $v) {
			if (abs($v-$last)<1e-9) {
				$outdiv = 'fail';
				break;
			}
			$last = $v;
		}
	} else if ($method=='jefferson') {
		$toraiseup = array();
		foreach ($quotas as $s=>$q) {
			$luq[$s] = floor($q);
			//calculate divisor needed to raise to next whole value
			for ($i=1;$i<5;$i++) {
				$toraiseup[] = $pop[$s]/($luq[$s]+$i);
			}
		}
		$toadd = $seats - array_sum($luq);
		arsort($toraiseup);
		$moddivs = array_values($toraiseup);

		//if the next value is the same, then the divisor that adds $toadd additional
		//seats would add $toadd+1 additional seats, so the method fails.
		if ($toadd==0) {
			$tolowerdown = array();
			foreach ($quotas as $s=>$q) {
				$luq[$s] = floor($q);
				//calculate divisor needed to lower to next whole value
				if ($luq[$s]==0) { break;}
				$tolowerdown[] = $pop[$s]/($luq[$s]);
			}
			$outdiv = '('.max($toraiseup).','.min($tolowerdown).']';
		} else if ($moddivs[$toadd-1]==$moddivs[$toadd]) {
			$outdiv = "fail";
		} else {
			$outdiv = '('.$moddivs[$toadd].','.$moddivs[$toadd-1].']';
		}

	} else if ($method=='adams') {
		$tolowerdown = array();
		foreach ($quotas as $s=>$q) {
			$luq[$s] = ceil($q);
			//calculate divisor needed to lower to next whole value
			for ($i=1;$i<5;$i++) {
				if ($luq[$s]-$i==0) { break;}
				$tolowerdown[] = $pop[$s]/($luq[$s]-$i);
			}
		}
		$tosub = array_sum($luq) - $seats;
		asort($tolowerdown);
		$moddivs = array_values($tolowerdown);
		//if the next value is the same, then the divisor that adds $tosub additional
		//seats would add $tosub+1 additional seats, so the method fails.
		if ($tosub==0) {
			$toraiseup = array();
			foreach ($quotas as $s=>$q) {
				$luq[$s] = ceil($q);
				//calculate divisor needed to lower to next whole value
				if ($luq[$s]-1==0) { break;}
				$toraiseup[] = $pop[$s]/($luq[$s]);
			}
			$outdiv = '['.max($toraiseup).','.min($tolowerdown).')';
		} else if ($moddivs[$tosub-1]==$moddivs[$tosub]) {
			$outdiv = "fail";
		} else {
			$outdiv = '['.$moddivs[$tosub-1].','.$moddivs[$tosub].')';
		}
	} else if ($method=='webster') {
		$tolowerdown = array();
		foreach ($quotas as $s=>$q) {
			$luq[$s] = round($q);
			//calculate divisor needed to lower to next whole value
		}
		$toadd = $seats - array_sum($luq);
		if ($toadd>=0) {
			//need to add seats.
			foreach ($quotas as $s=>$q) {
				for ($i=0;$i<5;$i++) {
					$tochange[] = $pop[$s]/($luq[$s]+$i+.5); //only need it to get over #.5
				}
			}
			arsort($tochange);
		} 
		if ($toadd==0) {
			$maxq = max($tochange);
			$tochange= array();
		}
		if ($toadd<=0){
			$toadd = -1*$toadd;
			//proceed as in adams
			foreach ($quotas as $s=>$q) {
				for ($i=0;$i<5;$i++) {
					if ($luq[$s]-$i-.50000001 <= 0) { break;}
					$tochange[] = $pop[$s]/($luq[$s]-$i-.5); //need it under #.5
				}
			}
			asort($tochange);
		}
		$moddivs = array_values($tochange);

		//if the next value is the same, then the divisor that adds $toadd additional
		//seats would add $toadd+1 additional seats, so the method fails.
		if ($toadd==0) {
			$minq = min($tochange);
			$outdiv = '('.$maxq.','.$minq.')';
		} else if ($moddivs[$toadd-1]==$moddivs[$toadd]) {
			$outdiv = "fail";
		} else {
			$outdiv = '('.min($moddivs[$toadd-1],$moddivs[$toadd]).','.max($moddivs[$toadd-1],$moddivs[$toadd]).')';
		}
	} else if ($method=='huntington') {
		foreach ($quotas as $s=>$q) {
			$luq[$s] = floor($q);
			$other[$s] = sqrt($luq[$s]*($luq[$s]+1));
			if ($q>$other[$s]) { $luq[$s]++;}
		}
        $toadd = $seats - array_sum($luq);
		if ($toadd==0) {
			$tochange = array();
			foreach ($quotas as $s=>$q) {
				if (floor($q)==0) {continue;}
				for ($i=0;$i<min(floor($q),1);$i++) {
					 $newq = $pop[$s]/(sqrt((floor($q)+$i)*(floor($q)+$i+1))+.0001); //what to get it over GM?
					 if ($newq<$divisor) {
					 	 $tochange[] = $newq;
					 }
				}
			}
			rsort($tochange);
			$quotamin = $tochange[0];
            $tochange = array();
			foreach ($quotas as $s=>$q) {
				if (floor($q)==0) {continue;}
				for ($i=0;$i<min(floor($q),2);$i++) {
                    $newq = $pop[$s]/(sqrt((floor($q)-$i)*(floor($q)-$i+1))-.000001); //what to get it under GM?
					if ($newq>$divisor) {
						$tochange[] = $newq; 
					}
				}
            }
			sort($tochange);
			$quotamax = $tochange[0];
		} else if ($toadd>0) {
			//need to add seats, so lower the divisor
			foreach ($quotas as $s=>$q) {
				if (floor($q)==0) {continue;}
				for ($i=0;$i<min(floor($q),4);$i++) {
					 $newq = $pop[$s]/(sqrt((floor($q)+$i)*(floor($q)+$i+1))+.000001); //what to get it over GM?
					 if ($newq<$divisor) {
					 	 $tochange[] = $newq;
					 }
				}
			}
			arsort($tochange);
		} else {
			$toadd = -1*$toadd;
			//need to remove seats, so increase the divisor
			foreach ($quotas as $s=>$q) {
				if (floor($q)==0) {continue;}
				for ($i=0;$i<min(floor($q),4);$i++) {
					$newq = $pop[$s]/(sqrt((floor($q)-$i)*(floor($q)-$i+1))-.0001); //what to get it under GM?
					if ($newq>$divisor) {
						$tochange[] = $newq; 
					}
				}
			}
			asort($tochange);
		}
		$sk = array_keys($tochange);
		$moddivs = array_values($tochange);
		
		if ($toadd==0) {
			$outdiv = "($quotamin,$quotamax)";
		} else if ($moddivs[$toadd-1]==$moddivs[$toadd]) {
			//if the next value is the same, then the divisor that adds $toadd additional
			//seats would add $toadd+1 additional seats, so the method fails.
			$outdiv = "fail";
		} else {
			$outdiv = '('.min($moddivs[$toadd-1],$moddivs[$toadd]).','.max($moddivs[$toadd-1],$moddivs[$toadd]).')';
		}

	} else if ($method=='lowndes') {
		foreach ($quotas as $s=>$q) {
			$luq[$s] = floor($q);
			$other[$s] = ($luq[$s]==0?1e100:($q - $luq[$s])/$luq[$s]);
		}
		$totest = array_values($other);
		sort($totest);
		$last = -1;
		foreach ($totest as $v) {
			if (abs($v-$last)<1e-9) {
				$outdiv = 'fail';
				break;
			}
			$last = $v;
		}
	}
	return array($divisor, $quotas, $luq, $other, $outdiv);
}


//banzhafpower(weights, quota)
// weights: list or array of voter weights
// quota: voting system quota
// format: defaults to decimal power index values
//         "counts": power counts, "frac": power index as fractions strings
// calculates the Banzhaf power index for the voting system.
// returns an array of power indexes 
//
// Based on http://www.hippasus.com/resources/socialsoftware/banzhaf.py (CC-BY)
function banzhafpower($weight, $quota, $format = "decimal") {
   			        
    $max_order = array_sum($weight);		# compute the maximum order for our polynomial
    $polynomial = array_fill(0, $max_order+1,0);
    $polynomial[0] = 1;
    
    $current_order = 0;                              # compute the polynomial coefficients
    $aux_polynomial = array_values($polynomial);
    for ($i=0;$i<count($weight);$i++) {
        $current_order += $weight[$i];
        
        for ($j=0;$j<$current_order+1;$j++) {
        	$aux_polynomial[$j] = $polynomial[$j] + (($j<$weight[$i])?0:$polynomial[$j-$weight[$i]]);
        }
        $polynomial = array_values($aux_polynomial);
    }
    $banzhaf_power = array_fill(0, count($weight), 0);              # create a list to hold the Banzhaf Power for each voter
    $swings = array_fill(0, $quota, 0);                             # create a list to compute the swings for each voter
    
    for ($i=0;$i<count($weight);$i++) {
    	    for ($j=0;$j<$quota;$j++) {				 # fill the swings list
    	    	    if ($j<$weight[$i]) {
    	    	    	    $swings[$j] = $polynomial[$j];
    	    	    } else {
    	    	    	    $swings[$j] = $polynomial[$j] - $swings[$j - $weight[$i]];
    	    	    }
    	    }
    	    for ($k=0;$k<$weight[$i];$k++) {
    	    	    $banzhaf_power[$i] += $swings[$quota-1-$k];	# fill the Banzhaf Power vector
    	    }
    }
    
    $total_power = array_sum($banzhaf_power);                       # compute the Total Banzhaf Power
    
    if ($format=="frac") {
    	    foreach ($banzhaf_power as $i=>$c) {
    	    	    $banzhaf_power[$i] = "$c/$total_power";
    	    }
    } else if ($format=="decimal") {
    	    foreach ($banzhaf_power as $i=>$c) {
    	    	    $banzhaf_power[$i] = $c/$total_power;
    	    }
    }
    
    return $banzhaf_power;
}

//shapleyshubikpower(weights, quota, [format])
// weights: list or array of voter weights   (max: 7 voters)  
// quota: voting system quota
// format: defaults to decimal power index values
//         "counts": power counts, "frac": power index as fractions strings
// calculates the Shapley-Shubik power index for the voting system.
// returns an array of power indexes 
function shapleyshubikpower($weights, $quota, $format = "decimal") {
	$n = count($weights);
	if ($n>7) {return "fail";}
	$pcnt = array_fill(0, $n, 0);
	
	shapleyshubikrecurse($pcnt, $weights, $quota, array_fill(0,$n,0), -1, 0, $n);
	
	$total_power = array_sum($pcnt);
	
	if ($format=="frac") {
	 	 foreach ($pcnt as $i=>$c) {
	 	 	 $pcnt[$i] = "$c/$total_power";
		 }
	} else if ($format=="decimal") {
		foreach ($pcnt as $i=>$c) {
			$pcnt[$i] = $c/$total_power;
		}
	}
	return $pcnt;
}

function shapleyshubikrecurse(&$powercnt, $weights, $quota, $used, $curplayer, $curpower, $n) {
	if ($curplayer!=-1) {
		$used[$curplayer] = 1;  
		$curpower += $weights[$curplayer];
		//echo "curplayer: ".($curplayer+1)."<br/>";
	}
	for ($i=0;$i<$n;$i++) {
		if ($used[$i]) {continue;}
		if ($curpower + $weights[$i] >= $quota) { //is pivotal player
			$remaining = $n - array_sum($used) - 1;
			//echo "player ".($i+1)." was pivotal with $remaining remaining <br/>";
			if ($remaining<2) { 
				$powercnt[$i] += 1;
			} else {
				for($f=2;$remaining-1>1;$f*=$remaining--);
				$powercnt[$i] += $f;
			}
		} else {
			//not pivotal, continue on
			shapleyshubikrecurse($powercnt, $weights, $quota, $used, $i, $curpower, $n);
		}                           
	}
}
