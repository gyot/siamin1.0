<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Renders the SPA shell with Open Graph / Twitter meta tags injected.
 *
 * Crawler bots (WhatsApp, Telegram, Facebook, ...) do not execute JavaScript,
 * so a plain SPA route only ever exposes the generic index.html title. The web
 * server routes bot requests for shareable pages to this endpoint, which looks
 * the kegiatan up and injects per-page meta tags into the same SPA shell.
 */
class OgMetaController extends Controller
{
    private const SITE_NAME = 'SIMAIK';

    private const SITE_TITLE = 'SIMAIK - Sistem Informasi Administrasi Manajemen Kegiatan';

    private const SITE_DESCRIPTION = 'Sistem Informasi Administrasi Manajemen Kegiatan - BPMP Provinsi Nusa Tenggara Barat';

    private const SHAREABLE_TITLES = [
        'daftar-peserta' => 'Daftar Pengisi Biodata',
        'unduh-sertifikat' => 'Unduh Sertifikat',
        'evaluasi' => 'Evaluasi Kegiatan',
        'formulir' => 'Formulir Pendaftaran',
        'laporan-evaluasi' => 'Laporan Evaluasi',
    ];

    public function show(Request $request)
    {
        // The shareable page path itself (e.g. /kegiatan/125/...): the web
        // server forwards these requests straight to Laravel, so the request
        // path IS the canonical share URL.
        $path = $request->getPathInfo();

        $html = Cache::remember(
            'og-meta:' . md5($path),
            now()->addSeconds((int) config('og.cache_ttl', 1800)),
            fn () => $this->render($request, $path)
        );

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=UTF-8');
    }

    private function render(Request $request, string $path): string
    {
        $segments = array_values(array_filter(explode('/', $path), 'strlen'));
        $type = $segments[0] ?? '';
        $kode = $segments[1] ?? null;

        $kegiatan = null;
        if ($kode !== null && ctype_digit($kode)) {
            $kegiatan = Kegiatan::query()->find((int) $kode);
        }

        $title = self::SITE_TITLE;
        $description = self::SITE_DESCRIPTION;
        $ogType = 'website';

        if ($kegiatan && $type === 'kegiatan') {
            $title = $kegiatan->nama_kegiatan;
            $description = $this->buildDescription($kegiatan);
            $ogType = 'article';
        } elseif ($kegiatan && isset(self::SHAREABLE_TITLES[$type])) {
            $title = self::SHAREABLE_TITLES[$type] . ' - ' . $kegiatan->nama_kegiatan;
            $description = $this->buildDescription($kegiatan);
        }

        $url = $this->baseUrl($request) . ($path === '/' ? '/' : $path);
        $image = $this->resolveImage($request, $kegiatan);

        $meta = implode("\n    ", [
            '<meta property="og:site_name" content="' . e(self::SITE_NAME) . '">',
            '<meta property="og:type" content="' . e($ogType) . '">',
            '<meta property="og:title" content="' . e($title) . '">',
            '<meta property="og:description" content="' . e($description) . '">',
            '<meta property="og:url" content="' . e($url) . '">',
            '<meta property="og:image" content="' . e($image) . '">',
            '<meta property="og:locale" content="id_ID">',
            '<meta name="twitter:card" content="summary_large_image">',
            '<meta name="twitter:title" content="' . e($title) . '">',
            '<meta name="twitter:description" content="' . e($description) . '">',
            '<meta name="twitter:image" content="' . e($image) . '">',
        ]);

        $template = $this->spaShell();

        $pageTitle = $title === self::SITE_TITLE ? self::SITE_TITLE : $title . ' - SIMAIK';
        $template = preg_replace('/<title>.*?<\/title>/is', '<title>' . e($pageTitle) . '</title>', $template, 1) ?? $template;

        if (str_contains($template, '</head>')) {
            $template = str_replace('</head>', "    {$meta}\n  </head>", $template);
        }

        return $template;
    }

    private function spaShell(): string
    {
        $path = config('og.spa_index');
        if ($path && is_file($path) && ($html = File::get($path)) !== false && $html !== '') {
            return $html;
        }

        return '<!DOCTYPE html>' . "\n"
            . '<html lang="id"><head><meta charset="UTF-8">'
            . '<title>' . e(self::SITE_TITLE) . '</title></head>'
            . '<body><div id="app"></div></body></html>';
    }

    private function buildDescription(Kegiatan $kegiatan): string
    {
        $description = trim(preg_replace('/\s+/u', ' ', strip_tags((string) $kegiatan->deskripsi)) ?? '');

        if ($description === '') {
            return $kegiatan->nama_kegiatan . ' - BPMP Provinsi Nusa Tenggara Barat';
        }

        if (mb_strlen($description) > 200) {
            $description = rtrim(mb_substr($description, 0, 200)) . '...';
        }

        return $description;
    }

    private function resolveImage(Request $request, ?Kegiatan $kegiatan): string
    {
        $flyer = $kegiatan?->flyer;

        if ($flyer && Storage::disk('public')->exists($flyer)) {
            return $this->baseUrl($request) . '/storage/' . ltrim($flyer, '/');
        }

        return $this->baseUrl($request) . '/og-default.png';
    }

    /**
     * Absolute public base URL. TLS is terminated at Cloudflare, so the origin
     * sees plain HTTP — trust X-Forwarded-Proto (or OG_BASE_URL) for correct
     * https:// URLs in the preview tags.
     */
    private function baseUrl(Request $request): string
    {
        if ($configured = config('og.base_url')) {
            return rtrim($configured, '/');
        }

        $scheme = explode(',', (string) $request->headers->get('X-Forwarded-Proto'))[0]
            ?: $request->getScheme();

        return $scheme . '://' . $request->getHost();
    }
}
