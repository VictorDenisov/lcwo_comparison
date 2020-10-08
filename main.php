<?php

function check ($w1, $w2) {		#w1 = original; w2 with errors
	$ret = '';
	$err = 0;

	$grouplen = mb_strlen($w1);
		
	if ($w1 == $w2) {
		return array(colorspan($w1, 'green'), 0);
	}

	# Same length?

	if (mb_strlen($w1) == mb_strlen($w2)) {
		for ($i=0; $i < mb_strlen($w2); $i++) {
			if (mb_substr($w1, $i, 1) == mb_substr($w2, $i, 1)) {
				$ret .= colorspan(mb_substr($w1, $i, 1), 'green');
			}
			else {
				$ret .= colorspan(mb_substr($w1, $i, 1), 'red');
				$err++;
			}
		}
		
		if ($err > 5) {
		    $err = 5;
		}

		return array($ret, $err);
	}

	# Just compare one by one, regardless of length
	for ($i=0; $i < mb_strlen($w1); $i++) {
		if (mb_substr($w2, $i, 1) != FALSE and 
			mb_substr($w1, $i, 1) == mb_substr($w2, $i, 1)) {
			$ret .= colorspan(mb_substr($w1, $i, 1), 'green');
		}
		else {
			$ret .= colorspan(mb_substr($w1, $i, 1), 'red');
			$err++;
		}
	}
	$test1 = array($ret, $err);
	$ret = ''; $err = 0;

	# Different length. Strategy: compare first letter of
	# both strings. If same, remove both. If different, add
	# error and remove first letter of longer string.

	while (1) {
		if (mb_strlen(mb_substr($w2, 0, 1)) &&
		mb_substr($w1, 0, 1) == mb_substr($w2, 0, 1)) {
			$ret .= colorspan(mb_substr($w1, 0, 1), 'green');
			$w1 = mb_substr($w1, 1);
			$w2 = mb_substr($w2, 1);
		}
		else {
				$err++;
				if (mb_strlen($w1) > mb_strlen($w2)) {
					$ret .= colorspan(mb_substr($w1, 0,1), 'red');
					$w1 = mb_substr($w1, 1);
				}
				else if (mb_strlen($w1) < mb_strlen($w2)) {
					$ret .= colorspan(mb_substr($w2, 0,1), 'red');
					$w2 = mb_substr($w2, 1);
				}
				else {
					$w1 = mb_substr($w1, 1);
					$w2 = mb_substr($w2, 1);
					$ret .= colorspan("-", 'blue');
				}
		}

		if (mb_strlen($w1) == 0 && mb_strlen($w2) == 0) {
			if ($err > $grouplen) {
			    $err = $grouplen;
			}
			$test2 = array($ret, $err);
			break;
		}
	} 


	#return $test1;

	if ($test2[1] < $test1[1]) {
			return $test2;
	}
	else {
			return $test1;
	}

}

function colorspan ($e, $c) {
	if ($c == 'red') {
		return ('<span style="font-family:monospace;color:#aa0000;text-decoration:underline;">'.$e.'</span>');
	}
	else if ($c == 'blue') {
		return ('<span style="font-family:monospace;color:#1111ff;font-style:italic;">'.$e.'</span>');
	}
	else {
		return ('<span style="font-family:monospace;color:#00aa00;">'.$e.'</span>');
	}
}

function print_array($a) {
	$l = count($a);
	for ($i = 0; $i < $l; $i++) {
		print_r($a[$i]);
		print('<br>');
	}
}

# First span has incorrect chars, second span has only dashes.
function merge_error_spans($sp1, $sp2) {
	$l1 = mb_strlen($sp1);
	$l2 = mb_strlen($sp2);
	$mspan = '';
	if ($l1 > $l2) {
		$mspan = $sp1;
	} else {
		$mspan = $sp1 . mb_substr($sp2, $l1, $l2 - $l1);
	}
	$err = mb_strlen($mspan);
	$result = '';
	for ($i = 0; $i < mb_strlen($mspan); $i++) {
		$c = mb_substr($mspan, $i, 1);
		if ($c == '-') {
			$result .= colorspan($c, 'blue');
		} else {
			$result .= colorspan($c, 'red');
		}
	}
	return array($result, $err);
}

# This function calculates the number of missed characters in the original strings.
# Any extra characters in the user's input are not considered mistakes.
function new_check($w1, $w2) {
	$ret = '';
	$err = 0;

	$l1 = mb_strlen($w1);
	$l2 = mb_strlen($w2);
	$a = array_fill(0, $l1 + 1, array_fill(0, $l2 + 1, 0));
	$a[0][0] = 0;
	for ($i = 1; $i <= $l1; $i++) {
		for ($j = 1; $j <= $l2; $j++) {
			$a[$i][$j] = $a[$i - 1][$j];
			if ($a[$i][$j - 1] > $a[$i][$j]) {
				$a[$i][$j] = $a[$i][$j - 1];
			}
			if (mb_substr($w1, $i - 1, 1) == mb_substr($w2, $j -1, 1)) {
				if ($a[$i - 1][$j - 1] + 1 > $a[$i][$j]) {
					$a[$i][$j] = $a[$i - 1][$j - 1] + 1;
				}
			}
		}
	}
	$err = 0;
	$i = $l1;
	$j = $l2;
	$error_span1 = '';
	$error_span2 = '';
	while ($i > 0 || $j > 0) {
		$new_i = $i;
		$new_j = $j;
		$value = '';

		if (($i > 0) && ($j > 0) && (mb_substr($w1, $i - 1, 1) == mb_substr($w2, $j - 1, 1)) && ($a[$i][$j] == $a[$i - 1][$j - 1] + 1 )) {
			$error_span = merge_error_spans($error_span1, $error_span2);
			$ret = $error_span[0] . $ret;
			$ret = colorspan(mb_substr($w1, $i - 1, 1), 'green') . $ret;
			$err = $err + $error_span[1];
			$error_span1 = '';
			$error_span2 = '';
			$i = $i - 1;
			$j = $j - 1;
			continue;
		}

		if (($j > 0) && ($a[$i][$j - 1] == $a[$i][$j])) {
			$i = $i;
			$j = $j - 1;
			$error_span2 = '-' . $error_span2;
			continue;
		}
		if (($i > 0) && ($a[$i - 1][$j] == $a[$i][$j])) {
			$error_span1 = mb_substr($w1, $i - 1, 1) . $error_span1;
			$i = $i - 1;
			$j = $j;
			continue;
		}
	}
	$error_span = merge_error_spans($error_span1, $error_span2);
	$ret = $error_span[0] . $ret;
	$err = $err + $error_span[1];

	return array($ret, $err);
}

#$s = check('abcde', 'xbe');
#$s = new_check('abcde', 'xbe');
#$s = new_check('abcde', 'abcde');
#$s = new_check('abcde', 'xce');
#$s = new_check('ORXDK', 'ORXDXK');
#$s = new_check('MCUFH', 'KMCUCCH');
$s = new_check('MCUFH', 'YYYMCxxVFHXXX');
#$s = merge_error_spans('abcde', '-------');
#print($s)

print_r($s[0]);
print('<br>');
print_r($s[1]);

?>
