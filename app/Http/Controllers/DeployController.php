<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeployController extends Controller
{
    public function deploy(Request $request)
    {
        // GitHub webhook secret
        $secret = env('GITHUB_WEBHOOK_SECRET');
        
        // Verify GitHub webhook signature
        $signature = $request->header('X-Hub-Signature-256');
        if (!$this->verifySignature($request, $signature, $secret)) {
            Log::error('Invalid webhook signature');
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Verify that this is a push to the main branch
        $payload = $request->all();
        if (!isset($payload['ref']) || $payload['ref'] !== 'refs/heads/main') {
            return response()->json(['message' => 'Not a push to main branch']);
        }

        try {
            // Execute deployment script
            $output = [];
            $returnVar = 0;
            exec('./deploy.sh', $output, $returnVar);

            if ($returnVar !== 0) {
                Log::error('Deployment failed', ['output' => $output]);
                return response()->json(['error' => 'Deployment failed'], 500);
            }

            Log::info('Deployment successful', ['output' => $output]);
            return response()->json(['message' => 'Deployment successful']);
        } catch (\Exception $e) {
            Log::error('Deployment error: ' . $e->getMessage());
            return response()->json(['error' => 'Deployment error'], 500);
        }
    }

    private function verifySignature(Request $request, $signature, $secret)
    {
        if (!$signature) {
            return false;
        }

        $payload = $request->getContent();
        $expectedSignature = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        
        return hash_equals($expectedSignature, $signature);
    }
}
