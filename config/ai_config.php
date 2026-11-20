<?php

include 'ai_secret.php';

define('GROQ_API_KEY', GROQ_SECRET_KEY);

define(
    'GROQ_API_URL',
    'https://api.groq.com/openai/v1/chat/completions'
);

define('GROQ_MODEL', 'openai/gpt-oss-120b');