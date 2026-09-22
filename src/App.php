<?php

declare(strict_types=1);

namespace App;

use App\Controllers\AuthController;
use App\Controllers\FileController;
use App\Controllers\HealthController;
use App\Exceptions\HttpException;
use App\Exceptions\RedirectException;
use App\Models\Model;
use Dotenv\Dotenv;
use Throwable;

// Creates all objects once and handles one web request.
class App
{
    public readonly Config $config;
    public readonly Database $db;
    private readonly Csrf $csrf;
    private readonly View $view;
    private readonly FileController $files;
    private readonly AuthController $authPages;
    private readonly HealthController $health;

    public function __construct(private readonly string $rootDir)
    {
        // Load .env if it exists (real environment variables still win, see Config).
        Dotenv::createImmutable($rootDir)->safeLoad();
        date_default_timezone_set('UTC');

        $this->config = new Config();
        $this->db = new Database($this->config);
        $this->csrf = new Csrf();
        Model::useDatabase($this->db);

        $auth = new Auth();
        $storage = new Storage($this->uploadDir());

        $this->view = new View($rootDir . '/views', $this->csrf, [
            'auth' => $auth,
            // Shown in the page footer.
            'hostname' => gethostname(),
            'dbHost' => $this->db->host(),
            'storage' => $storage->description(),
        ]);

        $this->files = new FileController(
            $auth,
            $storage,
            $this->view,
            $this->config->getInt('MAX_UPLOAD_MB', 10),
        );
        $this->authPages = new AuthController($auth, $this->view);
        $this->health = new HealthController($this->db);
    }

    public function run(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        try {
            // /health is called very often by monitoring tools and needs no session.
            if ($path !== '/health') {
                $this->startSession();
            }

            if ($method === 'POST' && !$this->csrf->isValid($_POST['_token'] ?? null) && !$this->bodyWasTooLarge()) {
                throw new HttpException(403);
            }

            $this->routes()->dispatch($method, $path);
        } catch (RedirectException $e) {
            // 303 = "see other": the browser loads the new page with GET (also after a form POST).
            header('Location: ' . $e->url, true, 303);
        } catch (HttpException $e) {
            $this->showError($e->status);
        } catch (Throwable $e) {
            error_log((string) $e);
            $details = $this->config->getBool('APP_DEBUG') ? $e->getMessage() : null;
            $this->showError(500, Database::problemHint($e), $details);
        }
    }

    private function routes(): Router
    {
        $router = new Router();

        $router->get('/', fn () => $this->files->index());
        $router->post('/upload', fn () => $this->files->upload());
        $router->get('/files/(\d+)/download', fn (string $id) => $this->files->download($id));
        $router->post('/files/(\d+)/delete', fn (string $id) => $this->files->delete($id));

        $router->get('/login', fn () => $this->authPages->showLogin());
        $router->post('/login', fn () => $this->authPages->login());
        $router->get('/register', fn () => $this->authPages->showRegister());
        $router->post('/register', fn () => $this->authPages->register());
        $router->post('/logout', fn () => $this->authPages->logout());

        $router->get('/health', fn () => $this->health->check());

        return $router;
    }

    private function startSession(): void
    {
        // Session files live in storage/sessions. The cookie is not readable from JavaScript
        // and is not sent along with requests started by other websites.
        session_save_path($this->rootDir . '/storage/sessions');
        session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
        session_start();
    }

    // PHP drops the whole form (including the CSRF token) when a request is bigger than
    // post_max_size. Such a request cannot change anything, so it may pass: the upload
    // page then shows "The file is larger than ...".
    private function bodyWasTooLarge(): bool
    {
        return $_POST === [] && $_FILES === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0;
    }

    private function uploadDir(): string
    {
        $dir = $this->config->get('UPLOAD_DIR', 'storage/uploads');
        $dir = rtrim($dir, '/\\');
        // Absolute paths look like "/var/uploads" (Linux, macOS) or "C:\uploads" (Windows).
        return preg_match('#^([A-Za-z]:)?[\\\\/]#', $dir) ? $dir : $this->rootDir . '/' . $dir;
    }

    private function showError(int $status, ?string $hint = null, ?string $details = null): void
    {
        // Drop anything a half-rendered page already produced.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $texts = [
            403 => ['Access denied', 'Your session has expired. Reload the page and try again.'],
            404 => ['Not found', 'This page or file does not exist.'],
            500 => ['Something went wrong', 'Please try again later.'],
        ];
        [$heading, $text] = $texts[$status] ?? $texts[500];

        $this->view->render('error', [
            'title' => $heading,
            'code' => $status,
            'heading' => $heading,
            'text' => $hint ?? $text,
            'details' => $details,
        ], $status);
    }
}
