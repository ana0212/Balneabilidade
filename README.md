# Projeto de Balneabilidade

Projeto desenvolvido a partir da base de dados de balneabilidade, com foco em tratamento, análise e modelagem dos dados.

## Estrutura

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
├── notebooks/
├── docs/
│   └── relatorio.pdf
├── balneabilidade.db
├── requirements.txt
└── README.md

## Etapas

- Análise exploratória e tratamento dos dados
- Normalização da base em `municipios`, `trechos` e `analises`
- Criação e carga do banco SQLite
- Implementação da regra de classificação dos trechos
- Desenvolvimento do dashboard em Streamlit
- Análise dos resultados e identificação dos principais padrões observados
- Documentação das decisões de tratamento, normalização e modelagem

## Tecnologias

Python, Pandas, SQLite, SQL, DBeaver, Streamlit e Git.