<?php
require_once "connect-db.php";
require_once "security.php";

verify_csrf_token();

if (!isset($_POST["song_id"])) {
    die("Song ID missing.");
}

$song_id = (int) $_POST["song_id"];

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("DELETE FROM Lyrics WHERE song_id = :song_id");
    $stmt->execute([":song_id" => $song_id]);

    $stmt = $pdo->prepare("DELETE FROM TaggedWithMoods WHERE song_id = :song_id");
    $stmt->execute([":song_id" => $song_id]);

    $stmt = $pdo->prepare("DELETE FROM TaggedWithContexts WHERE song_id = :song_id");
    $stmt->execute([":song_id" => $song_id]);

    $stmt = $pdo->prepare("DELETE FROM Songs WHERE song_id = :song_id");
    $stmt->execute([":song_id" => $song_id]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    die("Delete failed: " . $e->getMessage());
}

header("Location: index.php");
exit;
?>