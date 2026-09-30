# Sala de Leitura — Projeto V1

**Produto:** Sala de Leitura  
**Setor responsável:** Área do Livro Espírita — CEEBN (Centro de Educação Espírita Boa Nova)  
**Versão:** 1.1  
**Status:** Especificação funcional e técnica / base da implementação

---

## 1. Objetivo

Desenvolver uma aplicação web on-line para controlar o setor **Sala de Leitura**, do CEEBN — Centro de Educação Espírita Boa Nova.

A V1 deverá substituir os principais controles atualmente realizados por planilha, fichas de papel e controle manual, mantendo a lógica operacional já utilizada e acrescentando histórico, pesquisa e controle de permissões.

O escopo da V1 é:

1. Cadastro e controle do acervo de livros.
2. Cadastro de associados/leitores.
3. Controle de empréstimos e devoluções.
4. Consulta e análise de empréstimos em aberto/atrasados.
5. Registro e histórico de comunicações administrativas relacionadas a atrasos.
6. Balanço/conferência física do acervo (auditoria; não altera status do livro).
7. Cadastro de operadores/usuários e controle de permissões.
8. Histórico da vida do livro no acervo, inclusive entrada e baixa.
9. Cadastro auxiliar de Áreas e Classificações (listas fechadas).

---

# 2. Contexto da Sala de Leitura

A aplicação será destinada exclusivamente ao setor de **Empréstimos de livros**, também denominado **Sala de Leitura**.

A **Área do Livro Espírita** é o setor institucional responsável pela Sala de Leitura.

A Sala de Leitura mantém um acervo de livros recebidos por doação e os disponibiliza gratuitamente para empréstimo aos leitores/associados.

Não existe cobrança de locação pelo empréstimo.

Não há limite de quantidade de livros emprestados por associado.

# 3. Situação atual

O controle atual utiliza uma planilha e documentos físicos.

## 3.1 Catálogo

A planilha possui uma aba `Catálogo`, com as informações básicas dos livros:

- Código
- Título
- Autor / Médium
- Espírito
- Classificação

O código identifica o livro físico cadastrado no acervo.

## 3.2 Ficha do leitor

Atualmente existe uma ficha física individual para cada leitor.

A ficha contém:

- Nome
- Telefone
- Área
- Histórico de empréstimos:
  - Número do livro
  - Título
  - Retirada
  - Devolução

O aplicativo substituirá essa ficha física por um cadastro digital e seu histórico.

## 3.3 Ficha do livro

Cada livro possui uma ficha física guardada em um envelope na contracapa.

Quando o livro é emprestado, a ficha é retirada do envelope e fica no controle dos livros emprestados.

Quando o livro é devolvido, a data de devolução é registrada e a ficha retorna ao envelope.

O aplicativo deverá substituir esse processo físico.

## 3.4 Processo atual de empréstimo

Para retirar um livro, a pessoa precisa estar cadastrada como leitor/associado.

O responsável:

1. identifica o associado;
2. identifica o livro;
3. registra a retirada;
4. mantém o controle do empréstimo;
5. registra o empréstimo no histórico do leitor.

No sistema digital, a operação deverá ser registrada apenas uma vez e gerar automaticamente os históricos correspondentes.

## 3.5 Balanço atual

A planilha possui uma aba `balanço`.

Periodicamente são lançados os códigos dos livros encontrados fisicamente no acervo.

A planilha compara esses códigos com o catálogo e permite identificar quais livros cadastrados não foram encontrados no balanço.

A aplicação deverá transformar esse processo em uma funcionalidade própria de balanço do acervo (ferramenta de auditoria).

---

# 4. Princípios da V1

## 4.1 Livro é uma entidade única

Não haverá separação entre `Obra` e `Exemplar`.

Cada registro representa um livro físico do acervo.

Não é objetivo manter obras duplicadas no acervo, mas o sistema **não impede** o cadastro de títulos repetidos.

