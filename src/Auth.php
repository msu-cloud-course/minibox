<?php

declare(strict_types=1);

namespace App;

use App\Exceptions\RedirectException;
use App\Models\User;

// Who is logged in. The session only stores the user's id; the user comes from the database.
class Auth
{
    private ?User $user = null;
    private bool $loaded = false;

    /** The logged-in user, or null. */
    public function user(): ?User
    {
        if (!$this->loaded) {
            $this->loaded = true;
            $id = $_SESSION['user_id'] ?? null;
            $this->user = $id !== null ? User::find((int) $id) : null;
        }
        return $this->user;
    }

    /** Like user(), but sends visitors who are not logged in to the login page. */
    public function requireUser(): User
    {
        return $this->user() ?? throw new RedirectException('/login');
    }

    /** The user if user() was already called during this request (no database access). */
    public function knownUser(): ?User
    {
        return $this->loaded ? $this->user : null;
    }

    public function login(User $user): void
    {
        // A new session id after login protects against session fixation.
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user->id;
        $this->user = $user;
        $this->loaded = true;
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_regenerate_id(true);
        $this->user = null;
        $this->loaded = true;
    }
}
