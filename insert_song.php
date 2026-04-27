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
    $song_title = trim($_POST["song_title"]);
    $artist_name = trim($_POST["artist_name"]);
    $genre_name = trim($_POST["genre_name"]);
    $language_name = trim($_POST["language_name"]);
    $lyrics_text = trim($_POST["lyrics_text"]);
    // Getting existing moods/contexts
    $moods = $_POST["moods"] ?? [];
    $contexts = $_POST["contexts"] ?? [];
    // Getting moods/contexts that the user manually added
    $new_moods = $_POST["new_moods"] ?? [];
    $new_contexts = $_POST["new_contexts"] ?? [];
    // Getting suggested moods (so we can assign system vs user tags)
    $suggested_moods = $_POST["suggested_moods"] ?? [];
    $suggested_contexts = $_POST["suggested_contexts"] ?? [];

    if ($song_title && $artist_name && $genre_name && $language_name && $lyrics_text) {
        try {
            $pdo->beginTransaction();

            $artist_id = getOrCreateId($pdo, "Artists", "artist_name", $artist_name);
            $genre_id = getOrCreateId($pdo, "Genres", "genre_name", $genre_name);
            $language_id = getOrCreateId($pdo, "Languages", "language_name", $language_name);


            $stmt = $pdo->prepare("
                INSERT INTO Songs (song_title, artist_id, genre_id)
                VALUES (:song_title, :artist_id, :genre_id)
            ");
            $stmt->execute([
                ":song_title" => $song_title,
                ":artist_id" => $artist_id,
                ":genre_id" => $genre_id
            ]);

            $song_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO Lyrics (song_id, language_id, lyrics_text)
                VALUES (:song_id, :language_id, :lyrics_text)
            ");
            $stmt->execute([
                ":song_id" => $song_id,
                ":language_id" => $language_id,
                ":lyrics_text" => $lyrics_text
            ]);

            // Inserting moods
            $all_moods = array_merge($moods, $new_moods); // combining user-written and checked moods
            foreach ($all_moods as $mood) {
                $mood = trim($mood);
                if ($mood === "") continue;
                $mood_id = getOrCreateId($pdo, "Moods", "mood_name", $mood);
                // if this mood was suggested for this song, mark it as system-generated.
                // otherwise, it was selected/created by the user.
                $tag_source = in_array($mood, $suggested_moods) ? "system" : "user";

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

            // Inserting contexts
            $all_contexts = array_merge($contexts, $new_contexts); // combining user-written and checked contexts
            foreach ($all_contexts as $context) {
                $context = trim($context);
                if ($context === "") continue;
                $context_id = getOrCreateId($pdo, "Contexts", "context_name", $context);
                // if this context was suggested for this song, mark it as system-generated.
                // otherwise, it was selected/created by the user.
                $tag_source = in_array($context, $suggested_contexts) ? "system" : "user";

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
            die("Insert failed: " . $e->getMessage());
        }
    }
}

header("Location: index.php");
exit;
?>