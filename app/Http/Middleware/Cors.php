<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // $response = $next($request);

        // // Set the Access-Control-Allow-Origin header to the request's origin
        // $response->headers->set('Access-Control-Allow-Origin', $request->header('Origin'));

        // return $response;

        $origin = $request->header('Origin');

        // Set the Access-Control-Allow-Origin header to the requesting origin
        return response()
            ->json(['data' => 'your_response_data'])
            ->header('Access-Control-Allow-Origin', $origin);
    }
}
