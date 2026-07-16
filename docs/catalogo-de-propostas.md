# Catálogo de propostas

## Objetivo

Este documento registra o funcionamento e o layout observados na funcionalidade **Criar catálogo (Beta)** do sistema **Localizador de Editais** e define o contrato implementado no backend do Licitador.

> **Status no Licitador:** backend implementado. Este arquivo é também o contrato de integração para o agente responsável pelo frontend.

A análise foi realizada em 15/07/2026, usando uma sessão autenticada fornecida pelo usuário. Foram avaliados a abertura do catálogo a partir de **Minhas Propostas**, o formulário de montagem, a visualização gerada, o modo de edição da visualização e o fluxo de impressão.

URLs observadas:

```text
Edição:      https://painel.localizadordeeditais.com.br/?gpl_catalogo_from_proposta={proposal_id}
Visualização: https://painel.localizadordeeditais.com.br/?gpl_view_catalogo={catalog_id}
```

O parâmetro `proposal_id` identifica a proposta usada como origem. Depois da criação, a visualização utiliza um identificador próprio do catálogo, `catalog_id`.

Informações cadastrais sensíveis da empresa visualizada não foram reproduzidas neste documento.

## Fluxo de acesso

1. O usuário acessa a página **Minhas Propostas**.
2. Em uma proposta, seleciona a ação **Criar catálogo (Beta)**.
3. O sistema abre a página **Catálogo da Proposta**.
4. Os dados da empresa, do órgão e os itens disponíveis são carregados a partir da proposta.
5. O usuário personaliza o documento, administra os itens e pode selecionar imagens.
6. O usuário pode salvar a configuração ou gerar a visualização do catálogo.
7. Na visualização gerada, é possível imprimir, fechar ou habilitar uma edição visual temporária.

## Layout da página de edição

A página é organizada verticalmente, em formato de formulário documental. No desktop observado, o conteúdo ocupa uma coluna central e os itens aparecem como cartões empilhados.

### Cabeçalho da empresa

O topo apresenta um cartão com:

- logotipo da plataforma/empresa;
- razão social ou nome da empresa;
- endereço;
- CNPJ;
- telefone;
- e-mail.

Não foram observados campos para editar esses dados diretamente na página. Eles aparentam vir do cadastro da empresa vinculada à proposta.

### Personalização

O primeiro bloco editável contém:

| Campo | Comportamento observado |
|---|---|
| Título do catálogo | Campo de texto, preenchido inicialmente com `Catálogo de Produtos`; aparece no topo do documento gerado |
| Subtítulo | Campo de texto opcional |
| Observações gerais | Área de texto opcional, exibida depois da lista de produtos |

O campo de título possui o texto auxiliar **Exibido no topo do catálogo**. As observações possuem o texto auxiliar **Texto exibido após a lista de produtos. Opcional.**

### Dados do órgão

O bloco **Dados do Órgão** apresenta os seguintes campos:

- Órgão;
- Estado;
- Número da Compra;
- Número do Processo;
- Data de Recebimento;
- Data de Abertura.

Os valores são carregados a partir da licitação/proposta. Na página avaliada, esses dados estavam disponíveis no formulário e também foram reproduzidos na visualização final.

### Itens do catálogo

A seção **Itens do Catálogo** possui o botão **Adicionar item** e uma lista de cartões numerados como **Item 1**, **Item 2** e assim sucessivamente.

Cada item possui:

- ação para mover para cima;
- ação para mover para baixo;
- ação **Remover item**;
- área de imagem;
- ação **Selecionar imagem**;
- ação **Remover imagem**;
- Título do produto;
- Especificações;
- Quantidade;
- Unidade;
- Marca.

O primeiro item foi preenchido automaticamente com os dados do item da proposta: descrição/título, especificação, quantidade, unidade e marca. Quando não existe imagem, a área apresenta **Sem imagem**.

Não foram observados campos de preço no catálogo. O documento tem finalidade descritiva/comercial, centrada na apresentação dos produtos ou serviços.

## Administração dos itens

### Adicionar item

Ao selecionar **Adicionar item**, um novo cartão é inserido na lista sem recarregar a página. O novo item contém os mesmos campos e controles, mas começa sem título, especificações, quantidade, unidade, marca ou imagem.

