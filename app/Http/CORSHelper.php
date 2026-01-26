<?php
namespace App\Http;

use Illuminate\Http\Response;

class CORSHelper
{
    public static function response($data, $status = 200, $headers = [])
    {
        // Ensure $headers is always an array
        if (!is_array($headers)) {
            $headers = [];
        }
        
        $headers = array_merge($headers, [
            "Access-Control-Allow-Origin" => "*",
            "Access-Control-Allow-Methods" => "GET, POST, PUT, DELETE, OPTIONS",
            "Access-Control-Allow-Headers" => "Content-Type, Authorization, X-Requested-With",
        ]);
        
        return response()->json($data, $status, $headers);
    }
}