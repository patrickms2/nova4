-- -------------------------------------------------------------
-- TablePlus 26.7.9(745)
--
-- https://tableplus.com/
--
-- Database: mcp_studio2026
-- Generation Time: 2026-07-25 03:40:34.2190
-- -------------------------------------------------------------


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


DROP TABLE IF EXISTS `nova_bundle_orders`;
CREATE TABLE `nova_bundle_orders` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `bundle_reference` varchar(120) NOT NULL,
  `status` varchar(30) NOT NULL DEFAULT 'pending',
  `customer_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`customer_data`)),
  `la_geria_order_id` bigint(20) unsigned DEFAULT NULL,
  `la_geria_order_number` varchar(50) DEFAULT NULL,
  `la_geria_status` varchar(30) DEFAULT NULL,
  `la_geria_total` decimal(12,2) DEFAULT NULL,
  `lanzaloe_order_id` varchar(120) DEFAULT NULL,
  `lanzaloe_cart_id` varchar(120) DEFAULT NULL,
  `lanzaloe_status` varchar(30) DEFAULT NULL,
  `lanzaloe_error` text DEFAULT NULL,
  `factura_id` bigint(20) unsigned DEFAULT NULL,
  `redsys_order` varchar(50) DEFAULT NULL,
  `payment_status` varchar(30) NOT NULL DEFAULT 'pending',
  `payment_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payment_data`)),
  `paid_at` timestamp NULL DEFAULT NULL,
  `raw_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`raw_result`)),
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nova_bundle_orders_bundle_reference_unique` (`bundle_reference`),
  KEY `nova_bundle_orders_factura_id_foreign` (`factura_id`),
  CONSTRAINT `nova_bundle_orders_factura_id_foreign` FOREIGN KEY (`factura_id`) REFERENCES `facturas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `nova_bundle_products`;
CREATE TABLE `nova_bundle_products` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `reference` varchar(120) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT 1,
  `la_geria_product_id` varchar(120) NOT NULL,
  `la_geria_product_name` varchar(255) DEFAULT NULL,
  `la_geria_quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `la_geria_unit_price` decimal(10,2) DEFAULT NULL,
  `lanzaloe_sku` varchar(120) NOT NULL,
  `lanzaloe_product_name` varchar(255) DEFAULT NULL,
  `lanzaloe_quantity` int(10) unsigned NOT NULL DEFAULT 1,
  `lanzaloe_unit_price` decimal(10,2) DEFAULT NULL,
  `total_price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `currency` varchar(3) NOT NULL DEFAULT 'EUR',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nova_bundle_products_reference_unique` (`reference`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;



/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;