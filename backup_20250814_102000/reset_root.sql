FLUSH PRIVILEGES;
-- nastav root s klasickým pluginom a heslom
ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'rootpass';
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED WITH mysql_native_password BY 'rootpass';
CREATE USER IF NOT EXISTS 'root'@'::1' IDENTIFIED WITH mysql_native_password BY 'rootpass';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
GRANT ALL PRIVILEGES ON *.* TO 'root'@'::1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
