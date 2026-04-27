<?php
require_once "connect-db.php";
require_once "security.php";

$search = trim($_GET["search"] ?? "");
$moodSearch = trim($_GET["mood"] ?? "");
$contextSearch = trim($_GET["context"] ?? "");
$genreFilter = trim($_GET["genre"] ?? "");

$genreStmt = $pdo->query("SELECT genre_name FROM Genres ORDER BY genre_name ASC");
$genres = $genreStmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "
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
";

$where = [];
$params = [];

if ($search !== "") {
    $where[] = "(s.song_title LIKE :search OR a.artist_name LIKE :search)";
    $params[":search"] = "%" . $search . "%";
}

if ($moodSearch !== "") {
    $where[] = "m.mood_name LIKE :mood";
    $params[":mood"] = "%" . $moodSearch . "%";
}

if ($contextSearch !== "") {
    $where[] = "c.context_name LIKE :context";
    $params[":context"] = "%" . $contextSearch . "%";
}

if ($genreFilter !== "") {
    $where[] = "g.genre_name = :genre";
    $params[":genre"] = $genreFilter;
}

if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}

$sql .= "
    GROUP BY
        s.song_id,
        s.song_title,
        a.artist_name,
        g.genre_name,
        l.lyrics_text,
        lang.language_name
    ORDER BY LOWER(s.song_title) ASC
    LIMIT 100
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$songs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>LyricSense</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="page-wrap">
        <header class="hero hero-logo">
    <img src="assets/logo.png" alt="LyricSense Logo" class="main-logo">
    <p class="subtitle">The Right Song for Right Now</p>
</header>

        <section class="section toolbar-section">
            <form method="get" action="index.php" class="toolbar-form">

                <div class="field-group">
                    <label for="search">Search by song title or artist</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Try SZA, Taylor Swift, Saturn, Love..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="field-group">
                    <label for="mood">Search by mood</label>
                    <input
                        type="text"
                        id="mood"
                        name="mood"
                        placeholder="happy, sad, romantic..."
                        value="<?= htmlspecialchars($moodSearch) ?>"
                    >
                </div>

                <div class="field-group">
                    <label for="context">Search by context</label>
                    <input
                        type="text"
                        id="context"
                        name="context"
                        placeholder="study, breakup, gym, party..."
                        value="<?= htmlspecialchars($contextSearch) ?>"
                    >
                </div>

                <div class="field-group">
                    <label for="genre">Filter by genre</label>
                    <select id="genre" name="genre">
                        <option value="">All genres</option>
                        <?php foreach ($genres as $genre): ?>
                            <option value="<?= htmlspecialchars($genre["genre_name"]) ?>"
                                <?= $genreFilter === $genre["genre_name"] ? "selected" : "" ?>>
                                <?= htmlspecialchars($genre["genre_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="button-row">
                    <button type="submit">Apply</button>
                    <a class="secondary-btn" href="index.php">Clear</a>
                    <a class="secondary-btn" href="insert_song_form.php">Add Song</a>
                </div>

            </form>
        </section>

        <section class="section summary-section">
            <div class="summary-pill">
                Showing <strong><?= count($songs) ?></strong> songs
            </div>

            <div class="summary-pill">
                Ordered alphabetically by title
            </div>

            <?php if ($search !== ""): ?>
                <div class="summary-pill">
                    Song/Artist: <strong><?= htmlspecialchars($search) ?></strong>
                </div>
            <?php endif; ?>

            <?php if ($moodSearch !== ""): ?>
                <div class="summary-pill">
                    Mood: <strong><?= htmlspecialchars($moodSearch) ?></strong>
                </div>
            <?php endif; ?>

            <?php if ($contextSearch !== ""): ?>
                <div class="summary-pill">
                    Context: <strong><?= htmlspecialchars($contextSearch) ?></strong>
                </div>
            <?php endif; ?>

            <?php if ($genreFilter !== ""): ?>
                <div class="summary-pill">
                    Genre: <strong><?= htmlspecialchars($genreFilter) ?></strong>
                </div>
            <?php endif; ?>
        </section>

        <section class="section">
            <h2>Song Records</h2>

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

                        <div class="lyrics-preview">
                            <?= htmlspecialchars(substr($song["lyrics_text"], 0, 260)) ?>...
                        </div>

                        <div class="card-actions">
                            <a class="action-link" href="view_song.php?id=<?= htmlspecialchars($song["song_id"]) ?>">View</a>
                            <a class="action-link" href="edit_song.php?id=<?= htmlspecialchars($song["song_id"]) ?>">Edit</a>

                            <form
                                method="post"
                                action="delete_song.php"
                                style="display:inline;"
                                onsubmit="return confirm('Delete this song?');"
                            >
                                <input
                                    type="hidden"
                                    name="song_id"
                                    value="<?= htmlspecialchars($song["song_id"]) ?>"
                                >
                                <input
                                    type="hidden"
                                    name="csrf_token"
                                    value="<?= htmlspecialchars(csrf_token()) ?>"
                                >
                                <button type="submit" class="action-link delete-link">Delete</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</body>
</html>
```
