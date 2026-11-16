-- =============================================================================
-- SisContábil — banco de dados (MySQL 5.7+ / MariaDB 10.4+)
--
-- Cria o banco "contabil", as tabelas, os relacionamentos e os dados de exemplo.
-- Importe pelo phpMyAdmin (aba Importar) ou pelo terminal:
--     mysql -u root -p < database.sql
--
-- Usuário de teste:  admin  (ou admin@siscontabil.com.br)  /  senha: admin123
--
-- As datas de exemplo são relativas ao dia da importação (CURDATE()), para a demonstração
-- sempre ter obrigações vencidas, a vencer e concluídas.
-- =============================================================================

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS contabil
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE contabil;

-- Ordem de remoção inversa à de dependência (só para reimportar do zero).
DROP TABLE IF EXISTS documentos;
DROP TABLE IF EXISTS obrigacoes;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS usuarios;

-- -----------------------------------------------------------------------------
-- Usuários do escritório (quem acessa o sistema)
-- -----------------------------------------------------------------------------
CREATE TABLE usuarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(100) NOT NULL,
    usuario     VARCHAR(50)  NOT NULL,
    email       VARCHAR(150) NOT NULL,
    senha_hash  VARCHAR(255) NOT NULL COMMENT 'password_hash(); a senha nunca é guardada',
    criado_em   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_usuario (usuario),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Clientes do escritório (empresas ou pessoas físicas)
