# Virtual-Store_API-REST
 API REST em PHP para loja virtual.

 Até o momento, impementa:
 Cadastro de adminstradores com autenticação JWT;

### Falta implementar:
 - Exclusão de adminitrador;
 - Visualização específica de um administrador (Detalhes);
 - Alteração de senha;
 - Recuperação de senha;
 - Dados do website;
 - Categorias;
 - Produtos;
 - Clientes;
 
### Erros conhecidos:
 - Não bloqueia adminstrador de type A incluir/alterar campo para type D;
 - Não bloqueia duplicações; 


## Composição:
 1) Módulo principal;
 2) Módulo express-php-lite (inspirado no Express do node.js);
 3) Módulo my-jwt (depencias requerem composer);
 4) my-model (inspirado no Sequelize do node.js);
 5) MyLog;


## Instalação:

### Configurar my-jwt:
 
 1) Aplicar `composer install -d modules/my-jwt/` para instalar a depenência Carbon;

 2) Na pasta `/modules/my-jwt/` copiar `.config.example.php` para `.config.php`;

 3) Configurar .config.php:

  *MY_JWT_SECRET* ->  Chave para gerar o JWT das requisições autenticadas da API;
    
  Dica: Sugere-se usar `MyJWT::generateKey()`.
  A constante *JWT_TESTES* (em `/.env.php`) com valor verdadeiro habilita uso da rota para gerar keys:
       
      /util/jwt/generate-key

### Configurar my-model:

  1) Criar usuário e banco de dados no *MySQL* (pode-se adaptar MyModel para outros drivers);

  2) Na pasta `/modules/my-model/` copiar `.config.example.php` para `.config.php` e definir as constantes de conexão ao banco de dados;

### Configurar módulo principal:
 
 1) Copiar `.env.example.php` para `.env.php`;

 2) Configurar uma `.env.php` para o ambiente desejado;

 3) Executar os script sql para criar as tabelas no banco de dados da pasta `/src/database/create_tables`;

 Dica: para testes das rotas da API recomenda-se o Insomnia;


## Rotas:

    get / (Home)
    post /admin/login (entrada do adminitrador)
  
    get /util/jwt/validate (testa validação de token JWT)
    get /util/jwt/generate-key (gera uma chave privada de token JWT)
    get /util/jwt/generate-token (gera um token JWT)

  *As rotas seguintes requerem autenticação:*

    put /admin/:id (modifica um admintrador pelo identificador)
    get /admin (Lista totos os administradores)
    post /admin (adiciona novo adminitrador)


## <a href="LICENSE">Licença MIT</a>
