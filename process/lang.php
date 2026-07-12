<?php
declare(strict_types=1);

$dictionary = [
    'Method not allowed.' => [
        'pt' => 'Método não permitido.',
    ],
    'Thanks!' => [
        'pt' => 'Obrigado!',
    ],
    'Please fill in all fields.' => [
        'pt' => 'Por favor preencha todos os campos.',
    ],
    'Please enter a valid email address.' => [
        'pt' => 'Por favor introduza um email válido.',
    ],
    'Your message has been sent successfully.' => [
        'pt' => 'A sua mensagem foi enviada com sucesso.',
    ],
    'Something went wrong sending your message. Please try again later.' => [
        'pt' => 'Ocorreu um erro ao enviar a sua mensagem. Por favor tente novamente mais tarde.',
    ],
    'Mailchimp is not configured correctly.' => [
        'pt' => 'A subscrição não está configurada corretamente.',
    ],
    'Sorry, something went wrong. Please try again later.' => [
        'pt' => 'Ocorreu um erro. Por favor tente novamente mais tarde.',
    ],
    'Thanks for subscribing! Check your inbox to confirm.' => [
        'pt' => 'Obrigado por subscrever! Verifique o seu email para confirmar a subscrição.',
    ],
    "You're already subscribed!" => [
        'pt' => 'Já está subscrito!',
    ],
];

function t(string $text, string $lang = 'pt'): string
{
    global $dictionary;

    return $dictionary[$text][$lang] ?? $text;
}
