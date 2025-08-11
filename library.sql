-- phpMyAdmin SQL Dump
-- version 4.7.0
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le :  lun. 11 août 2025 à 22:12
-- Version du serveur :  5.7.17
-- Version de PHP :  7.1.3

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données :  `library`
--

-- --------------------------------------------------------

--
-- Structure de la table `archive`
--

CREATE TABLE `archive` (
  `idPrimaire` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `Objet` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `archive`
--

INSERT INTO `archive` (`idPrimaire`, `email`, `Objet`, `message`, `date_envoi`) VALUES
(1, 'falimananaluciezanah@gmail.com', 'Une lettre excuse ', 'Bonjour Zanah, désole le trainement sur votre réservation livre. Car on aura encore une problème avec ce livre. Mais on attente je vous prie de réserver sur une autre livre de même type si vous voulez. Au plus tard nous espérons de vous donner une bonne nouvelle sur le livre réserver.', '2025-07-22 15:28:07'),
(2, 'falimananaluciezanah@gmail.com', 'Conseil du choix livre', 'Bonjour Zanah, nous somme le bibliothèque acacia, à propos de votre réservation. Nous avons encore quelque chose à règle avec ce livre. Mais en  attend , je vous conseille de réserver une autre livre de même type si vous voulez. Mercie en avance, reste toujours en contact avec nous. ', '2025-07-25 08:37:00'),
(3, 'falimananaluciezanah@gmail.com', 'Réservation confirmé', 'Bonjour Zanah, votre réservation étiez confirmé. Vous pouvez le prendre demain à 9h00. ', '2025-08-08 16:30:35');

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

CREATE TABLE `livres` (
  `id` int(11) NOT NULL,
  `titre` varchar(200) NOT NULL,
  `auteur` varchar(100) NOT NULL,
  `annee` int(11) DEFAULT NULL,
  `categorie` varchar(50) DEFAULT NULL,
  `nb_exemplaires` int(11) NOT NULL,
  `photo` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`id`, `titre`, `auteur`, `annee`, `categorie`, `nb_exemplaires`, `photo`) VALUES
(1, 'Des Anneaux', 'J.RR Tolkiet', 2010, 'Aventure', 3, 'livre_1_689649024deb6.png'),
(2, 'Le comte de Monte-Cristo', 'Alexandre Adams', 2010, 'Aventure', 10, 'livre_2_689657f7524b0.png'),
(3, 'AI IN MOTION', 'Shawn Agacia', 2023, 'Informatique', 2, 'livre_3_6896582611c52.png'),
(4, 'Business Risk', 'Juliana Silva', 2025, 'Management', 1, 'livre_4_68964ab6b642e.png'),
(5, 'Kids drawing book', 'Larana', 2019, 'Aventure kids', 10, 'livre_5_689658bdb05da.png');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `livre_id` int(11) DEFAULT NULL,
  `date_reservation` datetime DEFAULT NULL,
  `statut` enum('en attente','validee','annulee','prise','rendu') DEFAULT 'en attente',
  `is_viewed` tinyint(1) NOT NULL DEFAULT '0',
  `date_prise` datetime DEFAULT NULL,
  `date_limite_retour` datetime DEFAULT NULL,
  `date_retour` datetime DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `user_id`, `livre_id`, `date_reservation`, `statut`, `is_viewed`, `date_prise`, `date_limite_retour`, `date_retour`) VALUES
(1, 4, 2, '2025-07-21 12:20:43', 'validee', 0, NULL, NULL, NULL),
(2, 4, 2, '2025-07-21 12:39:33', 'validee', 0, NULL, NULL, NULL),
(4, 1, 1, '2025-07-25 08:25:44', 'annulee', 0, NULL, NULL, NULL),
(8, 6, 2, '2025-08-11 14:33:28', 'prise', 1, '2025-08-11 22:08:59', '2025-08-25 22:08:59', NULL),
(9, 6, 5, '2025-08-11 14:33:44', 'validee', 1, NULL, NULL, NULL),
(10, 6, 3, '2025-08-11 16:19:29', 'annulee', 0, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `role` enum('user','admin') DEFAULT 'user',
  `profil` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `nom`, `email`, `mot_de_passe`, `telephone`, `role`, `profil`) VALUES
(6, 'Elia', 'elia@gmail.com', 'elia', '03256667823', 'user', '/assets/689a05fd34178_1734187017432 (1).jpg'),
(4, 'admin', 'walseanito@gmail.com', 'admin', '0328361447', 'admin', '/assets/images/profiles/687e26ed62e3e_lucas.png'),
(5, 'Zanah', 'falimananaluciezanah@gmail.com', 'zanah10win', '0328020720', 'user', '/assets/images/users/6895fa06b2f96_1735026366796.jpg');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `archive`
--
ALTER TABLE `archive`
  ADD PRIMARY KEY (`idPrimaire`);

--
-- Index pour la table `livres`
--
ALTER TABLE `livres`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `livre_id` (`livre_id`),
  ADD KEY `idx_reservations_date_limite` (`date_limite_retour`),
  ADD KEY `idx_reservations_statut_date` (`statut`,`date_limite_retour`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `archive`
--
ALTER TABLE `archive`
  MODIFY `idPrimaire` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;
--
-- AUTO_INCREMENT pour la table `livres`
--
ALTER TABLE `livres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;
--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;
--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
