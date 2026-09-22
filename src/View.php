<?php

declare(strict_types=1);

namespace App;

// Renders the PHP templates in views/ and offers small helpers to them
// (escaping, flash messages, formatting, icons).
class View
{
    public function __construct(
        private readonly string $viewsDir,
        private readonly Csrf $csrf,
        private readonly array $shared = [],   // variables every template can use (header and footer)
    ) {
    }

    /** Render views/{$template}.php inside views/layout.php. */
    public function render(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        $view = $this;
        $data += $this->shared;

        // The page template writes into a buffer; the layout prints it as $content.
        ob_start();
        extract($data);
        require "{$this->viewsDir}/{$template}.php";
        $content = ob_get_clean();

        require "{$this->viewsDir}/layout.php";
    }

    /** Escape text for HTML. Use it for everything that comes from users or the database. */
    public function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    /** The hidden field every POST form needs (see Csrf). */
    public function csrfField(): string
    {
        return '<input type="hidden" name="_token" value="' . $this->e($this->csrf->token()) . '">';
    }

    // ---------- flash messages: shown once, on the next page ----------

    public function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public function takeFlash(): ?array
    {
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        return $flash;
    }

    // ---------- formatting ----------

    /** 1536 -> "1.5 KB" */
    public function bytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $bytes;
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return $unit === 0 ? "$bytes B" : rtrim(rtrim(number_format($size, 1), '0'), '.') . ' ' . $units[$unit];
    }

    /** "2026-09-17 14:05:00" (UTC in the database) -> "17 Sep 2026, 14:05 UTC" */
    public function date(string $utc): string
    {
        return gmdate('j M Y, H:i', strtotime($utc . ' UTC')) . ' UTC';
    }

    // ---------- icons (Lucide, https://lucide.dev, ISC license) ----------

    private const ICONS = [
        'box' => '<path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/>',
        'upload' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" x2="12" y1="3" y2="15"/>',
        'download' => '<path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" x2="12" y1="15" y2="3"/>',
        'trash' => '<path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>',
        'file' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
        'image' => '<rect width="18" height="18" x="3" y="3" rx="2" ry="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/>',
        'text' => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M10 9H8"/><path d="M16 13H8"/><path d="M16 17H8"/>',
        'logout' => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/>',
        'archive' => '<rect width="20" height="5" x="2" y="3" rx="1"/><path d="M4 8v11a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8"/><path d="M10 12h4"/>',
    ];

    public function icon(string $name): string
    {
        return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" '
            . 'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . self::ICONS[$name] . '</svg>';
    }

    /** Pick an icon that matches the kind of file. */
    public function fileIcon(string $mimeType): string
    {
        return $this->icon(match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'text/'), $mimeType === 'application/pdf' => 'text',
            str_contains($mimeType, 'zip'), str_contains($mimeType, 'compressed'), str_contains($mimeType, 'tar') => 'archive',
            default => 'file',
        });
    }
}
