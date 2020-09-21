<?php

class ResendPasswordController {

  function store($req, $res) {
    $auth = new Auth(RESET_JWT_SECRET);
    if (!$auth->execute($req,$res)) {
      return false;
    }
    
    $res->send(['message'=>"Olá ResendPassword!"]);
  }
}
