<?php
// =============================================================================
// pizzeria.php — Esercizio: prenotazione di un posto in pizzeria
// Pagina unica: mostra il form, valida i campi obbligatori all'invio (POST)
// e nella stessa pagina mostra l'errore oppure il riepilogo dell'ordine.
// Tutto l'output è filtrato con htmlspecialchars() per sicurezza.
// =============================================================================

// --- Opzioni disponibili (chiave => etichetta mostrata a video) -------------
$sistemazioni = [                 // tipo di sistemazione (obbligatorio, singola)
    'sala'     => 'Sala interna',
    'terrazza' => 'Terrazza',
    'giardino' => 'Giardino',
    'bancone'  => 'Bancone',
];

$pizze = [                        // tipo di pizza (obbligatorio, singola)
    'margherita'       => 'Margherita',
    'marinara'         => 'Marinara',
    'diavola'          => 'Diavola',
    'capricciosa'      => 'Capricciosa',
    'quattro_stagioni' => 'Quattro Stagioni',
    'bufala'           => 'Bufala',
];

$impasti = [                      // tipo di impasto (obbligatorio, singolo)
    'napoletano'    => 'Napoletano',
    'romano'        => 'Romano',
    'integrale'     => 'Integrale',
    'senza_glutine' => 'Senza glutine',
];

$aggiunte = [                     // eventuali aggiunte (facoltative, multiple)
    'extra_bufala' => 'Extra mozzarella di bufala',
    'funghi'       => 'Funghi champignon',
    'olive'        => 'Olive taggiasche',
    'salame'       => 'Salame piccante',
    'prosciutto'   => 'Prosciutto crudo',
    'rucola'       => 'Rucola fresca',
];

// --- Gestione dell'invio del form -------------------------------------------
$errors = [];     // elenco dei messaggi d'errore
$inviato = false; // true se il form è stato inviato
$ordine  = null;  // dati validati, usati per il riepilogo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inviato = true;

    // Recupero i valori inviati (con fallback sicuri)
    $sistemazione = $_POST['sistemazione'] ?? '';
    $pizza        = $_POST['pizza'] ?? '';
    $impasto      = $_POST['impasto'] ?? '';
    $extra        = $_POST['aggiunte'] ?? [];
    if (!is_array($extra)) {
        $extra = [];
    }

    // Validazione dei campi obbligatori tramite "whitelist" (chiavi ammesse)
    if (!array_key_exists($sistemazione, $sistemazioni)) {
        $errors[] = 'Devi selezionare il tipo di sistemazione.';
    }
    if (!array_key_exists($pizza, $pizze)) {
        $errors[] = 'Devi selezionare il tipo di pizza.';
    }
    if (!array_key_exists($impasto, $impasti)) {
        $errors[] = 'Devi selezionare il tipo di impasto.';
    }

    // Le aggiunte sono facoltative: mantengo solo quelle valide
    $extra = array_values(array_intersect($extra, array_keys($aggiunte)));

    // Se non ci sono errori preparo i dati per il riepilogo
    if (empty($errors)) {
        $ordine = [
            'sistemazione' => $sistemazioni[$sistemazione],
            'pizza'        => $pizze[$pizza],
            'impasto'      => $impasti[$impasto],
            'aggiunte'     => $extra,
        ];
    }
}

