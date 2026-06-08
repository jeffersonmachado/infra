-- PowerDNS Authoritative MySQL Schema
CREATE TABLE IF NOT EXISTS domains (
  id INT AUTO_INCREMENT,
  name VARCHAR(255) NOT NULL,
  master VARCHAR(128) DEFAULT NULL,
  last_check INT DEFAULT NULL,
  type VARCHAR(8) NOT NULL DEFAULT 'NATIVE',
  notified_serial BIGINT DEFAULT NULL,
  account VARCHAR(40) DEFAULT NULL,
  options TEXT DEFAULT NULL,
  catalog VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY name_index (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS records (
  id BIGINT AUTO_INCREMENT,
  domain_id INT DEFAULT NULL,
  name VARCHAR(255) DEFAULT NULL,
  type VARCHAR(10) DEFAULT NULL,
  content TEXT DEFAULT NULL,
  ttl INT DEFAULT NULL,
  prio INT DEFAULT NULL,
  disabled TINYINT(1) DEFAULT 0,
  ordername VARCHAR(255) DEFAULT NULL,
  auth TINYINT(1) DEFAULT 1,
  change_date INT DEFAULT NULL,
  PRIMARY KEY (id),
  KEY nametype_index (name, type),
  KEY domain_id (domain_id),
  KEY ordername (ordername)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS supermasters (
  ip VARCHAR(64) NOT NULL,
  nameserver VARCHAR(255) NOT NULL,
  account VARCHAR(40) NOT NULL DEFAULT '',
  PRIMARY KEY (ip, nameserver)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS domainmetadata (
  id INT AUTO_INCREMENT,
  domain_id INT NOT NULL,
  kind VARCHAR(32) DEFAULT NULL,
  content TEXT,
  PRIMARY KEY (id),
  KEY domainmetadata_idx (domain_id, kind)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cryptokeys (
  id INT AUTO_INCREMENT,
  domain_id INT NOT NULL,
  flags INT NOT NULL,
  active BOOL DEFAULT NULL,
  published BOOL DEFAULT 1,
  content TEXT,
  PRIMARY KEY (id),
  KEY domainidindex (domain_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tsigkeys (
  id INT AUTO_INCREMENT,
  name VARCHAR(255) DEFAULT NULL,
  algorithm VARCHAR(50) DEFAULT NULL,
  secret VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY namealgoindex (name, algorithm)
) ENGINE=InnoDB;
