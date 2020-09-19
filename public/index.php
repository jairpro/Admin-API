<?php
  ini_set("error_log", "../php_errors.log");

  header('Access-Control-Allow-Origin: *');
  //header('Access-Control-Allow-Origin: https://admin.sistemanuvem.com/');
  //header('Access-Control-Allow-Credentials: true');
  header('Access-Control-Max-Age: 60');
  header('Access-Control-Allow-Headers: AccountKey,x-requested-with, Content-Type, origin, authorization, accept, client-security-token, host, date, cookie, cookie2');
  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
  
  $method = $_SERVER['REQUEST_METHOD'];
  if ($method==='OPTIONS') {
    header('Content-Type', 'application/json');
    exit();
  }




  $env_php = "../.env.php";
  if (!file_exists($env_php)) {
    header("HTTP/1.1 500 Internal Server error");
    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(['error'=>"The environment file remains to be defined."]);
    exit();
  }
  require_once $env_php;
  require_once "../modules/express-php-lite/autoload.php";
  require_once "../modules/my-jwt/autoload.php";
  require_once "../modules/my-sendgrid/autoload.php";
  require_once "../src/autoload.php";

  Debug::cleanNext();

  require_once "../src/routes.php";
