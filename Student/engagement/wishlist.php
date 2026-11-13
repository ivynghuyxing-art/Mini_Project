<?php

include '../../_base.php';

$_title = 'My WishList';
auth(['member']);
include '../../_head.php';

$_title ="My WishList";

$user = $_user-> user_id;

if(is_post()){
    $action = post('action');
    $book_id = post('book_id');

    if($book_id != '' && ctype_digit($book_id)){
        if($action == 'add'){
            $stm = $_db->prepare('INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)');
            $stm->execute([$user, $book_id]); 
        } elseif ($action == 'remove'){
            $stm = $_db->prepare('DELETE FROM wishlist WHERE user_id = ? AND book_id = ?');
            $stm->execute([$user, $book_id]);
        }
    }
}

?>