Isso permite complementar o catálogo com um produto que não estava originalmente na proposta.

### Ordenar itens

Os botões com setas para cima e para baixo indicam que o usuário pode definir manualmente a ordem dos produtos. A ordem apresentada no formulário deve ser preservada na visualização e impressão.

Os limites precisam ser respeitados na implementação: o primeiro item não deve subir além da primeira posição e o último não deve descer além da última posição.

### Remover e desfazer remoção

Ao selecionar **Remover item**, o cartão não desaparece imediatamente. Ele permanece na página e a ação muda para **Desfazer remoção**.

Portanto, a remoção é reversível enquanto o formulário ainda não foi salvo. Esse comportamento evita exclusões acidentais e sugere que o item é marcado localmente como removido antes da persistência.

Não foi efetuado o salvamento de um item marcado para remoção; assim, não foi possível confirmar se a exclusão persistida é lógica ou física.

### Imagem do item

Cada item aceita uma imagem independente. Foram identificadas as ações para selecionar e remover a imagem, além do estado vazio **Sem imagem**.

O seletor não foi aberto e nenhum arquivo foi enviado. Permanecem não confirmados:

- origem da imagem, como upload local ou biblioteca de mídia;
- formatos aceitos;
- limite de tamanho;
- recorte ou redimensionamento;
- dimensões recomendadas;
- momento do upload, imediato ou somente ao salvar;
- regras de armazenamento e exclusão.

## Ações do formulário

No final da página existem duas ações principais:

### Salvar catálogo

A ação **Salvar catálogo** aparenta persistir a personalização, os dados do órgão, os itens, sua ordem, imagens e itens marcados para remoção.

O salvamento não foi executado para não alterar dados no sistema de referência. Portanto, mensagens de sucesso, validações, obrigatoriedade dos campos e comportamento de criação/atualização ainda precisam ser definidos no Licitador.

### Gerar catálogo

A ação **Gerar catálogo** abre uma visualização separada do catálogo já existente. A URL da visualização usa o identificador próprio do catálogo e não o identificador da proposta.

No fluxo analisado, a visualização foi aberta em outra aba. Não foi possível confirmar se, para um catálogo novo ou alterado, é obrigatório salvar antes de gerar.

## Visualização gerada

A visualização possui aparência de documento pronto para impressão, com fundo claro e conteúdo organizado em uma única página no exemplo avaliado.

### Cabeçalho

Apresenta:

- título do catálogo em destaque;
- logotipo;
- dados cadastrais e de contato da empresa.

### Dados do órgão

O bloco **Dados do Órgão** reproduz os seis dados cadastrados no formulário de edição.

### Produtos

Cada produto é exibido em um cartão contendo:

- imagem ou o texto **Imagem não disponível**;
- título;
- especificações;
- quantidade;
- unidade;
- marca.

As observações gerais devem aparecer após a lista de produtos, conforme o texto auxiliar do formulário. A proposta avaliada não possuía observação preenchida, portanto esse conteúdo não apareceu na visualização.

### Ações da visualização

No canto superior direito existem três ações:

- **Imprimir catálogo**;
- **Fechar**;
- **Habilitar edição**.

Esses controles fazem parte da interface de visualização e não do conteúdo comercial que deve ser impresso.

## Edição na visualização

Ao selecionar **Habilitar edição**:

- o botão muda para **Desabilitar edição**;
- contornos azuis tracejados aparecem sobre diversas regiões textuais;
- os blocos da empresa, órgão e produtos passam a indicar visualmente que seu conteúdo pode ser alterado;
- título, dados do órgão, título e especificações do produto, quantidade, unidade e marca foram identificados como regiões editáveis.

Ao selecionar **Desabilitar edição**, os contornos desaparecem e o documento volta ao modo normal de visualização.

Não apareceu uma ação de salvar dentro da visualização. Assim, o comportamento mais provável é uma edição local, destinada a ajustes pontuais antes da impressão. Isso é uma **inferência visual**, não uma regra confirmada: não foram alterados textos nem recarregada a página para testar persistência.

