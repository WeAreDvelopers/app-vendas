# 📘 Guia de Uso - Sistema Multi-Empresa

## 🎯 Visão Geral

Este sistema permite gerenciar múltiplas empresas de forma isolada, cada uma com suas próprias:
- Importações e produtos
- Fornecedores
- Integrações (Mercado Livre, etc)
- Pedidos e publicações

---

## 🏢 Gerenciamento de Empresas

### Acessar Empresas
**Menu:** Sistema → Empresas

Ou acesse diretamente: `/panel/companies`

### Criar Nova Empresa

1. Acesse "Empresas"
2. Clique em "Nova Empresa"
3. Preencha:
   - **Nome** (obrigatório)
   - CNPJ/CPF (opcional)
   - Email (opcional)
   - Telefone (opcional)
4. Clique em "Criar Empresa"

✅ **Você será automaticamente:**
- Administrador da empresa
- A empresa será selecionada como ativa

### Trocar de Empresa

**Opção 1: Topbar (Rápido)**
1. Clique no seletor "🏢 Empresa Atual"
2. Selecione a empresa desejada
3. Pronto! Todos dados são filtrados automaticamente

**Opção 2: Lista de Empresas**
1. Acesse "Empresas"
2. Clique em "Trocar" no card da empresa
3. Confirme a troca

### Editar Empresa

1. Acesse "Empresas"
2. Clique em "Configurar" (apenas admins)
3. Altere os dados
4. Clique em "Salvar Alterações"

**Na tela de edição você verá:**
- Estatísticas (importações, produtos, fornecedores)
- Status das integrações
- Lista de usuários com acesso

---

## 🔌 Integrações

### Acessar Integrações
**Menu:** Sistema → Integrações

Ou acesse: `/panel/integrations`

### Conectar Mercado Livre

1. Acesse "Integrações"
2. No card do **Mercado Livre**, clique em "Conectar"
3. Você será redirecionado para o Mercado Livre
4. **Faça login** com a conta que deseja conectar
5. **Autorize** o aplicativo
6. Pronto! Você será redirecionado de volta

✅ **Confirmação:**
- Badge "Conectado" aparece
- Mostra o nickname da conta
- Data de conexão e expiração

### Desconectar Mercado Livre

1. Acesse "Integrações"
2. No card do Mercado Livre, clique "Desconectar"
3. Confirme a ação

⚠️ **Importante:** Isso não afeta outras empresas!

### Reconectar / Trocar Conta

1. Acesse "Integrações"
2. Clique em "Reconectar"
3. Faça login com a **nova conta** do ML
4. Autorize novamente

✅ **A antiga conta será substituída pela nova**

---

## 📦 Fluxo de Trabalho

### 1. Importar Produtos

1. Selecione a empresa desejada (topbar)
2. Acesse "Importações"
3. Faça upload do arquivo
4. Produtos serão vinculados à empresa atual

### 2. Processar com IA ou Converter

**Com IA:**
- Selecione produtos
- Clique "Processar com IA"
- Aguarde o processamento

**Sem IA (Rápido):**
- Selecione produtos
- Clique "Converter sem IA"
- Conversão imediata!

### 3. Ver Produtos

1. Acesse "Produtos"
2. Você verá apenas produtos **da empresa atual**
3. Troque de empresa para ver outros produtos

### 4. Publicar no Mercado Livre

1. Certifique-se que o ML está conectado
2. Acesse o produto
3. Configure e publique

✅ **Será publicado na conta ML da empresa atual**

---

## 🔒 Permissões

### Tipos de Usuário

**Administrador:**
- ✅ Editar dados da empresa
- ✅ Conectar/desconectar integrações
- ✅ Ver todas estatísticas
- ✅ Gerenciar produtos

**Colaborador:**
- ✅ Ver produtos
- ✅ Importar produtos
- ❌ Não pode editar empresa
- ❌ Não pode gerenciar integrações

### Verificar se é Admin

No card da empresa, aparece badge:
```
🛡️ Admin
```

---

## 💻 Para Desenvolvedores

### Helpers Globais Disponíveis

```php
// Empresa atual
$company = currentCompany();
$companyId = currentCompanyId();

// Mercado Livre
$mlIntegration = mlIntegration();
$isConnected = mlConnected();
$accessToken = mlAccessToken(); // Renova automaticamente!
$userId = mlUserId();
$nickname = mlNickname();

// Permissões
$isAdmin = isCompanyAdmin();
```

### Usar em Controllers

```php
use function currentCompanyId;

public function index() {
    $products = Product::where('company_id', currentCompanyId())->get();
}
```

### Usar em Queries

```php
// Automático com helper
$imports = DB::table('supplier_imports')
    ->where('company_id', currentCompanyId())
    ->get();
```

### Criar Registros

```php
// Sempre adicione company_id
Product::create([
    'company_id' => currentCompanyId(),
    'name' => 'Produto',
    // ... outros campos
]);
```

### Usar API do Mercado Livre

```php
use function mlAccessToken;
use Illuminate\Support\Facades\Http;

// O token é renovado automaticamente!
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . mlAccessToken()
])->get('https://api.mercadolibre.com/users/me');
```

