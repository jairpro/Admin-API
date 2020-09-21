<?php

class ResetPasswordController {

  function index($req, $res) {
    return $res->send("<h1>Olá ResetPassword index!</h1>");
  }

  function update($req, $res) {
    $auth = new Auth(RESET_JWT_SECRET);
    if (!$auth->execute($req,$res)) {
      return false;
    }

    $data = $req->body();

    if (!isset($data['newPassword'])) {
      return $res->status(400)->send(['error'=>'Missing new password.']);
    }
    $newPassword = $data['newPassword'];
    if (empty($newPassword)) {
      return $res->status(400)->send(['error'=>'The new password cannot be empty.']);
    }

    if (!isset($data['confirmPassword'])) {
      return $res->status(400)->send(['error'=>'Missing confirmation password.']);
    }
    $confirmPassword = $data['confirmPassword'];
    if (empty($confirmPassword)) {
      return $res->status(400)->send(['error'=>'Confirmation password cannot be empty.']);
    }

    if ($newPassword!==$confirmPassword) {
      return $res->status(400)->send(['error'=>'The confirmation password must be the same new password.']);
    }
    
    $model = new Admin();
    if (!$model->setup()) {
      $res->status(500)->send(['error'=>'Database connection failure.']);
    }

    $id = $req->userId;
    $found = $model->findByPk($id);
    if (!$found) {
      return $res->status(400)->send(['error'=>'User not found.']);
    }

    $update = $model->update([
      'hash' => password_hash($newPassword, PASSWORD_BCRYPT),
    ]);
    if (!$update) {
      return $res->status(500)->send(['error'=>'Error updating password.']);
    }
    
    $bl = MyJwtBlacklist::getInstance();
    $bl->revoke($req->token);

    return $res->send(['message'=>'Password has been updated!']);
  }
  
}