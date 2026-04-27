<?php
require_once "connect-db.php";

if (!isset($_GET["id"])) {
    die("Song ID missing.");
}

$song_id = (int) $_GET["id"];

$stmt = $pdo->prepare("
    SELECT 
        s.song_id,
        s.song_title,
        a.artist_name,
        g.genre_name,
        l.lyrics_text,
        l.language_id,
        lang.language_name
    FROM Songs s
    JOIN Artists a ON s.artist_id = a.artist_id
    JOIN Genres g ON s.genre_id = g.genre_id
    JOIN Lyrics l ON s.song_id = l.song_id
    JOIN Languages lang ON l.language_id = lang.language_id
    WHERE s.song_id = :song_id
");

$stmt->execute([":song_id" => $song_id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die("Song not found.");
}

// Loading all available moods and contexts from the database
$moodStmt = $pdo->query("SELECT mood_id, mood_name FROM Moods ORDER BY mood_name ASC");
$moods = $moodStmt->fetchAll(PDO::FETCH_ASSOC);

$contextStmt = $pdo->query("SELECT context_id, context_name FROM Contexts ORDER BY context_name ASC");
$contexts = $contextStmt->fetchAll(PDO::FETCH_ASSOC);

// Loading what moods and contexts have been selected from this song already
$currMoodStmt = $pdo->prepare("
    SELECT mood_id
    FROM TaggedWithMoods
    WHERE song_id = :song_id
");

$currMoodStmt->execute([":song_id" => $song_id]);
$current_moods = $currMoodStmt->fetchAll(PDO::FETCH_ASSOC);
$current_moods = array_column($current_moods, "mood_id");

$currContextStmt = $pdo->prepare("
    SELECT context_id
    FROM TaggedWithContexts
    WHERE song_id = :song_id
");

$currContextStmt->execute([":song_id" => $song_id]);
$current_contexts = $currContextStmt->fetchAll(PDO::FETCH_ASSOC);
$current_contexts = array_column($current_contexts, "context_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Song</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<br><br>
<div class="hero">
    <h1>Edit Song</h1>
</div>

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

<div class="section">
<form method="post" action="update_song.php">

<input type="hidden" name="song_id" value="<?= htmlspecialchars($song["song_id"]) ?>">

<div class="edit-layout">

    <div class="edit-column">
        <h3>Song Info</h3>

        <label>Song Title</label>
        <input type="text" name="song_title" value="<?= htmlspecialchars($song["song_title"]) ?>" required>

        <label>Artist Name</label>
        <input type="text" name="artist_name" value="<?= htmlspecialchars($song["artist_name"]) ?>" required>

        <label>Genre Name</label>
        <input type="text" name="genre_name" value="<?= htmlspecialchars($song["genre_name"]) ?>" required>

        <label>Language Name</label>
        <input type="text" name="language_name" value="<?= htmlspecialchars($song["language_name"]) ?>" required>
    </div>

    <!-- MIDDLE: LYRICS -->
    <div class="edit-column">
        <h3>Lyrics</h3>

        <textarea name="lyrics_text" required><?= htmlspecialchars($song["lyrics_text"]) ?></textarea>
    </div>

    <!-- RIGHT: MOODS + CONTEXTS -->
    <div class="edit-column">
        <h3>Moods</h3>

        <?php foreach ($moods as $mood): ?>
            <label>
                <input type="checkbox" name="moods[]" value="<?= htmlspecialchars($mood["mood_name"]) ?>"
                <?php if (in_array($mood["mood_id"], $current_moods)) echo "checked"; ?>>
                <?= htmlspecialchars($mood["mood_name"]) ?>
            </label><br>
        <?php endforeach; ?>

        <h3>Contexts</h3>

        <?php foreach ($contexts as $context): ?>
            <label>
                <input type="checkbox" name="contexts[]" value="<?= htmlspecialchars($context["context_name"]) ?>"
                <?php if (in_array($context["context_id"], $current_contexts)) echo "checked"; ?>>
                <?= htmlspecialchars($context["context_name"]) ?>
            </label><br>
        <?php endforeach; ?>

        <h3>Add New Mood</h3>
        <div id="moodBox">
            <input type="text" name="new_moods[]" placeholder="Type mood">
        </div>
        <button type="button" onclick="addMood()">+ Add another mood</button>

        <h3>Add New Context</h3>
        <div id="contextBox">
            <input type="text" name="new_contexts[]" placeholder="Type context">
        </div>
        <button type="button" onclick="addContext()">+ Add another context</button>
    </div>

</div>

<br>
<button type="submit">Update Song</button>

</form>

<p><a href="index.php">Back to Home</a></p>
</div>