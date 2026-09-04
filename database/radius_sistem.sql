-- MySQL dump 10.13  Distrib 8.4.3, for Win64 (x86_64)
--
-- Host: localhost    Database: radius_sistem
-- ------------------------------------------------------
-- Server version	8.4.3

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
-- Table structure for table `audit_logs`
--

DROP TABLE IF EXISTS `audit_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `audit_logs` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(50) NOT NULL,
  `entity_type` varchar(50) NOT NULL,
  `entity_id` int DEFAULT NULL,
  `details` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `audit_logs`
--

LOCK TABLES `audit_logs` WRITE;
/*!40000 ALTER TABLE `audit_logs` DISABLE KEYS */;
/*!40000 ALTER TABLE `audit_logs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `branches`
--

DROP TABLE IF EXISTS `branches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `branches` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `address` text NOT NULL,
  `city` varchar(100) NOT NULL,
  `latitude` decimal(11,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `target_market_notes` text,
  `geocoding_status` enum('verified','manual','pending','failed') DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_by` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  CONSTRAINT `branches_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `branches`
--

LOCK TABLES `branches` WRITE;
/*!40000 ALTER TABLE `branches` DISABLE KEYS */;
INSERT INTO `branches` VALUES (1,'Daycare Harapan Indah','Jl. Boulevard Raya, Harapan Indah','Bekasi',-6.18327140,106.98512770,'Kembali ke Harapan Indah','verified','2026-09-01 03:53:55','2026-09-01 03:56:34',1),(2,'Daycare Kemang','Komplek Kiray, Jl. Kemang Selatan II No.24, Bangka, Kec. Mampang Prapatan','Jakarta Selatan',-6.26276430,106.81332630,'','manual','2026-09-01 03:59:48','2026-09-01 04:23:37',1),(3,'Rumah Biru Cawang 1','Jl. O Kavling No.18, RT.10/RW.14, Kb. Baru, Kec. Tebet, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12830','Jakarta Selatan',-6.24048477,106.85928719,'','manual','2026-09-01 04:29:47','2026-09-04 00:00:00',1),(4,'Rumah Biru Cawang 2','Jl. O Kavling No.10, RT.9/RW.14, Kb. Baru, Kec. Tebet, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12830','Jakarta Selatan',-6.24121231,106.85903252,'','manual','2026-09-04 00:00:00','2026-09-04 00:00:00',1),(5,'Rumah Biru Tebet','Komplek Jepang, Jl. Tebet Dalam IV H No.15, RT.20/RW.1, Tebet Bar., Kec. Tebet, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12810','Jakarta Selatan',-6.22851919,106.85133443,'','manual','2026-09-04 00:00:00','2026-09-04 00:00:00',1),(6,'Rumah Biru Pasar Minggu','Komplek Perkantoran PT Adhi Karya, Jl. Raya Pasar Minggu No.KM. 18, RT.13/RW.1, Ps. Minggu, Kota Jakarta Selatan, Daerah Khusus Ibukota Jakarta 12510','Jakarta Selatan',-6.26580966,106.84562976,'','manual','2026-09-04 00:00:00','2026-09-04 00:00:00',1);
/*!40000 ALTER TABLE `branches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `covered_areas`
--

DROP TABLE IF EXISTS `covered_areas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `covered_areas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `branch_id` int NOT NULL,
  `area_name` varchar(150) NOT NULL,
  `area_type` enum('kelurahan','kecamatan','desa') NOT NULL,
  `kecamatan` varchar(100) DEFAULT NULL,
  `kabupaten_kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `distance_km` decimal(5,2) DEFAULT NULL,
  `source` enum('polygon','manual') DEFAULT 'polygon',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_branch` (`branch_id`),
  CONSTRAINT `covered_areas_ibfk_1` FOREIGN KEY (`branch_id`) REFERENCES `branches` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=208 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `covered_areas`
--

