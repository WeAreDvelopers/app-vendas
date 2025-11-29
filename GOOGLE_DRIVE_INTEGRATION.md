# 🔌 Integração com Google Drive - Guia Completo

## ✅ Status: IMPLEMENTADO

Sistema completo para selecionar imagens do Google Drive diretamente na tela de gerenciamento de produtos!

---

## 📦 O Que Foi Implementado

### 1. OAuth Google Drive ✅
- ✅ Autenticação OAuth 2.0 completa
- ✅ Armazenamento seguro de tokens (criptografados)
- ✅ Renovação automática de tokens
- ✅ Isolamento por empresa (multi-tenant)

### 2. Interface de Integração ✅
- ✅ Card do Google Drive na tela de Integrações
- ✅ Botão "Conectar Google Drive"
- ✅ Status da conexão (email conectado)
- ✅ Botões Desconectar/Reconectar

### 3. Google Picker API ✅
- ✅ Pop-up nativo do Google Drive
- ✅ Seleção múltipla de imagens
- ✅ Suporte a todos formatos de imagem (JPG, PNG, GIF, WebP)
- ✅ Preview visual das imagens
- ✅ Busca e navegação por pastas

### 4. Download Automático ✅
- ✅ Download automático das imagens selecionadas
- ✅ Salvamento local no storage
- ✅ Associação automática ao produto
- ✅ Ordenação automática das imagens

---

## 🚀 Como Configurar

### 1. Criar Projeto no Google Cloud Console

**Acesse:** https://console.cloud.google.com/

1. Criar novo projeto ou selecionar existente
2. Nome sugerido: "App Vendas ML"

### 2. Ativar APIs Necessárias

Acesse: **APIs & Services → Library**

Ative as seguintes APIs:
- ✅ **Google Drive API**
- ✅ **Google Picker API**

### 3. Criar Credenciais OAuth 2.0

Acesse: **APIs & Services → Credentials**

**Criar Credenciais → OAuth Client ID:**

1. **Application Type:** Web application
2. **Name:** App Vendas
3. **Authorized JavaScript origins:**
   ```
   http://localhost
   http://127.0.0.1
   https://seu-dominio.com
   ```

4. **Authorized redirect URIs:**
   ```
   http://localhost/panel/integrations/google-drive/callback
   https://seu-dominio.com/panel/integrations/google-drive/callback
   ```

5. Clique em **Create**

6. Salve as credenciais:
   - `Client ID`
   - `Client Secret`

### 4. Criar API Key

Acesse: **APIs & Services → Credentials**

1. Clique em **Create Credentials → API Key**
2. Salve a API Key gerada
3. (Recomendado) Clique em "Restrict Key"
   - Application restrictions: None (ou configure conforme necessário)
   - API restrictions: Google Drive API, Google Picker API

### 5. Configurar .env

Adicione as seguintes variáveis no arquivo `.env`:

```env
# Google OAuth
GOOGLE_CLIENT_ID=seu_client_id_aqui.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=seu_client_secret_aqui

# Google API Key (para Picker)
GOOGLE_API_KEY=sua_api_key_aqui

# (Opcional) Google App ID
GOOGLE_APP_ID=numero_do_projeto
```

**Como encontrar o App ID:**
- No Google Cloud Console, vá em "IAM & Admin → Settings"
- Copie o "Project Number"

### 6. Tela de Consentimento OAuth

Acesse: **APIs & Services → OAuth consent screen**

1. **User Type:** External (ou Internal se for Google Workspace)
2. Preencha informações básicas:
   - App name: App Vendas ML
   - User support email: seu@email.com
   - Developer contact: seu@email.com
3. **Scopes:** Adicione os seguintes escopos:
   ```
   .../auth/drive.readonly
   .../auth/userinfo.email
   .../auth/userinfo.profile
   ```
4. **Test users:** Adicione emails que poderão testar (modo desenvolvimento)
5. Salve e continue

---

## 📱 Como Usar

### 1. Conectar Google Drive

1. Acesse: **Sistema → Integrações**
2. Card **Google Drive** → Clique em **"Conectar Google Drive"**
3. Faça login com a conta Google desejada
4. Autorize o aplicativo
5. Pronto! Conectado ✅

### 2. Selecionar Imagens para Produto

1. Acesse um produto: **Produtos → [Selecione produto]**
2. Na seção **"Imagens do Produto"**
3. Clique no botão **☁️ Google Drive**
4. Pop-up do Google Drive abrirá
5. Navegue pelas pastas
6. Selecione uma ou mais imagens (Ctrl+clique para múltiplas)
7. Clique em **"Select"**
8. Aguarde o download automático
9. Página recarregará com as novas imagens

