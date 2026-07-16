# Acompanhamento de propostas

## Objetivo

Este documento registra o funcionamento observado na tela de acompanhamento de propostas do sistema **Localizador de Editais** e transforma a referência visual em requisitos para uma futura implementação no Licitador.

> **Status no Licitador:** backend implementado. Este arquivo também é o contrato de integração para a implementação do frontend.

A análise foi realizada em 15/07/2026, em uma sessão autenticada fornecida pelo usuário, a partir da listagem **Minhas Propostas** e da ação **Acompanhamento**.

URL observada:

```text
https://painel.localizadordeeditais.com.br/wp-admin/admin-ajax.php?action=gpl_valor_minimo&id={proposal_id}
```

O parâmetro `id` identifica a proposta que será acompanhada.

## Fluxo de acesso

1. O usuário acessa a página **Minhas Propostas**.
2. A página apresenta pesquisa textual, filtro por empresa e uma tabela paginada de propostas.
3. Cada proposta possui as ações:
   - Visualizar;
   - Editar;
   - Excluir;
   - Acompanhamento;
   - Criar catálogo (Beta).
4. Ao selecionar **Acompanhamento**, uma nova aba é aberta com o acompanhamento da proposta selecionada.

## Estrutura da tela de acompanhamento

### Cabeçalho

O topo funciona como cabeçalho de um documento comercial e contém:

- logotipo da plataforma;
- razão social da empresa participante;
- endereço da empresa;
- CNPJ;
- telefone;
- e-mail.

Os dados pertencem à empresa vinculada à proposta e não são editados diretamente nessa tela.

### Ações fixas

No canto superior direito existe um bloco flutuante, que permanece disponível durante a rolagem, com quatro ações:

- **Salvar**: persiste preços mínimos e classificações registradas;
- **Imprimir**: abre o fluxo de impressão do acompanhamento/documento;
- **Exportar Excel**: gera uma planilha com os dados do acompanhamento;
- **Fechar**: fecha a tela e retorna ao contexto anterior.

As ações de salvar, imprimir e exportar foram identificadas visualmente, mas não foram executadas para evitar alterações ou geração de artefatos no sistema de referência.

### Identificação da licitação

Abaixo do título **Acompanhamento de Licitação**, a tela apresenta uma linha resumida contendo:

- órgão;
- estado (UF);
- número da compra;
- número do processo.

Essas informações vêm da licitação associada à proposta.

## Regras de preço mínimo

A tela diferencia os valores utilizando uma legenda por cores:

- **Preço unitário mínimo**: campo editável;
- **Valor total mínimo**: valor calculado;
- **Vencedor**: classificação positiva do item;
- **Perdeu**: classificação negativa do item.

### Desconto em lote

Existe um controle **Aplicar desconto**, composto por:

- campo numérico percentual;
- indicação de `%`;
- botão **Aplicar em todos**;
- texto auxiliar com exemplo do cálculo do preço unitário.

O objetivo é calcular e preencher o preço unitário mínimo de todos os itens usando um percentual de desconto sobre o preço unitário da proposta.

Regra funcional esperada:

```text
preco_unitario_minimo = preco_unitario_proposta * (1 - percentual_desconto / 100)
valor_total_minimo = preco_unitario_minimo * quantidade
```

O preço mínimo continua editável individualmente depois da aplicação em lote.

## Grade de itens

Cada item da proposta é exibido em uma tabela com as seguintes colunas:

| Coluna | Comportamento observado |
|---|---|
| Item | Código ou número do item na licitação |
| Resultado | Controles Vencedor e Perdeu |
| Qtd | Quantidade proposta |
| Unid. | Unidade de fornecimento |
| Especificação | Descrição completa do produto ou serviço |
| Marca | Marca informada na proposta |
| Preço Unit. | Preço unitário original da proposta |
| Preço Unit. Min. | Campo editável para registrar o menor preço aceitável |
| Valor Total | Quantidade multiplicada pelo preço unitário original |
| Valor Total Min. | Quantidade multiplicada pelo preço unitário mínimo |

No rodapé da tabela existe uma linha **Total geral**, contendo pelo menos:

- soma dos valores totais originais;
- soma dos valores totais mínimos.

### Classificação do item

Para cada item o usuário pode registrar um resultado:

- **Vencedor**;
- **Perdeu**;
- sem classificação.

Visualmente, os controles são apresentados como seleções independentes próximas ao item. Para nossa implementação, devem funcionar como estados mutuamente exclusivos: um item não pode estar simultaneamente como vencedor e perdido.

Estado sugerido para o backend:

```text
pending | won | lost
```

Ao clicar em **Vencedor** ou **Perdeu**, o sistema de referência expande abaixo do item o formulário **Classificação do Item**, com três colocações:

| Posição | Campos |
|---|---|
| 1º lugar | Empresa, Marca e Preço |
| 2º lugar | Empresa, Marca e Preço |
| 3º lugar | Empresa, Marca e Preço |

No sistema de referência, ao marcar Vencedor, o primeiro lugar é preenchido automaticamente com a empresa da própria proposta. No Licitador, esse preenchimento automático é responsabilidade do frontend. O backend apenas valida e persiste a classificação recebida.

## Relatório de itens ganhos

Logo após a tabela existe uma área verde intitulada **Relatório de Itens Ganhos**.

Quando nenhum item foi classificado como vencedor, a seção apresenta a mensagem:

```text
Nenhum item marcado como vencedor ainda.
```

A posição e a mensagem indicam que essa seção é atualizada a partir da classificação dos itens e deve reunir somente os itens vencedores. A composição detalhada do relatório com itens vencedores não foi validada, pois não foram salvas alterações na proposta de referência.

Para o Licitador, o relatório deve apresentar:

- item;
- descrição;
- quantidade;
- unidade;
- marca;
- preço unitário final;
- valor total final;
- total geral ganho.

## Rodapé documental

Depois do relatório, a tela assume formato de documento comercial e apresenta:

- validade da proposta, indicada como de acordo com o edital;
- bloco de declarações do fornecedor;
- confirmação de ciência do instrumento convocatório;
- concordância com exigências, condições e solicitações do edital;
- declaração de que o preço inclui custos diretos e indiretos;
- declaração sobre critérios de julgamento, medição e pagamento;
- indicação de validade da proposta e forma de pagamento;
- dados bancários para pagamento;
- identificação e assinatura da empresa;
- cidade e data de emissão.

Os dados bancários e cadastrais são carregados da empresa vinculada à proposta. Informações sensíveis não foram reproduzidas neste documento.

## Comportamento esperado no Licitador

### Criação do acompanhamento

- Cada proposta deve possuir no máximo um acompanhamento ativo.
- O acompanhamento deve reutilizar os itens e valores da proposta sem duplicar ou alterar o orçamento original.
- Na primeira abertura, todos os itens devem começar com resultado `pending`.
- O preço unitário mínimo pode iniciar vazio ou com o preço original, conforme decisão de produto.

### Edição

O usuário deve poder:

- informar um percentual e aplicá-lo a todos os itens;
- editar o preço unitário mínimo de um item;
- marcar o item como vencedor;
- marcar o item como perdido;
- remover a classificação, retornando o item para pendente;
- salvar o acompanhamento e retomá-lo posteriormente.

Alterações no acompanhamento não devem modificar os itens e preços originais da proposta.

### Cálculos

- Os totais mínimos devem ser recalculados quando o preço mínimo for alterado.
- Valores monetários devem ser calculados no backend com precisão decimal.
- O total geral ganho deve considerar apenas itens vencedores.
- A aplicação de desconto deve ocorrer sobre o preço unitário original, evitando descontos cumulativos a cada clique.

### Permissões

- Somente usuários autorizados da empresa proprietária da proposta podem consultar ou editar o acompanhamento.
- Visualização, edição, impressão e exportação devem respeitar as mesmas regras de acesso da proposta.
- O backend não deve confiar apenas no identificador recebido na URL.

## Dados necessários

O acompanhamento precisa registrar, no mínimo:

### Acompanhamento

- proposta;
- percentual de desconto aplicado, quando houver;
- validade da proposta;
- observações ou declarações, caso sejam futuramente editáveis;
- usuário responsável pela última alteração;
- datas de criação e atualização.

### Itens acompanhados

- item da proposta;
- resultado (`pending`, `won` ou `lost`);
- preço unitário mínimo;
- valor total mínimo calculado;
- data da classificação;
- usuário responsável pela classificação.

### Colocações do item

- item acompanhado;
- posição entre `1` e `3`;
- nome da empresa;
- marca;
- preço ofertado.

Cada posição pode aparecer apenas uma vez por item. A classificação é uma lista independente para cada item da proposta.

Os dados descritivos do item, quantidade, unidade, marca e preço original devem continuar vindo do item da proposta.

## Endpoints implementados

Todos os endpoints abaixo exigem a mesma autenticação JWT das demais rotas protegidas da API.