LOCK TABLES `covered_areas` WRITE;
/*!40000 ALTER TABLE `covered_areas` DISABLE KEYS */;
INSERT INTO `covered_areas` VALUES (34,1,'Perwira','kelurahan','Bekasi Utara','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(35,1,'Harapanbaru','kelurahan','Bekasi Utara','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(36,1,'Kebalen','kelurahan','Babelan','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(37,1,'Pejuang','kelurahan','Medansatria','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(38,1,'Margamulya','kelurahan','Bekasi Utara','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(39,1,'Setia Asih','kelurahan','Tarumajaya','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(40,1,'Babelankota','desa','Babelan','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(41,1,'Bintara','kelurahan','Bekasi Barat','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(42,1,'Ujung Menteng','kelurahan','Cakung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(43,1,'Kalibaru','kelurahan','Medansatria','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(44,1,'Setia Mulya','desa','Tarumajaya','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(45,1,'Bahagia','kelurahan','Babelan','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(46,1,'Pulo Gebang','kelurahan','Cakung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(47,1,'Cakung Timur','kelurahan','Cakung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(48,1,'Kotabaru','kelurahan','Bekasi Barat','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(49,1,'Kranji','kelurahan','Bekasi Barat','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(50,1,'Kedungjaya','desa','Babelan','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(51,1,'Cakung Barat','kelurahan','Cakung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(52,1,'Penggilingan','kelurahan','Cakung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(53,1,'Harapanjaya','kelurahan','Bekasi Utara','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(54,1,'Pahlawan Setia','desa','Tarumajaya','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(55,1,'Rorotan','kelurahan','Cilincing','Kota Adm. Jakarta Utara','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(56,1,'Harapanmulya','kelurahan','Medansatria','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(57,1,'Teluk Pucung','kelurahan','Bekasi Utara','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(58,1,'Medansatria','kelurahan','Medansatria','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(59,1,'Kaliabang Tengah','kelurahan','Bekasi Utara','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(60,1,'Pusaka Rakyat','desa','Tarumajaya','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(61,1,'Cilincing','kecamatan','Cilincing','Kota Adm. Jakarta Utara','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(62,1,'Tarumajaya','kecamatan','Tarumajaya','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(63,1,'Bekasi Utara','kecamatan','Bekasi Utara','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(64,1,'Babelan','kecamatan','Babelan','Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(65,1,'Cakung','kecamatan','Cakung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 03:56:35'),(66,1,'Bekasi Barat','kecamatan','Bekasi Barat','Kota Bekasi','Jawa Barat',NULL,'polygon','2026-09-01 03:56:35'),(67,2,'Bintaro','kelurahan','Pesanggrahan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:37'),(68,2,'Gunung','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(69,2,'Tegal Parang','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(70,2,'Tebet Barat','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(71,2,'Pondok Pinang','kelurahan','Kebayoran Lama','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(72,2,'Gandaria Utara','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(73,2,'Cipete Selatan','kelurahan','Cilandak','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(74,2,'Bendungan Hilir','kelurahan','Tanah Abang','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(75,2,'Petogogan','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(76,2,'Senayan','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(77,2,'Pela Mampang','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(78,2,'Pejaten Barat','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(79,2,'Balekambang','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(80,2,'Batu Ampar','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(81,2,'Cililitan','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(82,2,'Pondok Labu','kelurahan','Cilandak','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(83,2,'Bangka','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(84,2,'Karet Kuningan','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(85,2,'Kuningan Timur','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(86,2,'Kebagusan','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(87,2,'Pengadegan','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(88,2,'Cawang','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(89,2,'Cipulir','kelurahan','Kebayoran Lama','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(90,2,'Grogol Selatan','kelurahan','Kebayoran Lama','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(91,2,'Gandaria Selatan','kelurahan','Cilandak','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(92,2,'Melawai','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(93,2,'Rawa Barat','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(94,2,'Cilandak Timur','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(95,2,'Mampang Prapatan','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(96,2,'Menteng Dalam','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(97,2,'Tebet Timur','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(98,2,'Rawajati','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(99,2,'Cipete Utara','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(100,2,'Selong','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(101,2,'Jati Padang','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(102,2,'Menteng Atas','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(103,2,'Duren Tiga','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(104,2,'Kebayoran Lama Utara','kelurahan','Kebayoran Lama','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(105,2,'Kebayoran Lama Selatan','kelurahan','Kebayoran Lama','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(106,2,'Kramat Pela','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(107,2,'Pulo','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(108,2,'Kuningan Barat','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(109,2,'Pejaten Timur','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(110,2,'Tanjung Barat','kelurahan','Jagakarsa','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(111,2,'Cikoko','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(112,2,'Lebak Bulus','kelurahan','Cilandak','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(113,2,'Cilandak Barat','kelurahan','Cilandak','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(114,2,'Pasar Minggu','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(115,2,'Kalibata','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(116,2,'Grogol Utara','kelurahan','Kebayoran Lama','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(117,2,'Gelora','kelurahan','Tanah Abang','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(118,2,'Karet Semanggi','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(119,2,'Ragunan','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(120,2,'Pancoran','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(121,2,'Cilandak','kecamatan','Cilandak','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(122,2,'Pesanggrahan','kecamatan','Pesanggrahan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(123,2,'Kebayoran Lama','kecamatan','Kebayoran Lama','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(124,2,'Tanah Abang','kecamatan','Tanah Abang','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(125,2,'Jagakarsa','kecamatan','Jagakarsa','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(126,2,'Setiabudi','kecamatan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(127,2,'Kebayoran Baru','kecamatan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(128,2,'Tebet','kecamatan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(129,2,'Kramatjati','kecamatan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:23:38'),(130,3,'Tegal Parang','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(131,3,'Tebet Barat','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(132,3,'Manggarai','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(133,3,'Bidara Cina','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(134,3,'Bali Mester','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(135,3,'Utan Kayu Selatan','kelurahan','Matraman','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(136,3,'Pinangranti','kelurahan','Makasar','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(137,3,'Petogogan','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(138,3,'Senayan','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(139,3,'Pela Mampang','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(140,3,'Pejaten Barat','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(141,3,'Balekambang','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(142,3,'Kampung Melayu','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(143,3,'Batu Ampar','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(144,3,'Cililitan','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(145,3,'Bangka','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(146,3,'Karet Kuningan','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(147,3,'Kuningan Timur','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(148,3,'Pengadegan','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(149,3,'Kayu Manis','kelurahan','Matraman','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(150,3,'Utan Kayu Utara','kelurahan','Matraman','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(151,3,'Cawang','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(152,3,'Kramatjati','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(153,3,'Makasar','kelurahan','Makasar','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(154,3,'Cipinang Muara','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(155,3,'Halim Perdana Kusuma','kelurahan','Makasar','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(156,3,'Cipinang Melayu','kelurahan','Makasar','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(157,3,'Klender','kelurahan','Duren Sawit','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(158,3,'Rawa Barat','kelurahan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(159,3,'Mampang Prapatan','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(160,3,'Karet','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(161,3,'Guntur','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(162,3,'Menteng Dalam','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(163,3,'Pegangsaan','kelurahan','Menteng','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(164,3,'Tebet Timur','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(165,3,'Rawajati','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(166,3,'Tengah','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(167,3,'Pondok Bambu','kelurahan','Duren Sawit','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(168,3,'Jati Padang','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(169,3,'Menteng Atas','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(170,3,'Menteng','kelurahan','Menteng','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(171,3,'Duren Tiga','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(172,3,'Paseban','kelurahan','Senen','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(173,3,'Kebon Manggis','kelurahan','Matraman','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(174,3,'Palmeriam','kelurahan','Matraman','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(175,3,'Kebon Baru','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(176,3,'Rawa Bunga','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(177,3,'Kuningan Barat','kelurahan','Mampang Prapatan','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(178,3,'Pejaten Timur','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(179,3,'Cikoko','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(180,3,'Pisangan Baru','kelurahan','Matraman','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(181,3,'Cipinang Cempedak','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(182,3,'Dukuh','kelurahan','Kramatjati','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(183,3,'Kebon Pala','kelurahan','Makasar','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(184,3,'Rawamangun','kelurahan','Pulogadung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(185,3,'Setia Budi','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(186,3,'Pasar Minggu','kelurahan','Pasar Minggu','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(187,3,'Pasar Manggis','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(188,3,'Kalibata','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(189,3,'Manggarai Selatan','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(190,3,'Kenari','kelurahan','Senen','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(191,3,'Bukit Duri','kelurahan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(192,3,'Rawasari','kelurahan','Cempaka Putih','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(193,3,'Cipinang Besar Selatan','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(194,3,'Pisangan Timur','kelurahan','Pulogadung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(195,3,'Cipinang','kelurahan','Pulogadung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(196,3,'Karet Semanggi','kelurahan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(197,3,'Pancoran','kelurahan','Pancoran','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(198,3,'Cipinang Besar Utara','kelurahan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(199,3,'Duren Sawit','kecamatan','Duren Sawit','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(200,3,'Cempaka Putih','kecamatan','Cempaka Putih','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(201,3,'Matraman','kecamatan','Matraman','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(202,3,'Setiabudi','kecamatan','Setiabudi','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(203,3,'Jatinegara','kecamatan','Jatinegara','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(204,3,'Pulogadung','kecamatan','Pulogadung','Kota Adm. Jakarta Timur','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(205,3,'Kebayoran Baru','kecamatan','Kebayoran Baru','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(206,3,'Senen','kecamatan','Senen','Kota Adm. Jakarta Pusat','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04'),(207,3,'Tebet','kecamatan','Tebet','Kota Adm. Jakarta Selatan','DKI Jakarta',NULL,'polygon','2026-09-01 04:31:04');
/*!40000 ALTER TABLE `covered_areas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','marketing') NOT NULL DEFAULT 'marketing',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Administrator','admin@radius.local','$2y$10$/YmITIF8i1XjqUefoJ8Z6uBbl5C88VHVa62rGRQFC4MM4nqyhFZPC','admin','2026-09-01 03:39:22','2026-09-01 06:40:23'),(2,'Marketing Test','marketing@radius.local','$2y$10$cZvAhx.46WWwDMVmzLC3iOHfkImXgVxoZPEgEeFscK0OLnqCU9Tcy','marketing','2026-09-01 04:43:15','2026-09-01 04:43:15');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-09-01 13:40:26

