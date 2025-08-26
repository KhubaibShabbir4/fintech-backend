<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class NormalizeApiPrefix
{
	public function handle(Request $request, Closure $next)
	{
		$path = '/'.ltrim($request->getPathInfo(), '/');
		if (!str_starts_with($path, '/api/')) {
			$known = ['auth/', 'payments/', 'payment/', 'merchant/', 'admin/'];
			foreach ($known as $prefix) {
				if (str_starts_with(ltrim($path, '/'), $prefix)) {
					// mutate path to include /api
					$request->server->set('REQUEST_URI', '/api'.$path);
					$request->server->set('PATH_INFO', '/api'.$path);
					break;
				}
			}
		}
		return $next($request);
	}
}


