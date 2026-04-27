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
        lang.language_name,
        GROUP_CONCAT(DISTINCT m.mood_name ORDER BY m.mood_name SEPARATOR ', ') AS moods,
        GROUP_CONCAT(DISTINCT c.context_name ORDER BY c.context_name SEPARATOR ', ') AS contexts
    FROM Songs s
    JOIN Artists a ON s.artist_id = a.artist_id
    JOIN Genres g ON s.genre_id = g.genre_id
    JOIN Lyrics l ON s.song_id = l.song_id
    JOIN Languages lang ON l.language_id = lang.language_id
    LEFT JOIN TaggedWithMoods twm ON s.song_id = twm.song_id
    LEFT JOIN Moods m ON twm.mood_id = m.mood_id
    LEFT JOIN TaggedWithContexts twc ON s.song_id = twc.song_id
    LEFT JOIN Contexts c ON twc.context_id = c.context_id
    WHERE s.song_id = :song_id
    GROUP BY s.song_id, s.song_title, a.artist_name, g.genre_name, l.lyrics_text, lang.language_name
");
$stmt->execute([":song_id" => $song_id]);
$song = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$song) {
    die("Song not found.");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($song["song_title"]) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-wrap">
        <section class="section">
            <h1><?= htmlspecialchars($song["song_title"]) ?></h1>
            <p class="subtitle"><?= htmlspecialchars($song["artist_name"]) ?></p>

            <div class="song-meta">
                <span class="meta-pill"><?= htmlspecialchars($song["genre_name"]) ?></span>
                <span class="meta-pill"><?= htmlspecialchars($song["language_name"]) ?></span>
            </div>

            <?php if (!empty($song["moods"])): ?>
                <div class="tag-section">
                    <p class="tag-heading">Moods</p>
                    <div class="tag-wrap">
                        <?php foreach (explode(", ", $song["moods"]) as $mood): ?>
                            <span class="tag-pill mood-pill"><?= htmlspecialchars($mood) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($song["contexts"])): ?>
                <div class="tag-section">
                    <p class="tag-heading">Contexts</p>
                    <div class="tag-wrap">
                        <?php foreach (explode(", ", $song["contexts"]) as $context): ?>
                            <span class="tag-pill context-pill"><?= htmlspecialchars($context) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="lyrics-preview" style="min-height:auto; margin-top:18px;">
                <?= nl2br(htmlspecialchars($song["lyrics_text"])) ?>
            </div>

            <div class="card-actions">
                <a class="action-link" href="index.php">Back</a>
                <a class="action-link" href="edit_song.php?id=<?= $song["song_id"] ?>">Edit</a>
                <a class="action-link delete-link" href="delete_song.php?id=<?= $song["song_id"] ?>" onclick="return confirm('Delete this song?');">Delete</a>
            </div>
        </section>
    </div>
</body>
</html>