```text
GET  /api/proposal/{proposal_id}/tracking
PUT  /api/proposal/{proposal_id}/tracking
POST /api/proposal/{proposal_id}/tracking/apply-discount
POST /api/proposal/{proposal_id}/tracking/finish
POST /api/proposal/{proposal_id}/tracking/reopen
GET  /api/proposal/{proposal_id}/tracking/export
GET  /api/proposal/{proposal_id}/tracking/print
```

O salvamento é feito em lote e ocorre dentro de uma transação.

## Layout de referência

A tela observada foi construída como uma folha/documento centralizado:

- fundo externo cinza-claro;
- conteúdo principal branco;
- cabeçalho corporativo no topo;
- título centralizado;
- dados da licitação em uma faixa;
- legenda e desconto antes da tabela;
- tabela compacta com cabeçalho escuro;
- campos editáveis destacados em amarelo;
- valores calculados destacados em verde-claro;
- relatório de ganhos em um painel verde;
- declarações e assinatura na parte inferior;
- botões de ação coloridos em um bloco flutuante à direita.

Em telas menores, a tabela deve permitir rolagem horizontal. A impressão deve esconder controles interativos e preservar apenas o documento.

## Decisões adotadas no backend

1. O preço mínimo começa vazio (`null`).
2. Os resultados possíveis são `pending`, `won` e `lost`.
3. Tanto `won` quanto `lost` exigem a classificação completa do item, com uma a três posições.
4. Declarações, assinatura e dados da empresa são somente leitura no acompanhamento e vêm da proposta.
5. O backend fornece payload específico de impressão e exportação CSV compatível com Excel.
6. São registrados usuário e data da classificação, além do usuário da última alteração geral.
7. A autorização atual continua vinculada ao proprietário da proposta (`user_id`).
8. O acompanhamento pode ser finalizado. Depois disso fica somente leitura até ser explicitamente reaberto.

## Pontos que ainda precisam de decisão de produto

Antes da implementação, devem ser confirmados:

1. O histórico completo de todas as alterações precisa ser auditável ou os campos atuais de usuário/data são suficientes?
2. Usuários diferentes poderão pertencer à mesma empresa e compartilhar acompanhamentos?
3. A exportação precisa evoluir de CSV compatível com Excel para `.xlsx` nativo?
4. A impressão será feita diretamente pelo navegador ou futuramente deverá existir um PDF gerado pelo backend?

## Contrato de integração com o frontend

Esta seção descreve o comportamento efetivamente implementado. Em caso de divergência com as seções de referência visual, esta seção deve prevalecer para a integração.

### Envelope JSON

As respostas JSON seguem o padrão:

```json
{
  "status": true,
  "message": null,
  "data": {},
  "error": null
}
```

Em erros de validação, `error` pode ser um objeto contendo mensagens por campo. Nos demais erros, será uma string.

### Abrir ou consultar acompanhamento

```http
GET /api/proposal/{proposal_id}/tracking
```

Na primeira chamada, o backend cria automaticamente um acompanhamento com status `open` e um registro `pending` para cada item atual da proposta. Chamadas posteriores retornam o mesmo acompanhamento.

Se novos itens forem adicionados à proposta, a próxima consulta adiciona os registros de acompanhamento que estiverem faltando. Os valores originais da proposta não são modificados.

Resposta resumida:

```json
{
  "status": true,
  "data": {
    "proposal": {
      "id": 7505,
      "company_id": 10,
      "tender_id": 20,
      "total_value": "1281803.19"
    },
    "company": {
      "corporate_reason": "Empresa exemplo",
      "cnpj": "00.000.000/0000-00"
    },
    "tender": {
      "organ_name": "Órgão exemplo",
      "uf": "BA",
      "number_purchase": "234",
      "process": "1232321"
    },
    "tracking": {
      "id": 1,
      "status": "open",
      "discount_percentage": null,
      "last_updated_by": 5,
      "finished_at": null,
      "created_at": "2026-07-15T12:00:00.000000Z",
      "updated_at": "2026-07-15T12:00:00.000000Z"
    },
    "items": [
      {
        "proposal_item_id": 15,
        "item": "1",
        "quantity": "2.0000",
        "unit": "UN",
        "specification": "Descrição do item",
        "brand": "Marca",
        "unit_price": "100.0000",
        "total_value": "200.00",
        "result": "pending",
        "minimum_unit_price": null,
        "minimum_total_value": null,
        "rankings": [],
        "classified_at": null,
        "classified_by": null
      }
    ],
    "totals": {
      "original": "200.00",
      "minimum": "0.00",
      "won": "0.00"
    },
    "won_items": [],
    "declarations": "Texto da proposta",
    "signature": {
      "responsible_name": "Responsável",
      "responsible_rg": "...",
      "responsible_cpf": "...",
      "city": "Cidade",
      "proposal_date": "2026-07-15"
    }
  }
}
```

