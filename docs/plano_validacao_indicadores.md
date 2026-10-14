# Plano de validação dos indicadores - Balneabilidade

## 1. Objetivo

Definir testes reproduzíveis para conferir a integridade dos dados, a
classificação atual, o histórico de mudanças, os totais por município e
os filtros do dashboard.

## 2. Estrutura considerada

-   `municipios(municipio_id, municipio_nome)`
-   `trechos(trecho_id, municipio_id, trecho_nome, trecho_descricao, estacao, latitude, longitude, periodicidade, fonte, excluido)`
-   `analises(analise_id, trecho_id, analise_data, quantitativo)`

O modelo `BASE — Balneabilidade` reúne dados de análise, trecho,
município e classificações atual e histórica.

Premissas usadas no protótipo --- confirmar com a equipe: - `fonte = 0`:
praia; `fonte = 1`: foz de rio ou riacho. - `excluido = 1`: trecho
inativo; o modelo atual considera `excluido = 0`. - A regra de
classificação considera até cinco análises recentes. - Praia e foz devem
ser analisadas separadamente até a confirmação de que a mesma regra se
aplica às duas.

## 3. Casos de teste

  -----------------------------------------------------------------------------------
  ID             Teste             Procedimento     Resultado         Evidência
                                                    esperado          
  -------------- ----------------- ---------------- ----------------- ---------------
  V01            IDs duplicados    Verificar chaves Nenhum ID         Resultado SQL
                                   primárias nas    repetido          
                                   três tabelas                       

  V02            Referências órfãs Procurar         Nenhuma           Resultado SQL
                                   análises sem     referência órfã   
                                   trecho e trechos ou exceção        
                                   sem município    documentada       

  V03            Trechos           Contar por       Totais            Resultado SQL
                 ativos/inativos   `excluido`       explicáveis;      
                                                    modelo exclui     
                                                    inativos          

  V04            Praia versus foz  Contar trechos   Categorias        Resultado SQL
                                   por `fonte`      separadas e       
                                                    coerentes com o   
                                                    cadastro          

  V05            Classificação     Contar trechos   Categorias e      Consulta e
                 atual             distintos por    ausências         exportação
                                   classificação    explicadas; não   
                                                    contar linhas de  
                                                    análise como      
                                                    trechos           

  V06            Histórico mínimo  Conferir trechos Sem classificação Casos
                                   com menos de     quando essa for a identificados
                                   cinco análises   regra vigente     

  V07            Histórico         Conferir alguns  Classificação     Tabela de
                 temporal          trechos          histórica         conferência
                                   ordenados por    reproduzível pela 
                                   data e ID        regra             
                                                    implementada      

  V08            Mudanças          Comparar         Mudança apenas    Consulta e
                                   classificação    quando as         exemplos
                                   anterior e       categorias        
                                   posterior        diferem e há      
                                                    categoria         
                                                    anterior          

  V09            Totais por        Contar trechos   Soma coerente com Resultado SQL
                 município         distintos e      o total geral sob 
                                   análises por     os mesmos filtros 
                                   município                          

  V10            Filtro de data    Escolher período Cartões           Captura
                                   e comparar com   conectados        
                                   consulta         respeitam o       
                                   independente     período           

  V11            Filtro de         Selecionar um    Cartões           Captura
                 município         município        conectados exibem 
                                                    apenas o          
                                                    município         
                                                    selecionado       

  V12            Filtros           Aplicar data e   Resultado         Captura e SQL
                 combinados        município        corresponde à     
                                                    interseção dos    
                                                    filtros           

  V13            Sem classificação Identificar      Ausência não é    Resultado SQL
                                   valores          convertida        
                                   nulos/ausentes   automaticamente   
                                                    em "Própria"      

  V14            Fozes             Repetir          Indicadores não   Consulta e
                                   contagens        confundem foz com captura
                                   separando        praia             
                                   `fonte = 0` e                      
                                   `fonte = 1`                        
  -----------------------------------------------------------------------------------

Registre para cada teste: data, base utilizada, resultado esperado,
resultado observado, status (`Aprovado`, `Reprovado`, `Bloqueado` ou
`Pendente`), evidência e observações.

## 4. Consultas SQL de conferência

Consultas para SQLite; ajuste nomes se o banco utilizado tiver
diferenças.

### 4.1 IDs duplicados

``` sql
SELECT municipio_id, COUNT(*) AS quantidade
FROM municipios
GROUP BY municipio_id HAVING COUNT(*) > 1;

SELECT trecho_id, COUNT(*) AS quantidade
FROM trechos
GROUP BY trecho_id HAVING COUNT(*) > 1;

SELECT analise_id, COUNT(*) AS quantidade
FROM analises
GROUP BY analise_id HAVING COUNT(*) > 1;
```

