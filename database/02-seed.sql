INSERT INTO companies (id, rut, legal_name, trade_name)
VALUES ('11111111-1111-4111-8111-111111111111', '76.000.000-0', 'BGV Enterprise SpA', 'BGV Enterprise')
ON DUPLICATE KEY UPDATE legal_name = VALUES(legal_name), trade_name = VALUES(trade_name);
