<?php
namespace IT\Pgda\Codes;

/**
 * Class ReturnCode
 * @package IT\Pgda\Codes
 */
class ReturnCode
{
    const DATA_BASE_ERROR = 9000;

    const UNKNOWN_CODE = "10001100101001"; // 9001

    const SUCCESS_CODE = 0;

    /**
     * @var array|int[]
     * If ADM service returns these codes we retry request attempt
     */
    private static array $retryStatusCodes = [
        1000,
        1001,
        9000,
        9001
    ];

    /**
     * @var array
     */
    protected static $codes = [
        self::UNKNOWN_CODE => [
            'en' => "Unknown Error Code",
            'it' => 'Unknown Error Code',
        ],
        0 => [
            'en' => "Generic Return Code Error",
            'it' => 'Errore di codice di ritorno generico',
        ],
        1 => [
            'en' => "Success",
            'it' => 'Successo',
        ],
        1000 => [
            "en" => "Generic message reading error",
            "it" => "Errore generico lettura messaggio",
        ],
        1001 => [
            "en" => "Currently being processed - please wait",
            "it" => "Elaborazione in corso - attendere prego",
        ],
        1003 => [
            "en" => "Transaction code refers to a different request already made",
            "it" => "Codice  transazione  si  riferisce  a  differente richiesta gia effettuata",
        ],
        1004 => [
            "en" => "Protocol version wrong or missing",
            "it" => "Versione protocollo errato o mancante",
        ],
        1010 => [
            "en" => "Unverified signature",
            "it" => "Firma non verificata",
        ],
        1020 => [
            "en" => "Transmitting licensee code non-existent or not authorised",
            "it" => "Codice concessionario trasmittente inesistente o  non abilitato",
        ],
        1040 => [
            "en" => "Session identifier wrong or missing All",
            "it" => "Identificativo sessione errato o mancante",
        ],
        1050 => [
            "en" => "Transmitting licensee code not authorised to send the message",
            "it" => "Codice concessionario trasmittente non abilitato ad inviare il messaggio",
        ],
        1060 => [
            "en" => "Wrong message length",
            "it" => "Lunghezza messaggio errata",
        ],
        1070 => [
            "en" => "Message already transmitted",
            "it" => "Messaggio gia trasmesso",
        ],
        1080 => [
            "en" => "Wrong message code",
            "it" => "Codice messaggio errato",
        ],
        1090 => [
            "en" => "Date/Hour wrong",
            "it" => "Data/Ora errata",
        ],
        1091 => [
            "en" => "Date/Hour indicated later than current one",
            "it" => "Data/Ora indicata successiva alla attuale",
        ],
        1092 => [
            "en" => "Date subsequent to the start date of service validity",
            "it" => "Data successiva alla data di inizio Validita del servizio",
        ],
        1100 => [
            "en" => "Percentage of prize money wrong",
            "it" => "Percentuale montepremi errata",
        ],
        1110 => [
            "en" => "Type of prize money wrong",
            "it" => "Tipologia montepremi errata",
        ],
        1120 => [
            "en" => "Ticket code wrong or non-existent",
            "it" => "Codice biglietto errato o inesistente",
        ],
        1140 => [
            "en" => "Prize amounts in the plan non consistent with prize money",
            "it" => "Importi piano dei premi non congruenti con montepremi",
        ],
        1150 => [
            "en" => "Amount transferred not consistent with prize amount",
            "it" => "Importo accredito non congruente con importo premi",
        ],
        1160 => [
            "en" => "Proponent licensee code non-existent or not authorised",
            "it" => "Codice concessionario proponente inesistente o non abilitato",
        ],
        1190 => [
            "en" => "Game code wrong or missing",
            "it" => "Codice gioco errato o mancante",
        ],
        1191 => [
            "en" => "Game not authorised for the licensee and FSC",
            "it" => "Gioco non abilitato per il concessionario ed FSC",
        ],
        1200 => [
            "en" => "Central system gambling session identifier not unique",
            "it" => "Identificativo della sessione di gioco non univoco",
        ],
        1210 => [
            "en" => "Platform code not valid",
            "it" => "Codice piattaforma non valido",
        ],
        1220 => [
            "en" => "Nominal participation right amount <=0 or greater than maximum amount allowed",
            "it" => "Importo nominale diritto di partecipazione <=0 o superiore al massimo consentito",
        ],
        1221 => [
            "en" => "Bonus amount <0 or greater than allowed",
            "it" => "Importo bonus <0 o superiore al consentito",
        ],
        1222 => [
            "en" => "Nominal jackpot amount <0",
            "it" => "Importo nominale jackpot <0",
        ],
        1223 => [
            "en" => "Nominal participation end amount <0",
            "it" => "Importo nominale di fine partecipazione <0",
        ],
        1224 => [
            "en" => "Nominal amount wages <0",
            "it" => "Importo nominale Puntato <0",
        ],
        1225 => [
            "en" => "Nominal amount won <0",
            "it" => "Importo nominale Vinto <0",
        ],
        1226 => [
            "en" => "Amount at start and end of participation, sums wages and returned as winnings inconsistent",
            "it" => "Importo inizio e fine partecipazione, somma puntate e restituito per vincita incongruenti",
        ],
        1230 => [
            "en" => "PRM value attribute (percentage) wrong",
            "it" => "PRM valore attributo (percentuale) errato",
        ],
        1240 => [
            "en" => "SMG value wrong or missing",
            "it" => "SMG valore errato o mancante",
        ],
        1241 => [
            "en" => "SMN value wrong or missing",
            "it" => "SMN valore errato o mancante",
        ],
        1250 => [
            "en" => "MNG value below 1",
            "it" => "MNG valore inferiore a 1",
        ],
        1260 => [
            "en" => "MXG value below 2",
            "it" => "MXG valore inferiore a 2",
        ],
        1261 => [
            "en" => "MXG value lower than MNG value",
            "it" => "MXG valore inferiore a MNG",
        ],
        1270 => [
            "en" => "Date earlier that start of session",
            "it" => "Data precedente ad inizio sessione",
        ],
        1271 => [
            "en" => "Date subsequent to end of session",
            "it" => "Data successiva alla fine sessione",
        ],
        1272 => [
            "en" => "Date subsequent to session validation",
            "it" => "Data successiva alla convalida sessione",
        ],
        1273 => [
            "en" => "Date earlier than session validation",
            "it" => "Data precedente alla convalida sessione",
        ],
        1280 => [
            "en" => "Alignment taking place, send the message later",
            "it" => "Quadratura  in  corso,  inviare  il  messaggio successivamente",
        ],
        1281 => [
            "en" => "Rebuy flag missing or wrong",
            "it" => "Flag riacquisto mancante o errato",
        ],
        1290 => [
            "en" => "Player pseudonym missing",
            "it" => "Pseudonimo giocatore assente",
        ],
        1300 => [
            "en" => "Platform not tested for the session type indicated",
            "it" => "Piattaforma non collaudata per il tipo sessione indicato",
        ],
        1310 => [
            "en" => "Network player pseudonym missing",
            "it" => "Pseudonimo giocatore circuito assente",
        ],
        1320 => [
            "en" => "Length of gambling account code field wrong or missing",
            "it" => "Lunghezza campo codice conto  gioco errato o mancante",
        ],
        1330 => [
            "en" => "Gambling account wrong or missing",
            "it" => "Conto di gioco errato o mancante",
        ],
        1333 => [
            "en" => "Status or type of session inconsistent with the type of session end",
            "it" => "Stato o tipo sessione incongruente con tipologia di fine sessione",
        ],
        1335 => [
            "en" => "Non-valid gambling account",
            "it" => "Conto di gioco non valido",
        ],
        1340 => [
            "en" => "Gambling account length not consistent with the account code value",
            "it" => "Lunghezza Conto di gioco non congruente con il valore del codice conto",
        ],
        1350 => [
            "en" => "Number of prizes or winners wrong or missing",
            "it" => "Numero dei premi o dei vincitori errato o mancante",
        ],
        1351 => [
            "en" => "First progressive number wrong or missing",
            "it" => "Progressivo iniziale errato o mancante",
        ],
        1352 => [
            "en" => "Last progressive number wrong or missing",
            "it" => "Progressivo finale errato o mancante",
        ],
        1353 => [
            "en" => "Partial amount wrong or missing",
            "it" => "Importo parziale errato o mancante",
        ],
        1354 => [
            "en" => "Number of wins present in the message wrong or missing",
            "it" => "Numero vincite presenti in invio errato o mancante",
        ],
        1355 => [
            "en" => "Win amount present in the message wrong or missing",
            "it" => "Importo  vincita  presente  in  invio  errato o mancante",
        ],
        1360 => [
            "en" => "Prize amount or transfer wrong or missing",
            "it" => "Importo premio o accredito errato o mancante",
        ],
        1370 => [
            "en" => "Participation right amount below nominal amount",
            "it" => "Importo diritto di partecipazione inferiore al nominale",
        ],
        1380 => [
            "en" => "Session validation - first purchase not allowed",
            "it" => "Sessione  convalidata  -  primo  acquisto  non consentito",
        ],
        1381 => [
            "en" => "Late purchase not allowed",
            "it" => "Acquisto tardivo non consentito",
        ],
        1390 => [
            "en" => "Session already validated",
            "it" => "Sessione gia convalidata",
        ],
        1400 => [
            "en" => "Proponent licensee code missing",
            "it" => "Codice Concessionario proponente mancante",
        ],
        1410 => [
            "en" => "Transmitting licensee code missing",
            "it" => "Codice Concessionario trasmittente mancante",
        ],
        1420 => [
            "en" => "Transaction code missing",
            "it" => "CodiceTransazione mancante",
        ],
        1421 => [
            "en" => "Participation identifier missing or wrong",
            "it" => "Identificativo di Partecipazione mancante o errato",
        ],
        1422 => [
            "en" => "Progressive participation numbering missing or wrong",
            "it" => "Progressivo di Partecipazione mancante o errato",
        ],
        1423 => [
            "en" => "Progressive participation numbering or participation identifier contains a mistaken value",
            "it" => "Progressivo partecipazione o Identificativo partecipazione non correttamente valorizzato.",
        ],
        1424 => [
            "en" => "Total amount of winnings resulting from bonuses missing or wrong",
            "it" => "Importo totale vincita da bonus mancante o errato",
        ],
        1425 => [
            "en" => "The player seems to have terminated his/her participation in the table",
            "it" => "Il  giocatore  risulta  aver  chiuso  la  sua partecipazione al tavolo",
        ],
        1426 => [
            "en" => "The player appears to have exceeded the limit wager amount",
            "it" => "Il  giocatore  risulta  aver  superato  il  limite dell'importo giocabile",
        ],
        1427 => [
            "en" => "The player never joined the table or the progressive participation number is wrong",
            "it" => "Il giocatore non si e mai seduto al tavolo oppure e errato il prog di partecipazione",
        ],
        1428 => [
            "en" => "The player has already joined the table",
            "it" => "Il giocatore e gia seduto al tavolo",
        ],
        1429 => [
            "en" => "Partial amount resulting from bonuses wrong or missing",
            "it" => "Importo parziale da bonus errato o mancante",
        ],
        1430 => [
            "en" => "Date missing",
            "it" => "Data assente",
        ],
        1431 => [
            "en" => "There are still players at the table",
            "it" => "Ci sono ancora giocatori seduti al tavolo",
        ],
        1432 => [
            "en" => "Participation amount below minimum value stated for table",
            "it" => "Importo di partecipazione inferiore al valore minimo dichiarato per il tavolo",
        ],
        1433 => [
            "en" => "Participation amount below above maximum value stated for table",
            "it" => "Importo di partecipazione superiore al valore massimo dichiarato per il tavolo",
        ],
        1440 => [
            "en" => "Message type missing",
            "it" => "Tipo messaggio assente",
        ],
        1441 => [
            "en" => "Message type not foreseen for this type of game transmission",
            "it" => "Tipo messaggio non previsto per modalita di trasmissone del gioco",
        ],
        1443 => [
            "en" => "Message type not foreseen for this type of game",
            "it" => "Tipo messaggio non previsto per tipo gioco",
        ],
        1450 => [
            "en" => "Body length missing",
            "it" => "Lunghezza body assente",
        ],
        1460 => [
            "en" => "Time missing",
            "it" => "Orario assente",
        ],
        1470 => [
            "en" => "BON - bonus value < > B or F",
            "it" => "BON - valore bonus < > B o F",
        ],
        1471 => [
            "en" => "JCK - jackpot value missing or wrong",
            "it" => "JCK - valore jackpot mancante o errato",
        ],
        1472 => [
            "en" => "CUP - value assigned to CUP missing or wrong",
            "it" => "CUP - valore attributo CUP mancante o errato",
        ],
        1473 => [
            "en" => "Bonus amount wrong or missing",
            "it" => "Importo bonus errato o mancante",
        ],
        1474 => [
            "en" => "Network code wrong or missing",
            "it" => "Codice rete errato o mancante",
        ],
        1475 => [
            "en" => "Partial amount resulting from jackpot missing or wrong",
            "it" => "Importo parziale da Jackpot mancante o errato",
        ],
        1476 => [
            "en" => "Partial amounts incorrect",
            "it" => "Importi parziali errati",
        ],
        1477 => [
            "en" => "Value assigned to MNI negative",
            "it" => "Valore attributo MNI negativo",
        ],
        1478 => [
            "en" => "Value assigned to MXI negative",
            "it" => "Valore attributo MXI negativo",
        ],
        1479 => [
            "en" => "Additional jackpot amount missing or wrong",
            "it" => "Importo jackpot aggiuntivo mancante o errato",
        ],
        1480 => [
            "en" => "Bonus participation amount wrong or missing",
            "it" => "Importo  Partecipazione  da  Bonus  errato o mancante",
        ],
        1481 => [
            "en" => "Value assigned to MXI below value assigned to MNI",
            "it" => "Valore  attributo  MXI  inferiore  al  valore dell'attributo MNI",
        ],
        1483 => [
            "en" => "The table has reached the maximum number of participants",
            "it" => "Il tavolo ha gia raggiunto il numero max di partecipanti",
        ],
        1484 => [
            "en" => "Value assigned to RAK negative",
            "it" => "valore attributo RAK negativo",
        ],
        1485 => [
            "en" => "Value assigned to RAK wrong",
            "it" => "valore attributo RAK errato",
        ],
        1486 => [
            "en" => "Value assigned to TAV wrong",
            "it" => "valore attributo TAV errato",
        ],
        1487 => [
            "en" => "Value assigned to SBL negative",
            "it" => "valore attributo SBL negativo",
        ],
        1488 => [
            "en" => "Value assigned to BBL negative",
            "it" => "valore attributo BBL negativo",
        ],
        1489 => [
            "en" => "Value assigned to SBL greater than the value assigned to BBL",
            "it" => "valore  attributo  SBL  maggiore  del  valore dell'attributo BBL",
        ],
        1490 => [
            "en" => "Gambling session identifier assigned by the validation system missing",
            "it" => "Identificativo sessione di gioco assegnato dal sistema di convalida assente",
        ],
        1491 => [
            "en" => "Value assigned to MNI above limit allowed by regulations",
            "it" => "valore attributo MNI superiore al limite consentito da normativa",
        ],
        1492 => [
            "en" => "Value assigned to MXI above limit allowed by regulations",
            "it" => "valore attributo MXI superiore al limite consentito da normativa",
        ],
        1493 => [
            "en" => "value VIN attribute missing or wrong",
            "it" => "valore attributo VIN mancante o errato",
        ],
        1494 => [
            "en" => "value MXP attribute missing or wrong",
            "it" => "valore attributo MXP mancante o errato",
        ],
        1495 => [
            "en" => "value RTP attribute missing or wrong",
            "it" => "valore attributo RTP mancante o errato",
        ],
        1496 => [
            "en" => "value NSI attribute missing or wrong",
            "it" => "valore attributo NSI mancante o errato",
        ],
        1497 => [
            "en" => "id_sezione_in field missing or wrong",
            "it" => "campo id_sezione_in mancante o errato",
        ],
        1500 => [
            "en" => "Player type missing",
            "it" => "Tipologia Giocatore mancante",
        ],
        1510 => [
            "en" => "Licensee code where the gambling account was opened missing",
            "it" => "Codice concessionario presso il quale e aperto il conto di gioco assente",
        ],
        1511 => [
            "en" => "Network code value wrong",
            "it" => "Errata valorizzazione codice rete",
        ],
        1520 => [
            "en" => "Licensee code where the gambling account was opened non-existent",
            "it" => "Codice concessionario presso il quale e aperto il conto di gioco inesistente",
        ],
        1530 => [
            "en" => "Body length stated does not coincide with actual one",
            "it" => "Lunghezza body dichiarata non coincidente con quella effettiva",
        ],
        1540 => [
            "en" => "Generic database error",
            "it" => "Errore generico base dati",
        ],
        1550 => [
            "en" => "Proponent code differs from transmitter code",
            "it" => "Codice proponente diverso da codice trasmittente",
        ],
        1560 => [
            "en" => "Proponent licensee temporarily deactivated",
            "it" => "Concessionario  proponente  temporaneamente disabilitato",
        ],
        1570 => [
            "en" => "Number of prizes not consistent with number stated",
            "it" => "Numero  premi  non  congruente  con quello dichiarato",
        ],
        1571 => [
            "en" => "Number of winners not consistent with number stated",
            "it" => "Numero vincitori non congruente con quello dichiarato",
        ],
        1580 => [
            "en" => "Number of winners stated differs from actual number",
            "it" => "Numero vincitori dichiarato diverso da quello effettivo",
        ],
        1590 => [
            "en" => "Prize money percentage stated in msg200 different to that stated in msg240",
            "it" => "Percentuale montepremi dichiarato nel msg200 diverso da quello dichiarato nel msg240",
        ],
        1600 => [
            "en" => "Percentage of prize money wrong",
            "it" => "Percentuale montepremi errata",
        ],
        1603 => [
            "en" => "Number of attributes below compulsory number",
            "it" => "Numero attributi inferiore a quelli obbligatori",
        ],
        1610 => [
            "en" => "IP address missing",
            "it" => "Indirizzo IP assente",
        ],
        1620 => [
            "en" => "Region wrong or missing",
            "it" => "Regione errata o mancante",
        ],
        1630 => [
            "en" => "Player pseudonym length wrong or missing",
            "it" => "Lunghezza pseudonimo del giocatore errata o mancante",
        ],
        1631 => [
            "en" => "Player pseudonym associated to another gambling account",
            "it" => "Pseudonimo del giocatore associato ad altro conto di gioco",
        ],
        1640 => [
            "en" => "Network player pseudonym length wrong or missing",
            "it" => "Lunghezza pseudonimo del giocatore nel circuito errata o mancante",
        ],
        1650 => [
            "en" => "Player pseudonym length inconsistent with pseudonym value",
            "it" => "Lunghezza pseudonimo del giocatore incongruente con valore pseudonimo",
        ],
        1660 => [
            "en" => "Network player pseudonym length inconsistent with pseudonym value",
            "it" => "Lunghezza pseudonimo del giocatore nel circuito incongruente con valore pseudonimo",
        ],
        1670 => [
            "en" => "Win amount not equivalent to prize money paid out",
            "it" => "Somma vincite non equivalente al montepremi erogato",
        ],
        1671 => [
            "en" => "Jackpot amount not consistent with jackpot paid out",
            "it" => "Somma jackpot non congruente con jackpot erogato",
        ],
        1672 => [
            "en" => "Additional jackpot amount not consistent with jackpot paid out",
            "it" => "Somma jackpot aggiuntivo non congruente con jackpot erogato",
        ],
        1700 => [
            "en" => "The session is already closed",
            "it" => "La sessione e stata gia chiusa",
        ],
        1710 => [
            "en" => "Not authorised to rebuy for the session in question",
            "it" => "Non e abilitato il rebuy per la sessione considerata",
        ],
        1720 => [
            "en" => "Maximum rebuy threshold exceeded",
            "it" => "Soglia massima importo rebuy superata",
        ],
        1740 => [
            "en" => "Payment already made to player account during the same session",
            "it" => "Accredito gia effettuato sul conto del giocatore nella stessa sessione",
        ],
        1750 => [
            "en" => "Participation right non-existent",
            "it" => "Diritto di partecipazione inesistente",
        ],
        1760 => [
            "en" => "Duplicate record",
            "it" => "Record duplicato",
        ],
        1770 => [
            "en" => "Connectivity services supplier missing",
            "it" => "Fornitore servizi di connettivita assente",
        ],
        1781 => [
            "en" => "Communication modality wrong or missing",
            "it" => "Modalita comunicazione errata o mancante",
        ],
        1810 => [
            "en" => "The session is closed",
            "it" => "La sessione e chiusa",
        ],
        1820 => [
            "en" => "Session closed and not validated",
            "it" => "La sessione e chiusa o non convalidata",
        ],
        1821 => [
            "en" => "Session not validated",
            "it" => "La sessione non e convalidata",
        ],
        1822 => [
            "en" => "Multi-stage session not completely validated",
            "it" => "La sessione multi-fase non e completamente convalidata",
        ],
        1830 => [
            "en" => "Game code not consistent with gambling session",
            "it" => "Codice gioco non congruente con sessione di gioco",
        ],
        1870 => [
            "en" => "Payment cannot be made, session not validated",
            "it" => "Accredito  non  effettuabile,  sessione  non convalidata",
        ],
        1871 => [
            "en" => "Progressive numbering missing or wrong",
            "it" => "Progressivo mancante o errato",
        ],
        1872 => [
            "en" => "Progressive numbering not consistent with session",
            "it" => "Progressivo incongruente con la sessione",
        ],
        1880 => [
            "en" => "Cancellation not allowed",
            "it" => "Annullamento non consentito",
        ],
        1881 => [
            "en" => "End of participation not allowed",
            "it" => "Fine partecipazione non consentita",
        ],
        1890 => [
            "en" => "Request made during servicing hours not allowed",
            "it" => "Richiesta effettuata in fascia oraria del servizio  non consentita",
        ],
        1910 => [
            "en" => "Participation right cancelled",
            "it" => "Diritto di partecipazione annullato",
        ],
        1920 => [
            "en" => "Licensee session identifier does not coincide with that of the session in question",
            "it" => "Identificativo sessione del concessionario non coincide con quello della Sessione di riferimento",
        ],
        1921 => [
            "en" => "The licensee is not the session proponent",
            "it" => "Il concessionario non e proponente della sessione",
        ],
        1930 => [
            "en" => "Proponent licensee session identifier does not coincide with that of the session in question",
            "it" => "Codice concessionario proponente non coincide con quello della Sessione di riferimento",
        ],
        1940 => [
            "en" => "Connectivity Service Supplier code does not coincide with that of the session in question",
            "it" => "Codice Fornitore Servizio di Connettivita non coincide con quello della Sessione di riferimento",
        ],
        1950 => [
            "en" => "Session date does not coincide with that of the session in question",
            "it" => "Data della sessione non coincide con quello della Sessione di riferimento",
        ],
        1960 => [
            "en" => "Game code does not coincide with that of the session in question  Network code does not coincide with that of the",
            "it" => "Codice del gioco non coincide con quello della Sessione di riferimento",
        ],
        1970 => [
            "en" => "session in question",
            "it" => "Codice del circuito non coincide con quello della Sessione di riferimento",
        ],
        1990 => [
            "en" => "Number of attributes missing or wrong",
            "it" => "Numero degli attributi mancante o errato",
        ],
        2000 => [
            "en" => "Session attribute missing or wrong",
            "it" => "Attributo sessione mancante o errato",
        ],
        2010 => [
            "en" => "Session attribute value missing or wrong",
            "it" => "Valore attributo sessione mancante o errato",
        ],
        2011 => [
            "en" => "Compulsory session attribute missing or wrong",
            "it" => "Attributo sessione obbligatorio mancante o errato",
        ],
        2020 => [
            "en" => "Licensee gambling session identifier missing or wrong",
            "it" => "Identificativo della sessione di gioco concessionario mancante o errato",
        ],
        2030 => [
            "en" => "Gambling session identifier validation system missing or wrong",
            "it" => "Identificativo della sessione di gioco sistema di convalida mancante o errato",
        ],
        2031 => [
            "en" => "Connected session identifier missing or wrong",
            "it" => "Identificativo sessione collegata mancante o errato",
        ],
        2032 => [
            "en" => "Session closing flag differs from 1 and 2",
            "it" => "Flag chiusura sessione diversa da 1 e 2",
        ],
        2040 => [
            "en" => "Participation right fee missing or wrong",
            "it" => "Importo diritto di partecipazione mancante o errato",
        ],
        2050 => [
            "en" => "Time missing or wrong",
            "it" => "Orario mancante o errato",
        ],
        2060 => [
            "en" => "Session not awaiting validation",
            "it" => "Sessione non in attesa di convalida",
        ],
        2061 => [
            "en" => "First level session not awaiting validation",
            "it" => "Sezione di primo livello non in attesa di convalida",
        ],
        2070 => [
            "en" => "Prize money amount missing or wrong",
            "it" => "Importo montepremi mancante o errato",
        ],
        2071 => [
            "en" => "Jackpot amount missing or wrong",
            "it" => "Importo jackpot mancante o errato",
        ],
        2073 => [
            "en" => "Total additional jackpot amount missing",
            "it" => "Importo jackpot aggiuntivo totale mancante",
        ],
        2074 => [
            "en" => "Prize amount resulting from jackpot missing or wrong",
            "it" => "Importo premio da jackpot mancante o errato",
        ],
        2075 => [
            "en" => "Progressive rectification numbering missing or wrong",
            "it" => "Progressivo rettifica mancante o errato",
        ],
        2076 => [
            "en" => "Additional prize amount resulting from jackpot missing or wrong",
            "it" => "Importo premio da jackpot aggiuntivo mancante o errato",
        ],
        2077 => [
            "en" => "Message cannot be rectified, winners already transmitted",
            "it" => "Messaggio non rettificabile, vincitori gia trasmessi",
        ],
        2078 => [
            "en" => "Message cannot be rectified, payments already transmitted",
            "it" => "Messaggio non rettificabile, accrediti gia trasmessi",
        ],
        2079 => [
            "en" => "Message cannot be rectified, linked jackpot used in another connected session",
            "it" => "Messaggio  non  rettificabile,  vincita  vincolata utilizzata in altra sessione collegata",
        ],
        2080 => [
            "en" => "The prize amounts not indicated in decreasing order",
            "it" => "Importo  dei  premi  non  indicato  in  ordine decrescente",
        ],
        2081 => [
            "en" => "Amounts not indicated in decreasing order",
            "it" => "Importi non indicati in ordine decrescente",
        ],
        2090 => [
            "en" => "Session closed or cancelled",
            "it" => "Sessione chiusa o annullata",
        ],
        2100 => [
            "en" => "Prize money percentage differs to that included in previous message sent",
            "it" => "Percentuale montepremi diversa da precedente invio messaggio",
        ],
        2110 => [
            "en" => "Prize money differs to that included in previous message sent",
            "it" => "Montepremi diverso da precedente invio messaggio",
        ],
        2111 => [
            "en" => "Jackpot differs to that included in previous message sent",
            "it" => "Jackpot diverso da precedente invio messaggio",
        ],
        2112 => [
            "en" => "The session does not foresee jackpots",
            "it" => "La sessione non prevede jackpot",
        ],
        2130 => [
            "en" => "Number of start of partial input not consistent",
            "it" => "Numero inizio inserimento parziale non coerente",
        ],
        2140 => [
            "en" => "Play bonus nominal amount <0 or greater than allowed",
            "it" => "Importo nominale da play bonus <0 o superiore al consentito",
        ],
        2150 => [
            "en" => "Prize amount not below prizes previously transmitted",
            "it" => "Importo premio non inferiore a premi gia trasmessi",
        ],
        2151 => [
            "en" => "Win amount not below winnings previously transmitted",
            "it" => "Importo  vincita  non  inferiore  a  vincite gia trasmesse",
        ],
        2160 => [
            "en" => "First prize number to be included missing or wrong",
            "it" => "Numero premio da inserire iniziale mancante o errato",
        ],
        2170 => [
            "en" => "Last prize number to be included missing or wrong",
            "it" => "Numero premio da inserire finale mancante o errato",
        ],
        2171 => [
            "en" => "Partial prize money missing or wrong",
            "it" => "Montepremi parziale mancante o errato",
        ],
        2172 => [
            "en" => "Additional partial jackpot amount missing or wrong",
            "it" => "Importo jackpot parziale mancante o errato",
        ],
        2173 => [
            "en" => "Number of prizes found in the message sent missing or wrong",
            "it" => "Numero premi presenti nell'invio mancante o errato",
        ],
        2175 => [
            "en" => "Rectification flag missing or wrong",
            "it" => "Flag rettifica mancante o errato",
        ],
        2180 => [
            "en" => "Prize amounts transmitted not consistent with partial prize money",
            "it" => "Importi dei premi trasmesso non congruenti con montepremi parziale",
        ],
        2190 => [
            "en" => "Partial prize amount transmitted not consistent with prize money paid out",
            "it" => "Importo  montepremi  parziale  trasmesso non congruente col montepremi erogato",
        ],
        2191 => [
            "en" => "Partial jackpot amount transmitted not consistent with jackpot paid out",
            "it" => "Importo jackpot parziale trasmesso non congruente col jackpot erogato",
        ],
        2192 => [
            "en" => "Additional partial jackpot amount transmitted not consistent with jackpot paid out",
            "it" => "Importo jackpot parziale aggiuntivo trasmesso non congruente col jackpot erogato",
        ],
        2200 => [
            "en" => "Day missing or wrong",
            "it" => "Giorno mancante o errato",
        ],
        2210 => [
            "en" => "Month missing or wrong",
            "it" => "Mese mancante o errato",
        ],
        2220 => [
            "en" => "Year missing or wrong",
            "it" => "Anno mancante o errato",
        ],
        2230 => [
            "en" => "Hour missing or wrong",
            "it" => "Ora mancante o errata",
        ],
        2240 => [
            "en" => "Minutes missing or wrong",
            "it" => "Minuti mancanti o errati",
        ],
        2250 => [
            "en" => "Seconds missing or wrong",
            "it" => "Secondi mancanti o errati",
        ],
        2260 => [
            "en" => "Participation right already given as winning",
            "it" => "Diritto di partecipazione gia risultante vincente",
        ],
        2270 => [
            "en" => "Win non-existent",
            "it" => "Vincita non esistente",
        ],
        2271 => [
            "en" => "Invalidation not authorised by ADM",
            "it" => "Invalidazione non autorizzata da AAMS",
        ],
        2280 => [
            "en" => "Session non-existent",
            "it" => "Sessione non esistente",
        ],
        2290 => [
            "en" => "Prize plan missing or incomplete",
            "it" => "Piano dei premi mancante o incompleto",
        ],
        2300 => [
            "en" => "Winner list missing or incomplete",
            "it" => "Lista vincitori mancante o incompleta",
        ],
        2301 => [
            "en" => "Amounts in winner list inconsistent",
            "it" => "Importi in lista vincitori incongruenti",
        ],
        2302 => [
            "en" => "Number of winners differs to that included in previous message sent",
            "it" => "Numero vincitori diverso da precedente invio messaggio",
        ],
        2303 => [
            "en" => "Total amount of winnings missing or wrong",
            "it" => "Importo totale vincite mancante o errato",
        ],
        2304 => [
            "en" => "Total amount of jackpot winnings missing or wrong",
            "it" => "Importo totale vincite da jackpot mancante o errato",
        ],
        2305 => [
            "en" => "Total amount of additional jackpot winnings missing or wrong",
            "it" => "Importo totale vincite da jackpot aggiuntivo mancante o errato",
        ],
        2310 => [
            "en" => "Prizes won not consistent with prize money paid out",
            "it" => "Importo  premi  vinti  non  congruiente  con montepremi erogato",
        ],
        2320 => [
            "en" => "Payments missing or incomplete",
            "it" => "Accrediti mancanti o incompleti",
        ],
        2330 => [
            "en" => "Prize money distributed below the percentage of amounts collected set by the legislation",
            "it" => "Montepremi distribuito inferiore alla percentuale della raccolta stabilita della normativa",
        ],
        2340 => [
            "en" => "Game type code wrong or missing",
            "it" => "Codice tipo gioco errato o mancante",
        ],
        2350 => [
            "en" => "Participation end amount resulting from real bonuses wrong or missing",
            "it" => "Importo fine partecipazione da real bonus errato o mancante",
        ],
        2360 => [
            "en" => "Session end date prior to presumed session end",
            "it" => "Data fine sessione precedente alla fine sessione presunta",
        ],
        2361 => [
            "en" => "Session end date prior to start date session",
            "it" => "Data fine sessione precedente alla data di inizio  sessione",
        ],
        2370 => [
            "en" => "Session end date prior to presumed session end",
            "it" => "Data fine sessione precedente alla fine sessione presunta",
        ],
        2380 => [
            "en" => "Session end date beyond the maximum presumed session end allowed",
            "it" => "Data fine sessione oltre la massima fine sessione presunta consentita",
        ],
        2390 => [
            "en" => "Request identifier wrong or missing",
            "it" => "Identificativo richiesta errato o mancante",
        ],
        2391 => [
            "en" => "Amount exceeds that of ticket",
            "it" => "Importo superiore a quello del ticket",
        ],
        2392 => [
            "en" => "Invalidation not authorised by ADM",
            "it" => "Invalidazione non autorizzata da aams",
        ],
        2393 => [
            "en" => "Day not yet checked",
            "it" => "Giornata non ancora controllata",
        ],
        2394 => [
            "en" => "No anomalies present",
            "it" => "Non sono presenti anomalie",
        ],
        2400 => [
            "en" => "Stage number missing or wrong",
            "it" => "Numero fasi mancante o errato",
        ],
        2401 => [
            "en" => "Progressive number of first stage missing or wrong",
            "it" => "Progressivo fase iniziale mancante o errato",
        ],
        2402 => [
            "en" => "Real bonus amounts waged wrong or missing",
            "it" => "Importo Puntato da real bonus errato o inesistente",
        ],
        2403 => [
            "en" => "Progressive number of last stage missing or wrong",
            "it" => "Progressivo fase finale mancante o errato",
        ],
        2404 => [
            "en" => "Amount waged from play bonuses wrong or missing",
            "it" => "Importo Puntato da play bonus errato o mancante",
        ],
        2405 => [
            "en" => "Date of game stages missing or wrong",
            "it" => "Data fasi di gioco mancante o errata",
        ],
        2406 => [
            "en" => "Hour of game stages missing or wrong",
            "it" => "Orario fase di gioco mancante o errata",
        ],
        2407 => [
            "en" => "Amount paid out from jackpots within the game missing or wrong",
            "it" => "Importo da jackpot interno al gioco mancante o errato",
        ],
        2408 => [
            "en" => "Day closing flag missing or wrong",
            "it" => "Flag chiusura giornata mancante o errato",
        ],
        2409 => [
            "en" => "Amount won from additional jackpot missing or wrong",
            "it" => "ImportoVinto da jackpot aggiuntivo errato o inesistente",
        ],
        2410 => [
            "en" => "Session status incompatible with invalidation  request",
            "it" => "Stato  sessione incompatilile  con  richiesta  invalidazione",
        ],
        2411 => [
            "en" => "Number of players missing or wrong",
            "it" => "Numero giocatori mancante o errato",
        ],
        2412 => [
            "en" => "Licensee income missing or wrong",
            "it" => "Introito concessionario mancante o errato",
        ],
        2413 => [
            "en" => "Withdrawal flag missing or wrong",
            "it" => "Flag prelievo mancante o errato",
        ],
        2414 => [
            "en" => "Pot amount missing or wrong",
            "it" => "Importo piatto mancante o errato",
        ],
        2415 => [
            "en" => "Real bonus win amounts wrong or missing",
            "it" => "Importo vinto da real bonus errato o mancante",
        ],
        2416 => [
            "en" => "Progressive number of stage missing or wrong",
            "it" => "Progressivo fase mancante o errato",
        ],
        2417 => [
            "en" => "Participation right identifier missing or wrong",
            "it" => "Identificativo diritto partecipazione mancante o errato",
        ],
        2418 => [
            "en" => "Amount available missing or wrong",
            "it" => "Importo disponibile mancante o errato",
        ],
        2419 => [
            "en" => "Win amount (if any) missing or wrong",
            "it" => "Importo (eventuale) vincita mancante o errato",
        ],
        2420 => [
            "en" => "Amount bet (if any) missing or wrong",
            "it" => "Importo (eventuale) puntato mancante o errato",
        ],
        2421 => [
            "en" => "Income amount missing or wrong",
            "it" => "Importo introito mancante o errato",
        ],
        2422 => [
            "en" => "Player data inconsistent",
            "it" => "Dati del giocatire incongruenti",
        ],
        2423 => [
            "en" => "Game stages inconsistent",
            "it" => "Fasi di gioco incongruenti",
        ],
        2424 => [
            "en" => "Last game stages not transmitted for the previous day",
            "it" => "Ultime fasi di gioco non trasmesse per giornata precedente",
        ],
        2425 => [
            "en" => "Day already closed",
            "it" => "Giornata gia chiusa",
        ],
        2426 => [
            "en" => "Amount won from play bonuses missing or wrong",
            "it" => "Importo vinto da paly bonus mancante o errato",
        ],
        2427 => [
            "en" => "Number of stages sent exceeds the total number of stages played",
            "it" => "Numero delle fasi inviate superiore al totale delle fasi giocate",
        ],
        2428 => [
            "en" => "Number of stages with income exceeding the total number of stages played",
            "it" => "Numero delle fasi con introito superiore al totale delle fasi giocate",
        ],
        2429 => [
            "en" => "Total number of game stages inconsistent with what was previously stated",
            "it" => "Numero totale delle fasi di gioco incongruenti con  quanto dichiarato in precedenza",
        ],
        2430 => [
            "en" => "Number of game stages sent inconsistent with what had been previously stated",
            "it" => "Numero fasi di gioco inviate incongruenti con quanto dichiarato in precedenza",
        ],
        2431 => [
            "en" => "The day in question has not been closed",
            "it" => "La giornata di riferimento non e stata chiusa",
        ],
        2432 => [
            "en" => "No 580 message was sent for the date in question",
            "it" => "Non e stato inviato alcun messaggio 580 per la data di riferimento",
        ],
        2433 => [
            "en" => "Right identifier not pertaining to the session/table",
            "it" => "Identificativo diritto non della sessione-tavolo",
        ],
        2434 => [
            "en" => "Real bonus nominal amount <0 or greater than allowed",
            "it" => "Importo nominale da real bonus <0 o superiore al consentito",
        ],
        2435 => [
            "en" => "Reference date missing or wrong",
            "it" => "Data di riferimento mancante o errata",
        ],
        2436 => [
            "en" => "Total number of game stages missing or wrong",
            "it" => "Numero totale fasi di gioco mancanto o errato",
        ],
        2437 => [
            "en" => "Number of game stages sent missing or wrong",
            "it" => "Numero fasi di gioco inviate mancante o errato",
        ],
        2438 => [
            "en" => "Total number of game stages with income missing or wrong",
            "it" => "Numero totale delle fasi con introito mancante o errato",
        ],
        2439 => [
            "en" => "Number of participating licensees missing or wrong",
            "it" => "Numero concessionari partecipanti mancante o errato",
        ],
        2440 => [
            "en" => "Licensee code wrong or missing",
            "it" => "Codice concessionario errato o mancante",
        ],
        2441 => [
            "en" => "Total amount of bets missing or wrong",
            "it" => "Importo totale delle puntate mancante o errato",
        ],
        2442 => [
            "en" => "Total amount of winnings missing or wrong",
            "it" => "Importo totale delle vincite mancante o errato",
        ],
        2443 => [
            "en" => "Total amount of income made or lost missing or wrong",
            "it" => "Importo totale introito raccolto o perso mancante o errato",
        ],
        2444 => [
            "en" => "Total amount of jackpot assigned missing or wrong",
            "it" => "Importo totale del jackpot assegnato mancante o errato",
        ],
        2445 => [
            "en" => "Number of participating licensees not consistent with number stated",
            "it" => "Numero  dei  concessionari partecipanti non congruente con quello dichiarato",
        ],
        2446 => [
            "en" => "Number of stages with income exceeds the number of game stages sent",
            "it" => "Numero delle fasi con introito superiore alle fasi di gioco inviate",
        ],
        2447 => [
            "en" => "Stage inconsistent or already transmitted",
            "it" => "Fase incongruente o gia trasmessa",
        ],
        2448 => [
            "en" => "Date and time of stage execution inconsistent with day in question",
            "it" => "Data ed ora svolgimento fase incongruente con giornata di riferimento",
        ],
        2449 => [
            "en" => "No 780 message was sent for the date in question",
            "it" => "Non e stato inviato alcun messaggio 780 per la data di riferimento",
        ],
        2450 => [
            "en" => "Stage time not subsequent to previous stage transmitted",
            "it" => "Orario fase non successivo a precedente fase trasmessa",
        ],
        2451 => [
            "en" => "No accounting data present",
            "it" => "Non sono presenti dati contabili",
        ],
        2452 => [
            "en" => "Licensee not authorised to make the request",
            "it" => "Concessionario non autorizzato alla richiesta",
        ],
        2453 => [
            "en" => "The player does not appear as a winner in the connected session",
            "it" => "Il giocatore non risulta vincente nella sessione collegata",
        ],
        2454 => [
            "en" => "Win already used in another session",
            "it" => "Vincita gia utilizzata in altra sessione",
        ],
        2455 => [
            "en" => "Amount earmarked for jackpot fund missing or wrong",
            "it" => "Importo a fondo jackpot mancante o errato",
        ],
        2456 => [
            "en" => "Total number of software modules sent missing or wrong",
            "it" => "Numero totale dei moduli software inviati mancato o errato",
        ],
        2457 => [
            "en" => "Item type missing or wrong",
            "it" => "Tipologia elemento mancato o errato",
        ],
        2458 => [
            "en" => "Item code missing or wrong",
            "it" => "Codice elemento mancato o errato",
        ],
        2459 => [
            "en" => "Module recognition details missing or wrong",
            "it" => "Estremi riconoscimento modulo mancato o errato",
        ],
        2460 => [
            "en" => "SHA1 missing or wrong",
            "it" => "SHA1 mancato o errato",
        ],
        2461 => [
            "en" => "Amount won from bonuses missing or wrong",
            "it" => "Importo vinto da bonus mancante o errato",
        ],
        2462 => [
            "en" => "Amount waged from real bonuses must be positive",
            "it" => "Importo puntato da real bonus deve essere positivo",
        ],
        2463 => [
            "en" => "Amount waged from play bonuses must be positive",
            "it" => "Importo puntato da play bonus deve essere positivo",
        ],
        2464 => [
            "en" => "Amount won from real bonuses missing or wrong",
            "it" => "Importo vinto da real bonus mancante o errato",
        ],
        2465 => [
            "en" => "Amount won from play bonuses missing or wrong",
            "it" => "Importo vinto da play bonus mancante o errato",
        ],
        2466 => [
            "en" => "Prize amount from jackpots within the game missing or wrong",
            "it" => "Importo premio da jackpot interno al gioco mancante o errato",
        ],
        2467 => [
            "en" => "Prize amount resulting from additional jackpots missing or wrong",
            "it" => "Importo premio da jackpot aggiuntivo mancante o errato",
        ],
        2468 => [
            "en" => "Amount available for real bonuses <0 or greater than allowed",
            "it" => "Importo disponibile da real bonus <0 o superiore al consentito",
        ],
        2469 => [
            "en" => "Amount available for play bonuses <0 or greater than allowed",
            "it" => "Importo disponibile da play bonus <0 o superiore al consentito",
        ],
        2470 => [
            "en" => "Amount waged from real bonuses <0 or greater than allowed",
            "it" => "Importo puntato da real bonus <0 o superiore al consentito",
        ],
        2471 => [
            "en" => "Amount waged from play bonuses <0 or greater than allowed",
            "it" => "Importo puntato da play bonus <0 o superiore al consentito",
        ],
        2472 => [
            "en" => "Amount won from real bonuses <0 or greater than allowed",
            "it" => "Importo vinto da real bonus <0 o superiore al consentito",
        ],
        2473 => [
            "en" => "Amount won from play bonuses <0 or greater than allowed",
            "it" => "Importo vinto da play bonus <0 o superiore al consentito",
        ],
        2474 => [
            "en" => "Amount won from jk1 <0 or greater than allowed",
            "it" => "Importo vinto da jk1 <0 o superiore al consentito",
        ],
        2475 => [
            "en" => "Amount won from jk2 <0 or greater than allowed",
            "it" => "Importo vinto da jk2 <0 o superiore al consentito",
        ],
        2476 => [
            "en" => "Amount available from bonuses missing or wrong",
            "it" => "Importo disponibile da bonus mancante o errato",
        ],
        2477 => [
            "en" => "Amount waged from bonuses wrong",
            "it" => "importo puntato da bonus errato",
        ],
        2478 => [
            "en" => "Amount available from bonuses missing or wrong",
            "it" => "importo disponibile da bonus mancante o errato",
        ],
        2479 => [
            "en" => "Bonus amount <0 or greater than allowed",
            "it" => "Importo bonus <0 o superiore al consentito",
        ],
        2480 => [
            "en" => "Bonus win amounts wrong or missing",
            "it" => "Importo vinto bonus errato o mancante",
        ],
        2481 => [
            "en" => "Amount waged wrong or missing",
            "it" => "Importo Puntato errato o mancante",
        ],
        2482 => [
            "en" => "Total amount returned missing or wrong",
            "it" => "Importo totale restituito mancato o errato",
        ],
        2483 => [
            "en" => "Data summary inconsistent",
            "it" => "Riepilogo dati incongruente",
        ],
        2484 => [
            "en" => "2484 - NMG bonus value wrong",
            "it" => "2484 - NMG valore bonus errato",
        ],
        2485 => [
            "en" => "Session type code wrong or missing",
            "it" => "Codice tipo sessione errato o mancante",
        ],
        2486 => [
            "en" => "The player does not appear to be involved in the table",
            "it" => "Il giocatore non risulta seduto al tavolo",
        ],
        2487 => [
            "en" => "Certificate not valid",
            "it" => "Certificato non valido",
        ],
        2488 => [
            "en" => "Certificate expired",
            "it" => "Certificato scaduto",
        ],
        2489 => [
            "en" => "Certificate CA not valid",
            "it" => "CA del certificato non valida",
        ],
        2490 => [
            "en" => "Num_seriale_cert field missing or wrong",
            "it" => "Campo num_seriale_cert mancante o errato",
        ],
        2491 => [
            "en" => "Lun_cert field missing or wrong",
            "it" => "Campo lun_cert mancante o errato",
        ],
        2492 => [
            "en" => "Date session end exceeds validation by 45 days",
            "it" => "Data fine sessione superiore a 45 giorni dalla convalida",
        ],
        2493 => [
            "en" => "Cod_tipo_tag field missing or wrong",
            "it" => "Campo cod_tipo_tag mancante o errato",
        ],
        2494 => [
            "en" => "Cod_tipo_tag field missing or wrong",
            "it" => "Campo prog_versione_cert mancante o errato",
        ],
        2495 => [
            "en" => "Prog_sub_versione_cert field missing or wrong",
            "it" => "Campo prog_sub_versione_cert mancante o errato",
        ],
        2496 => [
            "en" => "Lun_nome_modulo_critico field missing or wrong",
            "it" => "Campo lun_nome_modulo_critico  mancante  o errato",
        ],
        2497 => [
            "en" => "Nome_modulo_critico not communicated by EVA",
            "it" => "Nome_modulo_critico non comunicato da EVA",
        ],
        2498 => [
            "en" => "Hash_code_modulo_critico differs from the one communicated by EVA",
            "it" => "Hash_code_modulo_critico differente da quello comunicato da EVA",
        ],
        self::DATA_BASE_ERROR => [
            "en" => "Error in data base update operations of the validation system",
            "it" => "Errore operazioni di aggiornamento banche dati del sistema di convalida",
        ],
    ];

    /**
     * @param int $code
     * @param array $params
     * @return string
     */
    public function getCodeDescription(int $code, array $params = [], string $language = 'en'): string
    {
        if (isset(self::$codes[$code])) {
            return strtr(self::$codes[$code][$language], $params);
        } elseif ($code > self::DATA_BASE_ERROR) {
            return (self::$codes[self::UNKNOWN_CODE][$language]);
        }

        return (self::$codes[0][$language]);
    }

    /**
     * @return array|int[]
     */
    public static function retryStatusCodes() : array
    {
        return self::$retryStatusCodes;
    }
}