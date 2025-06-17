<?php

function sudo(){
    if( isset( $_SESSION['user_level'] ) && $_SESSION['user_level']==4 ){
        return true;
    }
    return false;
}