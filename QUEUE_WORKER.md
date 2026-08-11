# Sistema de Filas - Publicação no Mercado Livre

## Como Funciona

Quando você clica em "Publicar Agora", o sistema:
1. Salva todos os dados do formulário no banco
2. Marca o status como `queued` (na fila)
3. Envia um Job para a fila `mercado-livre`
4. O Worker processa o job em background
5. Você recebe feedback em tempo real na tela

## Iniciar o Queue Worker

### Opção 1: Worker Simples (desenvolvimento)
```bash
php artisan queue:work --queue=mercado-livre
```

### Opção 2: Worker com Timeout (recomendado)
```bash
php artisan queue:work --queue=mercado-livre --timeout=120 --tries=3
```

### Opção 3: Processar Todas as Filas
```bash
php artisan queue:work --tries=3
```

### Opção 4: Worker em Background (Windows)
```bash
start /B php artisan queue:work --queue=mercado-livre
```

### Opção 5: Worker Contínuo com Restart Automático
```bash
php artisan queue:work --queue=mercado-livre --timeout=120 --tries=3 --sleep=3 --max-time=3600
```

## Parâmetros Importantes

- `--queue=mercado-livre`: Processa apenas jobs da fila do Mercado Livre
- `--timeout=120`: Timeout de 2 minutos por job
- `--tries=3`: Tenta 3 vezes antes de falhar
- `--sleep=3`: Espera 3 segundos entre verificações
- `--max-time=3600`: Reinicia worker a cada 1 hora

## Monitorar a Fila

### Ver Jobs Pendentes
```bash
php artisan queue:monitor mercado-livre
```

### Ver Jobs Falhados
```bash
php artisan queue:failed
```

### Reprocessar Job Falhado
```bash
php artisan queue:retry [job-id]
```

### Reprocessar Todos os Falhados
```bash
php artisan queue:retry all
```

### Limpar Jobs Falhados
```bash
php artisan queue:flush
```

## Status dos Jobs

### Visualização na Interface

A tela de preparação mostra o status em tempo real:

- 🕒 **Queued** (Azul): Na fila aguardando processamento
- 🔄 **Processing** (Amarelo): Sendo publicado no Mercado Livre
- ✅ **Active** (Verde): Publicado com sucesso!
- ❌ **Failed** (Vermelho): Falha na publicação

### Atualização Automática

A tela se atualiza automaticamente a cada 3 segundos quando um job está em andamento.

## Notificações

Você receberá notificações por email quando:
- ✅ A publicação for concluída com sucesso
- ❌ Ocorrer uma falha na publicação

## Troubleshooting

### Worker não está processando jobs?

1. Verifique se o worker está rodando:
```bash
# Windows
tasklist | findstr php

# Verificar logs
php artisan queue:work --queue=mercado-livre --once
```

2. Verifique a configuração de filas em `.env`:
```env
QUEUE_CONNECTION=database
```

3. Certifique-se de que as tabelas de jobs existem:
```bash
php artisan queue:table
php artisan migrate
```

### Jobs ficam presos em "processing"?

Reinicie o worker:
```bash
php artisan queue:restart
```

### Configurar Worker para Rodar Automaticamente (Produção)

Use Supervisor ou Task Scheduler do Windows para manter o worker sempre rodando.

## Logs

Todos os erros são registrados em:
- `storage/logs/laravel.log`

Busque por:
```
Erro ao publicar no ML
Job PublishListingToML falhou
```

## Performance

- Cada job tenta 3 vezes com backoff: 1min, 5min, 15min
- Timeout de 2 minutos por tentativa
- Fila dedicada `mercado-livre` para não bloquear outras operações
