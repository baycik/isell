<?php
class Login extends CI_Controller{
    public function index(){
	include 'application/views/login/login.php';
    }
    public function dialog(){
	include 'application/views/login/loginDialog.html';
    }
}