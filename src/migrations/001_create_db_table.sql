CREATE TABLE IF NOT EXISTS db_table (
    id   INT PRIMARY KEY AUTO_INCREMENT,
    text VARCHAR(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO db_table (text) VALUES ('azerty'), ('abcdef'), ('xyz'), ('123456789');