-- -----------------------------------------------------------------------------
CREATE TABLE clientes (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome           VARCHAR(150) NOT NULL COMMENT 'Nome (pessoa física) ou razão social (empresa)',
    nome_fantasia  VARCHAR(150) NULL,
    documento      VARCHAR(14)  NOT NULL COMMENT 'CPF (11) ou CNPJ (14), só números',
    email          VARCHAR(150) NULL,
    telefone       VARCHAR(11)  NULL COMMENT 'DDD + número, só números',
    endereco       VARCHAR(200) NULL,
    cidade         VARCHAR(100) NULL,
    observacoes    TEXT         NULL,
    criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_clientes_documento (documento),
    KEY ix_clientes_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Obrigações fiscais e guias de cada cliente
-- "Vencida" não é gravada: é calculada (pendente com vencimento antes de hoje),
-- por isso nunca fica desatualizada.
-- -----------------------------------------------------------------------------
CREATE TABLE obrigacoes (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cliente_id     INT UNSIGNED NOT NULL,
    nome           VARCHAR(100) NOT NULL COMMENT 'Tipo da obrigação: DAS, FGTS, DCTFWeb...',
    descricao      TEXT         NULL,
    valor          DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    vencimento     DATE         NOT NULL,
    status         ENUM('pendente','concluida') NOT NULL DEFAULT 'pendente',
    concluida_em   DATETIME     NULL,
    criado_em      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_obrigacoes_cliente (cliente_id),
    KEY ix_obrigacoes_status_vencimento (status, vencimento),
    CONSTRAINT fk_obrigacoes_cliente FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT ck_obrigacoes_valor CHECK (valor >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Documentos solicitados aos clientes (e o arquivo recebido, quando houver)
-- O arquivo fica em storage/uploads com nome aleatório; aqui ficam os dados dele.
-- -----------------------------------------------------------------------------
CREATE TABLE documentos (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    cliente_id       INT UNSIGNED NOT NULL,
    titulo           VARCHAR(150) NOT NULL COMMENT 'Documento solicitado',
    descricao        TEXT         NULL,
    data_limite      DATE         NOT NULL COMMENT 'Prazo para o cliente enviar',
    situacao         ENUM('aguardando','recebido','conferido') NOT NULL DEFAULT 'aguardando',
    arquivo_nome     VARCHAR(200) NULL COMMENT 'Nome original do arquivo enviado',
    arquivo_salvo    VARCHAR(60)  NULL COMMENT 'Nome aleatório em storage/uploads',
    arquivo_tipo     VARCHAR(100) NULL,
    arquivo_tamanho  INT UNSIGNED NULL,
    enviado_em       DATETIME     NULL,
    criado_em        DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY ix_documentos_cliente (cliente_id),
    KEY ix_documentos_situacao_prazo (situacao, data_limite),
    CONSTRAINT fk_documentos_cliente FOREIGN KEY (cliente_id)
        REFERENCES clientes (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DADOS DE EXEMPLO (empresas e pessoas fictícias)
-- =============================================================================

-- Senha: admin123 (hash gerado com password_hash)
INSERT INTO usuarios (nome, usuario, email, senha_hash) VALUES
('Administrador', 'admin', 'admin@siscontabil.com.br', '$2y$10$6Cwyda2FYSm1HO/X6nvvtONvTCu3QYPhCH1tpHWsLQBctVaeQexyG');

INSERT INTO clientes (id, nome, nome_fantasia, documento, email, telefone, endereco, cidade, observacoes) VALUES
(1, 'Padaria Pão Dourado Ltda', 'Pão Dourado', '11222333000181', 'financeiro@paodourado.com.br', '3132224455', 'Rua das Flores, 120 - Centro', 'Belo Horizonte/MG', 'Simples Nacional. Folha com 6 funcionários.'),
(2, 'Auto Peças Rodovia Ltda', 'Rodovia Auto Peças', '45321987000184', 'contato@rodoviapecas.com.br', '3133561200', 'Av. Brasil, 2450 - Industrial', 'Contagem/MG', 'Lucro Presumido.'),
(3, 'Clínica Sorriso Saúde Ltda', 'Clínica Sorriso', '78124659000147', 'adm@clinicasorriso.com.br', '31988776655', 'Rua Rio de Janeiro, 800 - Sala 302', 'Belo Horizonte/MG', NULL),
(4, 'Mercadinho Bom Preço ME', 'Bom Preço', '30495812000116', 'bompreco@email.com', '31991234567', 'Rua Itapecerica, 45 - Lagoinha', 'Belo Horizonte/MG', 'Envia notas fiscais sempre no fim do mês.'),
(5, 'TechNova Sistemas Ltda', 'TechNova', '66581234000188', 'financeiro@technova.dev', '1130405060', 'Av. Paulista, 1000 - 12º andar', 'São Paulo/SP', 'Prestadora de serviços de software. ISS mensal.'),
(6, 'Construtora Alicerce Ltda', 'Alicerce Construções', '90817263000180', 'contabil@alicerce.eng.br', '3134445566', 'Rua Goiás, 300', 'Betim/MG', NULL),
(7, 'Ana Paula Ferreira', NULL, '52998224725', 'anapaula.ferreira@email.com', '31987654321', 'Rua Padre Eustáquio, 77', 'Belo Horizonte/MG', 'Psicóloga autônoma (carnê-leão).'),
(8, 'Carlos Eduardo Lima', NULL, '12345678909', 'carlos.lima@email.com', '31999887766', NULL, 'Nova Lima/MG', 'Declaração de IRPF.');

INSERT INTO obrigacoes (cliente_id, nome, descricao, valor, vencimento, status, concluida_em) VALUES
-- Vencidas (pendentes com vencimento já passado)
(1, 'DAS - Simples Nacional', 'Guia do Simples Nacional da competência anterior.', 1845.32, DATE_SUB(CURDATE(), INTERVAL 6 DAY), 'pendente', NULL),
(2, 'DCTFWeb', 'Declaração de débitos e créditos tributários federais.', 0.00, DATE_SUB(CURDATE(), INTERVAL 3 DAY), 'pendente', NULL),
(6, 'FGTS Digital', 'Recolhimento do FGTS da folha de pagamento.', 5320.75, DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'pendente', NULL),
(4, 'ICMS', 'ICMS próprio do período.', 932.10, DATE_SUB(CURDATE(), INTERVAL 10 DAY), 'pendente', NULL),
-- Vencem nos próximos dias
(3, 'DAS - Simples Nacional', 'Guia mensal do Simples Nacional.', 2310.00, CURDATE(), 'pendente', NULL),
(5, 'ISS', 'ISS sobre serviços prestados no mês.', 1250.40, DATE_ADD(CURDATE(), INTERVAL 2 DAY), 'pendente', NULL),
(2, 'IRPJ - Lucro Presumido', 'IRPJ trimestral (quota única).', 4870.90, DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'pendente', NULL),
(2, 'CSLL - Lucro Presumido', 'CSLL trimestral (quota única).', 2192.60, DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'pendente', NULL),
(7, 'Carnê-Leão', 'Recolhimento mensal obrigatório (IRPF).', 386.25, DATE_ADD(CURDATE(), INTERVAL 6 DAY), 'pendente', NULL),
(1, 'FGTS Digital', 'FGTS da folha do mês.', 1488.00, DATE_ADD(CURDATE(), INTERVAL 7 DAY), 'pendente', NULL),
-- A vencer mais adiante
(6, 'INSS - DCTFWeb', 'Contribuições previdenciárias da folha.', 7845.00, DATE_ADD(CURDATE(), INTERVAL 12 DAY), 'pendente', NULL),
(4, 'DAS - Simples Nacional', 'Guia mensal do Simples Nacional.', 678.90, DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'pendente', NULL),
(5, 'DCTFWeb', 'Envio da declaração mensal.', 0.00, DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'pendente', NULL),
(3, 'eSocial - Folha de Pagamento', 'Fechamento dos eventos periódicos da folha.', 0.00, DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'pendente', NULL),
(8, 'IRPF - Declaração Anual', 'Declaração de ajuste anual do imposto de renda.', 0.00, DATE_ADD(CURDATE(), INTERVAL 45 DAY), 'pendente', NULL),
(6, 'DEFIS', 'Declaração de informações socioeconômicas e fiscais.', 0.00, DATE_ADD(CURDATE(), INTERVAL 60 DAY), 'pendente', NULL),
-- Concluídas
(1, 'DAS - Simples Nacional', 'Guia paga pelo cliente e comprovante arquivado.', 1790.15, DATE_SUB(CURDATE(), INTERVAL 36 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 38 DAY)),
(3, 'DAS - Simples Nacional', 'Guia mensal do Simples Nacional.', 2180.00, DATE_SUB(CURDATE(), INTERVAL 36 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 37 DAY)),
(5, 'ISS', 'ISS do mês anterior.', 1190.00, DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 31 DAY)),
(2, 'DCTFWeb', 'Declaração do mês anterior.', 0.00, DATE_SUB(CURDATE(), INTERVAL 33 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 35 DAY)),
(6, 'FGTS Digital', 'FGTS do mês anterior.', 5102.30, DATE_SUB(CURDATE(), INTERVAL 31 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 32 DAY)),
(7, 'Carnê-Leão', 'Recolhimento do mês anterior.', 402.80, DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 25 DAY)),
(4, 'DAS - Simples Nacional', 'Guia do mês anterior.', 655.40, DATE_SUB(CURDATE(), INTERVAL 36 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 36 DAY)),
(1, 'eSocial - Folha de Pagamento', 'Folha fechada e enviada.', 0.00, DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'concluida', DATE_SUB(NOW(), INTERVAL 3 DAY));

INSERT INTO documentos (cliente_id, titulo, descricao, data_limite, situacao, arquivo_nome, arquivo_salvo, arquivo_tipo, arquivo_tamanho, enviado_em) VALUES
-- Aguardando envio (dois já passaram do prazo)
(4, 'Notas fiscais de entrada', 'Todas as notas de compra de mercadorias do mês.', DATE_SUB(CURDATE(), INTERVAL 2 DAY), 'aguardando', NULL, NULL, NULL, NULL, NULL),
(6, 'Folha de ponto dos funcionários', 'Necessária para fechar a folha de pagamento.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'aguardando', NULL, NULL, NULL, NULL, NULL),
(2, 'Extrato bancário', 'Extrato da conta corrente do mês, em PDF.', DATE_ADD(CURDATE(), INTERVAL 3 DAY), 'aguardando', NULL, NULL, NULL, NULL, NULL),
(5, 'Relatório de faturamento', 'Notas de serviço emitidas no mês.', DATE_ADD(CURDATE(), INTERVAL 5 DAY), 'aguardando', NULL, NULL, NULL, NULL, NULL),
(8, 'Informe de rendimentos', 'Informes dos bancos e do empregador para o IRPF.', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'aguardando', NULL, NULL, NULL, NULL, NULL),
(7, 'Recibos de atendimentos', 'Recibos emitidos aos pacientes no mês (carnê-leão).', DATE_ADD(CURDATE(), INTERVAL 4 DAY), 'aguardando', NULL, NULL, NULL, NULL, NULL),
-- Recebidos (arquivo anexado, falta conferir)
(1, 'Extrato bancário', 'Extrato da conta corrente do mês.', DATE_SUB(CURDATE(), INTERVAL 1 DAY), 'recebido', 'extrato-banco-pao-dourado.pdf', 'exemplo-extrato-bancario.pdf', 'application/pdf', 964, DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 'Folha de pagamento assinada', 'Folha do mês assinada pelos funcionários.', CURDATE(), 'recebido', 'folha-pagamento-clinica.pdf', 'exemplo-folha-pagamento.pdf', 'application/pdf', 918, DATE_SUB(NOW(), INTERVAL 1 DAY)),
-- Conferido
(1, 'Notas fiscais de entrada', 'Notas de compra do mês anterior.', DATE_SUB(CURDATE(), INTERVAL 30 DAY), 'conferido', 'notas-entrada-pao-dourado.pdf', 'exemplo-notas-fiscais.pdf', 'application/pdf', 860, DATE_SUB(NOW(), INTERVAL 31 DAY));