---

## ❓ Perguntas Frequentes

### Como adicionar outro usuário à empresa?

Atualmente via tinker:
```bash
php artisan tinker
> $company = App\Models\Company::find(1);
> $user = App\Models\User::find(2);
> $company->users()->attach($user->id, ['is_admin' => false]);
```

### Posso ter várias contas ML na mesma empresa?

Não. Cada empresa tem apenas 1 conta ML conectada por vez.
Para trocar, use "Reconectar".

### O que acontece se o token ML expirar?

✅ **Renovação automática!**
O helper `mlAccessToken()` verifica e renova automaticamente.

### Como ver dados de outra empresa?

1. Troque de empresa usando o seletor no topbar
2. Todos dados são filtrados automaticamente
3. Não é possível ver dados de outras empresas sem trocar

### Posso deletar uma empresa?

Atualmente não há UI para isso. Apenas via código:
```php
$company->delete(); // Deleta em cascata
```

⚠️ **Cuidado:** Isso deleta todos produtos, importações, etc!

---

## 🎨 Funcionalidades da Interface

### Topbar - Seletor de Empresa

**Quando tem 1 empresa:**
```
🏢 Empresa Padrão
```

**Quando tem várias:**
```
🏢 Empresa Atual ▼
```

Clique para ver dropdown com todas empresas.

### Tela de Empresas

**Cards mostram:**
- Nome da empresa
- Badge "Empresa Atual" ou "Disponível"
- Badge "Admin" se você for administrador
- Dados cadastrais (CNPJ, email, telefone)
- Botões de ação (Trocar, Configurar)

### Tela de Integrações

**Cards para cada plataforma:**
- 🛒 Mercado Livre (Funcional)
- 🛍️ Shopee (Em breve)
- 📦 Amazon (Em breve)
- 🔌 Outras

**Card ML quando conectado:**
- Badge verde "Conectado"
- Nome da conta (@nickname)
- Data de conexão
- Botões: Desconectar, Reconectar

---

## 📊 Dados Isolados

### O que cada empresa vê:

✅ **Isolado por empresa:**
- Importações
- Produtos
- Fornecedores
- Listings ML
- Pedidos
- Integrações (tokens ML diferentes)

❌ **Compartilhado:**
- Usuários (podem acessar várias empresas)
- Configurações globais do sistema

---

## 🚀 Casos de Uso

### Caso 1: Duas Lojas Diferentes

```
Empresa 1: Loja de Eletrônicos
└─ Conta ML: @eletronicos123
└─ Produtos: 500 eletrônicos

Empresa 2: Loja de Roupas
└─ Conta ML: @modafashion
└─ Produtos: 200 roupas
```

**Benefício:** Gestão separada, contas ML diferentes

### Caso 2: Matriz e Filial

```
Empresa 1: Matriz SP
└─ Conta ML: @empresasp

Empresa 2: Filial RJ
└─ Conta ML: @empresarj
```

**Benefício:** Cada filial gerencia seus produtos

### Caso 3: Marcas Diferentes

```
Empresa 1: Marca Premium
└─ Produtos de alta qualidade
└─ Conta ML oficial

Empresa 2: Marca Popular
└─ Produtos populares
└─ Outra conta ML
```

**Benefício:** Separação de marcas e públicos

---

## ✅ Checklist de Uso Diário

**Ao começar o dia:**
- [ ] Verificar empresa atual (topbar)
- [ ] Trocar se necessário
- [ ] Verificar integrações ativas

**Ao importar produtos:**
- [ ] Confirmar empresa atual
- [ ] Fazer upload
- [ ] Processar ou converter

**Ao publicar:**
- [ ] Verificar empresa atual
- [ ] Confirmar conta ML conectada
- [ ] Publicar produtos

---

## 🔧 Troubleshooting

### "Token expirado" no ML

✅ **Solução:** Use `mlAccessToken()` - renova automaticamente

### Não vejo meus produtos

✅ **Solução:** Verifique se está na empresa correta (topbar)

### Erro ao conectar ML

✅ **Soluções:**
1. Verifique se ML_APP_ID e ML_CLIENT_SECRET estão no .env
2. Certifique-se que a URL de callback está correta
3. Tente novamente

### Não consigo editar empresa

✅ **Solução:** Você precisa ser administrador da empresa

---

## 📝 Resumo Rápido

1. **Uma empresa** = Um conjunto isolado de dados
2. **Troca rápida** = Dropdown no topbar
3. **Integrações separadas** = Cada empresa tem sua conta ML
4. **Helpers prontos** = Use `mlAccessToken()`, `currentCompanyId()`, etc
5. **Renovação automática** = Tokens ML renovam sozinhos
6. **Isolamento total** = Dados não se misturam entre empresas

---

## 🎓 Próximos Passos

1. Crie sua segunda empresa para testar
2. Conecte diferentes contas do Mercado Livre
3. Importe produtos em cada empresa
4. Teste a troca entre empresas
5. Explore as estatísticas na tela de edição

**Documentação técnica completa:** `IMPLEMENTACAO_COMPLETA.md`

---

✅ **Sistema pronto para uso em produção!**
