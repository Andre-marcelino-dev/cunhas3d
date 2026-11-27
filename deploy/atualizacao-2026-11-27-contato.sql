-- Atualização do banco: formulário de contato sobre produtos (27/11/2026)
-- Use SÓ se o site já está no ar na Locaweb. Rode uma única vez pelo phpMyAdmin:
-- abra o banco do site -> aba "SQL" -> cole este conteúdo -> Executar.
-- Não apaga nada: só acrescenta duas colunas na tabela de mensagens.

ALTER TABLE `contact_messages`
  ADD COLUMN `product_id` bigint unsigned NULL DEFAULT NULL AFTER `user_id`,
  ADD COLUMN `phone` varchar(20) NULL DEFAULT NULL AFTER `email`,
  ADD CONSTRAINT `contact_messages_product_id_foreign`
      FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

-- Registra a migration como já aplicada (assim o Laravel não tenta criar as colunas de novo)
INSERT INTO `migrations` (`migration`, `batch`)
SELECT '2026_11_27_120000_add_product_to_contact_messages', COALESCE(MAX(`batch`), 0) + 1 FROM `migrations`;
