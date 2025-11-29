<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\CompanyIntegration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IntegrationController extends Controller
{
    /**
     * Tela de integrações
     */
    public function index(Request $request)
    {
        $company = $request->user()->getCurrentCompany();

        // Busca todas as integrações da empresa
        $integrations = $company->integrations()->get()->keyBy('integration_type');

        // Integração do Mercado Livre
        $mlIntegration = $integrations->get('mercado_livre');
        $mlConnected = $mlIntegration && $mlIntegration->isConnected();

        // Integração do Google Drive
        $driveIntegration = $integrations->get('google_drive');
        $driveConnected = $driveIntegration && $driveIntegration->isConnected();

        return view('panel.integrations.index', compact('company', 'mlIntegration', 'mlConnected', 'driveIntegration', 'driveConnected'));
    }

    /**
     * Inicia conexão com Mercado Livre (OAuth)
     */
    public function mercadoLivreConnect(Request $request)
    {
        $appId = env('ML_APP_ID');
        $redirectUri = route('panel.integrations.ml.callback');

        // Salva company_id na sessão para recuperar no callback
        session(['ml_oauth_company_id' => $request->user()->current_company_id]);

        $authUrl = "https://auth.mercadolivre.com.br/authorization?response_type=code&client_id={$appId}&redirect_uri={$redirectUri}";

        return redirect($authUrl);
    }

    /**
     * Callback OAuth do Mercado Livre
     */
    public function mercadoLivreCallback(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect()->route('panel.integrations.index')
                ->with('error', 'Erro ao conectar com Mercado Livre: código não fornecido.');
        }

        try {
            // Troca code por access_token
            $response = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
                'grant_type' => 'authorization_code',
                'client_id' => env('ML_APP_ID'),
                'client_secret' => env('ML_CLIENT_SECRET'),
                'code' => $code,
                'redirect_uri' => route('panel.integrations.ml.callback')
            ]);

            if (!$response->successful()) {
                throw new \Exception('Falha ao obter token: ' . $response->body());
            }

            $data = $response->json();

            // Recupera company_id da sessão
            $companyId = session('ml_oauth_company_id') ?? auth()->user()->current_company_id;
            session()->forget('ml_oauth_company_id');

            // Busca dados do usuário do ML
            $userResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $data['access_token']
            ])->get('https://api.mercadolibre.com/users/me');

            $userData = $userResponse->json();

            // Cria ou atualiza integração
            $integration = CompanyIntegration::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'integration_type' => 'mercado_livre'
                ],
                [
                    'active' => true,
                    'credentials' => [
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'],
                        'user_id' => $data['user_id'],
                        'nickname' => $userData['nickname'] ?? null,
                    ],
                    'settings' => [
                        'site_id' => $userData['site_id'] ?? 'MLB',
                    ],
                    'connected_at' => now(),
                    'expires_at' => now()->addSeconds($data['expires_in'] ?? 21600)
                ]
            );

            return redirect()->route('panel.integrations.index')
                ->with('ok', 'Mercado Livre conectado com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro no OAuth ML: ' . $e->getMessage());

            return redirect()->route('panel.integrations.index')
                ->with('error', 'Erro ao conectar: ' . $e->getMessage());
        }
    }

    /**
     * Desconecta Mercado Livre
     */
    public function mercadoLivreDisconnect(Request $request)
    {
        $companyId = $request->user()->current_company_id;

        $integration = CompanyIntegration::where('company_id', $companyId)
            ->where('integration_type', 'mercado_livre')
            ->first();

        if ($integration) {
            $integration->update([
                'active' => false,
                'credentials' => null
            ]);
        }

        return back()->with('ok', 'Mercado Livre desconectado.');
    }

    /**
     * Reconecta Mercado Livre (mesmo que connect, mas com mensagem diferente)
     */
    public function mercadoLivreReconnect(Request $request)
    {
        return $this->mercadoLivreConnect($request);
    }

    /**
     * Atualiza token do Mercado Livre (refresh token)
     */
    public function mercadoLivreRefreshToken(CompanyIntegration $integration)
    {
        try {
            $credentials = $integration->getDecryptedCredentials();

            if (!$credentials || !isset($credentials['refresh_token'])) {
                throw new \Exception('Refresh token não encontrado');
            }

            $response = Http::asForm()->post('https://api.mercadolibre.com/oauth/token', [
                'grant_type' => 'refresh_token',
                'client_id' => env('ML_APP_ID'),
                'client_secret' => env('ML_CLIENT_SECRET'),
                'refresh_token' => $credentials['refresh_token']
            ]);

            if (!$response->successful()) {
                throw new \Exception('Falha ao renovar token');
            }

            $data = $response->json();

            $integration->update([
                'credentials' => [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'user_id' => $credentials['user_id'],
                    'nickname' => $credentials['nickname'] ?? null,
                ],
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 21600)
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Erro ao renovar token ML: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Inicia conexão com Google Drive (OAuth)
     */
    public function googleDriveConnect(Request $request)
    {
        $clientId = env('GOOGLE_CLIENT_ID');
        $redirectUri = route('panel.integrations.drive.callback');

        // Salva company_id na sessão
        session(['drive_oauth_company_id' => $request->user()->current_company_id]);

        $scopes = [
            'https://www.googleapis.com/auth/drive.readonly',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile'
        ];

        $authUrl = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(' ', $scopes),
            'access_type' => 'offline',
            'prompt' => 'consent'
        ]);

        return redirect($authUrl);
    }

    /**
     * Callback OAuth do Google Drive
     */
    public function googleDriveCallback(Request $request)
    {
        $code = $request->get('code');

        if (!$code) {
            return redirect()->route('panel.integrations.index')
                ->with('error', 'Erro ao conectar com Google Drive: código não fornecido.');
        }

        try {
            // Troca code por access_token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'authorization_code',
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'code' => $code,
                'redirect_uri' => route('panel.integrations.drive.callback')
            ]);

            if (!$response->successful()) {
                throw new \Exception('Falha ao obter token: ' . $response->body());
            }

            $data = $response->json();

            // Recupera company_id da sessão
            $companyId = session('drive_oauth_company_id') ?? auth()->user()->current_company_id;
            session()->forget('drive_oauth_company_id');

            // Busca dados do usuário do Google
            $userResponse = Http::withHeaders([
                'Authorization' => 'Bearer ' . $data['access_token']
            ])->get('https://www.googleapis.com/oauth2/v2/userinfo');

            $userData = $userResponse->json();

            // Cria ou atualiza integração
            $integration = CompanyIntegration::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'integration_type' => 'google_drive'
                ],
                [
                    'active' => true,
                    'credentials' => [
                        'access_token' => $data['access_token'],
                        'refresh_token' => $data['refresh_token'] ?? null,
                        'email' => $userData['email'] ?? null,
                        'name' => $userData['name'] ?? null,
                    ],
                    'connected_at' => now(),
                    'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600)
                ]
            );

            return redirect()->route('panel.integrations.index')
                ->with('ok', 'Google Drive conectado com sucesso!');

        } catch (\Exception $e) {
            \Log::error('Erro no OAuth Google Drive: ' . $e->getMessage());

            return redirect()->route('panel.integrations.index')
                ->with('error', 'Erro ao conectar: ' . $e->getMessage());
        }
    }

    /**
     * Desconecta Google Drive
     */
    public function googleDriveDisconnect(Request $request)
    {
        $companyId = $request->user()->current_company_id;

        $integration = CompanyIntegration::where('company_id', $companyId)
            ->where('integration_type', 'google_drive')
            ->first();

        if ($integration) {
            $integration->update([
                'active' => false,
                'credentials' => null
            ]);
        }

        return back()->with('ok', 'Google Drive desconectado.');
    }

    /**
     * Atualiza token do Google Drive (refresh token)
     */
    public function googleDriveRefreshToken(CompanyIntegration $integration)
    {
        try {
            $credentials = $integration->getDecryptedCredentials();

            if (!$credentials || !isset($credentials['refresh_token'])) {
                throw new \Exception('Refresh token não encontrado');
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'refresh_token',
                'client_id' => env('GOOGLE_CLIENT_ID'),
                'client_secret' => env('GOOGLE_CLIENT_SECRET'),
                'refresh_token' => $credentials['refresh_token']
            ]);

            if (!$response->successful()) {
                throw new \Exception('Falha ao renovar token');
            }

            $data = $response->json();

            $integration->update([
                'credentials' => [
                    'access_token' => $data['access_token'],
                    'refresh_token' => $credentials['refresh_token'],
                    'email' => $credentials['email'] ?? null,
                    'name' => $credentials['name'] ?? null,
                ],
                'expires_at' => now()->addSeconds($data['expires_in'] ?? 3600)
            ]);

            return true;

        } catch (\Exception $e) {
            \Log::error('Erro ao renovar token Google Drive: ' . $e->getMessage());
            return false;
        }
    }
}
