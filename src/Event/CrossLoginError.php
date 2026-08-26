<?php

declare(strict_types = 1);

namespace Lingoda\CrossLoginBundle\Event;

/**
 * Why a cross-login hand-off did not produce an authenticated user.
 *
 * The value is what the receive controller appends to its fallback redirect, so it is part of
 * the public contract with the landing page. Keep the set small and stable, and never put an
 * exception message in a URL.
 */
enum CrossLoginError: string
{
    case MissingToken = 'missing_token';
    case InvalidToken = 'invalid_token';
    case UnknownUser = 'unknown_user';
}