Todos os valores decimais são retornados como **strings**. O frontend deve preservá-los como valores decimais e formatá-los para moeda apenas na apresentação.

### Salvar alterações

```http
PUT /api/proposal/{proposal_id}/tracking
Content-Type: application/json
```

É possível enviar somente os campos e itens alterados:

```json
{
  "discount_percentage": "10.0000",
  "items": [
    {
      "proposal_item_id": 15,
      "result": "won",
      "minimum_unit_price": "90.0000",
      "rankings": [
        {
          "position": 1,
          "company": "Empresa da proposta",
          "brand": "Marca A",
          "price": "90.0000"
        },
        {
          "position": 2,
          "company": "Empresa concorrente",
          "brand": "Marca B",
          "price": "95.0000"
        }
      ]
    },
    {
      "proposal_item_id": 16,
      "result": "lost",
      "minimum_unit_price": "45.5000",
      "rankings": [
        {
          "position": 1,
          "company": "Empresa concorrente",
          "brand": "Marca C",
          "price": "40.0000"
        },
        {
          "position": 3,
          "company": "Empresa da proposta",
          "brand": "Marca A",
          "price": "45.5000"
        }
      ]
    }
  ]
}
```

Regras:

- `discount_percentage` é opcional, aceita `null` ou valor entre `0` e `100`;
- `items` é opcional;
- `proposal_item_id` é obrigatório em cada item enviado;
- `result` aceita somente `pending`, `won` ou `lost`;
- `minimum_unit_price` aceita `null` ou número maior ou igual a zero;
- `rankings` é opcional em atualizações parciais, mas, quando enviado, substitui integralmente as colocações anteriores do item;
- `rankings` aceita no máximo três registros;
- `position` é obrigatória, aceita apenas `1`, `2` ou `3` e não pode se repetir dentro do mesmo item;
- `company` é obrigatória e aceita até 255 caracteres;
- `brand` é opcional e aceita até 255 caracteres;
- `price` é obrigatório e deve ser maior ou igual a zero;
- ao usar `pending`, o backend limpa data, usuário e todas as colocações automaticamente;
- `won` e `lost` exigem ao menos uma colocação salva;
- não é necessário preencher as três posições, mas cada posição enviada deve estar completa;
- itens não enviados permanecem inalterados;
- item pertencente a outra proposta é rejeitado;
- os totais da resposta são sempre recalculados pelo backend.

A resposta possui o mesmo formato completo do `GET`.

### Aplicar desconto em todos os itens

```http
POST /api/proposal/{proposal_id}/tracking/apply-discount
Content-Type: application/json
```

```json
{
  "discount_percentage": "10.0000"
}
```

O percentual é obrigatório e deve estar entre `0` e `100`.

O backend calcula novamente todos os preços mínimos usando sempre o preço unitário original:

```text
minimum_unit_price = unit_price * (1 - discount_percentage / 100)
```

Portanto, aplicar `10%` e depois `20%` resulta em `20%` sobre o preço original, e não em descontos cumulativos. A resposta já contém todos os itens e totais recalculados.

O frontend pode simular o cálculo antes da confirmação, mas deve substituir seu estado pelos valores devolvidos pelo backend após a requisição.

### Classificação dos itens

Mapeamento recomendado para os controles:

| Estado | Interface | Efeito |
|---|---|---|
| `pending` | Sem classificação | Limpa usuário, data e todas as colocações |
| `won` | Vencedor | Abre classificação, inclui o item em `won_items` e no total `won` |
| `lost` | Perdeu | Abre o mesmo formulário de classificação |

Os controles Vencedor e Perdeu devem ser mutuamente exclusivos. Não usar dois booleanos independentes no estado do frontend; usar diretamente o campo `result`.

O frontend deve montar o array `rankings`. Ao selecionar Vencedor, deve preencher localmente a primeira posição com:

- `company`: razão social disponível em `data.company`;
- `brand`: marca do item;
- `price`: preço definido pelo fluxo da interface, normalmente o preço final ou mínimo do próprio item.

O backend não identifica nem insere automaticamente a empresa da proposta em nenhuma colocação.

### Finalizar

```http
POST /api/proposal/{proposal_id}/tracking/finish
```

Altera o status para `finished` e preenche `finished_at`. A partir desse momento:

- consultas, impressão e exportação continuam permitidas;
- atualização e aplicação de desconto retornam HTTP `409`;
- o frontend deve desabilitar os campos e o botão Salvar.