Para o Licitador, é necessário decidir explicitamente se essas alterações:

- existem apenas no navegador e na impressão atual; ou
- devem ser enviadas e persistidas no catálogo.

Se o objetivo for reproduzir fielmente o comportamento aparente, a edição da visualização deve ser temporária e não deve substituir o formulário principal de edição.

## Impressão

A ação **Imprimir catálogo** abre a pré-visualização nativa de impressão do navegador.

No teste foram observados:

- documento de uma página para o catálogo avaliado;
- seleção de destino, incluindo salvar como PDF;
- seleção de páginas;
- quantidade de páginas por folha;
- configuração de margens;
- opção de cabeçalhos e rodapés;
- opção de gráficos de segundo plano;
- botões do navegador para salvar/imprimir ou cancelar.

A impressão foi cancelada. Nenhum PDF foi salvo e nenhuma impressão foi enviada.

O layout de impressão preservou o cabeçalho da empresa, os dados do órgão e o cartão do produto. As ações da interface não apareceram dentro do documento.

## Regras funcionais identificadas

- Um catálogo nasce no contexto de uma proposta.
- A proposta fornece os dados iniciais da empresa, do órgão e dos itens.
- O catálogo possui identificador e ciclo de vida próprios.
- Um catálogo pode conter itens originados da proposta e itens adicionados manualmente.
- Os dados copiados precisam ser editáveis no catálogo sem alterar a proposta de origem.
- Cada item tem conteúdo, imagem e posição próprios.
- A ordem dos itens é controlada pelo usuário.
- A remoção pode ser desfeita antes de salvar.
- A visualização deve manter a ordem e o conteúdo definidos no formulário.
- A ausência de imagem possui um estado visual explícito.
- A versão gerada é preparada para impressão e PDF.
- A visualização oferece um modo de edição pontual que precisa ser tratado separadamente da edição persistente.

## Dados implementados no backend

### Catálogo

- empresa proprietária;
- proposta de origem;
- título;
- subtítulo opcional;
- observações gerais opcionais;
- órgão;
- estado;
- número da compra;
- número do processo;
- data de recebimento;
- data de abertura;
- usuário criador;
- usuário responsável pela última alteração;
- datas de criação e atualização.

Os dados da empresa e do órgão são armazenados como uma cópia no catálogo. Dessa forma, alterações futuras na proposta, licitação ou empresa não modificam silenciosamente o documento comercial já preparado.

### Item do catálogo

- catálogo;
- item da proposta de origem, opcional para itens adicionados manualmente;
- título;
- especificações;
- quantidade;
- unidade;
- marca;
- referência da imagem, opcional;
- posição de ordenação;
- datas de criação e atualização.

### Imagem

As imagens são armazenadas no disco público configurado pelo Laravel, em `proposal-catalogs/{catalog_id}`. A API retorna `image_url` e não expõe `image_path`.

São aceitos arquivos JPG, JPEG, PNG e WEBP de até 5 MB. Ao substituir ou remover uma imagem, o arquivo anterior também é excluído do armazenamento.

## Comportamento esperado no Licitador

### Criação

- A ação deve existir em uma proposta que pertença à empresa autenticada.
- Na primeira abertura, a API deve criar ou preparar um rascunho com os dados da proposta.
- A criação não deve alterar a proposta nem seus itens.
- Cada proposta possui no máximo um catálogo. A primeira consulta cria o catálogo e as consultas seguintes retornam o mesmo registro.

### Edição persistente

O usuário deve poder:

- alterar título, subtítulo e observações;
- ajustar os dados do órgão no catálogo;
- editar os dados copiados dos itens;
- adicionar item manual;
- reordenar itens;
- marcar item para remoção e desfazer antes de salvar;
- selecionar, trocar e remover imagem;
- salvar e retomar o catálogo posteriormente.

### Visualização

- Deve existir uma rota ou endpoint de leitura pelo identificador do catálogo.
- A visualização precisa respeitar as mesmas permissões do catálogo.
- O layout deve ser próprio para impressão, com quebra de páginas previsível quando houver vários itens.
- Os controles de tela devem ser ocultados via estilo de impressão.
- O estado **Imagem não disponível** deve ser apresentado quando aplicável.

