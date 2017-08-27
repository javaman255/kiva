<?php
/* XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
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
 ** XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX */

require_once 'loan_obj.php';

$test_data = '{"data":{"loans":{"totalCount":5,"values":[
    {
        "plannedExpirationDate":"2017-08-15T12:00:01Z",
        "name":"Sindjere Group",
        "loanAmount":"3475.00",
        "fundedAmount":"2000.00",
        "id":1329651
    },{
        "plannedExpirationDate":"2017-08-15T23:59:59Z",
        "name":"Mar\u00eda Dinora",
        "loanAmount":"1200.00",
        "fundedAmount":"300.00",
        "id":1340939
    },{
        "plannedExpirationDate":"2017-08-16T00:00:00Z",
        "name":"Ep Lac Kivu Group",
        "loanAmount":"6650.00",
        "fundedAmount":"4275.00",
        "id":1329778
    },{
        "plannedExpirationDate":"2017-08-16T11:59:50Z",
        "name":"Elsy",
        "loanAmount":"10000.00",
        "fundedAmount":"5600.00",
        "id":1337927
    },{
        "plannedExpirationDate":"2017-08-17T12:00:07Z",
        "name":"Jesus",
        "loanAmount":"1525.00",
        "fundedAmount":"1425.00",
        "id":1339401
    }
]}}}';

$query = '
{
  loans(filters: {status: fundRaising, expiringSoon: true}, limit: 5) {
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
        $raw_data = @file_get_contents($url_to_api);
        if ($raw_data === false) {
            printf('There was a technical problem, try again later.<br>');
            die();
        }
    } catch (Exception $e) {
        printf('There was a technical problem, try again later.<br>');
        die();
    }

// The following lines are for testing
// $raw_data = $test_data;
// $compareTime = '2017-08-16T12:00:00Z';

    $loanArr = parse_raw_data($raw_data);

// Display results
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
 * returns an array of loan_obj's
 ** ************************************************************************** */
function parse_raw_data($str) {
    $array = json_decode($str, TRUE);
    $values = $array["data"]["loans"]["values"];

    for($i = 0; $i < sizeof($values); $i++) {
        $loan_obj[$i] = new Loan_obj();
        $loan_obj[$i]->id                    = $values[$i]["id"];
        $loan_obj[$i]->name                  = $values[$i]["name"];
        $loan_obj[$i]->loanAmount            = $values[$i]["loanAmount"];
        $loan_obj[$i]->fundedAmount          = $values[$i]["fundedAmount"];
        $loan_obj[$i]->plannedExpirationDate = $values[$i]["plannedExpirationDate"];
    }
    return $loan_obj;
}
?>
