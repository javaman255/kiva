<?php
/* *****************************************************************************
 * loan_obj
 ** ************************************************************************** */
class Loan_obj {
    var $id;
    var $name;
    var $loanAmount;
    var $fundedAmount;
    var $plannedExpirationDate;
    
    /* *****************************************************************************
     * print_obj()
     ** ************************************************************************** */
    public function print_obj() {
        printf("%d %s %d %d %s<br>",
            $this->id,
            $this->name,
            $this->loanAmount,
            $this->fundedAmount,
            $this->plannedExpirationDate
            );
    }
}
?>