Quando forem identificados livros duplicados no cadastro, o sistema deve **apenas informar** que o título já existe e **permitir** concluir o cadastro. A decisão operacional de manter ou baixar o excedente permanece com o administrador.

## 4.2 Baixa não significa exclusão

Um livro que deixar o acervo não deve ser apagado do sistema.

Seu cadastro e seu histórico devem permanecer.

A situação passa a ser `BAIXADO`, com:

- data da baixa;
- motivo da baixa;
- observação, quando necessária.

Exemplos de motivo:

- Perda;
- Repassado por doação;
- Outro motivo.

## 4.3 Histórico deve ser preservado

Operações importantes não devem apagar o passado.

O sistema deve permitir saber:

- quando o livro entrou no acervo;
- como entrou;
- empréstimos realizados;
- devoluções;
- balanços (auditoria);
- quando deixou o acervo;
- por que deixou o acervo;
- quem realizou as operações administrativas.

---

# 5. Módulos da aplicação V1

A aplicação deverá possuir os seguintes módulos:

1. Dashboard
2. Acervo / Livros
3. Associados
4. Empréstimos
5. Devoluções
6. Empréstimos em aberto/atrasados
7. Comunicações
8. Balanço do acervo
9. Usuários/Operadores
10. Histórico do livro
11. Histórico do associado
12. Cadastros auxiliares (Áreas e Classificações)

O acesso aos módulos deverá respeitar as permissões definidas neste documento.

---

# 6. Cadastro de livros

## 6.1 Dados

Cada livro deverá possuir:

- ID interno
- Código
- Título
- Autor / Médium
- Espírito
- Classificação (FK — lista fechada)
- Data de entrada no acervo
- Forma de entrada
- Situação atual
- Data da baixa
- Motivo da baixa
- Observação
- Operador de cadastro / alterações relevantes

## 6.2 Entrada no acervo

O sistema deve registrar quando o livro entrou no acervo.

A V1 deve permitir registrar a forma de entrada, especialmente `DOAÇÃO`.

A entrada gera registro em `MOVIMENTACOES_ACERVO` do tipo entrada.

## 6.3 Situação do livro

Situações oficiais do livro:

- `DISPONIVEL`
- `EMPRESTADO`
- `BAIXADO`

### Disponível
Livro pertence ao acervo e pode ser emprestado.

### Emprestado
Livro está atualmente com um associado.

### Baixado
Livro não pertence mais ao acervo disponível, mas seu registro histórico permanece.

**Nota:** `NÃO LOCALIZADO` **não** é situação do livro. É apenas um resultado possível do processamento do balanço. O livro permanece `DISPONIVEL` (ou `EMPRESTADO`/`BAIXADO`, conforme o caso) e pode continuar disponível para empréstimo. Opcionalmente, o administrador pode registrar uma observação no livro (ex.: “Não localizado no balanço de dd/mm/aa”) via ação “Registrar ausência”.

## 6.4 Aviso de título duplicado

Ao cadastrar um novo livro, se já existir outro registro com o mesmo título (comparação case-insensitive / trim), o sistema deve avisar o operador e permitir confirmar o cadastro mesmo assim.

---

# 7. Baixa de livro

Somente administrador pode realizar baixa.

Ao baixar um livro, registrar:

- data;
- usuário responsável;
- motivo;
- observação opcional.

Motivos iniciais:

- Perda;
- Repassado por doação;
- Outro.

A baixa deve gerar uma movimentação histórica em `MOVIMENTACOES_ACERVO`.

Não permitir que livro baixado seja utilizado em novo empréstimo.

---

# 8. Histórico do livro

Cada livro deve possuir uma área de histórico.

A linha do tempo combina:

1. **Movimentações de acervo** — entrada e baixa (com motivo);
2. **Empréstimos** — retiradas e devoluções no intervalo entre entrada e baixa;
3. **Observações de balanço** — quando houver “Registrar ausência” (não mudam a situação).

Exemplo:

