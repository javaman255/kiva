<?php
/* XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
 * 
 *                              K i v a _ t e s t
 * 
 * Some things to do:
 * Find a way to only retreive those loans which are within the timeframe.
 * 		(its currently wasting bandwidth and cpu time).  I.E. learn more about GraphQL.
 * Format DateTime better.
 * Produce a prettier webpage.
 * Build Kiva emulator.
 * Build version that doesn't require fundedAmount
 * 
 * 8/26
 * Renamed LoanObj to loan_obj
 * Refactored loan_obj into stand alone file.
 * Made parse_raw_data a bit less klunky.
 * Improved error catching and handling for file_get_contents().
 * Added local test data to reduce server hits by commenting out some code.
 * 
 * 8/29
 * Built kiva_emulator
 * Remove regerences to fundedAmount
 ** XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX */

require_once 'loan_obj.php';

    $query = '
    {
      loans(filters: {status: fundRaising, expiringSoon: true}, limit: 99999) {
        totalCount
        values {
          id
          name
          loanAmount
          plannedExpirationDate
        }
      }
    }
    ';

// Some url's that I will need
    $url_for_generic_loan = 'https://www.kiva.org/lend/';
    $url_to_api = 'http://william-rice.com/kiva_emulator.php';

/* *****************************************************************************
 * main program
 ** ************************************************************************** */

// prepare a string that represents the date and time 24 hours from now, in the same format as what comes back from the api
    $time = new DateTime();
    $time->setTimezone(new DateTimeZone('GMT'));
    $time->modify('+1 day');
    $compareTime = $time->format('Y-m-dTH:i:s');
    $compareTime = str_replace('UTC', 'T', $compareTime) . 'Z';

    try {
        $raw_data = file_get_contents($url_to_api);
        if ($raw_data === false) {
            printf('There was a technical problem, try again later.<br>');
            die();
        }
    } catch (Exception $e) {
        print($e);
        printf('There was a technical problem, try again later.<br>');
        die();
    }

    $loanArr = parse_raw_data($raw_data);
    
// Display results
    print('<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">');
    print('<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en"><head></head><body>');
    print('<h1>Kiva Loans which will Expire within One Day</h1>');
    print('<h2>as of ');
    print($compareTime . '<br>');
    print('<h2>by William Rice</h2><br>');
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
        print('<td></td>');
        print('<td>' . $loanArr[$i]->plannedExpirationDate . '</td>');
        print('</tr>' . "\n");
    }
    print ('</table><br><br>');
    print('data from: ' . $url_to_api);
    print('</body></html>');

/* *****************************************************************************
 * parse_raw_data()
 * returns an array of loan_obj's
 ** ************************************************************************** */
function parse_raw_data($str) {
    $array = json_decode($str, TRUE);
    $values = $array["data"]["loans"]["values"];
    $return_array = Array();
    for($i = 0; $i < sizeof($values); $i++) {
        $return_array[$i] = new Loan_obj();
        $return_array[$i]->id                    = $values[$i]["id"];
        $return_array[$i]->name                  = $values[$i]["name"];
        $return_array[$i]->loanAmount            = $values[$i]["loanAmount"];
        $return_array[$i]->plannedExpirationDate = $values[$i]["plannedExpirationDate"];
    }
    return $return_array;
}
?>
