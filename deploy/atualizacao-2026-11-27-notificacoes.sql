-- Atualização do banco: notificações no app (27/11/2026)
-- Rode uma única vez pelo phpMyAdmin: abra o banco do site -> aba "SQL" -> cole -> Executar.
-- Não apaga nada: cria 2 tabelas novas e uma coluna nos produtos.

-- Cada celular/navegador que ativou as notificações
CREATE TABLE `push_subscriptions` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `endpoint_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_key` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth_token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `push_subscriptions_endpoint_hash_unique` (`endpoint_hash`),
  KEY `push_subscriptions_user_id_foreign` (`user_id`),
  CONSTRAINT `push_subscriptions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Histórico das novidades enviadas pelo admin
CREATE TABLE `push_messages` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `body` varchar(240) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kind` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'novidade',
  `sent` int unsigned NOT NULL DEFAULT '0',
  `failed` int unsigned NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Marca o produto que já foi anunciado como "Novidade na loja".
-- Os que já existem ficam marcados, para não avisar todo mundo quando você editar um produto antigo.
ALTER TABLE `products` ADD COLUMN `push_announced_at` timestamp NULL DEFAULT NULL AFTER `active`;
UPDATE `products` SET `push_announced_at` = NOW();

-- Registra a migration como já aplicada
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_27_150000_create_push_tables', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
