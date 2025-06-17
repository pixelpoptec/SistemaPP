CREATE TABLE `tempo_rastreamento` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tarefa_id` int(11) NOT NULL,
  `tempo_horas` int(11) NOT NULL DEFAULT 0,
  `tempo_minutos` int(11) NOT NULL DEFAULT 0,
  `data_hora_inicio` datetime NOT NULL,
  `data_hora_fim` datetime DEFAULT NULL,
  `usuario_id` int(11) NOT NULL,
  `observacoes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_tempo_tarefa` (`tarefa_id`),
  KEY `fk_tempo_usuario` (`usuario_id`),
  CONSTRAINT `fk_tempo_tarefa` FOREIGN KEY (`tarefa_id`) REFERENCES `tarefas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tempo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE tempo_rastreamento ADD COLUMN segundos_totais INT NOT NULL DEFAULT 0 AFTER tempo_minutos;
ALTER TABLE `tempo_rastreamento` ADD COLUMN `tempo_segundos` int(11) NOT NULL DEFAULT 0 AFTER `observacoes`;

--------------------------------------------------------
-- VERSÃO NOVA --
--------------------------------------------------------
CREATE TABLE tempo_rastreamento (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tarefa_id INT NOT NULL,
    tempo_horas INT DEFAULT 0,
    tempo_minutos INT DEFAULT 0,
    segundos_totais INT DEFAULT 0,
    data_hora_inicio DATETIME,
    data_hora_fim DATETIME NULL,
    usuario_id INT NOT NULL,
    observacoes TEXT,
    FOREIGN KEY (tarefa_id) REFERENCES tarefas(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

