<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Email selalu disimpan & dicocokkan dalam huruf kecil tanpa spasi.
 * Di HP huruf pertama sering otomatis kapital ("Admin@gmail.com"),
 * sedangkan PostgreSQL (Supabase) membedakan huruf besar/kecil.
 */
class NormalizeEmailInput
{
    private const FIELDS = ['email', 'offline_customer_email'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET')) {
            $changes = [];

            foreach (self::FIELDS as $field) {
                $value = $request->input($field);

                if (is_string($value)) {
                    $changes[$field] = mb_strtolower(trim($value));
                }
            }

            if ($changes) {
                $request->merge($changes);
            }
        }

        return $next($request);
    }
}
