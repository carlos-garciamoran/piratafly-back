<?php

namespace App\Http\Middleware;

use App\User;
use Auth0\SDK\JWTVerifier;
use Closure;
use GuzzleHttp\Client;

class CheckJWT
{

    /**
     * Validate an incoming JWT access token.
     *
     * @param Request  $request - Illuminate HTTP Request object.
     * @param Closure  $next - Function to call when middleware is complete.
     *
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $accessToken = $request->bearerToken();

        if (empty($accessToken)) {
            return response()->json(['message' => 'Bearer token missing'], 401);
        }

        $laravelConfig = config('laravel-auth0');

        $jwtConfig = [
            'authorized_iss' => $laravelConfig['authorized_issuers'],
            'valid_audiences' => [$laravelConfig['api_identifier']],
            'supported_algs' => $laravelConfig['supported_algs'],
        ];

        try {
            $jwtVerifier = new JWTVerifier($jwtConfig);
            $decodedToken = $jwtVerifier->verifyAndDecode($accessToken);
        } catch (\Exception $e) {  // $e->getMessage()
            return response()->json(['message' => 'Unknown error'], 401);
        }

        // V1.1: move into dedicated middleware ('after' type?)
        // V2.0: improve performance by querying Redis (over MySQL)
        if (($user = User::where('sub', $decodedToken->sub)->first()) === null) {
            // V2.0: improve performance by parsing access token with user props
            //    -> avoid request to Auth0 API
            $client = new Client;
            $response = $client->get('https://'.$laravelConfig['domain'].'/userinfo', [
                'headers' => [
                    'Authorization' => $request->header('Authorization')
                ]
            ]);

            $data = json_decode($response->getBody());

            $user = User::create([
                'sub' => $data->sub,
                'email' => $data->email,
                'name' => $data->name
            ]);
        }

        // Make the user object accessible via the $request.
        $request->attributes->add(['user' => $user]);

        return $next($request);
    }
}
