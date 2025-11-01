-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Hôte : localhost:3306
-- Généré le : mar. 28 oct. 2025 à 09:19
-- Version du serveur : 11.4.8-MariaDB
-- Version de PHP : 8.4.13

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `voipoutlet_3tekapp`
--

-- --------------------------------------------------------

--
-- Structure de la table `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL,
  `email` varchar(180) NOT NULL,
  `roles` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '(DC2Type:json)' CHECK (json_valid(`roles`)),
  `password` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `lastname` varchar(255) NOT NULL,
  `address` varchar(255) DEFAULT NULL,
  `code` varchar(20) DEFAULT NULL,
  `ville` varchar(255) DEFAULT NULL,
  `pays` varchar(255) DEFAULT NULL,
  `office` varchar(255) NOT NULL,
  `is_verified` tinyint(1) NOT NULL,
  `phone` varchar(60) NOT NULL,
  `type_id` int(11) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user`
--

INSERT INTO `user` (`id`, `email`, `roles`, `password`, `name`, `lastname`, `address`, `code`, `ville`, `pays`, `office`, `is_verified`, `phone`, `type_id`, `profile_image`) VALUES
(1, 'info@odoip.fr', '[\"ROLE_ADMIN\"]', '$2y$13$7P6yk7fZxEuyfff4mQMXFecf72CLwwrD6H3Eed7O9DfdOXsC5zVJa', 'NGAMBA TSHITSHI', 'David', NULL, NULL, NULL, NULL, 'odoip telecom odoip telecom', 1, '0633731208', NULL, NULL),
(2, 'toufic.khreish@3tek-europe.com', '[\"ROLE_ADMIN\"]', '$2y$13$F98CXuNe9QM5lKrWQScn0uepH2azXfzwe8utFSbRi0wyvn9wVrOge', 'KHREISH', 'Toufic', NULL, NULL, NULL, NULL, '3TEK-EUROPE', 1, '0638786382', 3, NULL),
(3, 'dng@afritelec.fr', '[]', '$2y$13$kKlejQnKMyj9FNeyqixdMeFlp1BUImlJ36c2u7wMlWZDaPF6Yc2q6', 'afritelec', 'afritelec', NULL, NULL, NULL, NULL, 'afritelec', 0, '0633731208', 3, NULL),
(4, 'toufic.khreish@gmail.com', '[]', '$2y$13$yX/i8eZE6MXnw.NYhU5/WeZ8pQeyPEK78C3J8Sk9LiFX886myqIL2', 'KHREISH', 'Toufic', NULL, NULL, NULL, NULL, '3TEK-Europe', 1, '0638786382', 3, NULL),
(6, 'deleted_68fd304a2eb9c@deleted.com', '[\"ROLE_DELETED\"]', '$2y$13$Rrm7dEsADr/MN8/yXqSYSOTrActO2l6y4ZtB02FD6xwzffBXo0MXG', 'Utilisateur', 'Supprimé', NULL, NULL, NULL, NULL, 'N/A', 0, '0000000000', NULL, NULL);

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `UNIQ_IDENTIFIER_EMAIL` (`email`),
  ADD KEY `IDX_8D93D649C54C8C93` (`type_id`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `user`
--
ALTER TABLE `user`
  ADD CONSTRAINT `FK_8D93D649C54C8C93` FOREIGN KEY (`type_id`) REFERENCES `type` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
