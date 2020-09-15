# Virtual-Store_API-REST
 API REST em PHP para loja virtual.

 Até o momento, implementa:
 Cadastro de administradores com autenticação JWT;

### Falta implementar:
 - Recuperação de senha;
 - Dados do website;
 - Categorias;
 - Produtos;
 - Clientes;
 - E mais...
 
## Composição:
 1) Módulo principal;
 2) Módulo express-php-lite (inspirado no *Express* para node.js);
 3) Módulo my-jwt (dependêcias requerem *Composer*);
 4) my-model (inspirado no *Sequelize* para node.js);
 5) MyLog;


## Instalação:

### Configurar my-jwt:
 
 1) Aplicar `composer install -d modules/my-jwt/` para instalar a dependência *Carbon*;

 2) Na pasta `/modules/my-jwt/` copiar `.config.example.php` para `.config.php`;

 3) Configurar `.config.php`:

Constante | Descrição
--------- | ---------
MY_JWT_SECRET | Chave para gerar o JWT das requisições autenticadas da API
MY_JWT_TIMEOUT | Tempo para expirar o token em segundos a partir da hora atual
    
  Dica: Sugere-se usar `MyJWT::generateKey()`.
  A constante *JWT_TESTES* (em `/.env.php`) com valor verdadeiro habilita uso da rota para gerar keys:
       
      /util/jwt/generate-key

### Configurar my-model:

  1) Criar usuário e banco de dados no *MySQL* (pode-se adaptar *MyModel* para outros drivers);

  2) Na pasta `/modules/my-model/` copiar `.config.example.php` para `.config.php` e definir as constantes de conexão ao banco de dados;

Constante | Descrição
--------- | ---------
DB_DRIVER | Driver do banco de dados: *mysql* (padrão), *pgsql* (Postgres), *firebird* e *sqlite*. Poderá implementar outros drivers mais.
DB_HOST | Nome do servidor do banco de dados. Padrão: *localhost*
DB_NAME | Nome do banco de dados.
DB_USER | Nome do usuário do banco de dados.
DB_PASS | Senha do usuário do banco de dados.

  3) No banco de dados MySQL aplicar os scripts ordenadamente em: `src/database/migrations/` e em: `src/database/seeds/`

  Assim serão criadas as tabelas, atualizada as estruturas e adicionado o usuário desenvolvedor, que permite o primeiro login na rota `POST /admin/login`:
  
    user: dev
    password: secret

  4) Como ainda não há alteração de senha pela API, tão logo, alterar diretamente o campo _hash_ de _admins_ com sua senha hash de algoritmo *Bcrypt*. Pode-se pesquisar um gerador online.

  5) Alterar seu administrador (já pela rota `PUT /admin/:id`) e informar no campo email o seu próprio email para futura recuperação de senha pela API;

NOTA: Novas migrations e seeds poderão ser criados. Para cada nova migration criar o undo correspondente com mesmo nome de arquivo na pasta: `src/database/migrations_undo/`.


### Configurar módulo principal:
 
 1) Copiar `.env.example.php` para `.env.php`;
 
 2) Configurar uma `.env.php` para o ambiente desejado;

Constante | Descrição
--------- | ---------
ENV | Ambiente do sistema: *development* (padrão), *prodution*. Esse valor afeta determinadas funcionalidades. Verifique no código.
JWT_TESTS | Quando verdadeiro permite uso das rotas de testes do JWT.

 3) Executar os script SQL para criar as tabelas no banco de dados da pasta `/src/database/create_tables`;

 Dica: Para testes das rotas recomenda-se o *Insomnia*;


## Rotas:

Caminho | Método | Descrição | JWT
------- | ------ | --------- | ---
/ | GET | Home |
/admin/login | POST | Entrada do administrador |
/util/jwt/generate-key | GET | Gera uma chave privada de token JWT |
/util/jwt/generate-token | GET | Gera um token JWT |
/util/jwt/validate | GET | Testa validação de token JWT |
/admin | GET | Lista todos os administradores | S
/admin/:id | GET | Detalhes do administrador pelo identificador | S
/admin | POST | Adiciona novo administrador | S
/admin/:id | PUT | Modifica um administrador pelo identificador | S
/admin/:id | DELETE | Exclui um administrador pelo identificador | S
/admin/password | PUT | Modifica a senha do administrador | S


## <a href="LICENSE">Licença MIT</a>
