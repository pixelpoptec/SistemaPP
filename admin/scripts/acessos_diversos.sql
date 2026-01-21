select * from grupos;
select * from grupo_permissao;
select * from logs_acesso;
select * from permissoes;
select * from usuario_grupo;
select * from usuarios;

update usuarios set nome = 'Jaime' where id = 1;

INSERT INTO permissoes (nome, descricao) VALUES 
('precificacao', 'Acessar precificação');

select * from clientes;
update tarefas set data_abertura = '2025-05-26 10:00:00', termino_efetivo = '2025-06-03 10:00:00', previsao_termino = '2025-06-03' where id = 12;
update tarefas set tempo_horas = 4, tempo_minutos = 11 where id = 9;
select * from tarefas;

update tempo_rastreamento set data_hora_fim = null where id = 11;
select * from tempo_rastreamento;

select * from historico_precificacao;
ALTER TABLE historico_precificacao ADD COLUMN qtd_pecas INT NOT NULL DEFAULT 1 AFTER titulo;
ALTER TABLE historico_precificacao ADD COLUMN titulo VARCHAR(100) DEFAULT 'ND' AFTER id;
update historico_precificacao set titulo = 'ND' where id =3;

SELECT table_schema AS "Database", 
       ROUND(SUM(data_length + index_length) / (1024 * 1024 * 1024), 2) AS "Tamanho GB"
FROM information_schema.tables 
GROUP BY table_schema;

SELECT table_schema AS "Database", 
       ROUND(SUM(data_length + index_length) / (1024 * 1024), 2) AS "Tamanho MB"
FROM information_schema.tables 
GROUP BY table_schema;

select * from tarefas where data_abertura > '2026-01-01 00:00:00'






