# SisContábil

Sistema web para a rotina de um escritório de contabilidade: cadastro de clientes, controle de obrigações fiscais e vencimentos, e troca de documentos entre o escritório e os clientes.

Feito em **PHP 8 + MySQL/MariaDB**, com arquitetura **MVC** e um único ponto de entrada (`public/index.php`). Não usa frameworks nem bibliotecas externas: roda direto no XAMPP, sem Composer e sem internet.

## Acesso de teste

| Usuário | E-mail | Senha |
|---|---|---|
| `admin` | `admin@siscontabil.com.br` | `admin123` |

O login aceita o usuário **ou** o e-mail. Para trocar a senha, use **Minha conta**, no canto inferior do menu.

## Funcionalidades

| Área | O que faz |
|---|---|
| **Login** | Entrada por usuário ou e-mail, sessão com expiração por inatividade (2 h) e saída do sistema. Todas as páginas internas exigem login. |
| **Painel** | Total de clientes e de obrigações; obrigações pendentes, vencidas e concluídas; vencimentos dos próximos 7 dias; valor em atraso; tabela das obrigações que precisam de atenção e resumo dos documentos. Tudo calculado na hora a partir do banco. |
| **Clientes** | Cadastrar, listar, editar, excluir e pesquisar por nome, nome fantasia ou CPF/CNPJ (com ou sem pontuação). CPF e CNPJ são validados pelos dígitos verificadores. Cada cliente tem uma **ficha** com as suas obrigações e os seus documentos. |
| **Obrigações fiscais** | Cadastrar, editar, excluir e listar guias e declarações (cliente, nome, descrição, valor, vencimento e situação). Filtros por texto, cliente, situação e período de vencimento. Botão para marcar como concluída. |
| **Documentos** | Registrar o pedido de um documento ao cliente com data limite, anexar o arquivo recebido, abrir ou baixar o arquivo, e mudar a situação (Aguardando envio → Recebido → Conferido). Pedidos fora do prazo aparecem como **Atrasado**. Os documentos ficam organizados por cliente. |
| **Paginação** | Todas as listagens mostram 10 registros por página, mantendo os filtros. |

### Situações automáticas

- **Obrigação vencida:** não é gravada, é calculada. Uma obrigação *pendente* cujo vencimento já passou aparece como **Vencida** sozinha, sem rotina agendada, e nunca fica desatualizada.
- **Documento recebido:** ao anexar um arquivo num pedido *Aguardando envio*, ele passa para **Recebido**. A conferência é feita pelo contador, que marca como **Conferido**.

## Instalação no XAMPP (Windows)