Esperado: nenhuma linha retornada.

### 4.2 Referências órfãs

``` sql
SELECT a.analise_id, a.trecho_id
FROM analises a
LEFT JOIN trechos t ON t.trecho_id = a.trecho_id
WHERE t.trecho_id IS NULL;

SELECT t.trecho_id, t.municipio_id
FROM trechos t
LEFT JOIN municipios m ON m.municipio_id = t.municipio_id
WHERE m.municipio_id IS NULL;
```

### 4.3 Trechos por tipo e situação

``` sql
SELECT fonte, excluido, COUNT(*) AS total_trechos
FROM trechos
GROUP BY fonte, excluido
ORDER BY fonte, excluido;
```

### 4.4 Totais por município

``` sql
SELECT
    m.municipio_nome,
    COUNT(DISTINCT t.trecho_id) AS total_trechos,
    COUNT(a.analise_id) AS total_analises
FROM municipios m
LEFT JOIN trechos t ON t.municipio_id = m.municipio_id
LEFT JOIN analises a ON a.trecho_id = t.trecho_id
GROUP BY m.municipio_id, m.municipio_nome
ORDER BY m.municipio_nome;
```

Essa consulta inclui trechos inativos. Para reproduzir o dashboard,
aplique `t.excluido = 0` de maneira consistente e documente a escolha.

### 4.5 Distribuição da classificação atual

Se `classificacao_trechos` existir no banco:

``` sql
SELECT ct.classificacao, COUNT(DISTINCT ct.trecho_id) AS total_trechos
FROM classificacao_trechos ct
JOIN trechos t ON t.trecho_id = ct.trecho_id
WHERE t.excluido = 0
GROUP BY ct.classificacao
ORDER BY ct.classificacao;
```

Trechos ativos sem classificação:

``` sql
SELECT t.trecho_id, t.trecho_nome, m.municipio_nome
FROM trechos t
JOIN municipios m ON m.municipio_id = t.municipio_id
LEFT JOIN classificacao_trechos ct ON ct.trecho_id = t.trecho_id
WHERE t.excluido = 0 AND ct.trecho_id IS NULL
ORDER BY m.municipio_nome, t.trecho_nome;
```

### 4.6 Análises por município e dia

``` sql
SELECT m.municipio_nome, DATE(a.analise_data) AS data_analise,
       COUNT(*) AS total_analises
FROM analises a
JOIN trechos t ON t.trecho_id = a.trecho_id
JOIN municipios m ON m.municipio_id = t.municipio_id
WHERE t.excluido = 0
GROUP BY m.municipio_id, m.municipio_nome, DATE(a.analise_data)
ORDER BY data_analise, m.municipio_nome;
```

Isso verifica análises na data, não mudanças de classificação.

### 4.7 Conferência do histórico

Se a view `classificacao_historica` existir:

``` sql
SELECT t.trecho_id, t.trecho_nome, a.analise_data, a.analise_id,
       ch.classificacao
FROM analises a
JOIN trechos t ON t.trecho_id = a.trecho_id
LEFT JOIN classificacao_historica ch
  ON ch.analise_referencia_id = a.analise_id
WHERE t.excluido = 0
ORDER BY t.trecho_id, a.analise_data, a.analise_id;
```

Compare manualmente alguns trechos com o gráfico. Confirme nomes de
views e campos no banco antes de usar.

## 5. Regras a confirmar institucionalmente

-   A regra de classificação utilizada é a versão oficial vigente?
-   Como tratar trechos com menos de cinco análises?
-   Como tratar valores nulos, datas repetidas e dados ausentes?
-   A regra também se aplica às fozes?
-   Qual é a definição oficial de trecho ativo/inativo?
-   A classificação atual usa todas as análises disponíveis ou um
    período definido?
-   A data da mudança corresponde à coleta, análise ou publicação?
-   Qual é a periodicidade de atualização?
-   Quais indicadores contam análises e quais contam trechos distintos?
-   Os agrupamentos regionais usados são oficiais? No protótipo, são
    agrupamentos analíticos.
-   Qual fonte oficial deve ser usada para coordenadas, códigos IBGE e
    nomes de municípios?

Registre cada item como **Confirmado**, **Pendente** ou **Não
aplicável**, incluindo a fonte da confirmação.

## 6. Critério de conclusão

O plano estará concluído para o protótipo quando os testes aplicáveis
tiverem resultados e evidências registrados, e as limitações
documentadas. A validação institucional depende da conferência com os
dados oficiais e da confirmação das regras pertinentes.
