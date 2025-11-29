# Troubleshooting - Fila do Mercado Livre Online

## Problema: Fila não está executando no servidor

### Diagnóstico Rápido

Execute no servidor:
```bash
cd /home/dvelopers/vendas.dvelopers.com.br
php test_queue_online.php
```

Este script vai verificar:
- ✅ Configurações do Laravel
- ✅ Existência das tabelas necessárias
- ✅ Jobs pendentes e falhados
- ✅ Status dos listings
- ✅ Conexão com banco de dados
- ✅ Permissões de escrita
- ✅ Configuração do cron

---

## Causas Comuns e Soluções

### 1. Cron não está configurado para a fila `mercado-livre`

**Problema**: Seu cron atual processa apenas a fila `default`:
```bash
* * * * * cd /home/dvelopers/vendas.dvelopers.com.br && /usr/local/bin/php artisan queue:work --queue=default ...
```

**Solução**: Adicionar processamento da fila `mercado-livre`

#### Opção A: Adicionar linha separada (RECOMENDADO)
```bash
# Editar crontab
crontab -e

# Adicionar ESTA LINHA (manter a linha do 'default' também):
* * * * * cd /home/dvelopers/vendas.dvelopers.com.br && /usr/local/bin/php artisan queue:work --queue=mercado-livre --sleep=3 --tries=3 --timeout=120 --stop-when-empty >> /home/dvelopers/vendas.dvelopers.com.br/queue-ml.log 2>&1
```

#### Opção B: Processar ambas as filas em uma única linha
```bash
# Substituir a linha existente por:
* * * * * cd /home/dvelopers/vendas.dvelopers.com.br && /usr/local/bin/php artisan queue:work --queue=mercado-livre,default --sleep=3 --tries=3 --timeout=120 --stop-when-empty >> /home/dvelopers/vendas.dvelopers.com.br/queue.log 2>&1
```

**Verificar se funcionou**:
```bash
# Aguardar 1-2 minutos e verificar se o arquivo de log foi criado/atualizado
tail -f /home/dvelopers/vendas.dvelopers.com.br/queue-ml.log
```

---

### 2. Job não está sendo criado ao clicar em "Publicar"

**Verificar**: Abra o navegador e tente publicar um produto. Depois execute:

```bash
cd /home/dvelopers/vendas.dvelopers.com.br
php artisan tinker --execute="echo 'Jobs na tabela jobs: ' . DB::table('jobs')->count(); echo PHP_EOL; echo 'Listings em queued: ' . DB::table('mercado_livre_listings')->where('status', 'queued')->count();"
```

**Se não aparecer job**:
- Verifique se há erro na tela ao publicar
- Verifique logs: `tail -100 /home/dvelopers/vendas.dvelopers.com.br/storage/logs/laravel.log`

**Se aparecer listing em 'queued' mas sem job na tabela 'jobs'**:
- Problema no dispatch do job
- Verificar o método `publish()` em `MercadoLivreController.php:295`

---

### 3. Jobs ficam presos em "processing"

**Verificar**:
```bash
php artisan tinker --execute="DB::table('mercado_livre_listings')->where('status', 'processing')->get(['id', 'product_id', 'updated_at'])"
```

**Solução**: Reiniciar jobs presos
```bash
# Marcar como queued novamente
php artisan tinker --execute="DB::table('mercado_livre_listings')->where('status', 'processing')->update(['status' => 'queued']);"

# Aguardar o cron processar (1-2 minutos)
```

---

### 4. Worker travou e não está processando

**Verificar se há workers rodando**:
```bash
ps aux | grep "queue:work"
```

**Se aparecer processos travados**:
```bash
# Matar processos travados
pkill -f "queue:work"

# Aguardar próxima execução do cron (1 minuto)
```

---

### 5. Tabelas de fila não existem

**Verificar**:
```bash
php artisan tinker --execute="var_dump(Schema::hasTable('jobs')); var_dump(Schema::hasTable('failed_jobs'));"
```

