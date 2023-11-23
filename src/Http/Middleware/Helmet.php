<?php

namespace Tuples\Http\Middleware;

use Tuples\Http\Contracts\Middleware;

class Helmet extends Middleware
{
    public function handle(\Closure $next)
    {
        // Enable Content Security Policy (CSP)
        $csp = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';";
        $this->res->header('Content-Security-Policy', $csp);

        // Enable Strict-Transport-Security (HSTS)
        $this->res->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Prevent MIME sniffing
        $this->res->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS Protection
        $this->res->header('X-XSS-Protection', '1; mode=block');

        // Prevent Clickjacking
        $this->res->header('X-Frame-Options', 'DENY');

        // Execute the next middleware in the stack
        return $next();
    }
}
