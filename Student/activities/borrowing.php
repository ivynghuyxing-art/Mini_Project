<?php

include '../../_base.php';

$_title = 'My Borrowing';
auth(['member']);
include '../../_head.php';

if(is_post()){

    $_return_date = req('return_date');
    $_current = req('current_date');

    if($_return_date == ''){
        $_err['return_date'] = 'Required, please fill in return date ';
    }

}
?>

<?php 
include '../../_footer.php';
?>

