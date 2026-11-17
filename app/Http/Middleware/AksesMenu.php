<?php

namespace App\Http\Middleware;

use App\Support\HakAkses;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Proteksi API per menu: `AksesMenu:kunci-menu` (boleh beberapa kunci,
 * dipisah koma — lolos bila user punya salah satunya).
 *
 * Super admin lolos semua. User tanpa konfigurasi memakai menu DEFAULT
 * (lihat HakAkses::DEFAULT_MENUS). Route publik TIDAK dipasangi middleware ini.
 */
class AksesMenu
{
    public function handle(Request $request, Closure $next, string ...$menus): Response
    {
        if ($menus !== [] && HakAkses::punyaMenu($request->user(), ...$menus)) {
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Akses ditolak. Anda tidak memiliki hak akses menu ' . implode(' / ', $menus) . '.',
        ], 403);
    }
}
