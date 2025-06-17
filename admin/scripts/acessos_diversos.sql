select * from grupos;
select * from grupo_permissao;
select * from logs_acesso;
select * from permissoes;
select * from usuario_grupo;
select * from usuarios;

update usuarios set nome = 'Usuário' where id = 2;

INSERT INTO permissoes (nome, descricao) VALUES 
('precificacao', 'Acessar precificação');

select * from clientes;
update tarefas set data_abertura = '2025-05-13 10:00:00', termino_efetivo = '2025-06-03 10:00:00', previsao_termino = '2025-06-03' where id = 12;
update tarefas set tempo_horas = 1, tempo_minutos = 11 where id = 9;
select * from tarefas;
select * from tempo_rastreamento;





