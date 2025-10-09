# Frequently asked questions

## Come imposto un nuovo dossier completo?
Crea un contenuto in **Dossier**, compila stato, punteggio, punti chiave e attori, quindi attiva scheda, timeline e mappa dalle opzioni aggiuntive. Aggiungi eventi tramite **Eventi Dossier** per popolare automaticamente timeline e mappe.

## È possibile mostrare solo alcuni componenti del dossier?
Sì. Nelle opzioni del dossier attiva o disattiva la scheda riassuntiva, la timeline o la mappa. Le stesse impostazioni vengono rispettate anche quando il dossier è richiamato nei post collegati.

## Come collego un articolo a un dossier esistente?
Durante la modifica di un post standard utilizza la meta box **Dossier collegato** per selezionare il dossier. La scheda viene inserita automaticamente nel contenuto e puoi gestire markers personalizzati per l'articolo.

## Quali parametri accettano gli shortcode?
`[cv_dossier_context]` e `[cv_dossier_timeline]` richiedono l'ID del dossier (`id="123"`). `[cv_dossier_map]` accetta anche il parametro `height="400"` per personalizzare l'altezza della mappa.

## Come funziona il follow-up dei lettori?
Il form salva email e ID dossier nella tabella `wp_cv_dossier_followers` dopo aver validato il nonce e l'indirizzo. L'action `cv_dossier_follow` consente di inviare i dati a servizi esterni.

## Posso popolare la mappa con marker personalizzati?
Sì. Nella meta box della mappa nel post collegato puoi inserire marker manuali con titolo, descrizione, immagine e coordinate che vengono sanificati prima del salvataggio.

## Quali asset posso personalizzare?
Modifica gli stili in `css/cv-dossier.css` e gli script in `js/cv-dossier.js`. Il plugin carica Leaflet da CDN solo quando necessario per ridurre l'impatto sulle pagine.

## Dove trovo il text domain per le traduzioni?
Il text domain è `cv-dossier`. Inserisci i file `.po/.mo` nella cartella `languages/` del plugin.