1. **Instale o [XAMPP](https://www.apachefriends.org/)** com PHP 8 ou mais recente.
2. **Copie a pasta** `sistema-contabil` para `C:\xampp\htdocs\`. O resultado deve ser `C:\xampp\htdocs\sistema-contabil\public\index.php`.
3. Abra o **XAMPP Control Panel** e clique em **Start** no **Apache** e no **MySQL**.
4. **Crie o banco:** acesse <http://localhost/phpmyadmin>, abra a aba **Importar**, escolha o arquivo `database.sql` da pasta do projeto e clique em **Importar**. Ele cria o banco `contabil`, as tabelas, os relacionamentos e os dados de exemplo.
5. **Acesse** <http://localhost/sistema-contabil/> e entre com `admin` / `admin123`.

> Pelo terminal, o passo 4 equivale a `C:\xampp\mysql\bin\mysql.exe -u root < database.sql`.

### Configuração do banco

O padrão é o do XAMPP: servidor `127.0.0.1`, porta `3306`, usuário `root` **sem senha**, banco `contabil`. Se o seu MySQL tiver senha ou outro usuário, altere em [`app/Config/config.php`](app/Config/config.php) (seção `db`) ou defina as variáveis de ambiente `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` e `DB_PASS`.

### Configuração do PHP

O XAMPP já vem com tudo o que o sistema usa. Se algo não funcionar, confira no `C:\xampp\php\php.ini` (e reinicie o Apache depois de alterar):

| Item | Valor necessário | Para quê |
|---|---|---|
| `extension=pdo_mysql` | ativa (sem `;` na frente) | Conexão com o banco |
| `extension=fileinfo` | ativa | Conferir o tipo real dos arquivos enviados |
| `extension=mbstring` | ativa | Textos com acento |
| `upload_max_filesize` | 5M ou mais (o XAMPP vem com 40M) | Envio de documentos (o sistema aceita até 5 MB) |
| `post_max_size` | maior que `upload_max_filesize` | Envio de documentos |

O Apache precisa do **mod_rewrite** e de `AllowOverride All` na pasta `htdocs`, que é o padrão do XAMPP. Eles fazem valer os arquivos `.htaccess`, que deixam só a pasta `public/` acessível pelo navegador.

## Instalação com Docker (alternativa)

Com o [Docker Desktop](https://www.docker.com/products/docker-desktop/) instalado, na pasta do projeto:

```bash
docker compose up -d --build
```

Acesse <http://localhost:8090/sistema-contabil/>. O banco (MariaDB 10.4, a mesma versão do XAMPP) é criado automaticamente a partir do `database.sql` na primeira subida. Para parar: `docker compose down`; para apagar também o banco: `docker compose down -v`.

## Estrutura do projeto (MVC)

```
sistema-contabil/
├── public/                 ← única pasta acessível pelo navegador
│   ├── index.php           ← ponto único de entrada (index.php?r=rota)
│   └── assets/             ← CSS, JavaScript e imagens
├── routes/web.php          ← lista de rotas: endereço → controller e método
├── app/
│   ├── Config/             ← configuração e conexão PDO
│   ├── Core/               ← núcleo: roteador, sessão/login, CSRF, validação, paginação, upload
│   ├── Controllers/        ← recebem a requisição, validam e escolhem a view
│   ├── Models/             ← acesso ao banco (SQL com prepared statements) e regras dos dados
│   └── Views/              ← telas em HTML/PHP (layouts, páginas e partes reutilizáveis)
├── storage/
│   ├── uploads/            ← arquivos enviados (fora da pasta pública)
│   └── logs/               ← erros técnicos (a tela mostra só uma mensagem amigável)
├── tests/teste_sistema.php ← teste automatizado de todo o sistema
├── database.sql            ← banco: estrutura, relacionamentos e dados de exemplo
└── docker-compose.yml      ← execução opcional com Docker
```

**Caminho de uma requisição:** `public/index.php` → carrega `app/Core/bootstrap.php` (sessão, segurança) → o `Router` encontra a rota em `routes/web.php`, exige login e confere o token CSRF → o **Controller** lê e valida os dados → o **Model** consulta ou grava no banco → a **View** monta a página dentro do layout.

## Banco de dados

```
usuarios                       (quem acessa o sistema)

clientes 1 ──── N obrigacoes   (guias e declarações do cliente)
    1
    └────────── N documentos   (pedidos de documentos e arquivos recebidos)
```

| Tabela | Principais campos |
|---|---|
| `usuarios` | nome, usuario (único), email (único), senha_hash |
| `clientes` | nome/razão social, nome_fantasia, documento (CPF/CNPJ único, só números), email, telefone, endereco, cidade, observacoes |
| `obrigacoes` | cliente_id (FK), nome, descricao, valor (DECIMAL), vencimento, status (pendente/concluida), concluida_em |
| `documentos` | cliente_id (FK), titulo, descricao, data_limite, situacao (aguardando/recebido/conferido), dados do arquivo |

- As chaves estrangeiras usam **ON DELETE CASCADE**: ao excluir um cliente, as obrigações e os documentos dele saem junto, e os arquivos dele são apagados do disco. O sistema avisa isso antes de excluir.
- O CPF/CNPJ é **único** no banco, e valores em dinheiro usam `DECIMAL(12,2)`, sem erro de arredondamento.
- As datas dos dados de exemplo são relativas ao dia da importação. Por isso a demonstração sempre tem obrigações vencidas, a vencer e concluídas.

## Segurança

| Requisito | Como foi feito |
|---|---|
| SQL Injection | Todo acesso ao banco usa **PDO com prepared statements** reais (`ATTR_EMULATE_PREPARES = false`). Nenhum dado digitado é concatenado ao SQL. Na pesquisa, `%` e `_` são escapados. |
| XSS | Todo texto exibido passa pela função `e()` (`htmlspecialchars`). Há também uma **Content-Security-Policy** que bloqueia scripts que não sejam do próprio sistema. |
| CSRF | Todo formulário POST leva um **token aleatório da sessão**, conferido pelo roteador com `hash_equals`. Sem o token, nada é gravado. |
| Senhas | `password_hash()` no cadastro e `password_verify()` no login, com a mesma mensagem para usuário inexistente e senha errada. A senha nunca é gravada. |
| Sessões | Cookie `HttpOnly` e `SameSite`, `session.use_strict_mode`, **novo ID de sessão no login** (contra fixação de sessão), expiração por inatividade, e saída que destrói a sessão. |
| Páginas internas | O roteador exige login em todas as rotas, exceto a de login. |
| Validação no servidor | Cada formulário é validado no servidor (obrigatórios, tamanhos, e-mail, CPF/CNPJ, datas, valores e opções), com **mensagem de erro em cada campo**. |
| Arquivos enviados | Extensão em lista fechada (PDF, JPG, PNG, DOC, DOCX, XLS e XLSX), tamanho máximo de 5 MB e **tipo real conferido pelo conteúdo** (`finfo`). Um `.exe` renomeado para `.pdf` é recusado. O arquivo é salvo com **nome aleatório, fora da pasta pública**, e só é entregue a quem está logado. |
| Arquivos internos | Pelo navegador, `.htaccess` bloqueia tudo fora de `public/`: código, `database.sql` e uploads. |
| Erros | Os detalhes técnicos vão para `storage/logs/erros.log`; a tela mostra só uma mensagem amigável. |

## Teste automatizado

O arquivo `tests/teste_sistema.php` percorre o sistema pelo navegador HTTP, como um usuário faria, e confere **78 itens**:
- login e sessão;
- cadastro, edição, listagem e exclusão de clientes, obrigações e documentos;
- filtros e paginação;
- atualização dos indicadores do painel;
- envio de arquivos válidos e maliciosos;
- tentativas de SQL injection, XSS e CSRF.

Ele cria os próprios registros e os apaga no fim.

Com o sistema no ar:

```bash
# XAMPP
C:\xampp\php\php.exe tests\teste_sistema.php http://localhost/sistema-contabil

# Docker
docker run --rm --network siscontabil_default -v "%cd%:/app:ro" -w /app php:8.2-cli php tests/teste_sistema.php http://app/sistema-contabil
```

O resultado esperado é `Tudo certo: 78 verificações passaram.`

## Roteiro sugerido para a apresentação

1. **Login:** mostre o erro de senha incorreta e, depois, entre com `admin` / `admin123`.
2. **Painel:** apresente os indicadores e a tabela "Obrigações que precisam de atenção". Clique em ✓ numa obrigação vencida e mostre os números mudando na hora.
3. **Clientes:** cadastre um cliente.
   - Tente primeiro um CNPJ inválido, como `11.222.333/0001-82`, para mostrar a mensagem no campo.
   - Mostre a pesquisa por CNPJ e abra a **ficha** do cliente.
4. **Obrigações:** pela ficha, cadastre uma obrigação com vencimento ontem; ela aparece como **Vencida**. Use os filtros de situação e período e a paginação.
5. **Documentos:** pela ficha, solicite um documento.
   - Depois, na edição, anexe um PDF: a situação passa a **Recebido**.
   - Abra o arquivo e marque como **Conferido**.
   - Mostre o filtro "Prazo vencido" e a organização **por cliente**.
6. **Segurança:**
   - tente anexar um arquivo `.php` ou um `.exe` renomeado para `.pdf` (o sistema recusa);
   - abra <http://localhost/sistema-contabil/database.sql> (o acesso é bloqueado);
   - saia do sistema e tente abrir uma página interna (o sistema pede login).
7. **Exclusão:** exclua o cliente criado. O aviso informa que as obrigações e os documentos dele saem junto.

## Problemas comuns

| Sintoma | Causa provável | Solução |
|---|---|---|
| "Algo deu errado" logo ao entrar | Banco não importado ou MySQL parado | Inicie o MySQL no XAMPP e importe o `database.sql`. O detalhe fica em `storage/logs/erros.log`. |
| `could not find driver` no log | `pdo_mysql` desativado | Ative `extension=pdo_mysql` no `php.ini` e reinicie o Apache. |
| `Class "finfo" not found` no log | `fileinfo` desativado | Ative `extension=fileinfo` no `php.ini` e reinicie o Apache. |
| Erro 500 do Apache ao abrir a raiz | `mod_rewrite` desligado | Ative o `LoadModule rewrite_module` no `httpd.conf` (o padrão do XAMPP já vem ativo). |
| "Sessão expirada" ao enviar um formulário | A página ficou aberta por muito tempo, ou o login expirou | Recarregue a página e envie de novo. |
| Acentos estranhos nos dados | Banco importado com outra codificação | Importe de novo o `database.sql`, que já define `utf8mb4`. |
