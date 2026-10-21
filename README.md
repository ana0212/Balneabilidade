Projeto de Balneabilidade
Projeto de tratamento, modelagem, análise e visualização de dados de balneabilidade. Reúne scripts de preparação dos dados, banco SQLite, consultas SQL, dashboard em Streamlit, análises no Metabase e uma aplicação web em PHP.
Objetivos
- Preparar e tratar a base de dados de balneabilidade.
- Identificar inconsistências e registros duplicados.
- Organizar os dados em um modelo relacional.
- Aplicar regras de classificação dos trechos.
- Disponibilizar consultas, indicadores, gráficos e tabelas para explorar os resultados.
- Documentar as decisões de tratamento e modelagem.
Estrutura do projeto

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
│   ├── relatorio_semanal.php
│   ├── relatorio_pdf.php
│   ├── relatorio_model.php
│   ├── relatorio_queries.php
│   └── css/
│       ├──style.css
│       ├──relatorio_ style.css
│   └── js/
│       └── relatorio_semanal.js
├── notebooks/
├── docs/
│   └── relatorio.pdf
├── docker/
│   └── docker-compose.yml
├── balneabilidade.db
├── requirements.txt
└── README.md
```
A estrutura acima representa a organização prevista do projeto. Ajuste os nomes ou caminhos caso os arquivos no repositório sejam diferentes.

Etapas do projeto
1. Análise exploratória e tratamento dos dados.
2. Identificação e tratamento de inconsistências e registros duplicados.
3. Normalização da base nas tabelas municipios, trechos e analises.
4. Criação e carga do banco de dados SQLite.
5. Implementação das regras de classificação dos trechos.
6. Desenvolvimento do dashboard em Streamlit.
7. Criação de consultas e análises no Metabase.
8. Desenvolvimento da aplicação web em PHP integrada ao SQLite e ao Metabase.
9. Implementação de relatório semanal com indicadores, gráficos e tabelas.
10. Exportação do relatório para PDF.
11. Documentação das decisões de tratamento, normalização e modelagem.

Tecnologias
- Python e Pandas — tratamento e preparação dos dados.
- SQL e SQLite — modelagem, armazenamento e consultas.
- Streamlit — dashboard interativo.
- Metabase — exploração e visualização analítica.
- PHP e PDO — aplicação web e acesso ao banco SQLite.
- HTML, CSS e JavaScript — estrutura, apresentação e gráficos da interface.
- Composer — gerenciamento das dependências PHP.
- Dompdf — geração de relatórios em PDF.
- Docker Compose — execução local do serviço Metabase.
- Git — controle de versão.

Requisitos
Instale ou configure as ferramentas necessárias para a parte do projeto que deseja executar:
- Python e pip, para scripts e aplicação Streamlit.
- PHP, para a aplicação web.
- Composer, para instalar as dependências PHP.
- Docker com Docker Compose, para executar o Metabase localmente.
- O arquivo de banco balneabilidade.db, com os dados preparados.

Preparação dos dados
Instale as dependências Python:
pip install -r requirements.txt
Execute os scripts de preparação conforme a ordem definida no próprio projeto. Os nomes disponíveis são:
- scripts/tratamento.py
- scripts/normalizacao.py
A ordem exata e os argumentos necessários dependem da implementação dos scripts.

Aplicação PHP
A aplicação PHP utiliza SQLite por meio de PDO e oferece páginas de consulta e visualização dos dados. Também inclui um relatório semanal com gráficos e tabelas e a possibilidade de exportar o relatório em PDF.
Instalar dependências PHP

Na raiz do projeto, execute:
composer install

Esse comando instala as dependências declaradas no composer.json. Caso esse arquivo não esteja presente no repositório, confira a configuração de dependências antes de executar o comando.
Configurar variáveis de ambiente
A integração com o Metabase utiliza variáveis de ambiente, incluindo METABASE_SECRET_KEY. Configure o arquivo .env no local esperado pela aplicação e mantenha-o fora do controle de versão.

Executar o servidor PHP
Na raiz do projeto, execute:
php -S localhost:8000 -t php

Depois, acesse:
- Aplicação: http://localhost:8000
- Dashboard PHP: http://localhost:8000/dashboard.php
- Relatório semanal: http://localhost:8000/relatorio_semanal.php
As rotas acima pressupõem que os arquivos estejam diretamente na pasta php/.

Banco de dados
A aplicação utiliza o arquivo SQLite balneabilidade.db. Verifique se o caminho configurado em php/database.php aponta para o arquivo correto e se o banco contém as tabelas e os dados esperados.

Metabase com Docker
O projeto inclui uma configuração Docker Compose para execução local do Metabase.

A partir da pasta docker/, execute:
docker compose up -d
Depois, verifique a configuração de portas e o estado do contêiner. A configuração da aplicação PHP apresentada no projeto espera o Metabase em:
http://localhost:3000
A integração de dashboards incorporados depende da configuração de embedding do Metabase e da chave secreta correspondente. O Metabase também precisa estar configurado para acessar a fonte de dados SQLite utilizada pelo projeto.