CREATE TABLE IF NOT EXISTS apache_vhosts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    server_name VARCHAR(255) NOT NULL,
    server_alias VARCHAR(500) NULL,
    backend_scheme ENUM('http', 'https') NOT NULL DEFAULT 'http',
    backend_host VARCHAR(255) NOT NULL,
    backend_port INT NOT NULL,
    backend_path VARCHAR(255) NOT NULL DEFAULT '/',
    ssl_insecure TINYINT(1) NOT NULL DEFAULT 0,
    enabled TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_apache_vhosts_server_name (server_name)
);

INSERT INTO apache_vhosts (
    server_name,
    server_alias,
    backend_scheme,
    backend_host,
    backend_port,
    backend_path,
    ssl_insecure,
    enabled
) VALUES (
    'demo.results.com.br',
    '',
    'http',
    '10.10.2.30',
    8087,
    '/',
    0,
    0
);