| Data | Evento | Operador | Observação |
|---|---|---|---|
| 15/03/2024 | Entrada | Maria | Doação |
| 12/08/2024 | Empréstimo | Maria | Associado X |
| 25/08/2024 | Devolução | João | — |
| 10/09/2026 | Observação (balanço) | Admin | Não localizado no balanço de 10/09/2026 |
| 29/09/2026 | Baixa | Administrador | Repassado por doação |

O histórico deve ser somente leitura para usuários comuns.

---

# 9. Cadastro de associados

## 9.1 Dados mínimos

- ID
- Nome
- Telefone
- Área (FK — lista fechada)
- Ativo/Inativo
- Observação
- Data de cadastro
- Operador que cadastrou

O campo `Área` existe na ficha física atual e deve ser preservado.

## 9.2 Situação

Associado poderá estar:

- Ativo
- Inativo

Associado inativo não deve poder realizar novo empréstimo.

Não apagar associados que possuem histórico.

Não há limite de livros em aberto por associado.

---

# 9A. Cadastros auxiliares (Áreas e Classificações)

Área (associados) e Classificação (livros) são **listas fechadas**.

Deve existir cadastro simples (CRUD) para:

- Áreas
- Classificações

Regras:

- somente administrador gerencia essas listas;
- itens em uso não devem ser apagados de forma destrutiva (preferir inativar ou bloquear exclusão se houver vínculo);
- nos formulários de livro e associado, a seleção é feita a partir dessas listas.

---

# 10. Empréstimos

## 10.1 Regras

Para realizar um empréstimo:

1. associado deve estar cadastrado;
2. associado deve estar ativo;
3. livro deve estar disponível;
4. usuário deve possuir permissão para realizar empréstimo.

Não há limite de quantidade de empréstimos em aberto por associado.

## 10.2 Dados do empréstimo

- ID
- Livro
- Associado
- Data/hora da retirada
- Data da devolução
- Operador da retirada
- Operador da devolução
- Observação

Não existe prazo fixo de devolução definido pelo sistema.

## 10.3 Regra de integridade

Um livro não pode possuir mais de um empréstimo em aberto simultaneamente.

Ao registrar o empréstimo:

- situação do livro → `EMPRESTADO`;
- criar registro de empréstimo;
- registrar operador;
- registrar data/hora.

Ao registrar devolução:

- preencher data/hora da devolução;
- registrar operador;
- situação do livro → `DISPONIVEL`;
- preservar o empréstimo no histórico.

---

# 11. Consulta de empréstimos em aberto e atrasados

Deve existir uma área específica para análise.

O usuário informa o número de dias desejado.

Exemplo:

`Mostrar empréstimos em aberto há mais de [60] dias`

Outro dia:

`Mostrar empréstimos em aberto há mais de [15] dias`

Não haverá um prazo fixo de atraso configurado para a aplicação.

## 11.1 Cálculo

Somente empréstimos sem data de devolução entram na análise.

Dias em aberto:

`data atual - data de retirada`

## 11.2 Resultado

Exibir pelo menos:

- Associado
- Telefone
- Livro
- Código
- Data de retirada
- Quantidade de dias em aberto
- Última comunicação, se houver

A área de análise de atrasos é exclusiva do administrador.

---

# 12. Comunicações

O administrador poderá registrar uma comunicação ao associado relacionada a um empréstimo em aberto/atrasado.

Na V1, o sistema **registra** a comunicação (histórico). Integração de envio automático (WhatsApp etc.) não é obrigatória.

## 12.1 Requisito fundamental

Toda comunicação deve gerar histórico.

Não deve existir comunicação administrativa que desapareça depois de realizada.

## 12.2 Dados

- ID
- Associado
- Empréstimo relacionado, quando aplicável
- Data/hora
- Operador
- Meio de comunicação
- Assunto
- Mensagem
- Resultado/status

Meios poderão incluir, por exemplo:

- WhatsApp
- Telefone
- E-mail
- Outro

