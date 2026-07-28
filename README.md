# CostsFix - Plugin GLPI para Pellissari

Plugin para GLPI que automatiza a contabilizacao de custos de tarefas em tickets. Este plugin e uma versao customizada do [Costs](https://github.com/ticgal/costs) da TICGAL, adaptada para as necessidades especificas da Pellissari.

## Compatibilidade

- GLPI >= 11.0 e < 11.9

## Funcionalidades

### Funcionalidades do Plugin Original (Costs)

- **Contabilizacao automatica de custos**: Quando uma tarefa e concluida (estado "Feito"), um custo e automaticamente gerado no ticket.
- **Configuracao por entidade**: Defina custos fixos e por tempo para cada entidade.
- **Heranca de configuracao**: Entidades filhas podem herdar configuracoes da entidade pai.
- **Custos por perfil**: Configure custos diferentes para diferentes perfis de usuario.
- **Ticket faturavel**: Marque tickets como faturaveis ou nao faturaveis.
- **Tarefas privadas**: Opcao para contabilizar ou nao custos de tarefas privadas.

### Customizacoes Pellissari

Este fork inclui as seguintes customizacoes especificas para a Pellissari:

#### 1. Nome do Custo com Perfil e Nome do Usuario

O nome do custo agora segue o formato `[Nome do Perfil] Nome Sobrenome`:

**Exemplo:**
- `[Desenvolvedor] João Carlos`
- `[Suporte N2] Maria Silva`

Este formato e aplicado tanto no campo **nome** quanto na **descricao** do custo, facilitando a identificacao do responsavel pelo trabalho.

#### 2. Calculo do Tempo no Campo cost_time

Para compatibilidade com o sistema de faturamento da Pellissari, o tempo da tarefa (em minutos) e colocado no campo `cost_time`:

**Exemplo:**
- Tarefa de 2h40 (9600 segundos) -> `cost_time = 160.0` (minutos)

**Nota:** Esta alteracao foi necessaria pois o sistema de faturamento atual utiliza o campo `cost_time` para calcular o valor do servico baseado no tempo trabalhado.

#### 3. Estrutura de Arquivos

Os arquivos do plugin estao na raiz do repositorio (e nao em uma subpasta), seguindo o padrao de instalacao de plugins GLPI:

```
costsfix/
├── front/
├── inc/
├── locales/
├── templates/
├── hook.php
├── setup.php
└── README.md
```

## Instalacao

1. Baixe a release mais recente do plugin (arquivo `costsfix.tar.gz`)
2. Extraia o conteudo na pasta `plugins/` da sua instalacao GLPI:
   ```bash
   cd /var/www/glpi/plugins
   tar -xzf costsfix.tar.gz
   ```
3. Acesse a area de administracao do GLPI
4. Va em **Configurar > Plugins**
5. Localize o plugin **CostsFix - Pellissari** e clique em **Instalar**
6. Apos a instalacao, clique em **Ativar**

## Configuracao

### Configuracao Global

1. Va em **Configurar > Geral > CostsFix**
2. Configure se deseja adicionar a descricao da tarefa no comentario do custo

### Configuracao por Entidade

1. Va em **Administracao > Entidades**
2. Selecione a entidade desejada
3. Clique na aba **CostsFix**
4. Configure:
   - **Custo fixo**: Valor fixo adicionado a cada custo
   - **Custo por tempo**: Valor por unidade de tempo (usado no campo cost_time)
   - **Tarefa privada**: Se tarefas privadas devem gerar custos
   - **Ticket faturavel automatico**: Se novos tickets devem ser marcados como faturaveis por padrao

### Configuracao por Perfil

Na mesma aba de configuracao da entidade, voce pode associar custos diferentes para perfis especificos, permitindo que diferentes tipos de profissionais tenham valores de hora diferentes.

## Como Funciona

1. Um tecnico cria uma tarefa em um ticket faturavel
2. O tecnico marca a tarefa como "Feito"
3. O plugin automaticamente:
   - Cria um registro de custo no ticket
   - Preenche o nome do custo com `[Perfil] Nome Sobrenome` do tecnico
   - Calcula o `cost_time` baseado no tempo da tarefa (em minutos)
   - Aplica o custo fixo configurado

## Diferenca do Plugin Original

| Funcionalidade | Costs (Original) | CostsFix (Pellissari) |
|----------------|------------------|----------------------|
| Nome do custo | `task_id_user_id` | `[Perfil] Nome Sobrenome` |
| Campo cost_time | Valor configurado por entidade | Tempo da tarefa em minutos |
| Descricao do custo | Descricao da tarefa | `[Perfil] Nome Sobrenome` ou descricao |

## Creditos

- **Plugin Original**: [Costs](https://github.com/ticgal/costs) por [TICGAL](https://tic.gal)
- **Customizacao Pellissari**: [Ampris](https://github.com/O-Ampris)

## Licenca

Este plugin e distribuido sob a licenca GPLv3+. Veja o arquivo LICENSE para mais detalhes.

---

**Versao**: 4.0.0
**GLPI**: 11.0+
