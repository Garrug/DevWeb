-- MySQL dump 10.13  Distrib 8.0.44, for Linux (x86_64)
--
-- Host: localhost    Database: monsite
-- ------------------------------------------------------
-- Server version	8.0.44-0ubuntu0.24.04.1

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `Administrateur`
--

DROP TABLE IF EXISTS `Administrateur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Administrateur` (
  `idAdmin` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `mail` varchar(50) DEFAULT NULL,
  `identifiant` varchar(50) DEFAULT NULL,
  `mdp` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idAdmin`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Administrateur`
--

LOCK TABLES `Administrateur` WRITE;
/*!40000 ALTER TABLE `Administrateur` DISABLE KEYS */;
INSERT INTO `Administrateur` VALUES (3,'administrateur','administrateur','administrateur@gmail.com','administrateur','$2y$10$BF6GHqTYogwL0oolEO/zIeGp4g2.bxdtlaBg1LjDx/.ZIzDqqqCiK');
/*!40000 ALTER TABLE `Administrateur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Competence`
--

DROP TABLE IF EXISTS `Competence`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Competence` (
  `idCompetence` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idCompetence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Competence`
--

LOCK TABLES `Competence` WRITE;
/*!40000 ALTER TABLE `Competence` DISABLE KEYS */;
/*!40000 ALTER TABLE `Competence` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Document`
--

DROP TABLE IF EXISTS `Document`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Document` (
  `idDocument` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `lettre_motivation` text,
  `cv_filename` varchar(255) DEFAULT NULL,
  `dateDepot` date DEFAULT NULL,
  PRIMARY KEY (`idDocument`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Document`
--

LOCK TABLES `Document` WRITE;
/*!40000 ALTER TABLE `Document` DISABLE KEYS */;
INSERT INTO `Document` VALUES (1,'TP_SE_Memoire.pdf','Salut, salut, choisis moi stp','cv_5_7_1774196994.pdf','2026-03-22'),(2,'TP_SE_Memoire.pdf','yoyoyo, moi ?','cv_5_8_1774199672.pdf','2026-03-22');
/*!40000 ALTER TABLE `Document` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Entreprise`
--

DROP TABLE IF EXISTS `Entreprise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Entreprise` (
  `idEntreprise` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `mail` varchar(50) DEFAULT NULL,
  `identifiant` varchar(50) DEFAULT NULL,
  `mdp` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idEntreprise`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Entreprise`
--

LOCK TABLES `Entreprise` WRITE;
/*!40000 ALTER TABLE `Entreprise` DISABLE KEYS */;
INSERT INTO `Entreprise` VALUES (5,'entreprise','entreprise','entreprise@gmail.com','entreprise','$2y$10$k3/Yp0AGoBi3KfSYDb6kz.r9GkgLohY2gL5unQ12cbOX6sC2oJue6');
/*!40000 ALTER TABLE `Entreprise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Etudiant`
--

DROP TABLE IF EXISTS `Etudiant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Etudiant` (
  `idEtudiant` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `mail` varchar(50) DEFAULT NULL,
  `filiere` varchar(50) DEFAULT NULL,
  `annee` varchar(50) DEFAULT NULL,
  `groupe` varchar(50) DEFAULT NULL,
  `identifiant` varchar(50) DEFAULT NULL,
  `mdp` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idEtudiant`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Etudiant`
--

LOCK TABLES `Etudiant` WRITE;
/*!40000 ALTER TABLE `Etudiant` DISABLE KEYS */;
INSERT INTO `Etudiant` VALUES (5,'etudiant','etudiant','etudiant@gmail.com','informatique','3A','G1','etudiant','$2y$10$ypR.W.zOHZk31xcy/0rUV.rYpIpmwwXuCl5/gadzK2OeeOq5RqoqW');
/*!40000 ALTER TABLE `Etudiant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Jury`
--

DROP TABLE IF EXISTS `Jury`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Jury` (
  `idJury` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `mail` varchar(50) DEFAULT NULL,
  `identifiant` varchar(50) DEFAULT NULL,
  `mdp` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idJury`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Jury`
--

LOCK TABLES `Jury` WRITE;
/*!40000 ALTER TABLE `Jury` DISABLE KEYS */;
INSERT INTO `Jury` VALUES (3,'jury','jury','jury@gmail.com','jury','$2y$10$m/K0lT02MGPcOcUhLwckLeMSMy89fRrNwIfvkYDfgQdMyfqvxrvke');
/*!40000 ALTER TABLE `Jury` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Offre_de_stage`
--

DROP TABLE IF EXISTS `Offre_de_stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Offre_de_stage` (
  `idOffre` int NOT NULL AUTO_INCREMENT,
  `dateDepot` date DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `niveau` varchar(50) DEFAULT NULL,
  `duree` int DEFAULT NULL,
  `debutStage` date DEFAULT NULL,
  `idEntreprise` int NOT NULL,
  PRIMARY KEY (`idOffre`),
  KEY `idEntreprise` (`idEntreprise`),
  CONSTRAINT `Offre_de_stage_ibfk_1` FOREIGN KEY (`idEntreprise`) REFERENCES `Entreprise` (`idEntreprise`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Offre_de_stage`
--

LOCK TABLES `Offre_de_stage` WRITE;
/*!40000 ALTER TABLE `Offre_de_stage` DISABLE KEYS */;
INSERT INTO `Offre_de_stage` VALUES (7,'2026-03-21','offre de stage ing1 devweb','Bac+2','16:03:00','2026-03-28',5),(8,'2026-03-22','stage ing2','Bac+3','04:00:00','2026-03-20',5);
/*!40000 ALTER TABLE `Offre_de_stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Stage`
--

DROP TABLE IF EXISTS `Stage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Stage` (
  `idStage` char(50) NOT NULL,
  `dateDebut` date DEFAULT NULL,
  `dateFin` date DEFAULT NULL,
  `idJury` int NOT NULL,
  `idEtudiant` int NOT NULL,
  PRIMARY KEY (`idStage`),
  KEY `idJury` (`idJury`),
  KEY `idEtudiant` (`idEtudiant`),
  CONSTRAINT `Stage_ibfk_1` FOREIGN KEY (`idJury`) REFERENCES `Jury` (`idJury`),
  CONSTRAINT `Stage_ibfk_2` FOREIGN KEY (`idEtudiant`) REFERENCES `Etudiant` (`idEtudiant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Stage`
--

LOCK TABLES `Stage` WRITE;
/*!40000 ALTER TABLE `Stage` DISABLE KEYS */;
/*!40000 ALTER TABLE `Stage` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tache`
--

DROP TABLE IF EXISTS `Tache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tache` (
  `idTache` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `echeance` date DEFAULT NULL,
  PRIMARY KEY (`idTache`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tache`
--

LOCK TABLES `Tache` WRITE;
/*!40000 ALTER TABLE `Tache` DISABLE KEYS */;
/*!40000 ALTER TABLE `Tache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `Tuteur`
--

DROP TABLE IF EXISTS `Tuteur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `Tuteur` (
  `idTuteur` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(50) DEFAULT NULL,
  `prenom` varchar(50) DEFAULT NULL,
  `mail` varchar(50) DEFAULT NULL,
  `identifiant` varchar(50) DEFAULT NULL,
  `mdp` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`idTuteur`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `Tuteur`
--

LOCK TABLES `Tuteur` WRITE;
/*!40000 ALTER TABLE `Tuteur` DISABLE KEYS */;
INSERT INTO `Tuteur` VALUES (3,'tuteur','tuteur','tuteur@gmail.com','tuteur','$2y$10$8E0uG4f7eUrs3XdqEbTspOC/3ELbP90IlMsEXycroRv436.oQDHfm');
/*!40000 ALTER TABLE `Tuteur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `assigner`
--

DROP TABLE IF EXISTS `assigner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `assigner` (
  `idDocument` int NOT NULL,
  `idEtudiant` int NOT NULL,
  `idTuteur` int NOT NULL,
  `idTache` int NOT NULL,
  PRIMARY KEY (`idDocument`,`idEtudiant`,`idTuteur`,`idTache`),
  KEY `idEtudiant` (`idEtudiant`),
  KEY `idTuteur` (`idTuteur`),
  KEY `idTache` (`idTache`),
  CONSTRAINT `assigner_ibfk_1` FOREIGN KEY (`idDocument`) REFERENCES `Document` (`idDocument`),
  CONSTRAINT `assigner_ibfk_2` FOREIGN KEY (`idEtudiant`) REFERENCES `Etudiant` (`idEtudiant`),
  CONSTRAINT `assigner_ibfk_3` FOREIGN KEY (`idTuteur`) REFERENCES `Tuteur` (`idTuteur`),
  CONSTRAINT `assigner_ibfk_4` FOREIGN KEY (`idTache`) REFERENCES `Tache` (`idTache`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `assigner`
--

LOCK TABLES `assigner` WRITE;
/*!40000 ALTER TABLE `assigner` DISABLE KEYS */;
/*!40000 ALTER TABLE `assigner` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidater`
--

DROP TABLE IF EXISTS `candidater`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `candidater` (
  `idDocument` int NOT NULL,
  `idOffre` int NOT NULL,
  `idEtudiant` int NOT NULL,
  `choixEntreprise` varchar(50) DEFAULT NULL,
  `choixEtudiant` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idDocument`,`idOffre`,`idEtudiant`),
  KEY `idOffre` (`idOffre`),
  KEY `idEtudiant` (`idEtudiant`),
  CONSTRAINT `candidater_ibfk_1` FOREIGN KEY (`idDocument`) REFERENCES `Document` (`idDocument`),
  CONSTRAINT `candidater_ibfk_2` FOREIGN KEY (`idOffre`) REFERENCES `Offre_de_stage` (`idOffre`),
  CONSTRAINT `candidater_ibfk_3` FOREIGN KEY (`idEtudiant`) REFERENCES `Etudiant` (`idEtudiant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidater`
--

LOCK TABLES `candidater` WRITE;
/*!40000 ALTER TABLE `candidater` DISABLE KEYS */;
INSERT INTO `candidater` VALUES (1,7,5,'Accepté','Accepté'),(2,8,5,'En attente','Accepté');
/*!40000 ALTER TABLE `candidater` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commenterEntreprise`
--

DROP TABLE IF EXISTS `commenterEntreprise`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commenterEntreprise` (
  `idEntreprise` int NOT NULL,
  `idStage` char(50) NOT NULL,
  `contenu` varchar(1000) DEFAULT NULL,
  `dateCreation` date DEFAULT NULL,
  PRIMARY KEY (`idEntreprise`,`idStage`),
  KEY `idStage` (`idStage`),
  CONSTRAINT `commenterEntreprise_ibfk_1` FOREIGN KEY (`idEntreprise`) REFERENCES `Entreprise` (`idEntreprise`),
  CONSTRAINT `commenterEntreprise_ibfk_2` FOREIGN KEY (`idStage`) REFERENCES `Stage` (`idStage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commenterEntreprise`
--

LOCK TABLES `commenterEntreprise` WRITE;
/*!40000 ALTER TABLE `commenterEntreprise` DISABLE KEYS */;
/*!40000 ALTER TABLE `commenterEntreprise` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commenterEtudiant`
--

DROP TABLE IF EXISTS `commenterEtudiant`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commenterEtudiant` (
  `idEtudiant` int NOT NULL,
  `idStage` char(50) NOT NULL,
  `contenu` varchar(1000) DEFAULT NULL,
  `dateCreation` date DEFAULT NULL,
  PRIMARY KEY (`idEtudiant`,`idStage`),
  KEY `idStage` (`idStage`),
  CONSTRAINT `commenterEtudiant_ibfk_1` FOREIGN KEY (`idEtudiant`) REFERENCES `Etudiant` (`idEtudiant`),
  CONSTRAINT `commenterEtudiant_ibfk_2` FOREIGN KEY (`idStage`) REFERENCES `Stage` (`idStage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commenterEtudiant`
--

LOCK TABLES `commenterEtudiant` WRITE;
/*!40000 ALTER TABLE `commenterEtudiant` DISABLE KEYS */;
/*!40000 ALTER TABLE `commenterEtudiant` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commenterJury`
--

DROP TABLE IF EXISTS `commenterJury`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commenterJury` (
  `idJury` int NOT NULL,
  `idStage` char(50) NOT NULL,
  `contenu` varchar(1000) DEFAULT NULL,
  `dateCreation` date DEFAULT NULL,
  PRIMARY KEY (`idJury`,`idStage`),
  KEY `idStage` (`idStage`),
  CONSTRAINT `commenterJury_ibfk_1` FOREIGN KEY (`idJury`) REFERENCES `Jury` (`idJury`),
  CONSTRAINT `commenterJury_ibfk_2` FOREIGN KEY (`idStage`) REFERENCES `Stage` (`idStage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commenterJury`
--

LOCK TABLES `commenterJury` WRITE;
/*!40000 ALTER TABLE `commenterJury` DISABLE KEYS */;
/*!40000 ALTER TABLE `commenterJury` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `commenterTuteur`
--

DROP TABLE IF EXISTS `commenterTuteur`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `commenterTuteur` (
  `idTuteur` int NOT NULL,
  `idStage` char(50) NOT NULL,
  `contenu` varchar(1000) DEFAULT NULL,
  `dateCreation` date DEFAULT NULL,
  PRIMARY KEY (`idTuteur`,`idStage`),
  KEY `idStage` (`idStage`),
  CONSTRAINT `commenterTuteur_ibfk_1` FOREIGN KEY (`idTuteur`) REFERENCES `Tuteur` (`idTuteur`),
  CONSTRAINT `commenterTuteur_ibfk_2` FOREIGN KEY (`idStage`) REFERENCES `Stage` (`idStage`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `commenterTuteur`
--

LOCK TABLES `commenterTuteur` WRITE;
/*!40000 ALTER TABLE `commenterTuteur` DISABLE KEYS */;
/*!40000 ALTER TABLE `commenterTuteur` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `enregistrer`
--

DROP TABLE IF EXISTS `enregistrer`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `enregistrer` (
  `idOffre` int NOT NULL,
  `idEtudiant` int NOT NULL,
  PRIMARY KEY (`idOffre`,`idEtudiant`),
  KEY `idEtudiant` (`idEtudiant`),
  CONSTRAINT `enregistrer_ibfk_1` FOREIGN KEY (`idOffre`) REFERENCES `Offre_de_stage` (`idOffre`),
  CONSTRAINT `enregistrer_ibfk_2` FOREIGN KEY (`idEtudiant`) REFERENCES `Etudiant` (`idEtudiant`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `enregistrer`
--

LOCK TABLES `enregistrer` WRITE;
/*!40000 ALTER TABLE `enregistrer` DISABLE KEYS */;
/*!40000 ALTER TABLE `enregistrer` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `necessiter`
--

DROP TABLE IF EXISTS `necessiter`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `necessiter` (
  `idOffre` int NOT NULL,
  `idCompetence` int NOT NULL,
  PRIMARY KEY (`idOffre`,`idCompetence`),
  KEY `idCompetence` (`idCompetence`),
  CONSTRAINT `necessiter_ibfk_1` FOREIGN KEY (`idOffre`) REFERENCES `Offre_de_stage` (`idOffre`),
  CONSTRAINT `necessiter_ibfk_2` FOREIGN KEY (`idCompetence`) REFERENCES `Competence` (`idCompetence`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `necessiter`
--

LOCK TABLES `necessiter` WRITE;
/*!40000 ALTER TABLE `necessiter` DISABLE KEYS */;
/*!40000 ALTER TABLE `necessiter` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `selectionner`
--

DROP TABLE IF EXISTS `selectionner`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `selectionner` (
  `idEtudiant` int NOT NULL,
  `idTuteur` int NOT NULL,
  `annee` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`idEtudiant`,`idTuteur`),
  KEY `idTuteur` (`idTuteur`),
  CONSTRAINT `selectionner_ibfk_1` FOREIGN KEY (`idEtudiant`) REFERENCES `Etudiant` (`idEtudiant`),
  CONSTRAINT `selectionner_ibfk_2` FOREIGN KEY (`idTuteur`) REFERENCES `Tuteur` (`idTuteur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `selectionner`
--

LOCK TABLES `selectionner` WRITE;
/*!40000 ALTER TABLE `selectionner` DISABLE KEYS */;
/*!40000 ALTER TABLE `selectionner` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-03-23 14:56:58
