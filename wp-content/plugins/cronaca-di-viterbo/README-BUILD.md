# Build & Release Pipeline

## Prerequisiti

- PHP 8.2 (compatibile con 8.0+)
- Composer
- Bash, `rsync`, `zip` e `unzip`

## Comandi principali

Bump automatico della patch e generazione pacchetto:

```bash
bash build.sh --bump=patch
```

Impostare manualmente la versione e generare lo zip:

```bash
bash build.sh --set-version=1.2.3
```

Per scegliere un nome personalizzato per lo zip:

```bash
bash build.sh --bump=minor --zip-name=cv-dossier-context-beta
```

Lo script stampa la versione finale, il percorso completo dello zip creato e la lista dei file di primo livello inclusi nel pacchetto.

## GitHub Action

1. Effettua il commit delle modifiche e crea un tag semantico, ad esempio `v1.2.3`.
2. Push del tag su GitHub:

   ```bash
   git push origin v1.2.3
   ```

3. Il workflow `.github/workflows/build-plugin-zip.yml` si avvierà automaticamente, genererà lo zip con solo i file runtime e lo caricherà come artifact `plugin-zip`.
