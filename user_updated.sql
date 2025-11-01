-- Mise à jour de la table user avec les données du fichier user.sql
-- Adapté à la structure actuelle de la base de données 3tek

-- Vider la table user actuelle
DELETE FROM user;

-- Réinitialiser l'auto_increment
ALTER TABLE user AUTO_INCREMENT = 1;

-- Insérer les nouveaux utilisateurs avec la structure adaptée
INSERT INTO `user` (`id`, `email`, `roles`, `password`, `name`, `lastname`, `address`, `code`, `ville`, `pays`, `office`, `is_verified`, `phone`, `lot_id`) VALUES
(1, 'info@odoip.fr', '["ROLE_ADMIN"]', '$2y$13$7P6yk7fZxEuyfff4mQMXFecf72CLwwrD6H3Eed7O9DfdOXsC5zVJa', 'NGAMBA TSHITSHI', 'David', '', '', '', '', 'odoip telecom odoip telecom', 1, '0633731208', NULL),
(2, 'toufic.khreish@3tek-europe.com', '["ROLE_ADMIN"]', '$2y$13$F98CXuNe9QM5lKrWQScn0uepH2azXfzwe8utFSbRi0wyvn9wVrOge', 'KHREISH', 'Toufic', '', '', '', '', '3TEK-EUROPE', 1, '0638786382', NULL),
(3, 'dng@afritelec.fr', '[]', '$2y$13$kKlejQnKMyj9FNeyqixdMeFlp1BUImlJ36c2u7wMlWZDaPF6Yc2q6', 'afritelec', 'afritelec', '', '', '', '', 'afritelec', 0, '0633731208', NULL),
(4, 'toufic.khreish@gmail.com', '[]', '$2y$13$yX/i8eZE6MXnw.NYhU5/WeZ8pQeyPEK78C3J8Sk9LiFX886myqIL2', 'KHREISH', 'Toufic', '', '', '', '', '3TEK-Europe', 1, '0638786382', NULL),
(6, 'deleted_68fd304a2eb9c@deleted.com', '["ROLE_DELETED"]', '$2y$13$Rrm7dEsADr/MN8/yXqSYSOTrActO2l6y4ZtB02FD6xwzffBXo0MXG', 'Utilisateur', 'Supprimé', '', '', '', '', 'N/A', 0, '0000000000', NULL);

-- Mettre à jour l'auto_increment pour la prochaine insertion
ALTER TABLE user AUTO_INCREMENT = 8;