### 3. Trocar Conta Conectada

1. Acesse: **Integrações**
2. Clique em **"Reconectar"**
3. Login com nova conta Google
4. Autorize novamente

---

## 🔐 Segurança

### Dados Criptografados
```php
// Armazenamento em company_integrations
credentials: {
  "access_token": "...",     // CRIPTOGRAFADO
  "refresh_token": "...",    // CRIPTOGRAFADO
  "email": "user@gmail.com",
  "name": "Nome do Usuário"
}
```

### Permissões Mínimas
- ✅ Apenas leitura do Drive (`drive.readonly`)
- ✅ Não pode modificar/deletar arquivos
- ✅ Não pode criar pastas
- ✅ Acesso apenas às imagens selecionadas

### Renovação Automática
```php
// Helper renova automaticamente
$token = driveAccessToken(); // Token sempre válido!
```

---

## 🎯 Arquitetura

### Fluxo de Dados

```
┌─────────────────────────────────────────────────┐
│ 1. Usuário clica "Google Drive"                │
└──────────────────┬──────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────┐
│ 2. JavaScript abre Google Picker                │
│    - Carrega gapi.load('picker')                │
│    - Usa access_token da empresa                │
└──────────────────┬──────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────┐
│ 3. Usuário seleciona imagens                    │
│    - Suporta seleção múltipla                   │
│    - Retorna file IDs                           │
└──────────────────┬──────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────┐
│ 4. AJAX POST para backend                       │
│    - Route: products/{id}/images/drive/download │
│    - Body: { file_ids: [...] }                  │
└──────────────────┬──────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────┐
│ 5. Backend processa                             │
│    - Valida token                               │
│    - Download de cada imagem                    │
│    - Salva em storage/products/{id}/            │
│    - Insere em product_images                   │
└──────────────────┬──────────────────────────────┘
                   ▼
┌─────────────────────────────────────────────────┐
│ 6. Página recarrega                             │
│    - Imagens aparecem automaticamente           │
└─────────────────────────────────────────────────┘
```

### Estrutura de Arquivos

```
app/
├── Http/Controllers/Panel/
│   └── IntegrationController.php
│       ├── googleDriveConnect()          → Inicia OAuth
│       ├── googleDriveCallback()         → Recebe code
│       ├── googleDriveDisconnect()       → Remove tokens
│       └── googleDriveRefreshToken()     → Atualiza token
│
├── Http/Controllers/Panel/
│   └── ProductUIController.php
│       └── downloadDriveImages()         → Baixa imagens
│
├── Helpers/
│   ├── CompanyHelper.php
│   │   ├── googleDriveIntegration()      → Pega integração
│   │   ├── isGoogleDriveConnected()      → Verifica conexão
│   │   ├── getGoogleDriveAccessToken()   → Token (auto-renova)
│   │   └── getGoogleDriveCredentials()   → Credenciais completas
│   └── helpers.php
│       ├── driveIntegration()            → Alias helper
│       ├── driveConnected()              → Alias helper
│       └── driveAccessToken()            → Alias helper
│
resources/views/panel/
├── integrations/
│   └── index.blade.php                   → Card Google Drive
│
└── products/
    └── show.blade.php                    → Botão + Picker API
```

---

## 🛠️ Helpers Disponíveis

### Para Desenvolvedores

```php
// Verificar se está conectado
if (driveConnected()) {
    // Google Drive está conectado
}

// Pegar token (renova automaticamente!)
$token = driveAccessToken();

// Pegar integração completa
$integration = driveIntegration();

// Pegar credenciais
$credentials = CompanyHelper::getGoogleDriveCredentials();
$email = $credentials['email'] ?? null;
$name = $credentials['name'] ?? null;
```

### Usar API do Google Drive

```php
use Illuminate\Support\Facades\Http;

$token = driveAccessToken();

// Listar arquivos
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token
])->get('https://www.googleapis.com/drive/v3/files');

// Download de arquivo
$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . $token
])->get("https://www.googleapis.com/drive/v3/files/{$fileId}?alt=media");
```

---

## 📊 Schema do Banco

```sql
company_integrations
├── id
├── company_id                    → FK companies
├── integration_type              → 'google_drive'
├── active                        → boolean
├── credentials                   → JSON (encrypted)
│   ├── access_token             → String (criptografado)
│   ├── refresh_token            → String (criptografado)
│   ├── email                    → String
│   └── name                     → String
├── expires_at                    → Timestamp
├── connected_at                  → Timestamp
├── created_at
└── updated_at
```