**Se retornar `false`**:
```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

---

### 6. Erro de timeout (job demora mais de 60s)

**Sintoma**: Jobs aparecem em `failed_jobs` com erro de timeout

**Solução**: Aumentar timeout no cron (já está na configuração recomendada)
```bash
--timeout=120
```

Se o problema persistir, aumentar para `--timeout=180`

---

### 7. Erro de token expirado do Mercado Livre

**Verificar token**:
```bash
php artisan tinker --execute="
\$token = DB::table('mercado_livre_tokens')->where('is_active', 1)->first();
if(\$token) {
    echo 'Token expira em: ' . \$token->expires_at . PHP_EOL;
    echo 'Expirado? ' . (now()->greaterThan(\$token->expires_at) ? 'SIM' : 'NÃO') . PHP_EOL;
} else {
    echo 'Nenhum token ativo encontrado' . PHP_EOL;
}
"
```

**Solução**: Renovar autenticação
- Acesse: https://vendas.dvelopers.com.br/mercado-livre
- Clique em "Conectar com Mercado Livre"
- Autorize novamente

---

### 8. Erro de conexão com API do Mercado Livre

**Verificar conectividade**:
```bash
curl -I https://api.mercadolibre.com
```

**Se retornar timeout ou erro**:
- Verificar firewall do servidor
- Verificar se há bloqueio de saída na porta 443
- Contatar suporte do provedor

---

## Teste Manual da Fila

Para testar se a fila está funcionando manualmente:

```bash
cd /home/dvelopers/vendas.dvelopers.com.br

# Processar UM job apenas
php artisan queue:work --queue=mercado-livre --once

# Ver output em tempo real
php artisan queue:work --queue=mercado-livre --timeout=120 --tries=3 -vvv
```

**Se funcionar manualmente mas não no cron**:
- Problema de permissões ou PATH do cron
- Adicionar PATH completo do PHP no cron: `/usr/local/bin/php`

---

## Monitoramento Contínuo

### Ver logs em tempo real
```bash
tail -f /home/dvelopers/vendas.dvelopers.com.br/storage/logs/laravel.log
```

### Ver apenas erros do Mercado Livre
```bash
grep -i "mercado\|publishlisting" /home/dvelopers/vendas.dvelopers.com.br/storage/logs/laravel.log | tail -50
```

### Ver logs do cron
```bash
tail -f /home/dvelopers/vendas.dvelopers.com.br/queue-ml.log
```

---

## Solução Definitiva: Usar Supervisor (RECOMENDADO)

Ao invés de cron que inicia/para workers a cada minuto, use Supervisor para manter workers sempre rodando:

### 1. Instalar Supervisor
```bash
sudo apt-get update
sudo apt-get install supervisor
```

### 2. Criar configuração
```bash
sudo nano /etc/supervisor/conf.d/laravel-queue-ml.conf
```

```ini
[program:laravel-queue-ml]
process_name=%(program_name)s_%(process_num)02d
command=/usr/local/bin/php /home/dvelopers/vendas.dvelopers.com.br/artisan queue:work --queue=mercado-livre --sleep=3 --tries=3 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=dvelopers
numprocs=1
redirect_stderr=true
stdout_logfile=/home/dvelopers/vendas.dvelopers.com.br/storage/logs/worker-ml.log
stopwaitsecs=3600
```

### 3. Ativar
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-queue-ml:*
```

### 4. Verificar status
```bash
sudo supervisorctl status laravel-queue-ml:*
```

### 5. Comandos úteis
```bash
# Parar worker
sudo supervisorctl stop laravel-queue-ml:*

# Reiniciar worker
sudo supervisorctl restart laravel-queue-ml:*

# Ver logs
sudo supervisorctl tail -f laravel-queue-ml:laravel-queue-ml_00
```

---

## Checklist Completo

Execute esta checklist em ordem:

- [ ] 1. Executar `php test_queue_online.php` e anotar problemas
- [ ] 2. Verificar se cron está configurado para fila `mercado-livre`
- [ ] 3. Verificar se tabelas `jobs` e `failed_jobs` existem
- [ ] 4. Testar criação de job manualmente via navegador
- [ ] 5. Verificar se job aparece na tabela `jobs`
- [ ] 6. Aguardar 1-2 minutos e verificar se job foi processado
- [ ] 7. Verificar logs de erro em `storage/logs/laravel.log`
- [ ] 8. Se necessário, processar manualmente com `queue:work --once`
- [ ] 9. Considerar migrar de cron para Supervisor

---

## Contatos de Suporte

Se o problema persistir após seguir todos os passos:

1. **Compartilhe o resultado de**:
   ```bash
   php test_queue_online.php > diagnostico.txt
   tail -100 storage/logs/laravel.log >> diagnostico.txt
   crontab -l >> diagnostico.txt
   ```

2. **Informações úteis**:
   - Versão do PHP: `php -v`
   - Versão do Laravel: `php artisan --version`
   - Sistema operacional: `uname -a`
   - Espaço em disco: `df -h`