A integração efetiva com serviços externos não é obrigatória para a V1. O sistema deve, no mínimo, permitir registrar a comunicação.

Se houver integração futura, o registro da comunicação continuará existindo no sistema.

---

# 13. Histórico do associado

O histórico do associado possui duas categorias distintas.

## 13.1 Histórico de empréstimos

Usuários comuns e administradores podem visualizar:

- livros retirados;
- data da retirada;
- data da devolução;
- empréstimos atualmente em aberto.

## 13.2 Histórico de comunicações

Somente administradores podem visualizar:

- data;
- operador;
- meio;
- assunto;
- mensagem;
- resultado;
- empréstimo relacionado.

### Regra de segurança

Usuário comum não pode acessar o histórico de comunicações nem pela interface nem diretamente por API.

A autorização deve ser aplicada no backend.

---

# 14. Balanço do acervo

Somente administrador pode realizar balanço.

O balanço é **ferramenta de auditoria**: compara códigos lançados com o acervo esperado e **não altera a situação oficial do livro**.

## 14.1 Criar balanço

Registrar:

- ID
- Data/hora
- Responsável

Durante o balanço, o administrador informa os códigos dos livros fisicamente encontrados.

Os códigos podem ser digitados inicialmente.

O projeto deve ser estruturado de forma que futuramente possa receber leitura por código de barras, se desejado.

## 14.2 Resultado

O sistema compara os livros encontrados com os livros que deveriam estar no acervo.

Deve ser possível identificar no **resultado do balanço** (não como status do livro):

- encontrados;
- emprestados (não estão na estante, mas têm empréstimo aberto — não tratar como desaparecidos);
- não localizados (cadastrados, não baixados, não emprestados e não lançados no balanço);
- baixados;
- demais situações pertinentes.

## 14.3 Registrar ausência

Opcionalmente, a partir do resultado “não localizado”, o administrador pode usar **Registrar ausência**, que:

- **não** altera `LIVROS.situacao`;
- acrescenta observação no livro, no formato sugerido: `Não localizado no balanço de dd/mm/aa`;
- permanece visível no histórico/observações do livro.

## 14.4 Histórico de balanços

Cada balanço deve permanecer registrado.

É possível consultar balanços anteriores e seus resultados.

---

# 15. Usuários / Operadores

Existem dois perfis na V1:

## ADMIN

Pode:

- cadastrar livros;
- alterar livros;
- realizar baixa;
- consultar acervo;
- cadastrar associados;
- consultar associados;
- realizar empréstimos;
- registrar devoluções;
- consultar empréstimos;
- analisar atrasos;
- enviar/registrar comunicações;
- visualizar histórico de comunicações;
- realizar balanços;
- consultar balanços;
- registrar ausência a partir do balanço;
- cadastrar e administrar operadores;
- administrar Áreas e Classificações.

## USUARIO

Pode:

- consultar acervo;
- cadastrar associados;
- consultar associados;
- consultar histórico de empréstimos do associado;
- realizar empréstimos;
- registrar devoluções.

Não pode:

- cadastrar livros;
- alterar livros;
- baixar livros;
- realizar balanço;
- consultar balanços administrativos;
- analisar empréstimos atrasados;
- enviar/registrar comunicações administrativas;
- visualizar histórico de comunicações;
- administrar usuários;
- administrar Áreas e Classificações.

---

# 16. Auditoria

Operações relevantes devem registrar o operador responsável.

No mínimo:

- cadastro de livro;
- alteração de livro;
- entrada de livro;
- baixa;
- cadastro de associado;
- empréstimo;
- devolução;
- balanço;
- comunicação;
- registrar ausência;
- alterações administrativas.

Usuários desativados não devem ser apagados se existirem operações históricas vinculadas a eles.

O histórico deve continuar mostrando o nome do operador que realizou a operação.

---

# 17. Dashboard

A tela inicial poderá apresentar indicadores do acervo, por exemplo:

