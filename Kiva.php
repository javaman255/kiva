<?php
/* XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
 * Some things to do:
 * Find a way to only retreive those loans which are within the timeframe.
 * 		(its currently wasting bandwidth and cpu time).  I.E. learn more about .QL.
 * A more elegant wqy to turn the returned string into an array of LoanObj's.
 * Format DateTime better.
 * Produce a prettier webpage.
 * Add a "Loan Now" button.
 ** XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX */

/* *****************************************************************************
 * LoanObj
 ** ************************************************************************** */
class LoanObj {
	var $id;
	var $name;
	var $loanAmount;
	var $fundedAmount;
	var $plannedExpirationDate;
}

/* *****************************************************************************
 * main program
 ** ************************************************************************** */
$query = '
{
  loans (filters: {status: fundRaising, expiringSoon: true}, limit: 99999) {
    totalCount
    values {
      id
      name
      loanAmount
      fundedAmount
      plannedExpirationDate
    }
  }
}
';

// Some url's that I will need
$url_for_generic_loan = 'https://www.kiva.org/lend/';
$url_to_api = 'http://api.kivaws.org/graphql?query=' . urlencode($query);

// Get data from api
$raw_data = (string) file_get_contents($url_to_api);
if ($raw_data === FALSE) {
	printf('OOPS');
}
$loanArr = parse_raw_data($raw_data);

// prepare a string that represents the date and time 24 hours from now, in the same format as what comes back from the api
$dateFormat = 'Y-m-dTH:i:s';
$time = new DateTime();
$time->setTimezone(new DateTimeZone('GMT'));
$time->modify('+1 day');
$compareTime = $time->format($dateFormat);
$compareTime = str_replace('UTC', 'T', $compareTime) . 'Z';

// Display results
print ($compareTime);
print('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
print('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head></head><body>');
print('<h1>Kiva Loans which will Expire within One Day</h1>');
print('<table cellpadding=5><tr><td><b>Id</b></td><td><b>Name</b></td><td><b>$ Amount</b></td><td><b>$ Funded</b></td><td><b>$ Remaining</b></td><td><b>Expiration</b></td></tr>');
$loanCnt = 0;
$total_shortfall = 0;
for ($i = 0; $i < sizeof($loanArr); $i++) {
	if ($loanArr[$i]->plannedExpirationDate > $compareTime) {
		continue;
	}
	$loanCnt++;
	$missing = $loanArr[$i]->loanAmount - $loanArr[$i]->fundedAmount;
	$total_shortfall += $missing;
	$href = '<a href="' . $url_for_generic_loan . $loanArr[$i]->id;
	print('<tr>');
	print('<td>' . $loanArr[$i]->id . '</td>');
	print('<td>' . $href . '">' . $loanArr[$i]->name .'</a></td>');
	print('<td>' . $loanArr[$i]->loanAmount . '</td>');
	print('<td>' . $loanArr[$i]->fundedAmount . '</td>');
	printf('<td>%0.2f</td>', $missing);
	print('<td>' . $loanArr[$i]->plannedExpirationDate . '</td>');
	print('</tr>' . "\n");
}
print ('</table><br><br>');
printf('To complete funding on all %d loans due in the next 24 hours requires $%s.<br>', $loanCnt, number_format($total_shortfall, 0, '.', ','));
print('</body></html>');

/* *****************************************************************************
 * parse_raw_data()
 * returns an array of LoanObj's
 ** ************************************************************************** */
function parse_raw_data($str) {
	$start = strpos($str, '[') + 1;
	$end = strpos($str, ']');
	$len = $end - $start;
	$str = substr($str, $start, $len);
	$loans = explode('}', $str);
	for($i = 0; $i < sizeof($loans); $i++) {
		$start = strpos($loans[$i], 'i') - 1;
		$len = strlen($loans[$i]) - $start;
		$loans[$i] = substr($loans[$i], $start, $len);
		if ($loans[$i] == '') {
			unset($loans[$i]);
		} else {
			$loan = explode(',', $loans[$i]);
			$loanObj[$i] = new LoanObj();
			foreach($loan as $k => $v) {
				$start = strpos($v, ':') + 1;
				$len = strlen($v) - $start;
				$v = substr($v, $start, $len);
				$v = str_replace('"', '', $v);
				switch ($k) {
					case (0): $loanObj[$i]->id = $v;
					case (1): $loanObj[$i]->name = $v;
					case (2): $loanObj[$i]->loanAmount = $v;
					case (3): $loanObj[$i]->fundedAmount = $v;
					case (4): $loanObj[$i]->plannedExpirationDate = $v;
				}
			}
		}
	}
	return $loanObj;
}
?>
