<?php
/* XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
 * 
 *                          k i v a _ e m u l a t o r
 * 
 * Writes a string in the format of the one produced by api.kivaws.org/graphq
 * 
 * I want some of these to display and some to fail so I build the 
 * plannedExpirationDate to values from the time that it runs, and at various
 * significant points thereafter.  The fields that are not time sensitive come
 * straight from the kiva website.
 ** XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX */
    $time = new DateTime();

// Put those times together with relevant text from the kiva site.
    $raw_data  = '{"data":{"loans":{"totalCount":321,"values":[';
    $raw_data .= '{"id":1344804,"name":"Kun Pa Laing - 1 (A) Village Group","loanAmount":"1300.00","plannedExpirationDate":"';
    $raw_data .= dateStr($time, 0, 0, 0, 0, 1, 0);
    $raw_data .= '"},{"id":1348854,"name":"Lechuguitas Group","loanAmount":"4775.00","plannedExpirationDate":"';
    $raw_data .= dateStr($time, 1, 0, 0, 1, 0, 0);
    $raw_data .= '"},{"id":1345695,"name":"Djiguisseme Group","loanAmount":"2725.00","plannedExpirationDate":"';
    $raw_data .= dateStr($time, 0, 0, 0, 6, 0, 0);
    $raw_data .= '"},{"id":1347356,"name":"Jos\u00e9 Luis","loanAmount":"1500.00","plannedExpirationDate":"';
    $raw_data .= dateStr($time, 0, 0, 0, 23, 59, 0);
    $raw_data .= '"},{"id":1348726,"name":"Judith Marveli","loanAmount":"1000.00","plannedExpirationDate":"';
    $raw_data .= dateStr($time, 0, 1, 0, 0, 1, 0);
    $raw_data .= '"}]}}}';

//  Publish result
    print($raw_data);
    
/* *****************************************************************************
 * dateStr()
 ** ************************************************************************** */
function dateStr($base, $y, $mo, $d, $h, $mi, $s) {
    $work = new DateTime($base->format(''));
    $work->setTimezone(new DateTimeZone('GMT'));
    $work = $work->modify('+' . $y . ' YEARs');
    $work = $work->modify('+' . $mo . ' MONTHS');
    $work = $work->modify('+' . $d . ' DAYS');
    $work = $work->modify('+' . $h . ' HOURS');
    $work = $work->modify('+' . $mi . ' MINUTES');
    $work = $work->modify('+' . $s . ' SECONDS');
    $str = $work->format('Y-m-dTH:i:s');
    $str = str_replace('UTC', 'T', $str) . 'Z';
    
    return $str;
}
?>