<?php
declare(strict_types=1);
namespace App\Core;
use RuntimeException;
final class Mailer
{
    public static function send(string$subject,string$html,string$replyTo=''):void
    {
        $host=Env::get('MAIL_HOST','');$to=Env::get('CONTACT_TO_EMAIL','');$from=Env::get('MAIL_FROM_ADDRESS','');
        if(!$host||!$to||!$from)throw new RuntimeException('SMTP chưa được cấu hình.');
        $port=(int)Env::get('MAIL_PORT','587');$encryption=Env::get('MAIL_ENCRYPTION','tls');$target=($encryption==='ssl'?'ssl://':'').$host.':'.$port;
        $socket=stream_socket_client($target,$errorCode,$errorMessage,20,STREAM_CLIENT_CONNECT);if(!$socket)throw new RuntimeException("SMTP connection failed: {$errorMessage}");stream_set_timeout($socket,20);
        self::expect($socket,[220]);self::command($socket,'EHLO '.($_SERVER['SERVER_NAME']??'localhost'),[250]);
        if($encryption==='tls'){self::command($socket,'STARTTLS',[220]);if(!stream_socket_enable_crypto($socket,true,STREAM_CRYPTO_METHOD_TLS_CLIENT))throw new RuntimeException('SMTP TLS failed.');self::command($socket,'EHLO '.($_SERVER['SERVER_NAME']??'localhost'),[250]);}
        $username=Env::get('MAIL_USERNAME','');$password=Env::get('MAIL_PASSWORD','');if($username!==''){self::command($socket,'AUTH LOGIN',[334]);self::command($socket,base64_encode($username),[334]);self::command($socket,base64_encode($password),[235]);}
        self::command($socket,'MAIL FROM:<'.$from.'>',[250]);self::command($socket,'RCPT TO:<'.$to.'>',[250,251]);self::command($socket,'DATA',[354]);
        $headers=['From: '.self::cleanHeader(Env::get('MAIL_FROM_NAME','Oufeidun')).' <'.$from.'>','To: <'.$to.'>','Subject: =?UTF-8?B?'.base64_encode($subject).'?=','MIME-Version: 1.0','Content-Type: text/html; charset=UTF-8','Content-Transfer-Encoding: 8bit'];if($replyTo&&filter_var($replyTo,FILTER_VALIDATE_EMAIL))$headers[]='Reply-To: '.$replyTo;
        $payload=implode("\r\n",$headers)."\r\n\r\n".preg_replace('/(?m)^\./','..',$html)."\r\n.";fwrite($socket,$payload."\r\n");self::expect($socket,[250]);self::command($socket,'QUIT',[221]);fclose($socket);
    }
    private static function command($socket,string$command,array$codes):void{fwrite($socket,$command."\r\n");self::expect($socket,$codes);}
    private static function expect($socket,array$codes):void{$response='';do{$line=fgets($socket,515);if($line===false)throw new RuntimeException('SMTP disconnected.');$response.=$line;}while(isset($line[3])&&$line[3]==='-');$code=(int)substr($response,0,3);if(!in_array($code,$codes,true))throw new RuntimeException('SMTP error '.$code.': '.trim($response));}
    private static function cleanHeader(string$value):string{return trim(str_replace(["\r","\n"],' ',$value));}
}

