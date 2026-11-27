-- Atualização de segurança (27/11/2026)
-- Rode uma única vez pelo phpMyAdmin: abra o banco do site -> aba "SQL" -> cole -> Executar.
--
-- Desativa a conta de teste "cliente@teste.com" (a senha "cliente123" está no código).
-- A senha vira uma aleatória que ninguém conhece. A conta e os pedidos dela NÃO são apagados.

UPDATE `users`
   SET `password` = '$2y$12$uITNebXMr4cig8Psv2I.OuwqNJq47Ai2tec8IxhBEKhiqr5hqxybK',
       `remember_token` = NULL
 WHERE `email` = 'cliente@teste.com';

-- Derruba quem estiver logado com essa conta (no site e no app)
DELETE FROM `sessions` WHERE `user_id` = (SELECT `id` FROM `users` WHERE `email` = 'cliente@teste.com');
DELETE FROM `personal_access_tokens`
 WHERE `tokenable_type` LIKE 'App_Models_User'  -- "_" vale qualquer caractere (evita a barra invertida)
   AND `tokenable_id` = (SELECT `id` FROM `users` WHERE `email` = 'cliente@teste.com');