- Total de livros ativos (não baixados);
- Disponíveis;
- Emprestados;
- Baixados;
- Empréstimos em aberto.

Para usuário comum, mostrar somente indicadores compatíveis com suas permissões.

Informações administrativas de atrasos e comunicações não devem aparecer para usuário comum.

---

# 18. Pesquisa do acervo

Permitir pesquisa por:

- Código;
- Título;
- Autor / Médium;
- Espírito;
- Classificação.

Resultado deve mostrar:

- Código;
- Título;
- Autor / Médium;
- Espírito;
- Classificação;
- Situação.

Exemplo:

`A Gênese`

O sistema deve mostrar todos os registros correspondentes, inclusive livros baixados quando a consulta permitir histórico.

Na consulta operacional padrão, priorizar livros atualmente ativos no acervo.

---

# 19. Regras para livros duplicados

A Sala de Leitura não pretende manter obras duplicadas no acervo como política operacional, mas o sistema **não bloqueia** títulos repetidos.

Quando o operador cadastrar um livro cujo título já exista:

1. o sistema informa que o título já está cadastrado;
2. o operador pode confirmar e concluir o cadastro;
3. se a política operacional for retirar o excedente, o administrador registra a baixa com motivo `REPASSADO_POR_DOACAO` no exemplar que for repassado.

O livro repassado não deve ser apagado do sistema.

---

# 20. Modelo de dados conceitual

A V1 poderá utilizar uma estrutura semelhante a:

```text
USUARIOS
    id
    nome
    login
    senha_hash
    perfil          -- ADMIN | USUARIO
    ativo
    created_at
    updated_at

AREAS
    id
    nome
    ativo
    created_at
    updated_at

CLASSIFICACOES
    id
    nome
    ativo
    created_at
    updated_at

LIVROS
    id
    codigo              -- único
    titulo
    autor_medium
    espirito
    classificacao_id
    data_entrada
    forma_entrada
    situacao            -- DISPONIVEL | EMPRESTADO | BAIXADO
    data_baixa
    motivo_baixa
    observacao
    created_at
    updated_at

ASSOCIADOS
    id
    nome
    telefone
    area_id
    ativo
    observacao
    usuario_cadastro_id
    created_at
    updated_at

EMPRESTIMOS
    id
    livro_id
    associado_id
    data_retirada
    data_devolucao      -- null = em aberto
    usuario_retirada_id
    usuario_devolucao_id
    observacao
    created_at
    updated_at

COMUNICACOES
    id
    associado_id
    emprestimo_id
    usuario_id
    data_hora
    meio
    assunto
    mensagem
    resultado
    created_at

BALANCOS
    id
    data_hora
    usuario_id
    observacao

BALANCO_ITENS
    id
    balanco_id
    livro_id            -- nullable se código inválido
    codigo_informado
    encontrado          -- boolean: lançado como presente na conferência
    observacao

MOVIMENTACOES_ACERVO
    id
    livro_id
    data_hora
    tipo                -- ENTRADA | BAIXA
    usuario_id
    motivo              -- obrigatório na baixa
    observacao
```

Escopo de `MOVIMENTACOES_ACERVO`: **entrada no acervo** e **baixa (com motivo)**.  
Entre esses marcos, a movimentação operacional do livro é representada pelos **empréstimos** (retirada/devolução).

A implementação pode adaptar os nomes das tabelas/campos à tecnologia escolhida, desde que preserve as regras funcionais deste documento.

---

# 21. Integridade dos dados

Regras importantes:

1. Código do livro deve ser único.
2. Login do usuário deve ser único.
3. Livro baixado não pode ser emprestado.
4. Livro emprestado não pode receber outro empréstimo aberto.
5. Associado inativo não pode receber novo empréstimo.
6. Devolução somente pode ser registrada para empréstimo aberto.
7. Histórico não deve ser apagado por alterações de situação.
8. Livro baixado permanece no banco.
9. Associado inativo permanece no banco.
10. Usuário desativado permanece vinculado ao histórico das operações.
11. Usuário comum não pode acessar dados administrativos protegidos.
12. Histórico de comunicações é restrito ao administrador.
13. Balanço é restrito ao administrador e **não altera** a situação do livro.
14. Cadastro/alteração/baixa de livros é restrito ao administrador.
15. Áreas e Classificações são listas fechadas gerenciadas pelo administrador.
16. Não há limite de empréstimos em aberto por associado.

---

# 22. Interface

A interface deve privilegiar operação simples e rápida, pois será utilizada pelos trabalhadores da Sala de Leitura.

Operações mais frequentes devem estar facilmente acessíveis:

- pesquisar livro;
- pesquisar associado;
- novo empréstimo;
- registrar devolução.

A interface administrativa deve ficar separada das operações cotidianas quando isso facilitar a utilização.

Não criar funcionalidades ou telas desnecessárias na V1.

---

# 23. Segurança

Como a aplicação será on-line:

- autenticação obrigatória;
- senhas armazenadas com hash seguro;
- autorização baseada no perfil;
- autorização validada no backend;
- proteção das rotas administrativas;
- proteção dos dados de comunicação;
- registro de operações relevantes;
- não expor informações administrativas a usuários sem permissão.

Nunca confiar somente na ocultação de elementos da interface para controlar permissões.

---

# 24. Migração dos dados atuais

A planilha atual deverá ser utilizada como fonte inicial para migração do catálogo da Sala de Leitura.

A aba `Catálogo` contém:

- Código;
- Título;
- Autor / Médium;
- Espírito;
- Classificação.

Antes da importação definitiva:

1. validar códigos;
2. identificar registros duplicados;
3. identificar registros incompletos;
4. definir a situação inicial de cada livro (`DISPONIVEL`, salvo indicação contrária);
5. registrar, quando conhecido, que os livros existentes são acervo anterior à implantação.

**Empréstimos abertos na migração:** não serão importados automaticamente. Serão informados **manualmente** após a implantação do catálogo.

A migração dos dados históricos de empréstimos das fichas físicas não é obrigatória para a V1. O sistema deve começar a registrar o histórico digital a partir da implantação.

---

# 25. Fora do escopo da V1

A V1 é exclusivamente destinada ao controle da **Sala de Leitura / Empréstimos**.

Não implementar funcionalidades que não façam parte desse objetivo, como:

- controle financeiro;
- vendas ou comercialização de livros;
- mensalidades;
- cobrança;
- integração com outras operações da instituição;
- aplicativo móvel nativo;
- integração obrigatória com WhatsApp;
- código de barras obrigatório;
- notificações automáticas;
- relatórios estatísticos avançados.

Esses itens poderão ser considerados em versões futuras caso sejam pertinentes à Sala de Leitura.

# 26. Possíveis evoluções futuras

A arquitetura deve permitir evolução sem necessidade de reescrever o núcleo do sistema.

Possíveis funcionalidades futuras:

- leitura de código de barras;
- envio integrado de WhatsApp;
- mensagens automáticas;
- lembretes de devolução;
- relatórios estatísticos;
- gráficos de utilização do acervo;
- aplicativo/PWA para celular;
- impressão de etiquetas;
- impressão de relatórios;
- importação/exportação de dados;
- múltiplas unidades/Postos do Livro.

Essas funcionalidades não devem ser implementadas na V1 sem solicitação específica.

---

# 27. Critérios de aceite da V1

A V1 será considerada funcional quando for possível:

### Acervo
- cadastrar livro (com aviso de título duplicado, sem bloqueio);
- consultar livro;
- alterar livro;
- registrar entrada;
- registrar baixa;
- consultar histórico do livro;
- identificar situação atual (`DISPONIVEL` | `EMPRESTADO` | `BAIXADO`).

### Associados
- cadastrar associado;
- alterar cadastro;
- ativar/desativar associado;
- consultar histórico de empréstimos.

