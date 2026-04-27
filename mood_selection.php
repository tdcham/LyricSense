<?php
require_once "connect-db.php";
session_start();

if (!isset($_SESSION["song_data"]) || !isset($_SESSION["suggestions"])) {
    die("Session expired. Please go back and re-enter the song.");
}
$moodStmt = $pdo->query("SELECT mood_id, mood_name FROM Moods ORDER BY mood_name ASC");
$all_moods = $moodStmt->fetchAll(PDO::FETCH_ASSOC);

$contextStmt = $pdo->query("SELECT context_id, context_name FROM Contexts ORDER BY context_name ASC");
$all_contexts = $contextStmt->fetchAll(PDO::FETCH_ASSOC);

$song = $_SESSION["song_data"];
$suggestions = $_SESSION["suggestions"];

$suggested_moods = $suggestions["moods"] ?? [];
$suggested_contexts = $suggestions["contexts"] ?? [];

?>

<!DOCTYPE html>
<html>
<head>
    <title>Select Moods & Contexts</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<script>
function addMood() {
    const box = document.getElementById("moodBox");

    const input = document.createElement("input");
    input.type = "text";
    input.name = "new_moods[]";
    input.placeholder = "Type mood";

    box.appendChild(input);
}

function addContext() {
    const box = document.getElementById("contextBox");

    const input = document.createElement("input");
    input.type = "text";
    input.name = "new_contexts[]";
    input.placeholder = "Type context";

    box.appendChild(input);
}
</script>

<div class="page-wrap">
<div class="section">

<h2>Select Moods & Contexts</h2>
<p class="subtitle">Choose desired moods and context for this song. Suggested tags are italicized.</p>

<form method="POST" action="insert_song.php">

    <input type="hidden" name="song_title" value="<?= htmlspecialchars($song["song_title"]) ?>">
    <input type="hidden" name="artist_name" value="<?= htmlspecialchars($song["artist_name"]) ?>">
    <input type="hidden" name="genre_name" value="<?= htmlspecialchars($song["genre_name"]) ?>">
    <input type="hidden" name="language_name" value="<?= htmlspecialchars($song["language_name"]) ?>">
    <input type="hidden" name="lyrics_text" value="<?= htmlspecialchars($song["lyrics_text"]) ?>">
    <?php foreach ($suggested_moods as $suggested_mood): ?>
        <input type="hidden" name="suggested_moods[]" value="<?= htmlspecialchars($suggested_mood) ?>">
    <?php endforeach; ?>
    <?php foreach ($suggested_contexts as $suggested_context): ?>
        <input type="hidden" name="suggested_contexts[]" value="<?= htmlspecialchars($suggested_context) ?>">
    <?php endforeach; ?>

    <!-- Moods checkbox: all moods in DB included, but suggested moods are italicized -->
    <h3>Moods</h3>

    <?php foreach ($all_moods as $mood): ?>
        <?php $isSuggested = in_array($mood["mood_name"], $suggested_moods); ?>

        <label style="<?= $isSuggested ? 'font-style: italic;' : '' ?>">
            <input type="checkbox"
                name="moods[]"
                value="<?= htmlspecialchars($mood["mood_name"]) ?>">
            <?= htmlspecialchars($mood["mood_name"]) ?>
        </label><br>
    <?php endforeach; ?>
    
    <!-- Contexts checkbox: all contexts included, but suggested contexts are italicized -->
    <h3>Contexts</h3>

    <?php foreach ($all_contexts as $context): ?>
        <?php $isSuggested = in_array($context["context_name"], $suggested_contexts); ?>

        <label style="<?= $isSuggested ? 'font-style: italic;' : '' ?>">
            <input type="checkbox"
                name="contexts[]"
                value="<?= htmlspecialchars($context["context_name"]) ?>">
            <?= htmlspecialchars($context["context_name"]) ?>
        </label><br>
    <?php endforeach; ?>

    <!-- CUSTOM MOOD/CONTEXT ADDITIONS -->
    <h3>Add New Mood</h3>

    <div id="moodBox">
        <input type="text" name="new_moods[]" placeholder="Type mood">
    </div>
    <button type="button" onclick="addMood()">
        + Add another mood
    </button>
    
    <h3>Add New Context</h3>

    <div id="contextBox">
        <input type="text" name="new_contexts[]" placeholder="Type context">
    </div>
    <button type="button" onclick="addContext()">
        + Add another context
    </button>

    <br><br><br>

    <!-- FINAL SUBMIT -->

    <div class="button-row">
        <button type="submit">Save Song</button>
        <a href="insert_song_form.php" class="secondary-btn">Back</a>
    </div>

</form>

</div>
</div>

</body>
</html>