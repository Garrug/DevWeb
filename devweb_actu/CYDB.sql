DROP DATABASE IF EXISTS CYDB;
CREATE DATABASE CYDB;
USE CYDB;

DROP TABLE IF EXISTS commenterEleve;
DROP TABLE IF EXISTS commenterEntreprise;
DROP TABLE IF EXISTS commenterJury;  
DROP TABLE IF EXISTS commenterTuteur;
DROP TABLE IF EXISTS assigner;
DROP TABLE IF EXISTS necessiter;
DROP TABLE IF EXISTS candidater;
DROP TABLE IF EXISTS enregistrer;
DROP TABLE IF EXISTS selectionner;
DROP TABLE IF EXISTS OffreDeStage;
DROP TABLE IF EXISTS OffreAncienne;
DROP TABLE IF EXISTS Tâche;
DROP TABLE IF EXISTS Compétence;
DROP TABLE IF EXISTS Stage;
DROP TABLE IF EXISTS Entreprise;
DROP TABLE IF EXISTS Jury;
DROP TABLE IF EXISTS Tuteur;
DROP TABLE IF EXISTS Etudiant;
DROP TABLE IF EXISTS Document;
DROP TABLE IF EXISTS Administrateur;

CREATE TABLE Administrateur(
   idAdministrateur INT AUTO_INCREMENT,
   mail VARCHAR(50),
   identifiant VARCHAR(50) NOT NULL,
   mdp VARCHAR(50) NOT NULL,
   PRIMARY KEY(idAdministrateur)
);

CREATE TABLE Document(
   idDocument INT AUTO_INCREMENT,
   nom VARCHAR(50) NOT NULL,
   dateDepot DATE NOT NULL,
   PRIMARY KEY(idDocument)
);

CREATE TABLE Etudiant(
   idEtudiant INT AUTO_INCREMENT,
   nom VARCHAR(50),
   prenom VARCHAR(50),
   mail VARCHAR(50) NOT NULL,
   filiere VARCHAR(50),
   annee INT,
   groupe INT,
   identifiant VARCHAR(50),
   mdp VARCHAR(50),
   PRIMARY KEY(idEtudiant)
);

CREATE TABLE Tuteur(
   idTuteur INT AUTO_INCREMENT,
   nom VARCHAR(50),
   prenom VARCHAR(50),
   mail VARCHAR(50),
   identifiant VARCHAR(50),
   mdp VARCHAR(50),
   PRIMARY KEY(idTuteur)
);

CREATE TABLE Jury(
   idJury INT AUTO_INCREMENT,
   nom VARCHAR(50) NOT NULL,
   mail VARCHAR(50),
   identifiant VARCHAR(50) NOT NULL,
   mdp VARCHAR(50) NOT NULL,
   PRIMARY KEY(idJury)
);

CREATE TABLE Entreprise(
   idEntreprise INT AUTO_INCREMENT,
   nom VARCHAR(50) NOT NULL,
   description VARCHAR(2000) NOT NULL,
   mail VARCHAR(50) NOT NULL,
   identifiant VARCHAR(50) NOT NULL,
   mdp VARCHAR(50) NOT NULL,
   PRIMARY KEY(idEntreprise)
);

CREATE TABLE Stage(
   idStage INT AUTO_INCREMENT,
   dateDebut DATE,
   dateFin DATE,
   nomTuteurEntreprise VARCHAR(50),
   mailTuteurEntreprise VARCHAR(50),
   idJury INT NOT NULL,
   idEtudiant INT NOT NULL,
   PRIMARY KEY(idStage),
   FOREIGN KEY(idJury) REFERENCES Jury(idJury),
   FOREIGN KEY(idEtudiant) REFERENCES Etudiant(idEtudiant)
);

CREATE TABLE Compétence(
   idCompetence INT AUTO_INCREMENT,
   nom VARCHAR(50) NOT NULL,
   PRIMARY KEY(idCompetence)
);

CREATE TABLE Tâche(
   idTache INT AUTO_INCREMENT,
   nom VARCHAR(50),
   description VARCHAR(1000),
   echeance DATE,
   PRIMARY KEY(idTache)
);

