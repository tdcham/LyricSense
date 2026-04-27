<?php
require_once "connect-db.php";
require_once "suggest_tags.php";

// Initializing default values
$song_title = "";
$artist_name = "";
$genre_name = "";
$language_name = "";
$lyrics_text = "";

// Loading existing songs artists, moods, contexts, genres, and languages from the database.
// Used for populating checkboxes and datalists in the form with values already stored in the DB.
$songStmt = $pdo->query("SELECT song_title FROM Songs ORDER BY song_title ASC");
$songs = $songStmt->fetchAll(PDO::FETCH_ASSOC);

$artistStmt = $pdo->query("SELECT artist_name FROM Artists ORDER BY artist_name ASC");
$artists = $artistStmt->fetchAll(PDO::FETCH_ASSOC);

$moodStmt = $pdo->query("SELECT mood_id, mood_name FROM Moods ORDER BY mood_name ASC");
$moods = $moodStmt->fetchAll(PDO::FETCH_ASSOC);

$contextStmt = $pdo->query("SELECT context_id, context_name FROM Contexts ORDER BY context_name ASC");
$contexts = $contextStmt->fetchAll(PDO::FETCH_ASSOC);

$genreStmt = $pdo->query("SELECT genre_id, genre_name FROM Genres ORDER BY genre_name ASC");
$genres = $genreStmt->fetchAll(PDO::FETCH_ASSOC);

$languageStmt = $pdo->query("SELECT language_id, language_name FROM Languages ORDER BY language_name ASC");
$languages = $languageStmt->fetchAll(PDO::FETCH_ASSOC);

// After initial form submission, check if the song has been added to the DB or not to determine next steps
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $song_title = trim($_POST["song_title"]);
    $artist_name = trim($_POST["artist_name"]);
    $genre_name = trim($_POST["genre_name"]);
    $language_name = trim($_POST["language_name"]);
    $lyrics_text = trim($_POST["lyrics_text"]);

    // Duplicate check
    $stmt = $pdo->prepare("
        SELECT song_id
        FROM Songs
        WHERE song_title = :song_title
        AND artist_id = (
            SELECT artist_id FROM Artists WHERE artist_name = :artist_name
        )
    ");

    $stmt->execute([
        ":song_title" => $song_title,
        ":artist_name" => $artist_name
    ]);

    $existing = $stmt->fetch();

    if ($existing) {
        header("Location: edit_song.php?id=" . $existing["song_id"]);
        exit;
    }

    // store data for next step
    $_SESSION["song_data"] = [
        "song_title" => $song_title,
        "artist_name" => $artist_name,
        "genre_name" => $genre_name,
        "language_name" => $language_name,
        "lyrics_text" => $lyrics_text
    ];

    $_SESSION["suggestions"] = detect_moods_and_contexts($lyrics_text);

    header("Location: mood_selection.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Song (Manual Entry)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <div class="page-wrap">
    <div class="section">

    <h2>Add New Song</h2>
    <p class="subtitle">Enter song information, then generate suggested moods & contexts.</p>

    <form method="POST">
            
        <!-- Part 1: Song Information (Used to check if song is a duplicate before generating moods/contexts) -->
        <div class="field-group">
            <label>Song Title</label>
            <input type="text" name="song_title" list="songs" value="<?= htmlspecialchars($song_title) ?>" required>

            <datalist id="songs">
                <?php foreach ($songs as $song): ?>
                    <option value="<?= htmlspecialchars($song['song_title']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="field-group">
            <label>Artist</label>
            <input type="text" name="artist_name" list="artists" value="<?= htmlspecialchars($artist_name) ?>" required>

            <datalist id="artists">
                <?php foreach ($artists as $artist): ?>
                    <option value="<?= htmlspecialchars($artist['artist_name']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="field-group">
            <label>Genre</label>
            <input type="text" name="genre_name" list="genres" value="<?= htmlspecialchars($genre_name) ?>" required>

            <datalist id="genres">
                <?php foreach ($genres as $genre): ?>
                    <option value="<?= htmlspecialchars($genre['genre_name']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="field-group">
            <label>Language</label>
            <input type="text" name="language_name" list="languages" value="<?= htmlspecialchars($language_name) ?>" required>

            <datalist id="languages">
                <?php foreach ($languages as $language): ?>
                    <option value="<?= htmlspecialchars($language['language_name']) ?>">
                <?php endforeach; ?>
            </datalist>
        </div>

        <div class="field-group">
            <label>Lyrics</label>
            <textarea name="lyrics_text" rows="8" required><?= htmlspecialchars($lyrics_text) ?></textarea>
        </div>

        <div class="button-row">
            <button type="submit" >Next: Add Moods & Contexts</button>
            <a class="secondary-btn" href="index.php">Back</a>
        </div>

    </form>

</div>
</div>

</body>
</html>