// --- Funzioni di supporto per il form (valori "sticky" e output sicuro) -----
function veciale(string $campo, string $default = ''): string
{
    return htmlspecialchars($_POST[$campo] ?? $default, ENT_QUOTES, 'UTF-8');
}
function selezionato(string $campo, string $valore): string
{
    return (($_POST[$campo] ?? '') === $valore) ? ' checked' : '';
}
function spuntato(string $campo, string $valore): string
{
    return in_array($valore, (array)($_POST[$campo] ?? []), true) ? ' checked' : '';
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prenota il tuo posto in pizzeria</title>
    <style>
        :root { --bordo:#d8d8d8; --accento:#c0392b; --ok:#1e8e3e; --err:#c0392b; }
        * { box-sizing: border-box; }
        body { font-family: system-ui, "Segoe UI", Arial, sans-serif; background:#f6f6f6; color:#222; margin:0; padding:2rem 1rem; }
        main { max-width: 640px; margin: 0 auto; }
        h1 { font-size: 1.5rem; margin: 0 0 1.25rem; }
        .card { background:#fff; border:1px solid var(--bordo); border-radius:10px; padding:1.5rem; margin-bottom:1.25rem; }
        fieldset { border:1px solid var(--bordo); border-radius:8px; padding:1rem 1.25rem; margin:0 0 1.25rem; }
        legend { font-weight:600; padding:0 .4rem; }
        label { display:inline-flex; align-items:center; gap:.5rem; padding:.25rem 0; cursor:pointer; }
        select { padding:.45rem .5rem; border:1px solid var(--bordo); border-radius:6px; min-width:240px; }
        .scelte { display:flex; flex-wrap:wrap; gap:.4rem 1.5rem; }
        .azioni { margin-top:.5rem; }
        button { background:var(--accento); color:#fff; border:0; padding:.65rem 1.4rem; border-radius:8px; font-size:1rem; cursor:pointer; }
        button:hover { filter:brightness(1.07); }
        .msg { border-radius:8px; padding:1rem 1.25rem; margin-bottom:1.25rem; }
        .msg.errore { background:#fdecea; border:1px solid #f5c6cb; color:var(--err); }
        .msg.ok { background:#e6f4ea; border:1px solid #b7e1c4; color:var(--ok); }
        .msg h2 { margin:0 0 .5rem; font-size:1.05rem; }
        .msg ul { margin:0; padding-left:1.2rem; }
        .riepilogo { list-style:none; padding:0; margin:0; }
        .riepilogo li { padding:.4rem 0; border-bottom:1px dashed var(--bordo); }
        .riepilogo li:last-child { border-bottom:0; }
        .riepilogo strong { display:inline-block; min-width:130px; }
        a.nuovo { color:var(--accento); text-decoration:none; font-weight:600; }
        .nota { font-size:.85rem; color:#666; margin:.9rem 0 0; }
        .vh { position:absolute; width:1px; height:1px; padding:0; margin:-1px; overflow:hidden; clip:rect(0,0,0,0); white-space:nowrap; border:0; }
    </style>
</head>
<body>
<main>

    <h1>🍕 Prenota il tuo posto in pizzeria</h1>


    <?php if ($ordine !== null): ?>

        <!-- ==================== RIEPILOGO ORDINE ==================== -->
        <div class="msg ok">
            <h2>Ordine effettuato correttamente!</h2>
            <p>Grazie, ecco il riepilogo della tua prenotazione:</p>
            <ul class="riepilogo">
                <li><strong>Sistemazione:</strong> <?= htmlspecialchars($ordine['sistemazione'], ENT_QUOTES, 'UTF-8') ?></li>
                <li><strong>Pizza:</strong> <?= htmlspecialchars($ordine['pizza'], ENT_QUOTES, 'UTF-8') ?></li>
                <li><strong>Impasto:</strong> <?= htmlspecialchars($ordine['impasto'], ENT_QUOTES, 'UTF-8') ?></li>
                <li><strong>Aggiunte:</strong>
                    <?php if (empty($ordine['aggiunte'])): ?>
                        Nessuna aggiunta
                    <?php else: ?>
                        <?= htmlspecialchars(implode(', ', array_map(fn($k) => $aggiunte[$k], $ordine['aggiunte'])), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </li>
            </ul>
            <p><a class="nuovo" href="">Effettua un nuovo ordine</a></p>
        </div>

    <?php else: ?>

        <!-- ==================== EVENTUALE ERRORE ==================== -->
        <?php if ($inviato && !empty($errors)): ?>
            <div class="msg errore">
                <h2>Attenzione: non è stato possibile completare l'ordine.</h2>
                <p>Inserisci tutti i dati obbligatori nel form:</p>
                <ul>
                    <?php foreach ($errors as $errore): ?>
                        <li><?= htmlspecialchars($errore, ENT_QUOTES, 'UTF-8') ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- ==================== FORM ==================== -->
        <form class="card" method="post" action="">

            <fieldset>
                <legend>Tipo di sistemazione *</legend>
                <div class="scelte">
                    <?php foreach ($sistemazioni as $chiave => $etichetta): ?>
                        <label>
                            <input type="radio" name="sistemazione" value="<?= $chiave ?>"<?= selezionato('sistemazione', $chiave) ?>>
                            <?= htmlspecialchars($etichetta, ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <fieldset>
                <legend>Tipo di pizza *</legend>
                <label class="vh" for="pizza">Tipo di pizza</label>
                <select name="pizza" id="pizza">
                    <option value="">-- Seleziona una pizza --</option>
                    <?php foreach ($pizze as $chiave => $etichetta): ?>
                        <option value="<?= $chiave ?>"<?= (($_POST['pizza'] ?? '') === $chiave) ? ' selected' : '' ?>><?= htmlspecialchars($etichetta, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </fieldset>

            <fieldset>
                <legend>Tipo di impasto *</legend>
                <label class="vh" for="impasto">Tipo di impasto</label>
                <select name="impasto" id="impasto">
                    <option value="">-- Seleziona un impasto --</option>
                    <?php foreach ($impasti as $chiave => $etichetta): ?>
                        <option value="<?= $chiave ?>"<?= (($_POST['impasto'] ?? '') === $chiave) ? ' selected' : '' ?>><?= htmlspecialchars($etichetta, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </fieldset>

            <fieldset>
                <legend>Eventuali aggiunte (facoltative)</legend>
                <div class="scelte">
                    <?php foreach ($aggiunte as $chiave => $etichetta): ?>
                        <label>
                            <input type="checkbox" name="aggiunte[]" value="<?= $chiave ?>"<?= spuntato('aggiunte', $chiave) ?>>
                            <?= htmlspecialchars($etichetta, ENT_QUOTES, 'UTF-8') ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </fieldset>

            <div class="azioni">
                <button type="submit">Invia ordine</button>
            </div>

            <p class="nota">I campi contrassegnati con * sono obbligatori.</p>
        </form>

    <?php endif; ?>

</main>
</body>
</html>