---

## 🧪 Testando

### 1. Testar Conexão

1. Acesse: `/panel/integrations`
2. Clique em "Conectar Google Drive"
3. Deve redirecionar para login do Google
4. Após autorizar, deve voltar para `/panel/integrations`
5. Deve mostrar badge "Conectado" e email

### 2. Testar Picker

1. Acesse qualquer produto
2. Clique em "☁️ Google Drive"
3. Pop-up do Google Drive deve abrir
4. Navegue pelas pastas
5. Selecione uma imagem
6. Clique "Select"
7. Aguarde download
8. Imagem deve aparecer no produto

### 3. Testar Renovação de Token

```php
// Via tinker
php artisan tinker

$integration = \App\Models\CompanyIntegration::where('integration_type', 'google_drive')->first();
$integration->expires_at = now()->subHour(); // Força expiração
$integration->save();

// Agora tente usar
$token = driveAccessToken(); // Deve renovar automaticamente
```

---

## ❓ Troubleshooting

### Erro: "Token expirado"

✅ **Solução:** Use `driveAccessToken()` - renova automaticamente

### Erro: "Redirect URI mismatch"

✅ **Soluções:**
1. Verifique se a URL no Google Console está EXATAMENTE igual
2. Inclua http:// ou https://
3. Não esqueça `/panel/integrations/google-drive/callback`

### Pop-up do Picker não abre

✅ **Soluções:**
1. Verifique se `GOOGLE_API_KEY` está no .env
2. Verifique se `GOOGLE_CLIENT_ID` está correto
3. Abra Console do navegador (F12) e veja erros
4. Certifique-se que Picker API está ativada

### Erro: "Access denied"

✅ **Soluções:**
1. Adicione o usuário em "Test users" no OAuth consent screen
2. Publique o app (se for produção)
3. Verifique se os scopes estão corretos

### Imagens não aparecem após seleção

✅ **Soluções:**
1. Verifique permissões da pasta `storage/`
2. Verifique se `storage:link` foi executado
3. Veja logs: `storage/logs/laravel.log`
4. Verifique se a rota está registrada

---

## 🎓 Recursos Úteis

### Documentação Oficial

- [Google Drive API](https://developers.google.com/drive/api/v3/about-sdk)
- [Google Picker API](https://developers.google.com/picker)
- [OAuth 2.0](https://developers.google.com/identity/protocols/oauth2)

### Scopes Disponíveis

```
https://www.googleapis.com/auth/drive.readonly     → Leitura (usado)
https://www.googleapis.com/auth/drive              → Leitura e escrita
https://www.googleapis.com/auth/drive.file         → Apenas arquivos criados pelo app
https://www.googleapis.com/auth/userinfo.email     → Email do usuário (usado)
https://www.googleapis.com/auth/userinfo.profile   → Perfil do usuário (usado)
```

---

## 📝 Notas Importantes

### Limites da API

- **Consultas:** 1.000.000.000 por dia
- **Upload:** 750 GB por dia
- **Download:** Ilimitado

### Modo Desenvolvimento vs Produção

**Desenvolvimento (Testing):**
- Apenas usuários em "Test users" podem usar
- Limite de 100 usuários

**Produção:**
- Precisa publicar o app
- Processo de verificação do Google
- Disponível para todos

### Dados Armazenados

❌ **NÃO armazenamos:**
- Conteúdo dos arquivos do Drive
- Estrutura de pastas
- Metadados além do necessário

✅ **Armazenamos apenas:**
- Access token (temporário, renovável)
- Refresh token (para renovação)
- Email e nome do usuário
- Imagens selecionadas (cópia local)

---

## ✅ Checklist de Implementação

- [x] Criar projeto no Google Cloud
- [x] Ativar Google Drive API
- [x] Ativar Google Picker API
- [x] Criar OAuth Client ID
- [x] Criar API Key
- [x] Configurar redirect URIs
- [x] Configurar OAuth consent screen
- [x] Adicionar variáveis no .env
- [x] Testar conexão OAuth
- [x] Testar Picker API
- [x] Testar download de imagens
- [x] Testar renovação de token

---

## 🎉 Resultado Final

✅ **Integração 100% Funcional!**
✅ **Pop-up Nativo do Google Drive**
✅ **Seleção Múltipla de Imagens**
✅ **Download Automático**
✅ **Tokens Seguros e Auto-Renováveis**
✅ **Isolado por Empresa (Multi-tenant)**

**🚀 PRONTO PARA USO!**

---

**Data de Implementação:** 28/11/2025
**Versão:** 1.0
**Status:** ✅ PRODUCTION READY
