<?php
# Implementing mooddetection.py logic in php to run when users add a song manually
# Sources
    # https://www.w3schools.com/php/php_functions.asp
    # https://www.w3schools.com/php/func_string_strtolower.asp
    # https://www.php.net/manual/en/class.ds-set.php
    # https://www.w3schools.com/php/php_ref_array.asp (implementing set() in php)
    # https://www.w3schools.com/php/php_looping.asp
    # https://www.w3schools.com/php/php_ref_regex.asp (converting re.search to php)

function detect_moods_and_contexts($lyrics_text){
    $text = strtolower($lyrics_text);

    $mood_keywords = [
        "happy" => [
            "happy", "joy", "smile", "celebrate", "good time", "fun", "dance", "party", "shine"
        ],
        "sad" => [
            "sad", "cry", "tears", "alone", "lonely", "heartbreak", "broken", "hurt", "pain", "miss you"
        ],
        "calm" => [
            "calm", "breathe", "slow", "peace", "quiet", "soft", "rest", "dream", "float"
        ],
        "angry" => [
            "angry", "mad", "rage", "fight", "hate", "furious", "shout", "blood"
        ],
        "motivated" => [
            "win", "strong", "rise", "grind", "push", "power", "focus", "goal", "champion"
        ],
        "romantic" => [
            "love", "kiss", "baby", "forever", "hold you", "touch", "beautiful"
        ],
        "anxious" => [
            "fear", "scared", "anxious", "worry", "stress", "panic", "nervous"
        ]
    ];

    $context_keywords = [
        "party" => [
            "party", "dance", "club", "dj", "drink", "celebrate"
        ],
        "breakup" => [
            "ex", "goodbye", "left me", "heartbreak", "broken", "miss you", "alone"
        ],
        "study" => [
            "focus", "books", "learn", "study", "homework", "exam"
        ],
        "gym" => [
            "run", "push", "workout", "training", "exercise", "grind"
        ],
        "sleep" => [
            "sleep", "dream", "bed", "rest", "fall asleep"
        ],
        "driving" => [
            "road", "car", "drive", "highway", "ride", "wheels"
        ],
        "romance" => [
            "love", "kiss", "touch", "hold you", "forever"
        ],
        "relaxing" => [
            "calm", "peace", "breathe", "quiet", "soft", "rest"
        ]
    ];

    $found_moods = [];
    $found_contexts = [];

    foreach ($mood_keywords as $mood => $keywords) {
        foreach ($keywords as $keyword) {
            if (preg_match("/\b" . preg_quote($keyword, "/") . "\b/", $text)) {
                $found_moods[] = $mood;
                break;
            }
        }
    }

    foreach ($context_keywords as $context => $keywords) {
        foreach ($keywords as $keyword) {
            if (preg_match("/\b" . preg_quote($keyword, "/") . "\b/", $text)) {
                $found_contexts[] = $context;
                break;
            }
        }
    }

    if (empty($found_moods)) {
        $found_moods[] = "calm";
    }

    if (empty($found_contexts)) {
        $found_contexts[] = "relaxing";
    }

    # Using array_unique to make them into sets
    return [
        "moods" => array_unique($found_moods),
        "contexts" => array_unique($found_contexts)
    ];
}
?>
