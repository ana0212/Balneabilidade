# Projeto de Balneabilidade

Projeto desenvolvido a partir da base de dados de balneabilidade, com foco em tratamento, análise e modelagem dos dados.

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
- Normalização da base em `municipios`, `trechos` e `analises`
- Criação e carga do banco SQLite
- Implementação da regra de classificação dos trechos
- Desenvolvimento do dashboard em Streamlit
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
Python, Pandas, SQLite, SQL, DBeaver, Streamlit e Git.