CREATE TABLE OffreAncienne(
   idOffre INT AUTO_INCREMENT,
   dateDepot DATE,
   debutStage DATE,
   description VARCHAR(2000),
   niveau VARCHAR(50),
   duree INT,
   idEntreprise INT NOT NULL,
   PRIMARY KEY(idOffre),
   FOREIGN KEY(idEntreprise) REFERENCES Entreprise(idEntreprise)
);

CREATE TABLE OffreDeStage(
   idOffre INT AUTO_INCREMENT,
   dateDepot DATE,
   debutStage DATE,
   description VARCHAR(2000),
   niveau VARCHAR(50),
   duree INT,
   estDisponible INT,
   idEntreprise INT NOT NULL,
   PRIMARY KEY(idOffre),
   FOREIGN KEY(idEntreprise) REFERENCES Entreprise(idEntreprise)
);

CREATE TABLE selectionner(
   idEtudiant INT,
   idTuteur INT,
   annee INT,
   PRIMARY KEY(idEtudiant, idTuteur, annee),
   FOREIGN KEY(idEtudiant) REFERENCES Etudiant(idEtudiant),
   FOREIGN KEY(idTuteur) REFERENCES Tuteur(idTuteur)
);

CREATE TABLE enregistrer(
   idOffre INT,
   idEtudiant INT,
   PRIMARY KEY(idOffre, idEtudiant),
   FOREIGN KEY(idOffre) REFERENCES OffreDeStage(idOffre),
   FOREIGN KEY(idEtudiant) REFERENCES Etudiant(idEtudiant)
);

CREATE TABLE candidater(
   idDocument INT,
   idOffre INT,
   idEtudiant INT,
   choixEntreprise INT,
   choixEtudiant INT,
   choixEcole INT,
   PRIMARY KEY(idDocument, idOffre, idEtudiant),
   FOREIGN KEY(idDocument) REFERENCES Document(idDocument),
   FOREIGN KEY(idOffre) REFERENCES OffreDeStage(idOffre),
   FOREIGN KEY(idEtudiant) REFERENCES Etudiant(idEtudiant)
);

CREATE TABLE necessiter(
   idOffre INT,
   idCompetence INT,
   PRIMARY KEY(idOffre, idCompetence),
   FOREIGN KEY(idOffre) REFERENCES OffreDeStage(idOffre),
   FOREIGN KEY(idCompetence) REFERENCES Compétence(idCompetence)
);

CREATE TABLE assigner(
   idDocument INT,
   idEtudiant INT,
   idTuteur INT,
   idTache INT,
   PRIMARY KEY(idDocument, idEtudiant, idTuteur, idTache),
   FOREIGN KEY(idDocument) REFERENCES Document(idDocument),
   FOREIGN KEY(idEtudiant) REFERENCES Etudiant(idEtudiant),
   FOREIGN KEY(idTuteur) REFERENCES Tuteur(idTuteur),
   FOREIGN KEY(idTache) REFERENCES Tâche(idTache)
);

CREATE TABLE commenterTuteur(
   idTuteur INT,
   idStage INT,
   contenu VARCHAR(2000),
   dateCreation DATE,
   PRIMARY KEY(idTuteur, idStage, dateCreation),
   FOREIGN KEY(idTuteur) REFERENCES Tuteur(idTuteur),
   FOREIGN KEY(idStage) REFERENCES Stage(idStage)
);

CREATE TABLE commenterJury(
   idJury INT,
   idStage INT,
   contenu VARCHAR(2000),
   dateCreation DATE,
   PRIMARY KEY(idJury, idStage, dateCreation),
   FOREIGN KEY(idJury) REFERENCES Jury(idJury),
   FOREIGN KEY(idStage) REFERENCES Stage(idStage)
);

CREATE TABLE commenterEntreprise(
   idEntreprise INT,
   idStage INT,
   contenu VARCHAR(2000),
   dateCreation DATE,
   PRIMARY KEY(idEntreprise, idStage, dateCreation),
   FOREIGN KEY(idEntreprise) REFERENCES Entreprise(idEntreprise),
   FOREIGN KEY(idStage) REFERENCES Stage(idStage)
);

CREATE TABLE commenterEleve(
   idEtudiant INT,
   idStage INT,
   contenu VARCHAR(2000),
   dateCreation DATE,
   PRIMARY KEY(idEtudiant, idStage, dateCreation),
   FOREIGN KEY(idEtudiant) REFERENCES Etudiant(idEtudiant),
   FOREIGN KEY(idStage) REFERENCES Stage(idStage)
);