### Reabrir

```http
POST /api/proposal/{proposal_id}/tracking/reopen
```

Altera o status para `open`, limpa `finished_at` e libera novamente a edição.

### Dados para impressão

```http
GET /api/proposal/{proposal_id}/tracking/print
```

Retorna o mesmo payload completo do acompanhamento, acrescido de:

```json
{
  "document": {
    "title": "Acompanhamento de Licitação",
    "generated_at": "2026-07-15T12:00:00.000000Z",
    "print_css_hint": "Ocultar controles interativos e imprimir o conteúdo em formato A4."
  }
}
```

Esse endpoint **não retorna PDF**. O frontend deve montar a visualização de impressão e usar a impressão do navegador. Na mídia `print`, deve ocultar botões, campos e controles de classificação.

### Exportar

```http
GET /api/proposal/{proposal_id}/tracking/export
```

Retorna download com:

```text
Content-Type: text/csv; charset=UTF-8
Content-Disposition: attachment; filename="acompanhamento-proposta-{proposal_id}.csv"
```

O arquivo usa:

- UTF-8 com BOM;
- separador `;`;
- uma linha por item;
- colunas de resultado, valores originais e mínimos;
- empresa, marca e preço do primeiro, segundo e terceiro lugares;
- totais original, mínimo e ganho ao final.

No frontend, o download deve ser tratado como `blob`. Embora abra normalmente no Excel, o formato entregue atualmente é `.csv`, não `.xlsx`.

### Códigos HTTP relevantes

| Código | Situação |
|---|---|
| `200` | Operação concluída |
| `400` | Payload inválido, item de outra proposta ou regra de validação violada |
| `404` | Proposta inexistente ou pertencente a outro usuário |
| `409` | Tentativa de editar um acompanhamento finalizado |

### Estado recomendado no frontend

O frontend deve manter:

```ts
type TrackingResult = 'pending' | 'won' | 'lost'
type TrackingStatus = 'open' | 'finished'

interface TrackingItem {
  proposal_item_id: number
  item: string | null
  quantity: string | null
  unit: string | null
  specification: string | null
  brand: string | null
  unit_price: string | null
  total_value: string | null
  result: TrackingResult
  minimum_unit_price: string | null
  minimum_total_value: string | null
  rankings: TrackingRanking[]
  classified_at: string | null
  classified_by: number | null
}

interface TrackingRanking {
  id?: number
  position: 1 | 2 | 3
  company: string
  brand: string | null
  price: string
}
```

Recomendações de integração:

- carregar a tela sempre pelo `GET tracking`;
- usar `proposal_item_id` como chave estável da linha;
- manter as colocações isoladas por `proposal_item_id`;
- ao abrir Vencedor ou Perdeu, editar uma cópia local das colocações e enviá-la em `rankings` ao salvar;
- ao marcar Vencedor, preencher o primeiro lugar no frontend antes de abrir ou exibir o formulário;
- não enviar linhas vazias da segunda ou terceira posição;
- enviar apenas itens alterados no `PUT` ou enviar todas as linhas, pois ambos são aceitos;
- substituir os itens e totais locais pela resposta de cada operação;
- não calcular totais definitivos apenas no navegador;
- exibir confirmação antes de finalizar;
- oferecer Reabrir somente quando o status for `finished`;
- apresentar erros por campo quando `error` for um objeto;
- tratar `404` como recurso indisponível e `409` como estado somente leitura.

## Arquivos do backend relacionados

```text
app/Enums/ProposalTrackingStatus.php
app/Enums/ProposalTrackingItemResult.php
app/Models/ProposalTracking.php
app/Models/ProposalTrackingItem.php
app/Models/ProposalTrackingItemRanking.php
app/Services/Proposal/ProposalTrackingService.php
app/Http/Controllers/ProposalTrackingController.php
database/migrations/2026_07_15_000002_create_proposal_trackings_tables.php
database/migrations/2026_07_15_000003_replace_tracking_classification_position_with_rankings.php
routes/api.php
tests/Feature/ProposalTest.php
```

## Limites desta análise

- Nenhuma alteração foi salva no sistema de referência.
- As ações Imprimir e Exportar Excel não foram executadas.
- O conteúdo exato do relatório com itens vencedores não foi validado.
- Regras internas de arredondamento, persistência e autorização não podem ser determinadas apenas pela interface e deverão ser definidas no Licitador.

Esses limites dizem respeito somente ao sistema usado como referência visual. O comportamento efetivamente implementado no Licitador está definido na seção **Contrato de integração com o frontend**.
