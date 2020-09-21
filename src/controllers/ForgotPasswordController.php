<?php

$dir = dirname(__FILE__)."/";
$env_php = $dir."../../modules/my-sendgrid/.env.php";
if (!file_exists($env_php)) {
  header("HTTP/1.1 500 Internal Server error");
  header("Content-Type: application/json; charset=utf-8");
  echo json_encode(['error'=>"The environment file remains to be defined to my-sendgrid."]);
  exit();
}
require_once $env_php;

class ForgotPasswordController {

  protected $modelClass = 'Admin';
  protected $fieldUser = 'user';
  protected $fieldEmail = 'email';
  protected $fieldName = 'name';

  function store($req, $res) {
    $data = $req->body();

    if (!is_array($data)) {

      $auth = new Auth(RESET_JWT_SECRET);
      if (!$auth->execute($req,$res)) {
        return false;
      }
      
      if (!$req->userId) {
        $res->status(401)->send(['error'=>"Invalid Token."]);
      }
  
      $model = new $this->modelClass();
      if (!$model->setup()) {
        $res->status(500)->send(['error'=>'Database connection failure.']);
      }
      
      $found = $model->findByPk($req->userId);
    }

    else {
      if (!isset($data['user'])) {
        $res->status(400)->send(['error' => "Missing the user field."]);
      }
      $user = $data['user'];
    
      $model = new $this->modelClass();
      if (!$model->setup()) {
        $res->status(500)->send(['error'=>'Database connection failure.']);
      }
 
      $found = $model->findOne([
        'or'=>[
          $this->fieldUser => $user,
          $this->fieldEmail => $user
        ]
      ]);
    }

    if (!$found) {
      $res->status(400)->send(['message' => "User not found."]);
    }
  
    $toEmail = isset($found[$this->fieldEmail]) ? $found[$this->fieldEmail] : false; 

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
      $res->status(400)->send(['message' => "User does not have valid email."]);
    }
  
    $toName = isset($found[$this->fieldName]) ? $found[$this->fieldName] : "";

    $jwt = new MyJWT();
    $jwt->timeout(RESET_JWT_TIMEOUT);
    $tokenData = [
      'id' => $found['id'],
      'user' => $found['user']
    ];
    $resetToken = $jwt->generateToken($tokenData, RESET_JWT_SECRET);

    $cancelToken = '';

    $appUrl = MAIL_APP_URL;
    $appFriendlyDomain = MAIL_APP_FRIENDLY_DOMAIN;
    
    // LOGO
    // ====
    $serviceUrl = MAIL_SERVICE_URL;
    $logoAlt = MAIL_SERVICE_NAME;
    $logoSrc = MAIL_LOGO_SRC;

    // RESET
    // =====
    $resetUrl = MAIL_RESET_ADMIN_URL.$resetToken;
  
    $replyToEmail = MAIL_REPLY_TO_EMAIL;
    $fromNameSignature = MAIL_FROM_NAME_SIGNATURE;
    $fromName = MAIL_FROM_NAME;
    $slogan = MAIL_SLOGAN;
    
    $fromDetails = MAIL_FROM_DETAILS;
    
    // CANCEL
    // ======
    $cancelUrl = MAIL_CANCEL_URL.$cancelToken;

    $html = "
<html>
<head>
<style>

</style>
</head>
<body>
<a href=\"$serviceUrl\"><img alt=\"$logoAlt\" src=\"$logoSrc\"></a>
<br>
<p>Olá $toName,
<p>Uma solicitação foi recebida para alterar a senha de sua conta $fromName.
<br>
<p><a href=\"$resetUrl\">Redefinir senha</a>
<br> 
<p>Se você não iniciou esta solicitação, entre em contato conosco imediatamente em $replyToEmail.
<br>
<p>Obrigado,
<br>$fromNameSignature
<br>
<p>$fromName
<br>$slogan
<br>
<p>$fromDetails
<br>
<p>Email enviado usando <a href=\"$appUrl\">$appFriendlyDomain</a> . 
".
($cancelToken ? ("<p>Para cancelar <a href=\"$cancelUrl\">clique aqui</a>".PHP_EOL) : "")
."
</body>
</html>
";
    $fakeWarning = MAIL_SEND_FAKE ? ' (FAKE)' : '';

    $sendgrid = new MySendgrid();
    $result = $sendgrid->send([
      'toEmail' =>  $toEmail,
      'toName' =>  $toName,

      //'subject' =>  "Sua solicitação de redefinição de senha do SendGrid",
      'subject' =>  "Recuperação de senha",

      //'plain' =>  strip_tags($html),
      'html' =>  $html,
    ]);

    if (!$result) {
      $res->status(500)->send(['error' => "The message can not be$fakeWarning sent."]);
    }

    return $res->send(['message'=>"Message$fakeWarning sent successfully."]);
  }
}