### Empréstimos
- localizar associado;
- localizar livro;
- registrar retirada;
- impedir retirada de livro indisponível;
- registrar devolução;
- impedir dois empréstimos simultâneos do mesmo livro;
- visualizar empréstimos em aberto;
- permitir múltiplos empréstimos em aberto para o mesmo associado.

### Atrasos
- informar quantidade de dias para consulta;
- listar empréstimos em aberto acima desse número de dias;
- consultar dados necessários para contato;
- restringir essa área ao administrador.

### Comunicações
- administrador registrar comunicação;
- relacionar comunicação ao associado;
- relacionar comunicação ao empréstimo quando aplicável;
- manter histórico;
- restringir histórico ao administrador.

### Balanço
- criar balanço;
- lançar códigos encontrados;
- comparar com acervo;
- identificar não localizados **no resultado** (sem alterar status do livro);
- registrar ausência (observação) opcionalmente;
- preservar histórico dos balanços;
- restringir ao administrador.

### Cadastros auxiliares
- administrar Áreas;
- administrar Classificações.

### Usuários
- autenticar;
- criar usuários;
- definir perfil;
- ativar/desativar usuário;
- aplicar permissões no backend;
- preservar operador das operações históricas.

---

# 28. Regra geral para desenvolvimento

Este documento é a referência funcional da **V1 da Sala de Leitura**.

Durante o desenvolvimento:

- não alterar regras de negócio sem registrar a alteração;
- não adicionar funcionalidades fora do escopo sem aprovação;
- priorizar simplicidade operacional;
- preservar históricos;
- garantir integridade dos dados;
- aplicar permissões no backend;
- manter separação entre operação cotidiana e funções administrativas.

Qualquer decisão técnica deve servir às regras funcionais aqui definidas, e não alterar essas regras por conveniência da implementação.

---

# 29. Stack tecnológica

Mesma base do projeto ContaCerta:

## 29.1 Backend

- PHP 8.3 ou superior compatível com a versão Laravel adotada;
- Laravel 13 ou versão estável equivalente no momento da implementação;
- camada de serviços quando necessário;
- filas Laravel quando pertinente.

## 29.2 Banco de dados

- MySQL 8.4 LTS ou versão LTS equivalente;
- chaves estrangeiras e constraints;
- índices para código, título, situação e empréstimos em aberto.

## 29.3 Frontend

- Vue 3;
- Inertia.js;
- Tailwind CSS 4;
- Vite;
- componentes reutilizáveis;
- interface responsiva e operacionalmente simples.

## 29.4 Ambiente

- Desenvolvimento em **Docker Compose** (nginx, PHP-FPM, MySQL, Node/Vite, Mailpit; queue/scheduler quando necessário);
- único requisito no computador de desenvolvimento: Docker Engine + Compose;
- o mesmo empacotamento deve poder ir para servidor Linux e uso via web.

## 29.5 Utilitários

- PhpSpreadsheet (ou equivalente) para importação futura do catálogo a partir da planilha.

---

# 30. Fluxo principal da aplicação

```text
                    ÁREA DO LIVRO ESPÍRITA
                    (setor responsável)
                              |
                      SALA DE LEITURA
                         (produto)
                              |
                    +---------+---------+
                    |                   |
               USUÁRIO              ADMINISTRADOR
                    |                   |
          +---------+---------+   +-----+---------------------------+
          |         |         |   |       |       |      |    |     |
       Acervo   Associados  Empr. Livros  Balanço Atrasos Comun. Cad.
          |         |         |     |       |       |      |   (Áreas/
          +---------+---------+-----+-------+-------+------+  Classif.)
                              |
                         HISTÓRICOS
                              |
             +----------------+----------------+
             |                |                |
        Histórico        Histórico        Histórico
        do livro       do associado      das operações
        (entrada/baixa +                 restrito por perfil
         empréstimos +
         obs. de balanço)
```

## Fim da especificação funcional V1
