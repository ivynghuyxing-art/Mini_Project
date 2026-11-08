<?php

include '../../_base.php';

$_title = 'My WishList';
include '../../_head.php';

auth(['student']);

$_title ="My WishList";

$user = $user-> user_id;

if(is_post()){
    $action = post('action');
    $book_id = post('book_id');

    if($book_id != '' && ctype_digit($book_id)){
        if($action == 'add'){
            $stm = $_db->prepare('INSERT INTO wishlist (user_id, book_id) VALUES (?, ?)');
            $stm->execute([$user, $book_id]);
            redirect('wishkist.php');
        } elseif ($action == 'remove'){
            $stm = $_db->prepare('DELETE FROM wishlist WHERE user_id = ? AND book_id = ?');
            $stm->execute([$user, $book_id]);
            redirect('wishlist.php');
        }
    }redirect('wishlist.php');
}

?>