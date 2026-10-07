# Projeto de Balneabilidade

Projeto desenvolvido a partir da base de dados de balneabilidade, com foco em tratamento, análise e modelagem dos dados.

## Estrutura
```text
# Projeto de Balneabilidade

Projeto desenvolvido a partir da base de dados de balneabilidade, com foco em tratamento, análise, modelagem dos dados e desenvolvimento de aplicações para consulta e visualização dos resultados.

## Estrutura

```text
Balneabilidade/
├── data/
│   ├── bruto/
│   └── processed/
├── scripts/
│   ├── normalizacao.py
│   └── tratamento.py
├── sql/
│   ├── classificacao.sql
│   └── schema.sql
├── app/
│   ├── app.py
│   ├── database.py
│   └── pages/
├── php/
│   ├── index.php
│   ├── dashboard.php
│   ├── database.php
│   ├── queries.php
│   ├── metabase.php
│   └── style.css
├── notebooks/
├── docs/
│   └── relatorio.pdf
├── docker/
│   └── docker-compose.yml
├── balneabilidade.db
├── requirements.txt
└── README.md
```

## Etapas
- Análise exploratória e tratamento dos dados
- Identificação e tratamento de inconsistências e registros duplicados
- Normalização da base nas tabelas municipios, trechos e analises
- Criação e carga do banco SQLite
- Implementação da regra de classificação dos trechos
- Desenvolvimento do dashboard em Streamlit
- Desenvolvimento de consultas e análises no Metabase
- Dseenvolvimento de aplicação web em PHP integrada ao SQLite e ao Metabase
- Análise dos resultados e identificação dos principais padrões observados
- Documentação das decisões de tratamento, normalização e modelagem

## Como executar
Instale as dependências:

```bash
pip install -r requirements.txt
```

## Metabase
O projeto também possui configuração para execução local do Metabase
via Docker, utilizando o banco SQLite `balneabilidade.db` como fonte
de dados.

### Executar
A partir da pasta `docker/`:

```bash
docker compose up -d
```

## Tecnologias
Python, Pandas, SQLite, SQL, DBeaver, Streamlit, PHP e Git.

## Aplicação PHP
Aplicação web para consulta dos dados de
balneabilidade utilizando PHP e SQLite.

### Executar
php -S localhost:8000 -t php

Acessar:
http://localhost:8000

## Próximas etapas
- Publicação da aplicação

