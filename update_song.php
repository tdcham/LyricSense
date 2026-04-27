<?php
require_once "connect-db.php";

// Helper function to reuse logic for checking if a value exists + adding it if it doesnt
function getOrCreateId($pdo, $table, $column, $value) {
    $id_map = [
        "Artists" => "artist_id",
        "Genres" => "genre_id",
        "Languages" => "language_id",
        "Moods" => "mood_id",
        "Contexts" => "context_id"
    ];
    $idColumn = $id_map[$table];

    $stmt = $pdo->prepare("SELECT $idColumn FROM {$table} WHERE {$column} = :val");
    $stmt->execute([":val" => $value]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row[$idColumn];
    }

    $stmt = $pdo->prepare("INSERT INTO {$table} ({$column}) VALUES (:val)");
    $stmt->execute([":val" => $value]);

    return $pdo->lastInsertId();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $song_id = (int) $_POST["song_id"];
    $song_title = trim($_POST["song_title"]);
    $artist_name = trim($_POST["artist_name"]);
    $genre_name = trim($_POST["genre_name"]);
    $language_name = trim($_POST["language_name"]);
    $lyrics_text = trim($_POST["lyrics_text"]);
    // Getting existing moods/contexts
    $moods = $_POST["moods"] ?? [];
    $contexts = $_POST["contexts"] ?? [];
    // Getting moods/contexts that the user added during the update
    $new_moods = $_POST["new_moods"] ?? [];
    $new_contexts = $_POST["new_contexts"] ?? [];

    try {
        $pdo->beginTransaction();

        $artist_id = getOrCreateId($pdo, "Artists", "artist_name", $artist_name);
        $genre_id = getOrCreateId($pdo, "Genres", "genre_name", $genre_name);
        $language_id = getOrCreateId($pdo, "Languages", "language_name", $language_name);

        $stmt = $pdo->prepare("
            UPDATE Songs
            SET song_title = :song_title, artist_id = :artist_id, genre_id = :genre_id
            WHERE song_id = :song_id
        ");
        $stmt->execute([
            ":song_title" => $song_title,
            ":artist_id" => $artist_id,
            ":genre_id" => $genre_id,
            ":song_id" => $song_id
        ]);

        $stmt = $pdo->prepare("
            UPDATE Lyrics
            SET language_id = :language_id, lyrics_text = :lyrics_text
            WHERE song_id = :song_id
        ");
        $stmt->execute([
            ":language_id" => $language_id,
            ":lyrics_text" => $lyrics_text,
            ":song_id" => $song_id
        ]);

        // updating moods and contexts

        // 0) Save old tag sources before deleting
        $oldMoodSources = [];

        $stmt = $pdo->prepare("
            SELECT m.mood_name, t.tag_source
            FROM TaggedWithMoods t
            JOIN Moods m ON t.mood_id = m.mood_id
            WHERE t.song_id = :song_id
        ");
        $stmt->execute([":song_id" => $song_id]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oldMoodSources[$row["mood_name"]] = $row["tag_source"];
        }

        $oldContextSources = [];

        $stmt = $pdo->prepare("
            SELECT c.context_name, t.tag_source
            FROM TaggedWithContexts t
            JOIN Contexts c ON t.context_id = c.context_id
            WHERE t.song_id = :song_id
        ");
        $stmt->execute([":song_id" => $song_id]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $oldContextSources[$row["context_name"]] = $row["tag_source"];
        }
        // 1) Delete old tags from this song
        $pdo->prepare("DELETE FROM TaggedWithMoods WHERE song_id = :song_id")
            ->execute([":song_id" => $song_id]);

        $pdo->prepare("DELETE FROM TaggedWithContexts WHERE song_id = :song_id")
            ->execute([":song_id" => $song_id]);

        // 2) Update tags for moods/contexts already in db
        $all_moods = array_merge($moods, $new_moods);
        foreach ($all_moods as $mood) {
            $mood = trim($mood);
            if ($mood === "") continue;
            $mood_id = getOrCreateId($pdo, "Moods", "mood_name", $mood);

            $tag_source = $oldMoodSources[$mood] ?? "user";

            $stmt = $pdo->prepare("
                INSERT INTO TaggedWithMoods (song_id, mood_id, tag_source)
                VALUES (:song_id, :mood_id, :tag_source)
            ");

            $stmt->execute([
                ":song_id" => $song_id,
                ":mood_id" => $mood_id,
                ":tag_source" => $tag_source
            ]);
        }

        $all_contexts = array_merge($contexts, $new_contexts);
        foreach ($all_contexts as $context) {
            $context = trim($context);
            if ($context === "") continue;
            $context_id = getOrCreateId($pdo, "Contexts", "context_name", $context);

            $tag_source = $oldContextSources[$context] ?? "user";

            $stmt = $pdo->prepare("
                INSERT INTO TaggedWithContexts (song_id, context_id, tag_source)
                VALUES (:song_id, :context_id, :tag_source)
            ");

            $stmt->execute([
                ":song_id" => $song_id,
                ":context_id" => $context_id,
                ":tag_source" => $tag_source
            ]);
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Update failed: " . $e->getMessage());
    }
}

header("Location: index.php");
exit;
?>