PRAGMA foreign_keys = ON;

CREATE TABLE municipios (
    municipio_id INTEGER PRIMARY KEY,
    municipio_nome TEXT NOT NULL
);

CREATE TABLE trechos (
    trecho_id INTEGER PRIMARY KEY,
    municipio_id INTEGER NOT NULL,
    trecho_nome TEXT NOT NULL,
    trecho_descricao TEXT,
    estacao TEXT,
    latitude REAL,
    longitude REAL,
    periodicidade INTEGER,
    fonte INTEGER,
    excluido INTEGER,
    FOREIGN KEY (municipio_id)
    REFERENCES municipios (municipio_id)
);

CREATE TABLE analises (
    analise_id INTEGER PRIMARY KEY,
    trecho_id INTEGER NOT NULL,
    analise_data TEXT NOT NULL,
    quantitativo INTEGER,
    FOREIGN KEY (trecho_id)
    REFERENCES trechos (trecho_id)
);