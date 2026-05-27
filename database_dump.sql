-- MySQL dump 10.13  Distrib 8.0.45, for Linux (x86_64)
--
-- Host: localhost    Database: e-commerce-platform
-- ------------------------------------------------------
-- Server version	8.0.45-0ubuntu0.24.04.1

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
-- Table structure for table `addresses`
--

DROP TABLE IF EXISTS `addresses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `addresses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `line1` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `line2` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `city` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `state` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `zip` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `addresses_user_id_foreign` (`user_id`),
  CONSTRAINT `addresses_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `addresses`
--

LOCK TABLES `addresses` WRITE;
/*!40000 ALTER TABLE `addresses` DISABLE KEYS */;
/*!40000 ALTER TABLE `addresses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache`
--

DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache`
--

LOCK TABLES `cache` WRITE;
/*!40000 ALTER TABLE `cache` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cache_locks`
--

DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cache_locks`
--

LOCK TABLES `cache_locks` WRITE;
/*!40000 ALTER TABLE `cache_locks` DISABLE KEYS */;
/*!40000 ALTER TABLE `cache_locks` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cart_items`
--

DROP TABLE IF EXISTS `cart_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cart_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cart_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `cart_items_cart_id_foreign` (`cart_id`),
  KEY `cart_items_product_id_foreign` (`product_id`),
  CONSTRAINT `cart_items_cart_id_foreign` FOREIGN KEY (`cart_id`) REFERENCES `carts` (`id`),
  CONSTRAINT `cart_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cart_items`
--

LOCK TABLES `cart_items` WRITE;
/*!40000 ALTER TABLE `cart_items` DISABLE KEYS */;
/*!40000 ALTER TABLE `cart_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `carts`
--

DROP TABLE IF EXISTS `carts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `carts` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `carts_user_id_foreign` (`user_id`),
  CONSTRAINT `carts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `carts`
--

LOCK TABLES `carts` WRITE;
/*!40000 ALTER TABLE `carts` DISABLE KEYS */;
INSERT INTO `carts` VALUES (1,4,'2026-05-07 11:00:30','2026-05-07 11:00:30'),(2,5,'2026-05-08 12:43:54','2026-05-08 12:43:54'),(3,6,'2026-05-08 19:20:24','2026-05-08 19:20:24'),(4,1,'2026-05-09 12:42:19','2026-05-09 12:42:19'),(5,7,'2026-05-14 20:38:02','2026-05-14 20:38:02');
/*!40000 ALTER TABLE `carts` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `categories`
--

DROP TABLE IF EXISTS `categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categories` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `categories`
--

LOCK TABLES `categories` WRITE;
/*!40000 ALTER TABLE `categories` DISABLE KEYS */;
INSERT INTO `categories` VALUES (1,'Electronics','2026-05-05 12:49:49','2026-05-05 12:49:49'),(2,'Clothing','2026-05-05 12:49:49','2026-05-05 12:49:49'),(3,'Home & Kitchen','2026-05-05 12:49:49','2026-05-05 12:49:49'),(4,'Books','2026-05-05 12:49:49','2026-05-05 12:49:49');
/*!40000 ALTER TABLE `categories` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `failed_jobs`
--

DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `failed_jobs`
--

LOCK TABLES `failed_jobs` WRITE;
/*!40000 ALTER TABLE `failed_jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `failed_jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `job_batches`
--

DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `job_batches`
--

LOCK TABLES `job_batches` WRITE;
/*!40000 ALTER TABLE `job_batches` DISABLE KEYS */;
/*!40000 ALTER TABLE `job_batches` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `jobs`
--

DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` tinyint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `jobs`
--

LOCK TABLES `jobs` WRITE;
/*!40000 ALTER TABLE `jobs` DISABLE KEYS */;
/*!40000 ALTER TABLE `jobs` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `migrations`
--

DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `migrations`
--

LOCK TABLES `migrations` WRITE;
/*!40000 ALTER TABLE `migrations` DISABLE KEYS */;
INSERT INTO `migrations` VALUES (1,'0001_01_01_000000_create_users_table',1),(2,'0001_01_01_000001_create_cache_table',1),(3,'0001_01_01_000002_create_jobs_table',1),(4,'2026_04_18_102205_create_users_table',1),(5,'2026_04_18_102301_create_categories_table',1),(6,'2026_04_18_102334_create_products_table',1),(7,'2026_04_18_102405_create_cart_table',1),(8,'2026_04_18_102433_create_cart_items_table',1),(9,'2026_04_18_102500_create_orders_table',1),(10,'2026_04_18_102528_create_order_items_table',1),(11,'2026_04_18_102558_create_product_images_table',1),(12,'2026_04_18_102624_create_product_variants_table',1),(13,'2026_04_18_102653_create_reviews_table',1),(14,'2026_04_18_102720_create_payments_table',1),(15,'2026_04_18_102755_create_addresses_table',1),(16,'2026_04_18_102828_create_vendors_table',1),(17,'2026_04_18_102853_create_vendor_products_table',1),(18,'2026_05_04_214206_create_personal_access_tokens_table',1);
/*!40000 ALTER TABLE `migrations` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `order_items`
--

DROP TABLE IF EXISTS `order_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `order_items` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `quantity` int NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `order_items_order_id_foreign` (`order_id`),
  KEY `order_items_product_id_foreign` (`product_id`),
  CONSTRAINT `order_items_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`),
  CONSTRAINT `order_items_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `order_items`
--

LOCK TABLES `order_items` WRITE;
/*!40000 ALTER TABLE `order_items` DISABLE KEYS */;
INSERT INTO `order_items` VALUES (1,1,1,1,1200.00,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(2,2,1,1,2374.00,'2026-05-13 10:11:39','2026-05-13 10:11:39'),(3,2,2,1,2841.00,'2026-05-13 10:11:39','2026-05-13 10:11:39'),(4,2,3,1,2329.00,'2026-05-13 10:11:39','2026-05-13 10:11:39'),(5,2,4,1,2294.00,'2026-05-13 10:11:39','2026-05-13 10:11:39'),(6,2,5,2,3218.00,'2026-05-13 10:11:39','2026-05-13 10:11:39'),(7,3,1,1,2374.00,'2026-05-14 20:40:05','2026-05-14 20:40:05'),(8,3,2,1,2841.00,'2026-05-14 20:40:05','2026-05-14 20:40:05'),(9,3,3,1,2329.00,'2026-05-14 20:40:05','2026-05-14 20:40:05'),(10,3,4,1,2294.00,'2026-05-14 20:40:05','2026-05-14 20:40:05'),(11,3,5,1,3218.00,'2026-05-14 20:40:05','2026-05-14 20:40:05'),(12,4,4,1,2294.00,'2026-05-14 20:52:26','2026-05-14 20:52:26'),(13,4,1,1,2374.00,'2026-05-14 20:52:26','2026-05-14 20:52:26'),(14,4,2,1,2841.00,'2026-05-14 20:52:26','2026-05-14 20:52:26'),(15,4,3,1,2329.00,'2026-05-14 20:52:26','2026-05-14 20:52:26'),(16,4,5,2,3218.00,'2026-05-14 20:52:26','2026-05-14 20:52:26'),(17,5,38,1,1298.00,'2026-05-15 08:44:11','2026-05-15 08:44:11');
/*!40000 ALTER TABLE `order_items` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `orders`
--

DROP TABLE IF EXISTS `orders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `orders` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `total_price` decimal(10,2) NOT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `orders_user_id_foreign` (`user_id`),
  CONSTRAINT `orders_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `orders`
--

LOCK TABLES `orders` WRITE;
/*!40000 ALTER TABLE `orders` DISABLE KEYS */;
INSERT INTO `orders` VALUES (1,2,1200.00,'completed','2026-05-05 12:49:49','2026-05-05 12:49:49'),(2,6,16274.00,'pending','2026-05-13 10:11:39','2026-05-13 10:11:39'),(3,7,13056.00,'cancelled','2026-05-14 20:40:05','2026-05-14 20:43:39'),(4,7,16274.00,'paid','2026-05-14 20:52:26','2026-05-15 06:24:38'),(5,7,1298.00,'pending','2026-05-15 08:44:11','2026-05-15 08:44:11');
/*!40000 ALTER TABLE `orders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `password_reset_tokens`
--

DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `password_reset_tokens`
--

LOCK TABLES `password_reset_tokens` WRITE;
/*!40000 ALTER TABLE `password_reset_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `password_reset_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payments`
--

DROP TABLE IF EXISTS `payments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `order_id` bigint unsigned NOT NULL,
  `method` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `amount` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_order_id_foreign` (`order_id`),
  CONSTRAINT `payments_order_id_foreign` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payments`
--

LOCK TABLES `payments` WRITE;
/*!40000 ALTER TABLE `payments` DISABLE KEYS */;
INSERT INTO `payments` VALUES (1,4,NULL,'paid','4DD55252H7628122H',16274.00,'2026-05-15 06:08:53','2026-05-15 06:24:38'),(2,5,NULL,'pending','9MR80334CC512814F',1298.00,'2026-05-15 08:45:03','2026-05-15 08:45:03'),(3,5,NULL,'pending','2BY18004FC832574H',1298.00,'2026-05-15 08:59:14','2026-05-15 08:59:14');
/*!40000 ALTER TABLE `payments` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `personal_access_tokens`
--

DROP TABLE IF EXISTS `personal_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `personal_access_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint unsigned NOT NULL,
  `name` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text COLLATE utf8mb4_unicode_ci,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
  KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`),
  KEY `personal_access_tokens_expires_at_index` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `personal_access_tokens`
--

LOCK TABLES `personal_access_tokens` WRITE;
/*!40000 ALTER TABLE `personal_access_tokens` DISABLE KEYS */;
/*!40000 ALTER TABLE `personal_access_tokens` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_images`
--

DROP TABLE IF EXISTS `product_images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_images` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `position` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_images_product_id_foreign` (`product_id`),
  CONSTRAINT `product_images_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_images`
--

LOCK TABLES `product_images` WRITE;
/*!40000 ALTER TABLE `product_images` DISABLE KEYS */;
INSERT INTO `product_images` VALUES (1,1,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(2,2,'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(3,3,'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(4,4,'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(5,5,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(6,6,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(7,7,'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(8,8,'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(9,9,'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(10,10,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(11,11,'https://images.unsplash.com/photo-1496181133206-80ce9b88a853?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(12,12,'https://images.unsplash.com/photo-1523275335684-37898b6baf30?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(13,13,'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(14,14,'https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(15,15,'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(16,16,'https://images.unsplash.com/photo-1594932224010-77f3ad36bc3d?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(17,17,'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(18,18,'https://images.unsplash.com/photo-1539008835158-a3f2d226a26a?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(19,19,'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(20,20,'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(21,21,'https://images.unsplash.com/photo-1594932224010-77f3ad36bc3d?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(22,22,'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(23,23,'https://images.unsplash.com/photo-1539008835158-a3f2d226a26a?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(24,24,'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(25,25,'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(26,26,'https://images.unsplash.com/photo-1594932224010-77f3ad36bc3d?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(27,27,'https://images.unsplash.com/photo-1549298916-b41d501d3772?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(28,28,'https://images.unsplash.com/photo-1539008835158-a3f2d226a26a?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(29,29,'https://images.unsplash.com/photo-1584917865442-de89df76afd3?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(30,30,'https://images.unsplash.com/photo-1521572267360-ee0c2909d518?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(31,31,'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(32,32,'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(33,33,'https://images.unsplash.com/photo-1592078615290-033ee584e267?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(34,34,'https://images.unsplash.com/photo-1517668808822-9ebb02f2a0e6?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(35,35,'https://images.unsplash.com/photo-1578500494198-246f612d3b3d?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(36,36,'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(37,37,'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(38,38,'https://images.unsplash.com/photo-1592078615290-033ee584e267?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(39,39,'https://images.unsplash.com/photo-1517668808822-9ebb02f2a0e6?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(40,40,'https://images.unsplash.com/photo-1578500494198-246f612d3b3d?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(41,41,'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(42,42,'https://images.unsplash.com/photo-1507473885765-e6ed057f782c?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(43,43,'https://images.unsplash.com/photo-1592078615290-033ee584e267?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(44,44,'https://images.unsplash.com/photo-1517668808822-9ebb02f2a0e6?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(45,45,'https://images.unsplash.com/photo-1578500494198-246f612d3b3d?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(46,46,'https://images.unsplash.com/photo-1589998059171-988d887df646?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(47,47,'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(48,48,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(49,49,'https://images.unsplash.com/photo-1589998059171-988d887df646?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(50,50,'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(51,51,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(52,52,'https://images.unsplash.com/photo-1589998059171-988d887df646?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(53,53,'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(54,54,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(55,55,'https://images.unsplash.com/photo-1589998059171-988d887df646?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(56,56,'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(57,57,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(58,58,'https://images.unsplash.com/photo-1589998059171-988d887df646?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(59,59,'https://images.unsplash.com/photo-1507842217343-583bb7270b66?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(60,60,'https://images.unsplash.com/photo-1544947950-fa07a98d237f?auto=format&fit=crop&w=800&q=80',0,'2026-05-05 12:49:49','2026-05-05 12:49:49');
/*!40000 ALTER TABLE `product_images` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `product_variants`
--

DROP TABLE IF EXISTS `product_variants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `product_variants` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `product_id` bigint unsigned NOT NULL,
  `sku` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  `price` decimal(10,2) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `product_variants_product_id_foreign` (`product_id`),
  CONSTRAINT `product_variants_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `product_variants`
--

LOCK TABLES `product_variants` WRITE;
/*!40000 ALTER TABLE `product_variants` DISABLE KEYS */;
/*!40000 ALTER TABLE `product_variants` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `stock` int NOT NULL DEFAULT '0',
  `image` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `category_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `products_category_id_foreign` (`category_id`),
  CONSTRAINT `products_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'Aether Pro Laptop 1',2374.00,'Experience the pinnacle of LUWI craftsmanship. The Aether Pro Laptop is a masterclass in modern design.',44,NULL,1,'2026-05-05 12:49:49','2026-05-14 20:52:26'),(2,'Chronos Gold Watch 2',2841.00,'Experience the pinnacle of LUWI craftsmanship. The Chronos Gold Watch is a masterclass in modern design.',37,NULL,1,'2026-05-05 12:49:49','2026-05-14 20:52:26'),(3,'Zenith Studio Cam 3',2329.00,'Experience the pinnacle of LUWI craftsmanship. The Zenith Studio Cam is a masterclass in modern design.',17,NULL,1,'2026-05-05 12:49:49','2026-05-14 20:52:26'),(4,'Nova Mobile 12 4',2294.00,'Experience the pinnacle of LUWI craftsmanship. The Nova Mobile 12 is a masterclass in modern design.',30,NULL,1,'2026-05-05 12:49:49','2026-05-14 20:52:26'),(5,'Vector Pods Max 5',3218.00,'Experience the pinnacle of LUWI craftsmanship. The Vector Pods Max is a masterclass in modern design.',31,NULL,1,'2026-05-05 12:49:49','2026-05-14 20:52:26'),(6,'Aether Pro Laptop 6',2020.00,'Experience the pinnacle of LUWI craftsmanship. The Aether Pro Laptop is a masterclass in modern design.',50,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(7,'Chronos Gold Watch 7',605.00,'Experience the pinnacle of LUWI craftsmanship. The Chronos Gold Watch is a masterclass in modern design.',19,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(8,'Zenith Studio Cam 8',1054.00,'Experience the pinnacle of LUWI craftsmanship. The Zenith Studio Cam is a masterclass in modern design.',18,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(9,'Nova Mobile 12 9',2473.00,'Experience the pinnacle of LUWI craftsmanship. The Nova Mobile 12 is a masterclass in modern design.',43,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(10,'Vector Pods Max 10',1436.00,'Experience the pinnacle of LUWI craftsmanship. The Vector Pods Max is a masterclass in modern design.',40,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(11,'Aether Pro Laptop 11',3429.00,'Experience the pinnacle of LUWI craftsmanship. The Aether Pro Laptop is a masterclass in modern design.',36,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(12,'Chronos Gold Watch 12',1572.00,'Experience the pinnacle of LUWI craftsmanship. The Chronos Gold Watch is a masterclass in modern design.',22,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(13,'Zenith Studio Cam 13',3104.00,'Experience the pinnacle of LUWI craftsmanship. The Zenith Studio Cam is a masterclass in modern design.',28,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(14,'Nova Mobile 12 14',1405.00,'Experience the pinnacle of LUWI craftsmanship. The Nova Mobile 12 is a masterclass in modern design.',40,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(15,'Vector Pods Max 15',1224.00,'Experience the pinnacle of LUWI craftsmanship. The Vector Pods Max is a masterclass in modern design.',41,NULL,1,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(16,'Imperial Silk Suit 1',588.00,'Experience the pinnacle of LUWI craftsmanship. The Imperial Silk Suit is a masterclass in modern design.',17,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(17,'Vanguard Leather Boots 2',1085.00,'Experience the pinnacle of LUWI craftsmanship. The Vanguard Leather Boots is a masterclass in modern design.',10,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(18,'Elysian Evening Gown 3',1517.00,'Experience the pinnacle of LUWI craftsmanship. The Elysian Evening Gown is a masterclass in modern design.',26,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(19,'Nomad Leather Carryall 4',700.00,'Experience the pinnacle of LUWI craftsmanship. The Nomad Leather Carryall is a masterclass in modern design.',18,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(20,'Aura Linen Set 5',2629.00,'Experience the pinnacle of LUWI craftsmanship. The Aura Linen Set is a masterclass in modern design.',38,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(21,'Imperial Silk Suit 6',2260.00,'Experience the pinnacle of LUWI craftsmanship. The Imperial Silk Suit is a masterclass in modern design.',29,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(22,'Vanguard Leather Boots 7',3301.00,'Experience the pinnacle of LUWI craftsmanship. The Vanguard Leather Boots is a masterclass in modern design.',28,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(23,'Elysian Evening Gown 8',2810.00,'Experience the pinnacle of LUWI craftsmanship. The Elysian Evening Gown is a masterclass in modern design.',25,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(24,'Nomad Leather Carryall 9',2130.00,'Experience the pinnacle of LUWI craftsmanship. The Nomad Leather Carryall is a masterclass in modern design.',43,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(25,'Aura Linen Set 10',822.00,'Experience the pinnacle of LUWI craftsmanship. The Aura Linen Set is a masterclass in modern design.',19,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(26,'Imperial Silk Suit 11',874.00,'Experience the pinnacle of LUWI craftsmanship. The Imperial Silk Suit is a masterclass in modern design.',38,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(27,'Vanguard Leather Boots 12',2358.00,'Experience the pinnacle of LUWI craftsmanship. The Vanguard Leather Boots is a masterclass in modern design.',50,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(28,'Elysian Evening Gown 13',2273.00,'Experience the pinnacle of LUWI craftsmanship. The Elysian Evening Gown is a masterclass in modern design.',16,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(29,'Nomad Leather Carryall 14',2881.00,'Experience the pinnacle of LUWI craftsmanship. The Nomad Leather Carryall is a masterclass in modern design.',28,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(30,'Aura Linen Set 15',379.00,'Experience the pinnacle of LUWI craftsmanship. The Aura Linen Set is a masterclass in modern design.',11,NULL,2,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(31,'Nordic Pine Sofa 1',1399.00,'Experience the pinnacle of LUWI craftsmanship. The Nordic Pine Sofa is a masterclass in modern design.',20,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(32,'Eclipse Sphere Lamp 2',546.00,'Experience the pinnacle of LUWI craftsmanship. The Eclipse Sphere Lamp is a masterclass in modern design.',18,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(33,'Studio Oak Chair 3',2830.00,'Experience the pinnacle of LUWI craftsmanship. The Studio Oak Chair is a masterclass in modern design.',33,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(34,'Minimalist Coffee Maker 4',953.00,'Experience the pinnacle of LUWI craftsmanship. The Minimalist Coffee Maker is a masterclass in modern design.',32,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(35,'Ceramic Bloom Vase 5',2138.00,'Experience the pinnacle of LUWI craftsmanship. The Ceramic Bloom Vase is a masterclass in modern design.',25,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(36,'Nordic Pine Sofa 6',1689.00,'Experience the pinnacle of LUWI craftsmanship. The Nordic Pine Sofa is a masterclass in modern design.',21,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(37,'Eclipse Sphere Lamp 7',2575.00,'Experience the pinnacle of LUWI craftsmanship. The Eclipse Sphere Lamp is a masterclass in modern design.',17,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(38,'Studio Oak Chair 8',1298.00,'Experience the pinnacle of LUWI craftsmanship. The Studio Oak Chair is a masterclass in modern design.',40,NULL,3,'2026-05-05 12:49:49','2026-05-15 08:44:11'),(39,'Minimalist Coffee Maker 9',381.00,'Experience the pinnacle of LUWI craftsmanship. The Minimalist Coffee Maker is a masterclass in modern design.',45,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(40,'Ceramic Bloom Vase 10',2416.00,'Experience the pinnacle of LUWI craftsmanship. The Ceramic Bloom Vase is a masterclass in modern design.',28,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(41,'Nordic Pine Sofa 11',2363.00,'Experience the pinnacle of LUWI craftsmanship. The Nordic Pine Sofa is a masterclass in modern design.',23,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(42,'Eclipse Sphere Lamp 12',2053.00,'Experience the pinnacle of LUWI craftsmanship. The Eclipse Sphere Lamp is a masterclass in modern design.',33,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(43,'Studio Oak Chair 13',3420.00,'Experience the pinnacle of LUWI craftsmanship. The Studio Oak Chair is a masterclass in modern design.',37,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(44,'Minimalist Coffee Maker 14',2352.00,'Experience the pinnacle of LUWI craftsmanship. The Minimalist Coffee Maker is a masterclass in modern design.',32,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(45,'Ceramic Bloom Vase 15',3176.00,'Experience the pinnacle of LUWI craftsmanship. The Ceramic Bloom Vase is a masterclass in modern design.',34,NULL,3,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(46,'The Art of Minimalism 1',1660.00,'Experience the pinnacle of LUWI craftsmanship. The The Art of Minimalism is a masterclass in modern design.',45,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(47,'Architectural Digest 2',3279.00,'Experience the pinnacle of LUWI craftsmanship. The Architectural Digest is a masterclass in modern design.',22,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(48,'Luxury Living Vol. 1 3',1096.00,'Experience the pinnacle of LUWI craftsmanship. The Luxury Living Vol. 1 is a masterclass in modern design.',33,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(49,'The Art of Minimalism 4',1535.00,'Experience the pinnacle of LUWI craftsmanship. The The Art of Minimalism is a masterclass in modern design.',45,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(50,'Architectural Digest 5',2653.00,'Experience the pinnacle of LUWI craftsmanship. The Architectural Digest is a masterclass in modern design.',21,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(51,'Luxury Living Vol. 1 6',1711.00,'Experience the pinnacle of LUWI craftsmanship. The Luxury Living Vol. 1 is a masterclass in modern design.',39,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(52,'The Art of Minimalism 7',2373.00,'Experience the pinnacle of LUWI craftsmanship. The The Art of Minimalism is a masterclass in modern design.',46,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(53,'Architectural Digest 8',2879.00,'Experience the pinnacle of LUWI craftsmanship. The Architectural Digest is a masterclass in modern design.',37,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(54,'Luxury Living Vol. 1 9',300.00,'Experience the pinnacle of LUWI craftsmanship. The Luxury Living Vol. 1 is a masterclass in modern design.',18,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(55,'The Art of Minimalism 10',1183.00,'Experience the pinnacle of LUWI craftsmanship. The The Art of Minimalism is a masterclass in modern design.',31,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(56,'Architectural Digest 11',2189.00,'Experience the pinnacle of LUWI craftsmanship. The Architectural Digest is a masterclass in modern design.',44,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(57,'Luxury Living Vol. 1 12',1209.00,'Experience the pinnacle of LUWI craftsmanship. The Luxury Living Vol. 1 is a masterclass in modern design.',28,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(58,'The Art of Minimalism 13',2882.00,'Experience the pinnacle of LUWI craftsmanship. The The Art of Minimalism is a masterclass in modern design.',43,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(59,'Architectural Digest 14',2571.00,'Experience the pinnacle of LUWI craftsmanship. The Architectural Digest is a masterclass in modern design.',38,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49'),(60,'Luxury Living Vol. 1 15',336.00,'Experience the pinnacle of LUWI craftsmanship. The Luxury Living Vol. 1 is a masterclass in modern design.',28,NULL,4,'2026-05-05 12:49:49','2026-05-05 12:49:49');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `reviews`
--

DROP TABLE IF EXISTS `reviews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reviews` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `rating` int NOT NULL,
  `comment` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reviews_user_id_foreign` (`user_id`),
  KEY `reviews_product_id_foreign` (`product_id`),
  CONSTRAINT `reviews_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `reviews_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `reviews`
--

LOCK TABLES `reviews` WRITE;
/*!40000 ALTER TABLE `reviews` DISABLE KEYS */;
INSERT INTO `reviews` VALUES (1,2,1,5,'Excellent product','2026-05-05 12:49:49','2026-05-05 12:49:49'),(2,3,1,4,'Very good','2026-05-05 12:49:49','2026-05-05 12:49:49');
/*!40000 ALTER TABLE `reviews` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sessions`
--

DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sessions` (
  `id` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `payload` longtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sessions`
--

LOCK TABLES `sessions` WRITE;
/*!40000 ALTER TABLE `sessions` DISABLE KEYS */;
INSERT INTO `sessions` VALUES ('flI29G88KxsBpl0POuOnMYk0HBPwwgmZlMihbk9r',7,'127.0.0.1','Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/148.0.0.0 Safari/537.36','eyJfdG9rZW4iOiJnOWVjeUllY0hNR25vNzFsWVRMYTlqOWRrOUxINjBHbkFiZENzb2wxIiwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJfcHJldmlvdXMiOnsidXJsIjoiaHR0cDpcL1wvMTI3LjAuMC4xOjgwMDBcL29yZGVycyIsInJvdXRlIjoib3JkZXJzLmluZGV4In0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjo3fQ==',1778839154);
/*!40000 ALTER TABLE `sessions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `user`
--

DROP TABLE IF EXISTS `user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `user`
--

LOCK TABLES `user` WRITE;
/*!40000 ALTER TABLE `user` DISABLE KEYS */;
/*!40000 ALTER TABLE `user` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'user',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=8 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'Admin User','admin@test.com','$2y$12$tHlIkIMx3fbZdVt1uwgqfOiwxElCCxOYIEke3H/56nbjI1BuVcQPC','admin','2026-05-05 12:49:48','2026-05-05 12:49:48'),(2,'John Doe','user1@test.com','$2y$12$J5op.V4wACZUsUGf0EPeVuUOD/Q12JetMEqZDEolz2zzNdfcHYfQy','user','2026-05-05 12:49:48','2026-05-05 12:49:48'),(3,'Jane Doe','user2@test.com','$2y$12$HuHEDKytEWJ2XZiI748TX.mCVMfJ.Wd2fxnfibzIBP1d4HEIxHwcm','user','2026-05-05 12:49:49','2026-05-05 12:49:49'),(4,'mafking','example@gmail.com','$2y$12$BN5T4rXLr56veWr74VQ75uyMUH8yUQUxb7OkTHGJr6OJXbO43EMfS','user','2026-05-07 10:57:25','2026-05-07 10:57:25'),(5,'mafking','test@gmail.com','$2y$12$Ivbljdum4V7vuXGCvRzXMOjyQBX5GyKWbJEmgxwSeNlY9yPygJaBi','user','2026-05-08 12:43:16','2026-05-08 12:43:16'),(6,'LUWI','example2@gmail.com','$2y$12$pBJT8PvIE.vX91WY6KXJIunRyuGhauTmf6FkyIAKTkhDHs7Yq9gKi','admin\r\n','2026-05-08 19:18:51','2026-05-08 19:18:51'),(7,'MAFULETI','mafuletil@gmail.com','$2y$12$w6Y8esFzSR2SSYrAO0CfB.lKZ.Boor4E/ec3QJbxGUjRzayynr9hO','user','2026-05-14 20:36:53','2026-05-14 20:36:53');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendor_products`
--

DROP TABLE IF EXISTS `vendor_products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendor_products` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `vendor_id` bigint unsigned NOT NULL,
  `product_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `vendor_products_vendor_id_foreign` (`vendor_id`),
  KEY `vendor_products_product_id_foreign` (`product_id`),
  CONSTRAINT `vendor_products_product_id_foreign` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`),
  CONSTRAINT `vendor_products_vendor_id_foreign` FOREIGN KEY (`vendor_id`) REFERENCES `vendors` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendor_products`
--

LOCK TABLES `vendor_products` WRITE;
/*!40000 ALTER TABLE `vendor_products` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendor_products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vendors`
--

DROP TABLE IF EXISTS `vendors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `vendors` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `contact_info` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vendors`
--

LOCK TABLES `vendors` WRITE;
/*!40000 ALTER TABLE `vendors` DISABLE KEYS */;
/*!40000 ALTER TABLE `vendors` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-05-18 21:14:58
