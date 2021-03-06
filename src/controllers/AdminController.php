<?php

class AdminController {
  
  function index($req=null, $res=null) {
    if (!isset($req->userId)) {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $admin = new Admin();
    
    if (!$admin->setup()) {
      $res->status(500)->send(['error'=>'Database connection failure.']);
    }

    $found = $admin->findByPk($req->userId);
    if (!$found) {
      $res->status(500)->send(['error'=>'Invalid token or user not found.']);
    }
    
    if ($found['status']==='I') {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    if (!in_array($found['type'], [
      Admin::TYPE_DEV, 
      Admin::TYPE_ADMIN
    ])) {
      $res->status(401)->send(['message'=>'You do not have permission.']);
    }

    $options = [];
    $options['attributes'] = [
      'id',
      'user',
      'name',
      'email',
      'status',
      'type',
      'created_at',
      'updated_at'
    ];
    if ($found['type']!==Admin::TYPE_DEV) {
      $options['where'] = [
        'ne' => [
          "IFNULL(type,'')" => Admin::TYPE_DEV
        ]
      ];
    }

    $all = $admin->findAll($options);
    if (!$all) {
      $res->status(500)->send(['error'=>'Request failure.']);
    }

    $res->send($all);
  }

  function view($req, $res) {
    if (!isset($req->userId)) {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $model = new Admin();

    if (!$model->setup()) {
      $res->status(500)->send(['error'=>'Database connection failure.']);
    }

    $me = $model->findByPk($req->userId);

    if (!$me) {
      $res->status(500)->send(['error'=>'Invalid token or user not found.']);
    }
    
    if ($me['status']==='I') {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $target = $model->findByPk($req->param('id'));
    if (!$target) {
      $res->status(404)->send(['message'=>'User not found.']);
    }

     /**
     *  Regra view por type:
     * 
     *  D: Desenv: Acesso total
     *  A: Admin: Permite ecluir apenas Operadores
     *  O: Operator: Exclusão bloqueada; 
     * 
     * */
    $itsMe = $req->param('id')===$req->userId;
    
    switch ($me['type']) {
      case Admin::TYPE_DEV:
      break;
      
      case Admin::TYPE_ADMIN:
        $denyTypes = [
          Admin::TYPE_DEV,
        ];
        if (in_array($target['type'], $denyTypes)) {
          $res->status(401)->send(['message'=>'Access denied.']);
        }
      break;

      case Admin::TYPE_OPERATOR:
        if (!$itsMe) {
          $res->status(401)->send(['message'=>'Access denied.']);
        }
      break;
    }
    unset($target['hash']);

    return $res->send($target);
  }

  function store($req=null, $res=null) {
    if (!isset($req->userId)) {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $admin = new Admin();

    if (!$admin->setup()) {
      $res->status(500)->send(['error'=>'Database connection failure.']);
    }
    
    $me = $admin->findByPk($req->userId);
    if (!$me) {
      $res->status(500)->send(['message'=>'Invalid token or User not found.']);
    }
    
    if ($me['status']==='I') {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    if (!in_array($me['type'], [
      Admin::TYPE_DEV, 
      Admin::TYPE_ADMIN
    ])) {
      $res->status(401)->send(['message'=>'You do not have permission.']);
    }
   
    $data = $req->body();

    switch ($me['type']) {
      case Admin::TYPE_ADMIN:
        // Para Administradores permite apenas inclusão de Administradores e Operadores
        $allowTypes = [
          Admin::TYPE_ADMIN,
          Admin::TYPE_OPERATOR
        ];
        if (isset($data['type']) && !in_array($data['type'], $allowTypes)) {
          $res->status(401)->send(['message'=>'You do not have permission.']);
        }
      break;
    }

    if (!isset($data['email']) || !$data['email']) {
      $res->status(402)->send(['error'=>'Missin email.']);
    }

    $password = isset($data['password']) ? $data['password'] : false;
    if (!$password) {
      $res->status(402)->send(['error'=>'Missin password.']);
    }
    unset($data['password']);
    $data['hash'] = password_hash($password, PASSWORD_BCRYPT);

    $search = [];
    if (isset($data['email'])) {
      $search['email'] = $data['email'];
    }
    if (isset($data['user'])) {
      $search['user'] = $data['user'];
    }
    if (isset($data['id'])) {
      $search['id'] = $data['id'];
    }
    $options = ['or' => $search];
    
    $found = $admin->findOne($options);
    if ($found) {
      $res->status(400)->send(['error'=>'User already exists.']);
    }

    $result = $admin->create($data);
    
    if (!$result) {
      $res->status(500)->send(['error'=>'Operation failure.']);
    }

    $id = $admin->lastInsertId();
    if (!$result) {
      $res->status(500)->send(['error'=>'The administrator was added but did not return his identifier.']);
    }

    $new = $admin->findByPk($id);
    if (!$new) {
      $res->status(500)->send(['error'=>'The administrator was added but did not return his data.']);
    }

    unset($new['hash']);
    unset($new['updated_at']);

    $res->send($new);
  }

  function update($req=null, $res=null) {
    if (!isset($req->userId)) {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $model = new Admin();

    if (!$model->setup()) {
      $res->status(500)->send(['error'=>'Database connection failure.']);
    }

    $me = $model->findByPk($req->userId);

    if (!$me) {
      $res->status(500)->send(['error'=>'Invalid token or user not found.']);
    }
    
    if ($me['status']==='I') {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $target = $model->findByPk($req->param('id'));
    if (!$target) {
      $res->status(404)->send(['message'=>'User not found.']);
    }

    /**
     *  Regra update por type:
     * 
     *  D: Desenv: acesso total
     *  A: Admin: acesso total exceto Desenv e Admin. em Admin permitido para si nos campos: email, name, user
     *  O: Operator: Em Operator permitido para si, nos campos: email, name, user; 
     * 
     * */
    $itsMe = $req->param('id')===$req->userId;
    $data = $req->body();
    if (!is_array($data) or empty($data)) {
      $res->status(400)->send(['error' => "Invalid request body."]);
    }

    $dataKeys= array_keys($data);

    switch ($me['type']) {
      case Admin::TYPE_DEV:
        $denyFields = [
          'id',
          'created_at',
          'updated_at',
        ];
        // alterando a si mesmo
        if ($itsMe) {
          $denyFields[] = 'type';
          $denyFields[] = 'status';
        }
        // se tentar alterar campo bloquado
        foreach($dataKeys as $key) {
          if (in_array($key, $denyFields)) {
            return $res->status(401)->send(['message'=>'You do not have permission.']);
          }
        }
      break;

      case Admin::TYPE_ADMIN:
        // alterando a si mesmo
        if ($itsMe) {
          // se tentando alterar campo além do permido
          $allowFields = [
            'email',
            'name',
            'user',
          ];
          // Se tentando alterar campo além do permido
          foreach($dataKeys as $key) {
            if (!in_array($key, $allowFields)) {
              return $res->status(401)->send(['message'=>'You do not have permission.']);
            }
          }
        }

        // Alterando outro usuário
        else {
          // bloqueada alteração nos seguintes usuários:
          $denyTypes = [
            Admin::TYPE_DEV,
            Admin::TYPE_ADMIN,
          ];
          if (in_array($target['type'], $denyTypes)) {
            $res->status(401)->send(['message'=>'You do not have permission.']);
          }

          $allowFields = [
            'status',
            'type',
            'email',
            'name',
            'user',
          ];
          // Se tentando alterar campo além do permido
          foreach($dataKeys as $key) {
            if (!in_array($key, $allowFields)) {
              return $res->status(401)->send(['message'=>'You do not have permission.']);
            }
          }

          $allowDataTypes = [
            Admin::TYPE_OPERATOR,
            Admin::TYPE_ADMIN,
          ]; 
          // Se tentando alterar type além do permido
          if (in_array("type", $dataKeys) && !in_array($data['type'], $allowDataTypes)) {
            $res->status(401)->send(['message'=>'You do not have permission.']);
          }
        }
      break;
      
      case Admin::TYPE_OPERATOR:
        // bloqueia alterar outro usuário
        if (!$itsMe) {
          $res->status(401)->send(['message'=>'You do not have permission.']);
        }
        // se tentando alterar campo além do permido
        $allowFields = [
          'email',
          'name',
          'user',
        ];
          // Se tentando alterar campos além do permido
          foreach($dataKeys as $key) {
          if (!in_array($key, $allowFields)) {
            return $res->status(401)->send(['message'=>'You do not have permission.']);
          }
        }
      break;

      default:
        $res->status(401)->send(['message'=>'You do not have permission.']);
      break;
    }

    
    $search = [];
    if (isset($data['email'])) {
      $search['email'] = $data['email'];
    }
    if (isset($data['user'])) {
      $search['user'] = $data['user'];
    }
    if (isset($data['id'])) {
      $search['id'] = $data['id'];
    }
    if (count($search)>0) {
      $options = [];
      
      $options['where'] = [
        'ne' => ['id'=> $target['id']],
        'or' => $search,
      ];

      $found = $model->findOne($options);
      if ($found && $found['id']!==$target['id']) {
        $res->status(400)->send(['error'=>'User already exists.']);
      }
    }

    $result = $model->update($data);

    if (!$result) {
      $res->status(500)->send(['error'=>"update failure."]);
    }

    $res->send($result);
  }

  function destroy($req, $res) {
    if (!isset($req->userId)) {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $model = new Admin();

    if (!$model->setup()) {
      $res->status(500)->send(['error'=>'Database connection failure.']);
    }

    $me = $model->findByPk($req->userId);

    if (!$me) {
      $res->status(500)->send(['error'=>'Invalid token or user not found.']);
    }
    
    if ($me['status']==='I') {
      $res->status(401)->send(['message'=>'Access denied.']);
    }

    $target = $model->findByPk($req->param('id'));
    if (!$target) {
      $res->status(404)->send(['message'=>'User not found.']);
    }

     /**
     *  Regra update por type:
     * 
     *  D: Desenv: Acesso total
     *  A: Admin: Permite ecluir apenas Operadores
     *  O: Operator: Exclusão bloqueada; 
     * 
     * */
    
    switch ($me['type']) {
      case Admin::TYPE_DEV:
      break;
      
      case Admin::TYPE_ADMIN:
        $denyTypes = [
          Admin::TYPE_DEV,
          Admin::TYPE_ADMIN,
        ];
        if (in_array($target['type'], $denyTypes)) {
          $res->status(401)->send(['message'=>'Access denied.']);
        }
      break;

      case Admin::TYPE_OPERATOR:
        $res->status(401)->send(['message'=>'Access denied.']);
      break;
    }

    $result = $model->destroy();
    if (!$result) {
      $res->status(401)->send(['error'=>'Delete failure.']);
    }

    return $res->send(['message'=>'User deleted successfully.']);
  }
}