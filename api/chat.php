
<?php

require_once __DIR__ . '/../_base.php';
require_once __DIR__ . '/../config/ai_config.php';

header('Content-Type: application/json; charset=utf-8');

auth(['member']);

if (!is_post()) {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request method.'
    ]);
    exit;
}

// Read JSON request from JavaScript
$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON request.'
    ]);
    exit;
}

// Get member information
$user_id = $_user->user_id;

// Get member's fine records
$stm = $_db->prepare("
    SELECT 
        f.fine_id,
        f.borrowing_id,
        f.amount,
        f.reason,
        f.payment_status,
        f.created_at,
        f.paid_at
    FROM fine f
    INNER JOIN borrowing b
        ON f.borrowing_id = b.borrowing_id
    WHERE b.user_id = ?
");

$stm->execute([$user_id]);
$fines = $stm->fetchAll(PDO::FETCH_ASSOC);

// Get member's wishlist records
$stm = $_db->prepare("
    SELECT wishlist_id, book_id, added_date
    FROM wishlist
    WHERE user_id = ?
");

$stm->execute([$user_id]);
$wishlists = $stm->fetchAll(PDO::FETCH_ASSOC);

// Get member's borrowing records
$stm = $_db->prepare("
    SELECT borrowing_id, book_id, borrow_date, due_date, return_date, status, fine_amount
    FROM borrowing
    WHERE user_id = ?
");

$stm->execute([$user_id]);
$borrowings = $stm->fetchAll(PDO::FETCH_ASSOC);

// Get member's reservation records
$stm = $_db->prepare("
    SELECT reservation_id, book_id, reservation_date, expiry_date, status
    FROM reservation
    WHERE user_id = ?
");

$stm->execute([$user_id]);
$reservations = $stm->fetchAll(PDO::FETCH_ASSOC);

// Get message and conversation ID
$message = trim($input['message'] ?? '');
$conversation_id = $input['conversation_id'] ?? '';

if ($message === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Please enter a message.'
    ]);
    exit;
}

// Check API Key
if (GROQ_API_KEY === '') {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Groq API Key is not configured.'
    ]);
    exit;
}

try {
    // Create new conversation
    if ($conversation_id === '') {

        $stm = $_db->prepare('
            INSERT INTO conversation (user_id, created_at)
            VALUES (?, NOW())
        ');

        $stm->execute([$user_id]);

        $conversation_id = $_db->lastInsertId();

    } else {

        // Verify conversation ownership
        $stm = $_db->prepare('
            SELECT conversation_id
            FROM conversation
            WHERE conversation_id = ?
            AND user_id = ?
        ');

        $stm->execute([$conversation_id, $user_id]);

        if (!$stm->fetch()) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid conversation.'
            ]);
            exit;
        }
    }

    // Get previous messages
    $stm = $_db->prepare('
        SELECT sender_type, message_content
        FROM message
        WHERE conversation_id = ?
        ORDER BY message_id DESC
        LIMIT 20
    ');

    $stm->execute([$conversation_id]);
    $history = array_reverse($stm->fetchAll(PDO::FETCH_ASSOC));

   // Prepare member's database information for AI
$member_data = [
    'Borrowing Records' => $borrowings,
    'Reservation Records' => $reservations,
    'Fine Records' => $fines,
    'Wishlist Records' => $wishlists
];

// Send database information to Groq AI
$messages = [
    [
        'role' => 'system',
        'content' => "
            You are Story Vault AI Library Assistant.

            Introduction:
            When starting a new conversations, introduce yourself as the
            Story Vault Assistant.
            Briefly explain that you can help members with:
            -Book Information
            -Borrowing records
            -Reservation records
            -Fine records
            -Wishlist records
            
            Help members with their library information.

            The following is the current logged-in member's
            actual database information:

            " . json_encode($member_data, JSON_PRETTY_PRINT) . "

            Instructions:
            1. Answer questions using the provided database information.
            2. Only provide information belonging to the current member.
            3. Do not invent borrowing, reservation, fine or wishlist records.
            4. If there are no records, tell the member clearly.
            5. Explain unpaid fines using the payment_status field.
            6. Answer in the same language used by the member.
            7. Be friendly and clear.
        "
    ]
];

    foreach ($history as $item) {

        $messages[] = [
            'role' => $item['sender_type'] === 'member'
                ? 'user'
                : 'assistant',
            'content' => $item['message_content']
        ];

    }

    // Add current message
    $messages[] = [
        'role' => 'user',
        'content' => $message
    ];

    // Prepare Groq API request
    $payload = [
        'model' => GROQ_MODEL,
        'messages' => $messages,
        'temperature' => 0.7,
        'max_tokens' => 500
    ];

    $ch = curl_init(GROQ_API_URL);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . GROQ_API_KEY
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false) {
        throw new Exception('Connection failed: ' . $curl_error);
    }

    $result = json_decode($response, true);

    // Check Groq API response
    if ($status < 200 || $status >= 300 ||
        !isset($result['choices'][0]['message']['content'])) {

        throw new Exception(
            $result['error']['message'] ?? 'Unable to get AI response.'
        );
    }

    $reply = $result['choices'][0]['message']['content'];

    // Save member message
    $stm = $_db->prepare('
        INSERT INTO message
        (conversation_id, sender_type, message_content, sent_at)
        VALUES (?, ?, ?, NOW())
    ');

    $stm->execute([
        $conversation_id,
        'member',
        $message
    ]);

    // Save AI response
    $stm->execute([
        $conversation_id,
        'ai',
        $reply
    ]);

    // Return successful response
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversation_id,
        'reply' => $reply
    ]);

}
catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);

}