### Segurança e permissões

- A API não deve confiar apenas nos identificadores recebidos na URL.
- Somente usuários autorizados da empresa proprietária podem criar, consultar, alterar, imprimir ou excluir o catálogo.
- A proposta de origem deve ser validada no escopo da empresa autenticada.
- Uploads precisam de validação e armazenamento privado ou exposição controlada conforme a política do projeto.

## Contrato implementado no backend

Todos os endpoints exigem a autenticação JWT e o usuário ativo já utilizados nas rotas protegidas da API.

```text
GET    /api/proposal/{proposal_id}/catalog
PUT    /api/proposal/{proposal_id}/catalog
POST   /api/proposal/{proposal_id}/catalog/generate
POST   /api/proposal/{proposal_id}/catalog/items/{item_id}/image
DELETE /api/proposal/{proposal_id}/catalog/items/{item_id}/image
GET    /api/proposal-catalog/{catalog_id}/view
```

Não existe endpoint de exclusão do catálogo inteiro nem endpoints individuais para criar ou editar itens. O formulário é salvo como uma unidade transacional por meio do `PUT`.

### Envelope das respostas

As respostas JSON seguem o padrão:

```json
{
  "status": true,
  "message": null,
  "data": {},
  "error": null
}
```

Erros de validação retornam HTTP `400`; proposta, catálogo ou item fora do escopo do usuário retornam `404`. O backend valida tanto a propriedade da proposta quanto o pertencimento dos itens enviados.

### Abrir ou inicializar o catálogo

```http
GET /api/proposal/{proposal_id}/catalog
```

Na primeira chamada, o backend cria automaticamente o catálogo, copia o snapshot atual da empresa, os dados do órgão e todos os itens da proposta. Nas chamadas seguintes, retorna o mesmo catálogo sem duplicar registros nem recarregar os dados da proposta.

Formato relevante de `data`:

```json
{
  "id": 10,
  "proposal_id": 35,
  "user_id": 7,
  "company_id": 4,
  "title": "Catálogo de Produtos",
  "subtitle": null,
  "general_notes": null,
  "organ_name": "Órgão de exemplo",
  "organ_state": "MG",
  "purchase_number": "001/2026",
  "process_number": "PROC-1",
  "receipt_date": null,
  "opening_date": null,
  "company_snapshot": {
    "corporate_reason": "Empresa de exemplo",
    "cnpj": "...",
    "logo": null
  },
  "last_updated_by": 7,
  "generated_at": null,
  "items": [
    {
      "id": 50,
      "proposal_catalog_id": 10,
      "proposal_item_id": 80,
      "title": "Produto de exemplo",
      "specification": "Descrição completa",
      "quantity": "2.0000",
      "unit": "UN",
      "brand": "Marca A",
      "position": 1,
      "image_original_name": null,
      "image_mime": null,
      "image_url": null
    }
  ],
  "proposal": {},
  "company": {}
}
```

Campos de data podem ser serializados em ISO 8601 pelo Laravel. O frontend deve formatá-los para exibição e enviar datas editadas no formato `YYYY-MM-DD`.

### Salvar o catálogo

```http
PUT /api/proposal/{proposal_id}/catalog
Content-Type: application/json
```

Exemplo:

```json
{
  "title": "Catálogo de Produtos",
  "subtitle": "Linha profissional 2026",
  "general_notes": "Condições e observações gerais.",
  "organ_name": "Órgão de exemplo",
  "organ_state": "MG",
  "purchase_number": "001/2026",
  "process_number": "PROC-1",
  "receipt_date": "2026-07-20",
  "opening_date": "2026-07-21",
  "items": [
    {
      "id": 50,
      "proposal_item_id": 80,
      "title": "Produto editado",
      "specification": "Descrição completa",
      "quantity": 2,
      "unit": "UN",
      "brand": "Marca A",
      "position": 1
    },
    {
      "title": "Item adicionado manualmente",
      "specification": "Não possui item de proposta como origem",
      "quantity": 1,
      "unit": "UN",
      "brand": "Marca B",
      "position": 2
    }
  ]
}
```

Regras importantes:

