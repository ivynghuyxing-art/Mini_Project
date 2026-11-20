
<?php

include '../../_base.php';

auth(['member']);

$_title = 'AI Chat';

$user_id = $_user->user_id;
$conversation_id = get('conversation_id', '');

// Get conversation history
$stmt = $_db->prepare("
    SELECT conversation_id, created_at
    FROM conversation
    WHERE user_id = ?
    ORDER BY created_at DESC
");
$stmt->execute([$user_id]);
$history = $stmt->fetchAll();

// Load selected conversation messages
$messages = [];

if ($conversation_id) {
    $stmt = $_db->prepare("
        SELECT conversation_id
        FROM conversation
        WHERE conversation_id = ? AND user_id = ?
    ");
    $stmt->execute([$conversation_id, $user_id]);

    if ($stmt->fetch()) {
        $stmt = $_db->prepare("
            SELECT sender_type, message_content, sent_at
            FROM message
            WHERE conversation_id = ?
            ORDER BY sent_at ASC
        ");
        $stmt->execute([$conversation_id]);
        $messages = $stmt->fetchAll();
    }
    else {
        $conversation_id = '';
    }
}

include __DIR__ . '/../../_head.php';

?>

<div class="ai-container">

    <!-- Header -->
    <div class="ai-header">
        <div>
            <h2>Story Vault AI</h2>
            <p>Your personal reading assistant</p>
        </div>

        <div class="ai-actions">
            <button type="button" id="historyBtn">
                Chat History
            </button>

            <button type="button" id="newChatBtn">
                + New Chat
            </button>
        </div>
    </div>

    <!-- Chat Messages -->
    <div class="ai-messages" id="messages">

        <?php if (empty($messages)): ?>

            <div class="ai-welcome" id="welcomeMessage">
                <h2>Hello! Welcome to Story Vault AI</h2>
                <p>Ask me anything about books or reading.</p>
            </div>

        <?php else: ?>

            <?php foreach ($messages as $msg): ?>

                <div class="ai-message <?= $msg->sender_type === 'member' ? 'member' : 'ai' ?>">
                    <div class="message-content">
                        <?= nl2br(htmlspecialchars($msg->message_content)) ?>
                    </div>
                </div>

            <?php endforeach; ?>

        <?php endif; ?>

    </div>

    <!-- Input Area -->
    <div class="ai-input-area">

        <form id="chatForm" class="ai-form">

            <input
                type="text"
                id="messageInput"
                placeholder="Ask Story Vault AI..."
                autocomplete="off"
                required
            >

            <button type="submit" id="sendBtn">
                Send
            </button>

        </form>

        <p class="ai-error" id="errorMessage"></p>

    </div>

</div>


<!-- Chat History Modal -->
<div class="history-modal" id="historyModal">

    <div class="history-box">

        <div class="history-header">
            <h3>Chat History</h3>

            <button type="button" class="history-close" id="closeHistoryBtn">
                x
            </button>
        </div>

        <div class="history-list">

            <?php if (empty($history)): ?>

                <p class="history-empty">No conversation history yet.</p>

            <?php else: ?>

                <?php foreach ($history as $item): ?>

                    <a
                        class="history-item"
                        href="?conversation_id=<?= urlencode($item->conversation_id) ?>"
                    >
                        <span>Conversation #<?= htmlspecialchars($item->conversation_id) ?></span>

                        <small>
                            <?= htmlspecialchars($item->created_at) ?>
                        </small>
                    </a>

                <?php endforeach; ?>

            <?php endif; ?>

        </div>

    </div>

</div>

<script>
window.chatConfig = {
    conversationId: <?= json_encode($conversation_id) ?>
};
</script>

<script src="https://cdn.jsdelivr.net/npm/marked/marked.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/dompurify@3/dist/purify.min.js"></script>
<script src="../../js/app.js"></script>

<?php 
include __DIR__ . '/../../_footer.php'; ?>
