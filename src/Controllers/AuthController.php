<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Exceptions\RedirectException;
use App\Models\User;
use App\View;

// Sign up, log in, log out.
class AuthController
{
    public function __construct(
        private readonly Auth $auth,
        private readonly View $view,
    ) {
    }

    // GET /login
    public function showLogin(): void
    {
        $this->redirectIfLoggedIn();
        $this->view->render('login', ['title' => 'Log in', 'email' => '', 'error' => null]);
    }

    // POST /login
    public function login(): void
    {
        $email = $this->normalizeEmail($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        $user = User::findByEmail($email);

        // Same message for "no such user" and "wrong password", so nobody can find out who has an account.
        if ($user === null || !$user->checkPassword($password)) {
            $this->view->render('login', [
                'title' => 'Log in',
                'email' => $email,
                'error' => 'Invalid email or password.',
            ], 422);
            return;
        }

        $this->auth->login($user);
        throw new RedirectException('/');
    }

    // GET /register
    public function showRegister(): void
    {
        $this->redirectIfLoggedIn();
        $this->view->render('register', ['title' => 'Sign up', 'email' => '', 'errors' => []]);
    }

    // POST /register
    public function register(): void
    {
        $email = $this->normalizeEmail($_POST['email'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');

        $errors = [];
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || strlen($email) > 255) {
            $errors['email'] = 'Enter a valid email address.';
        } elseif (User::findByEmail($email) !== null) {
            $errors['email'] = 'This email is already registered.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        } elseif ($password !== $confirmation) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            $this->view->render('register', ['title' => 'Sign up', 'email' => $email, 'errors' => $errors], 422);
            return;
        }

        $this->auth->login(User::create($email, $password));
        $this->view->flash('success', 'Welcome to MiniBox! Upload your first file.');
        throw new RedirectException('/');
    }

    // POST /logout
    public function logout(): void
    {
        $this->auth->logout();
        throw new RedirectException('/login');
    }

    private function normalizeEmail(mixed $email): string
    {
        return strtolower(trim((string) $email));
    }

    private function redirectIfLoggedIn(): void
    {
        if ($this->auth->user() !== null) {
            throw new RedirectException('/');
        }
    }
}