- metadados são parciais: campos não enviados permanecem como estão;
- quando `items` não é enviado, os itens atuais permanecem inalterados;
- quando `items` é enviado, ele representa a lista final completa;
- item existente deve enviar `id`;
- item originado da proposta deve manter `proposal_item_id`;
- o vínculo `proposal_item_id` de um item já existente é imutável; para trocar a origem, remova o item e crie outro;
- item manual não envia `id` nem `proposal_item_id` na primeira gravação;
- item existente omitido da lista é removido do catálogo;
- a remoção/desfazer antes de salvar é um estado local do frontend;
- `position` não pode se repetir; o backend ordena a lista recebida e normaliza as posições para `1..N`;
- as imagens dos itens mantidos são preservadas, mesmo não aparecendo no JSON do `PUT`;
- imagens de itens removidos também são apagadas do armazenamento;
- qualquer salvamento limpa `generated_at`, indicando que o catálogo precisa ser gerado novamente;
- o retorno contém o catálogo completo e os IDs definitivos dos itens novos.

### Enviar, substituir ou remover imagem

Um item manual precisa primeiro ser salvo pelo `PUT`, para que receba um `id`. Depois, a imagem pode ser enviada:

```http
POST /api/proposal/{proposal_id}/catalog/items/{item_id}/image
Content-Type: multipart/form-data

image=<arquivo>
```

Formatos aceitos: JPG, JPEG, PNG e WEBP. Tamanho máximo: 5 MB. O campo multipart deve se chamar `image`.

O mesmo endpoint substitui uma imagem existente e remove o arquivo anterior. Para remover sem substituir:

```http
DELETE /api/proposal/{proposal_id}/catalog/items/{item_id}/image
```

As duas operações retornam o item atualizado e limpam `generated_at` do catálogo.

### Gerar e visualizar

```http
POST /api/proposal/{proposal_id}/catalog/generate
```

Marca o instante atual em `generated_at` e retorna o catálogo completo. O backend não gera HTML nem PDF: a página documental, a edição temporária e a impressão são responsabilidades do frontend.

Para carregar a visualização pelo identificador próprio do catálogo:

```http
GET /api/proposal-catalog/{catalog_id}/view
```

Essa rota continua autenticada e devolve o mesmo payload completo. Não existe compartilhamento público nesta implementação.

## Decisões implementadas

- cardinalidade de um catálogo por proposta;
- snapshot da empresa e dos dados do órgão;
- cópia inicial dos itens, sem sincronização automática posterior;
- itens adicionais manuais;
- atualização transacional da lista completa;
- posições únicas e normalizadas;
- exclusão de itens omitidos no salvamento;
- imagens públicas controladas por endpoints autenticados de escrita;
- formatos JPG, JPEG, PNG e WEBP, com limite de 5 MB;
- substituição e remoção do arquivo físico anterior;
- visualização autenticada;
- geração representada por `generated_at`;
- edição visual temporária, impressão e PDF deixados para o navegador/frontend.

## Pontos não confirmados no sistema de referência

Por segurança, nenhuma alteração foi persistida no sistema usado como referência. Continuam sem confirmação externa:

- limites originais de caracteres e imagens;
- persistência do modo **Habilitar edição**;
- quebra de página com muitos produtos;
- possibilidade de vários catálogos por proposta;
- existência de exclusão completa ou compartilhamento público.

As decisões do Licitador para esses pontos estão descritas no contrato acima e têm precedência durante a implementação do frontend.

## Validação do backend

- migration `2026_07_15_000004_create_proposal_catalogs_tables` aplicada com sucesso no MySQL;
- constraints possuem nomes explícitos compatíveis com o limite de identificadores do MySQL;
- suíte automatizada: 26 testes aprovados e 129 asserções;
- cobertura específica para inicialização idempotente, snapshot, itens, ordenação, remoção, segurança, imagens, geração e visualização.

## Prompt para o agente do frontend

