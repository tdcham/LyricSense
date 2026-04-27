<?php
require_once "connect-db.php";

$mood = "";
$songs = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $mood = trim($_POST["mood"]);

    if ($mood !== "") {
        try {
            // Call stored procedure
            $stmt = $pdo->prepare("CALL GetSongsByMood(:mood)");
            $stmt->execute([":mood" => $mood]);

            // Get basic results (song titles)
            $basicSongs = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt->closeCursor();

            // OPTIONAL: enrich results to match index.php UI
            $songs = [];

            foreach ($basicSongs as $row) {
                $title = $row["song_title"];

                $query = $pdo->prepare("
                    SELECT
                        s.song_id,
                        s.song_title,
                        a.artist_name,
                        g.genre_name,
                        l.lyrics_text
                    FROM Songs s
                    JOIN Artists a ON s.artist_id = a.artist_id
                    JOIN Genres g ON s.genre_id = g.genre_id
                    JOIN Lyrics l ON s.song_id = l.song_id
                    WHERE s.song_title = :title
                    LIMIT 1
                ");

                $query->execute([":title" => $title]);
                $full = $query->fetch(PDO::FETCH_ASSOC);

                if ($full) {
                    $songs[] = $full;
                }
            }

        } catch (Exception $e) {
            die("Query failed: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Search by Mood</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="page-wrap">

    <header class="hero">
        <h1>Search by Mood</h1>
        <p class="subtitle">Find songs based on how you feel</p>
    </header>

    <section class="section">
        <form method="post" action="mood_search.php" class="toolbar-form">

            <div class="field-group">
                <label for="mood">Enter mood</label>
                <input
                    type="text"
                    id="mood"
                    name="mood"
                    placeholder="happy, sad, chill..."
                    value="<?= htmlspecialchars($mood) ?>"
                    required
                >
            </div>

            <div class="button-row">
                <button type="submit">Search</button>
                <a class="secondary-btn" href="index.php">Back</a>
            </div>

        </form>
    </section>

    <section class="section">

        <?php if ($mood !== ""): ?>
            <h2>Results for "<?= htmlspecialchars($mood) ?>"</h2>
        <?php endif; ?>

        <div class="card-grid">

            <?php foreach ($songs as $song): ?>
                <article class="song-card">

                    <div class="song-card-header">
                        <div>
                            <h3><?= htmlspecialchars($song["song_title"]) ?></h3>
                            <p class="artist-name"><?= htmlspecialchars($song["artist_name"]) ?></p>
                        </div>
                        <div class="song-id">ID <?= htmlspecialchars($song["song_id"]) ?></div>
                    </div>

                    <div class="song-meta">
                        <span class="meta-pill"><?= htmlspecialchars($song["genre_name"]) ?></span>
                    </div>

                    <div class="lyrics-preview">
                        <?= htmlspecialchars(substr($song["lyrics_text"], 0, 200)) ?>...
                    </div>

                </article>
            <?php endforeach; ?>

        </div>

        <?php if ($mood !== "" && empty($songs)): ?>
            <p>No songs found for this mood.</p>
        <?php endif; ?>

    </section>

</div>

</body>
</html>