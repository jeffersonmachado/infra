-- Import zone data for results.com.br and results.intranet
-- Replaces @ with the domain name

-- results.com.br (domain_id = 1)
INSERT INTO records (domain_id, name, type, content, ttl, prio) VALUES
(1, 'results.com.br', 'SOA', 'ns1.results.com.br. infra.results.com.br. 2026051801 300 120 604800 300', 300, NULL),
(1, 'results.com.br', 'NS', 'ns1.results.com.br.', 300, NULL),
(1, 'results.com.br', 'NS', 'ns2.results.com.br.', 300, NULL),
(1, 'results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, 'results.com.br', 'MX', 'mx1.results.com.br.', 300, 10),
(1, 'results.com.br', 'MX', 'mx2.results.com.br.', 300, 20),
(1, 'ns1.results.com.br', 'A', '177.68.74.176', 300, NULL),
(1, 'ns2.results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, 'www.results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, 'r-observe.results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, 'mx1.results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, 'mx2.results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, 'imap.results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, 'smtp.results.com.br', 'A', '201.6.110.53', 300, NULL),
(1, '_dmarc.results.com.br', 'TXT', '"v=DMARC1; p=none; rua=mailto:dmarc@results.com.br"', 300, NULL);

-- results.intranet (domain_id = 2)
INSERT INTO records (domain_id, name, type, content, ttl, prio) VALUES
(2, 'results.intranet', 'SOA', 'ns1.results.intranet. infra.results.intranet. 2026041801 300 120 604800 300', 300, NULL),
(2, 'results.intranet', 'NS', 'ns1.results.intranet.', 300, NULL),
(2, 'results.intranet', 'NS', 'ns2.results.intranet.', 300, NULL),
(2, 'ns1.results.intranet', 'A', '10.10.2.1', 300, NULL),
(2, 'ns2.results.intranet', 'A', '10.10.2.20', 300, NULL),
(2, 'srvldap0.results.intranet', 'A', '10.10.2.10', 300, NULL),
(2, 'srvldap1.results.intranet', 'A', '10.10.2.11', 300, NULL),
(2, 'srvldap2.results.intranet', 'A', '10.10.2.7', 300, NULL),
(2, 'srvldap3.results.intranet', 'A', '10.10.2.7', 300, NULL),
(2, 'ripabx.results.intranet', 'A', '10.10.2.240', 300, NULL),
(2, 'srvmail0.results.intranet', 'A', '10.10.2.3', 300, NULL),
(2, 'srvhttp.results.intranet', 'A', '10.10.2.60', 300, NULL),
(2, 'srvmysql.results.intranet', 'A', '10.10.2.99', 300, NULL),
(2, 'srvhttp0.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'srvhttp1.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'rbi.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'rdelivery.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'mocambique.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'alemanha.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'gana.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'suecia.results.intranet', 'A', '10.10.2.30', 300, NULL),
(2, 'srvvoz.results.intranet', 'A', '10.10.2.240', 300, NULL),
(2, 'zambia.results.intranet', 'A', '10.10.2.3', 300, NULL);