```text
Implemente no frontend do Licitador a funcionalidade “Criar catálogo (Beta)” usando como especificação integral o arquivo docs/catalogo-de-propostas.md do backend.

Objetivo:
- adicionar/usar a ação “Criar catálogo (Beta)” na listagem de propostas;
- construir a tela de edição do catálogo;
- construir a visualização documental preparada para impressão/PDF;
- reproduzir o layout e os comportamentos mapeados no documento;
- não alterar o backend nem inventar campos ou endpoints diferentes do contrato.

API autenticada disponível:
- GET /api/proposal/{proposal_id}/catalog: abre e inicializa uma única vez o catálogo;
- PUT /api/proposal/{proposal_id}/catalog: salva metadados e, quando enviado, substitui transacionalmente a lista completa de itens;
- POST /api/proposal/{proposal_id}/catalog/items/{item_id}/image: multipart/form-data com campo image;
- DELETE /api/proposal/{proposal_id}/catalog/items/{item_id}/image: remove a imagem;
- POST /api/proposal/{proposal_id}/catalog/generate: marca a geração e retorna o payload completo;
- GET /api/proposal-catalog/{catalog_id}/view: carrega a visualização autenticada.

Tela de edição:
- cabeçalho com logo e snapshot da empresa;
- campos título, subtítulo e observações gerais;
- campos órgão, estado, número da compra, número do processo, data de recebimento e data de abertura;
- seção “Itens do Catálogo” em cartões ordenados;
- cada item tem imagem, título, especificações, quantidade, unidade e marca;
- ações adicionar item, mover para cima, mover para baixo, remover item e desfazer remoção;
- ao remover, mantenha o cartão marcado localmente e ofereça “Desfazer remoção”; só retire o item do array enviado ao salvar;
- preserve IDs de itens existentes e proposal_item_id dos itens originados da proposta;
- itens manuais novos não têm id nem proposal_item_id até o primeiro PUT;
- envie positions únicas seguindo a ordem visual;
- implemente estados de carregamento, erro, vazio, salvando e sucesso sem bloquear a edição desnecessariamente.

Imagens:
- permita selecionar JPG, JPEG, PNG ou WEBP de até 5 MB;
- mostre preview local imediatamente;
- para item novo, primeiro salve o catálogo, leia o id retornado e depois envie o arquivo;
- para item já persistido, use o endpoint multipart de imagem;
- ao substituir ou remover, atualize a UI com o item retornado;
- use image_url para exibir e “Sem imagem”/“Imagem não disponível” quando for null.

Salvar e gerar:
- “Salvar catálogo” chama PUT com a lista final completa de itens;
- se items for omitido, o backend não altera a lista; se for enviado, itens omitidos são excluídos;
- depois do PUT, substitua o estado local pelo payload retornado para capturar IDs e posições normalizadas;
- uploads pendentes de itens novos devem ocorrer somente após esse retorno;
- “Gerar catálogo” deve garantir que alterações e imagens pendentes foram salvas e então chamar POST generate;
- abra/navegue para a visualização usando o catalog_id retornado.

Visualização:
- documento com título/subtítulo, empresa, dados do órgão, cartões de produtos e observações ao final;
- mostrar imagem ou placeholder, título, especificações, quantidade, unidade e marca;
- ações “Imprimir catálogo”, “Fechar” e “Habilitar edição”;
- “Habilitar edição” deve permitir ajustes temporários no DOM, com contornos tracejados, sem persistir no backend;
- alternar o texto para “Desabilitar edição” quando ativo;
- imprimir com window.print();
- criar CSS @media print que esconda ações/controles, preserve cores e imagens e trate quebras de página entre cartões;
- não implementar geração de PDF no servidor.

Integração e qualidade:
- seguir os padrões atuais do projeto para rotas, serviços HTTP, componentes, formulários, notificações e estilos;
- respeitar autenticação e o envelope { status, message, data, error };
- formatar datas ISO recebidas e enviar datas como YYYY-MM-DD;
- tratar HTTP 400 como validação e HTTP 404 como recurso sem acesso/inexistente;
- manter o layout responsivo, principalmente ações dos cartões e rodapé, sem overflow horizontal;
- não expor image_path; usar somente image_url;
- adicionar testes proporcionais aos padrões existentes e executar lint/build/testes ao final;
- documentar no frontend os arquivos criados, decisões de UI e comandos de validação executados.
```
