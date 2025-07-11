<?php
namespace IT\Pacg\Codes;

/**
 * Appendices 5.11
 * Class ReturnCode
 */
class ReturnCode
{
    const SUCCESS_CODE = 1024;
    const SUBJECT_IS_SELF_EXCLUDED = 1298;

    /**
     * @var array
     */
    protected static $codes = [
        0    => [
            'en' => "Generic Return Code Error",
            'it' => 'Errore di codice di ritorno generico',
        ],
        1024 => [
            'en' => 'Outcome ok',
            'it' => 'Esito ok',
        ],
        1025 => [
            'en' => 'Operation temporarily prohibited',
            'it' => 'Operazione momentaneamente interdetta',
        ],
        1026 => [
            'en' => 'Request still being processed',
            'it' => 'Richiesta ancora in fase di elaborazione',
        ],
        1027 => [
            'en' => 'Transaction ID related to a previously processed request',
            'it' => 'ID Transazione relativo ad una richiesta già processata',
        ],
        1028 => [
            'en' => 'Invalid Username Token',
            'it' => 'Username Token non valido',
        ],
        1029 => [
            'en' => 'Generic error',
            'it' => 'Errore generico',
        ],
        1030 => [
            'en' => 'Unauthorised operation',
            'it' => 'Operazione non autorizzata',
        ],
        1031 => [
            'en' => 'Data not processed',
            'it' => 'Dati non elaborati',
        ],
        1032 => [
            'en' => 'Disabled message',
            'it' => 'Messaggio disabilitato',
        ],
        1100 => [
            'en' => 'Unidentified Transmitting Licensee',
            'it' => 'Concessionario trasmittente non identificato',
        ],
        1101 => [
            'en' => 'Licensee account holder not identified',
            'it' => 'Concessionario titolare conto non identificato',
        ],
        1102 => [
            'en' => 'Licensee Account holder and VAT number not consistent',
            'it' => 'Concessionario titolare conto e Partita IVA non congruenti',
        ],
        1103 => [
            'en' => 'Original network account belonging to the GAD licensee network',
            'it' => 'Rete conto originario appartenente alla rete concessioni GAD',
        ],
        1104 => [
            'en' => 'Original network account not belonging to the GAD licensee network',
            'it' => 'Rete conto destinazione non appartenente alla rete concessioni GAD',
        ],
        1105 => [
            'en' => 'Original licensee account not identified',
            'it' => 'Concessionario conto originario non identificato',
        ],
        1106 => [
            'en' => 'Destination licensee account not identified',
            'it' => 'Concessionario conto destinazione non identificato',
        ],
        1107 => [
            'en' => 'Original and destination licensee account unrelated',
            'it' => 'Concessionari conto originario e destinazione non correlati',
        ],
        1108 => [
            'en' => 'Network account not belonging to the GAD bookmaker network',
            'it' => 'Rete titolare conto non appartenente alla rete concessioni GAD',
        ],
        1109 => [
            'en' => 'The holder of the licensee account does not use the same FSC as the transmitting licensee',
            'it' => 'Concessionario titolare conto non utilizza lo stesso FSC del Concessionario trasmittente',
        ],
        1200 => [
            'en' => 'The subject already holds an account with the licensee',
            'it' => 'Il soggetto è già titolare di un conto presso il concessionario',
        ],
        1201 => [
            'en' => 'Existing account',
            'it' => 'Conto esistente',
        ],
        1202 => [
            'en' => 'Non-existent account',
            'it' => 'Conto non esistente',
        ],
        1203 => [
            'en' => 'Transactions on account not allowed: account blocked',
            'it' => 'Movimentazione conto non consentita: conto chiuso',
        ],
        1204 => [
            'en' => 'Transactions on account not allowed: account dormant',
            'it' => 'Movimentazione conto non consentita: conto dormiente',
        ],
        1205 => [
            'en' => 'Account balance not allowed: dormant account',
            'it' => 'Saldo conto non consentito: conto dormiente',
        ],
        1206 => [
            'en' => 'Change of Province of Residence account not allowed: account closed',
            'it' => 'Modifica provincia residenza conto non consentita: conto chiuso',
        ],
        1207 => [
            'en' => 'Change of providence of residence not allowed: account dormant',
            'it' => 'Modifica provincia residenza conto non consentita: conto dormiente',
        ],
        1208 => [
            'en' => 'Transaction on account not allowed: account open',
            'it' => 'Riapertura conto non consentita: conto aperto',
        ],
        1209 => [
            'en' => 'Transaction on account not allowed: account closed',
            'it' => 'Riapertura conto non consentita: conto chiuso',
        ],
        1210 => [
            'en' => 'Reopening account not allowed: account dormant',
            'it' => 'Riapertura conto non consentita: conto dormiente',
        ],
        1211 => [
            'en' => 'Account suspension not allowed: account suspended',
            'it' => 'Sospensione conto non consentita: conto sospeso',
        ],
        1212 => [
            'en' => 'Account suspension not allowed: account closed',
            'it' => 'Sospensione conto non consentita: conto chiuso',
        ],
        1213 => [
            'en' => 'Account suspension not allowed: account dormant',
            'it' => 'Sospensione conto non consentita: conto dormiente',
        ],
        1214 => [
            'en' => 'Account closure not allowed: account closed',
            'it' => 'Chiusura conto non consentita: conto chiuso',
        ],
        1215 => [
            'en' => 'Account closure not allowed: account dormant',
            'it' => 'Chiusura conto non consentita: conto dormiente',
        ],
        1216 => [
            'en' => 'Transaction amount lower than or equal to zero',
            'it' => 'Importo movimento minore o uguale a zero',
        ],
        1217 => [
            'en' => 'Balance amount negative',
            'it' => 'Importo saldo negativo',
        ],
        1218 => [
            'en' => 'Bonus balance amount negative',
            'it' => 'Importo bonus saldo negativo',
        ],
        1219 => [
            'en' => 'Sum of transaction amount and Bonus balance greater than balance amount',
            'it' => 'Somma di importo movimento e importo bonus saldo maggiore di importo saldo',
        ],
        1220 => [
            'en' => 'Bonus balance amount greater than balance amount',
            'it' => 'Importo bonus saldo maggiore di importo saldo',
        ],
        1221 => [
            'en' => 'Bonus detail not valid: non-existent game type',
            'it' => 'Dettaglio bonus saldo non valido: tipo gioco inesistente',
        ],
        1222 => [
            'en' => 'Balance amount detail not valid: amount lower than or equal to zero',
            'it' => 'Dettaglio bonus saldo non valido: importo minore o uguale a zero',
        ],
        1223 => [
            'en' => 'Bonus detail not valid: duplicated game type',
            'it' => 'Dettaglio bonus saldo non valido: tipo gioco duplicato',
        ],
        1224 => [
            'en' => 'Bonus detail not valid: amount sum different from Bonus balance amount',
            'it' => 'Dettaglio bonus saldo non valido: somma importi diversa da importo bonus saldo',
        ],
        1225 => [
            'en' => 'Transaction amount lower than or equal to zero',
            'it' => 'Importo bonus minore o uguale a zero',
        ],
        1226 => [
            'en' => 'Bonus balance amount lower than Bonus amount',
            'it' => 'Importo bonus saldo minore di importo bonus',
        ],
        1227 => [
            'en' => 'Bonus detail not valid: non-existent game type',
            'it' => 'Dettaglio bonus non valido: tipo gioco inesistente',
        ],
        1228 => [
            'en' => 'Balance amount detail not valid: amount lower than or equal to zero',
            'it' => 'Dettaglio bonus non valido: importo minore o uguale a zero',
        ],
        1229 => [
            'en' => 'Bonus detail not valid: duplicated game type',
            'it' => 'Dettaglio bonus non valido: tipo gioco duplicato',
        ],
        1230 => [
            'en' => 'Bonus detail not valid: amount sum different to Bonus amount',
            'it' => 'Dettaglio bonus non valido: somma importi diversa da importo bonus',
        ],
        1231 => [
            'en' => 'Bonus detail not consistent with Bonus balance detail',
            'it' => 'Dettaglio bonus non congruente con Dettaglio bonus saldo',
        ],
        1232 => [
            'en' => 'Province of residence not valid',
            'it' => 'Provincia di residenza non valida',
        ],
        1233 => [
            'en' => 'Unauthorised',
            'it' => 'Non autorizzato',
        ],
        1234 => [
            'en' => 'Change of status reason not valid',
            'it' => 'Causale cambio stato non valida',
        ],
        1235 => [
            'en' => 'Transaction reversal not possible: transaction already reversed',
            'it' => 'Storno movimento non possibile: movimento già stornato',
        ],
        1236 => [
            'en' => 'Reversal Receipt ID not found or not consistent',
            'it' => 'ID Ricevuta Storno non presente o non congruente',
        ],
        1237 => [
            'en' => 'Transaction Reversal not possible: Gambling Account, specified different to that of the transaction',
            'it' => 'Storno movimento non possibile: Conto di Gioco specificato diverso da quello del movimento',
        ],
        1238 => [
            'en' => 'Transaction reversal not possible: amount specified greater than transaction amount',
            'it' => 'Storno movimento non possibile: importo specificato maggiore di quello del movimento',
        ],
        1239 => [
            'en' => 'Transaction Reversal not possible: time limit exceeded',
            'it' => 'Storno movimento non possibile: superato il limite di tempo',
        ],
        1240 => [
            'en' => 'Gambling account integration not allowed: dormant account',
            'it' => 'Integrazione apertura conto non consentita: conto dormiente',
        ],
        1241 => [
            'en' => 'Integration of gambling account opening not allowed: account and subject are inconsistent',
            'it' => 'Integrazione apertura conto non consentita: conto e soggetto incongruenti',
        ],
        1242 => [
            'en' => 'The subject is the holder of accounts suspended through self-exclusion',
            'it' => 'Il soggetto è titolare di conti sospesi per autoesclusione',
        ],
        1260 => [
            'en' => 'Updating of gambling account holder document details not allowed: account closed',
            'it' => 'Aggiorna dati documento titolare conto non consentita: conto chiuso',
        ],
        1261 => [
            'en' => 'Updating of gambling account holder document details not allowed: account dormant',
            'it' => 'Aggiorna dati documento titolare conto non consentita: conto dormiente',
        ],
        1262 => [
            'en' => 'Original Gambling Account non-existent',
            'it' => 'Conto di gioco originario non esistente',
        ],
        1263 => [
            'en' => 'Account migration not allowed: original account closed',
            'it' => 'Migrazione conto non consentita: conto originario chiuso',
        ],
        1264 => [
            'en' => 'Account migration not allowed: original account dormant',
            'it' => 'Migrazione conto non consentita: conto originario dormiente',
        ],
        1265 => [
            'en' => 'Tax code not associated to the original gambling account',
            'it' => 'Codice fiscale non associato al conto di gioco originario',
        ],
        1266 => [
            'en' => 'Existing destination account',
            'it' => 'Conto di destinazione esistente',
        ],
        1267 => [
            'en' => 'The subject already holds an account with the destination licensee',
            'it' => 'Il soggetto è già titolare di un conto presso il concessionario di destinazione',
        ],
        1268 => [
            'en' => 'Data not available',
            'it' => 'Dati non disponibili',
        ],
        1269 => [
            'en' => 'Original gambling account different from the destination one',
            'it' => 'Conto di gioco originario diverso da quello di destinazione',
        ],
        1270 => [
            'en' => 'Account migration not allowed: original account blocked',
            'it' => 'Migrazione conto non consentita: conto originario bloccato',
        ],
        1271 => [
            'en' => 'Transactions on account not allowed: account blocked',
            'it' => 'Movimentazione conto non consentita: conto bloccato',
        ],
        1272 => [
            'en' => 'Updating of gambling account holder document details not allowed: account blocked',
            'it' => 'Aggiorna dati documento titolare conto non consentita: conto bloccato',
        ],
        1273 => [
            'en' => 'Change of province of residence not allowed: account blocked',
            'it' => 'Modifica provincia di residenza non consentita: conto bloccato',
        ],
        1274 => [
            'en' => 'Account balance not allowed: account blocked',
            'it' => 'Saldo conto non consentito: conto bloccato',
        ],
        1275 => [
            'en' => 'Account block not allowed: account blocked',
            'it' => 'Blocco conto non consentito: conto bloccato',
        ],
        1276 => [
            'en' => 'Account block not allowed: account dormant',
            'it' => 'Blocco conto non consentito: conto dormiente',
        ],
        1277 => [
            'en' => 'Unblocking of account not allowed: status differs from the original',
            'it' => 'Sblocco conto non consentito: stato diverso da quello di partenza',
        ],
        1278 => [
            'en' => 'Document not present in ID registration. Use message “Update Gambling Account Holder Document Data” to insert it',
            'it' => 'Documento non presente in anagrafica. Utilizzare messaggio “Aggiorna Dati Documento Titolare Conto di Gioco” per inserirlo',
        ],
        1279 => [
            'en' => 'Document not present in ID registration. Use message ‘Integration of Simplified gambling account opening for natural person’ to input it',
            'it' => 'Documento non presente in anagrafica. Utilizzare messaggio “Integrazione apertura semplificata conto di gioco persona fisica” per inserirlo',
        ],
        1280 => [
            'en' => 'Document not present in ID registration. Legal Entity Account',
            'it' => 'Documento non presente in anagrafica. Conto di persona giuridica',
        ],
        1281 => [
            'en' => 'Updating of gambling account holder document details not allowed: account belongs to legal entity',
            'it' => 'Aggiornamento dati documento titolare conto non consentito: conto di persona giuridica',
        ],
        1282 => [
            'en' => 'Updating of gambling account owner document details not allowed: gambling account opened with message ‘Simplified gambling account opened for natural person’. Use message “Integration of simplified gambling account for a natural person”',
            'it' => 'Aggiornamento dati documento titolare conto non consentito: conto aperto con messaggio “Apertura semplificata conto di gioco persona fisica”. Utilizzare messaggio “Integrazione apertura semplificata conto di gioco persona fisica”',
        ],
        1283 => [
            'en' => 'Gambling account integration not allowed: account not open in simplified mode',
            'it' => 'Integrazione apertura conto non consentita: conto non aperto in modalità semplificata',
        ],
        1284 => [
            'en' => 'Integration of gambling account opening not allowed: document already present',
            'it' => 'Integrazione apertura conto non consentita: documento già presente',
        ],
        1285 => [
            'en' => 'Balance date not valid',
            'it' => 'Data saldo non valida',
        ],
        1286 => [
            'en' => 'Non-existent limit type',
            'it' => 'Tipo limite inesistente',
        ],
        1287 => [
            'en' => 'Account limit cannot be set: account closed',
            'it' => 'Aggiornamento limite non consentito: conto chiuso',
        ],
        1288 => [
            'en' => 'Account limit cannot be set: account dormant',
            'it' => 'Aggiornamento limite non consentito: conto dormiente',
        ],
        1289 => [
            'en' => 'Account limit cannot be set: account blocked',
            'it' => 'Aggiornamento limite non consentito: conto bloccato',
        ],
        1290 => [
            'en' => 'Account limit cannot be set: limit amount the same as previous limit',
            'it' => 'Aggiornamento limite non consentito: importo uguale al precedente',
        ],
        1291 => [
            'en' => 'Limits not present',
            'it' => 'Limiti non presenti',
        ],
        1292 => [
            'en' => 'Non existent self-exclusion type',
            'it' => 'Tipo autoesclusione inesistente',
        ],
        1293 => [
            'en' => 'Self-exclusion not allowed: the subject does not hold an account with the licensee',
            'it' => 'Autoesclusione non consentita: il soggetto non ha un conto con il concessionario',
        ],
        1294 => [
            'en' => 'Reactivation not allowed: open ended self-exclusion not present',
            'it' => 'Riattivazione non consentita: autoesclusione a tempo indeterminato non presente',
        ],
        1295 => [
            'en' => 'Reactivation already under way',
            'it' => 'Riattivazione già in corso',
        ],
        1296 => [
            'en' => 'Reactivation not allowed: self-exclusion for less than six months',
            'it' => 'Riattivazione non consentita: autoesclusione da meno di sei mesi',
        ],
        1297 => [
            'en' => 'Self-exclusion not allowed: self-exclusion already in force',
            'it' => 'Autoesclusione non consentita: autoesclusione già in corso',
        ],
        1298 => [
            'en' => 'The subject is self-excluded',
            'it' => 'Il soggetto è autoescluso',
        ],
        1299 => [
            'en' => 'Limit amounts not consistent',
            'it' => 'Importi limiti non coerenti',
        ],
        1300 => [
            'en' => 'Natural person not identified',
            'it' => 'Persona fisica non identificata',
        ],
        1301 => [
            'en' => 'Natural person deceased',
            'it' => 'Persona fisica deceduta',
        ],
        1302 => [
            'en' => 'Natural person a minor',
            'it' => 'Persona fisica minorenne',
        ],
        1303 => [
            'en' => 'Natural person registration details not valid',
            'it' => 'Dati anagrafici persona fisica non validi',
        ],
        1304 => [
            'en' => 'Natural person tax code not valid',
            'it' => 'Codice fiscale persona fisica non valido',
        ],
        1400 => [
            'en' => 'Dormant communication not allowed: account already dormant',
            'it' => 'Comunicazione dormiente non consentita: conto già dormiente',
        ],
        1401 => [
            'en' => 'Dormant communication not allowed: account blocked',
            'it' => 'Comunicazione dormiente non consentita: conto bloccato',
        ],
        1402 => [
            'en' => 'Limit removal not allowed: limit type not present',
            'it' => 'Cancellazione limite non consentita: tipo limite non presente',
        ],
        1403 => [
            'en' => 'Limit removal not allowed: no other limits are present',
            'it' => 'Cancellazione limite non consentita: non sono presenti altri limiti',
        ],
        1404 => [
            'en' => 'The subject is not self-excluded',
            'it' => 'Il soggetto non è autoescluso',
        ],
        1405 => [
            'en' => 'Pseudonym cannot be updated: account closed',
            'it' => 'Aggiornamento pseudonimo non consentito: il conto è chiuso',
        ],
        1406 => [
            'en' => 'Pseudonym cannot be updated: account dormant',
            'it' => 'Aggiornamento pseudonimo non consentito: il conto è dormiente',
        ],
        1407 => [
            'en' => 'Pseudonym cannot be updated: account is blocked',
            'it' => 'Aggiornamento pseudonimo non consentito: il conto è bloccato',
        ],
        1408 => [
            'en' => 'Pseudonym the same as the previous one',
            'it' => 'Pseudonimo uguale al precedente',
        ],
        1409 => [
            'en' => 'E-mail update not allowed: account closed',
            'it' => 'Aggiornamento posta elettronica non consentito: il conto è chiuso',
        ],
        1410 => [
            'en' => 'E-mail update not allowed: account is dormant',
            'it' => 'Aggiornamento posta elettronica non consentito: il conto è dormiente',
        ],
        1411 => [
            'en' => 'E-mail update not allowed: account blocked',
            'it' => 'Aggiornamento posta elettronica non consentito: il conto è bloccato',
        ],
        1412 => [
            'en' => 'E-mail address the same as previous one',
            'it' => 'Posta elettronica uguale alla precedente',
        ],
        1413 => [
            'en' => 'E-mail address formally incorrect',
            'it' => 'Posta elettronica formalmente errata',
        ],
        1414 => [
            'en' => 'Pseudonym not present',
            'it' => 'Pseudonimo non presente',
        ],
        1415 => [
            'en' => 'E-mail address not present',
            'it' => 'Posta elettronica non presente',
        ],
        1416 => [
            'en' => 'The subject has never been self-excluded',
            'it' => 'Il soggetto non è mai stato autoescluso',
        ],
        1417 => [
            'en' => 'Type of ID document not allowed',
            'it' => 'Tipo di documento di identità non consentito',
        ],
        1418 => [
            'en' => 'Number of ID document holding special characters',
            'it' => 'Numero del documento di identità con caratteri speciali',
        ],
        1419 => [
            'en' => 'Issuing date of the ID document before the birth date of the owner of the gambling account',
            'it' => 'Data di rilascio del documento di identità prima della data di nascita del proprietario del conto di gioco',
        ],
        1420 => [
            'en' => 'Issuing date of the ID document subsequent to current date',
            'it' => 'Data di rilascio del documento di identità successiva alla data corrente',
        ],
        1421 => [
            'en' => 'Payment method not allowed for the transaction reason reported',
            'it' => 'Metodo di pagamento non consentito per il motivo della transazione segnalato',
        ],
        1422 => [
            'en' => 'Province of residence already existing',
            'it' => 'Provincia di residenza già esistente',
        ],
        1423 => [
            'en' => 'Migration not allowed: status of the destination account not consistent',
            'it' => 'Migrazione non consentita: lo stato dell\'account di destinazione non è coerente',
        ],
        1424 => [
            'en' => 'Bonus reversal not allowed: bonus already cancelled',
            'it' => 'Riversamento bonus non consentito: bonus già annullato',
        ],
        1425 => [
            'en' => 'Bonus reversal not allowed for bonus reversal type 1: specified amount greater than the bonus amount',
            'it' => "Riversamento bonus non consentito per il tipo di riscossione bonus 1: importo specificato maggiore dell'importo del bonus",
        ],
        1426 => [
            'en' => 'Bonus reversal not allowed: specified game account different from the bonus one',
            'it' => 'Riversamento bonus non consentito: account di gioco specificato diverso da quello del bonus',
        ],
        1427 => [
            'en' => 'Bonus reversal not allowed: time limit exceeded',
            'it' => 'Riversamento bonus non consentito: limite di tempo superato',
        ],
        1428 => [
            'en' => 'Bonus detail of the reversal not congruent with the bonus detail to be reversed',
            'it' => 'Dettaglio del bonus da annullare non congruente con il dettaglio del bonus da riscuotere',
        ],
        1429 => [
            'en' => 'Bonus reversal not allowed: bonus reversal type not allowed',
            'it' => 'Riversamento bonus non consentito: tipo di riscossione bonus non consentito',
        ],
        1430 => [
            'en' => 'Bonus reversal not allowed for bonus reversal type 2: specified amount less than or equal to the bonus amount',
            'it' => "Riversamento bonus non consentito per il tipo di riscossione bonus 2: importo specificato minore o uguale all'importo del bonus",
        ],
        1431 => [
            'en' => 'Type of personal data provision not allowed',
            'it' => 'Tipo di fornitura di dati personali non consentito',
        ],
        1432 => [
            'en' => 'Reactivation not allowed: self-exclusion carried out by another person',
            'it' => "Riattivazione non consentita: autoesclusione effettuata da un'altra persona",
        ],
        1433 => [
            'en' => 'Request not allowed: the subject does not have an active account with the dealer',
            'it' => 'Richiesta non consentita: il soggetto non ha un account attivo con il rivenditore',
        ],
        1434 => [
            'en' => 'Legal entity account type does not exist',
            'it' => 'Il tipo di account entità giuridica non esiste',
        ],
        1435 => [
            'en' => 'Exceeded the maximum limit on the number of test accounts',
            'it' => 'Superato il limite massimo sul numero di account di test',
        ],

    ];

    /**
     * @param int $code
     * @param array $params
     * @param string $language
     * @return string
     */
    public function getCodeDescription(int $code, array $params = [], string $language = 'en'): string
    {
        if (isset(self::$codes[$code])) {
            return strtr(self::$codes[$code][$language], $params);
        } else {
            return (self::$codes[0][$language]);
        }
    }
}
