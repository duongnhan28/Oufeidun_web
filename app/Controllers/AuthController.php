<?php
declare(strict_types=1);
namespace App\Controllers;
use App\Core\Auth;use App\Core\Csrf;use App\Core\Response;
final class AuthController
{
    public function login():never{if(!Csrf::valid($_POST['_csrf']??null))Response::redirect('/login?error=csrf');$username=trim((string)($_POST['username']??''));$password=(string)($_POST['password']??'');if($username===''||$password==='')Response::redirect('/login?error=invalid');try{$result=Auth::attempt($username,$password,substr((string)($_SERVER['REMOTE_ADDR']??'127.0.0.1'),0,45));}catch(\Throwable){Response::redirect('/login?error=database');}if(!empty($result['ok']))Response::redirect('/admin');Response::redirect('/login?error='.(!empty($result['retryAfter'])?'blocked':'invalid'));}
    public function logout():never{Auth::logout();Response::redirect('/login');}
}

