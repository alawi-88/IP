<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;

class NafathSettings extends Model
{
    use LogsActivity, HasActivityLog;

    protected $fillable = [
        'is_enabled',
        'client_id',
        'client_secret',
        'redirect_uri',
        'logout_uri',
        'environment',
        'login_method',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    protected array $logFields = [
        'is_enabled',
        'client_id',
        'client_secret',
        'redirect_uri',
        'logout_uri',
        'environment',
        'login_method',
    ];

    protected string $moduleName = 'Nafath Settings';
    protected string $logName = 'nafath_settings';

    /**
     * Get the current Nafath settings instance
     */
    public static function current(): self
    {
        $settings = self::first();
        if (!$settings) {
            $settings = new self();
            $settings->is_enabled = false;
            $settings->save();
        }
        return $settings;
    }

    /**
     * Check if Nafath SSO is enabled
     */
    public function isEnabled(): bool
    {
        return (bool) $this->is_enabled && !empty($this->client_id) && !empty($this->client_secret);
    }

    /**
     * Enable Nafath SSO with OAuth2 credentials
     */
    public function enable(string $clientId, string $clientSecret, string $redirectUri = null, string $logoutUri = null, string $environment = 'production', string $loginMethod = 'both'): bool
    {
        return $this->update([
            'is_enabled' => true,
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri' => $redirectUri,
            'logout_uri' => $logoutUri,
            'environment' => $environment,
            'login_method' => $loginMethod,
        ]);
    }

    /**
     * Disable Nafath SSO
     */
    public function disable(): bool
    {
        return $this->update([
            'is_enabled' => false,
        ]);
    }

    /**
     * Get available login methods
     */
    public static function getLoginMethods(): array
    {
        return [
            'nafath' => 'Nafath Only',
            'credentials' => 'Regular Method Only',
            'both' => 'Both Options',
        ];
    }

    /**
     * Check if Nafath login is available
     */
    public function isNafathLoginAvailable(): bool
    {
        return $this->isEnabled() && in_array($this->login_method, ['nafath', 'both']);
    }

    /**
     * Check if regular login is available
     */
    public function isRegularLoginAvailable(): bool
    {
        return in_array($this->login_method, ['credentials', 'both']);
    }

    /**
     * Get the MIP base URL based on environment
     */
    public function getMipBaseUrl(): string
    {
        return match($this->environment) {
            'test' => 'https://iamtst.logisti.sa',
            'staging' => 'https://stg-iam.logisti.sa',
            'production' => 'https://iam.logisti.sa',
            default => 'https://iam.logisti.sa',
        };
    }

    /**
     * Get the token endpoint URL
     */
    public function getTokenEndpoint(): string
    {
        return $this->getMipBaseUrl() . '/connect/token';
    }

    /**
     * Get the userinfo endpoint URL
     */
    public function getUserInfoEndpoint(): string
    {
        return $this->getMipBaseUrl() . '/connect/userinfo';
    }

    /**
     * Get the discovery document URL
     */
    public function getDiscoveryEndpoint(): string
    {
        return $this->getMipBaseUrl() . '/.well-known/openid-configuration';
    }

    /**
     * Get the authorization endpoint URL (Login page)
     */
    public function getAuthorizationEndpoint(): string
    {
        return $this->getMipBaseUrl() . '/Account/Login';
    }

    /**
     * Generate PKCE code verifier and challenge
     */
    public function generatePkceParameters(): array
    {
        $codeVerifier = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $codeChallenge = rtrim(strtr(base64_encode(hash('sha256', $codeVerifier, true)), '+/', '-_'), '=');

        return [
            'code_verifier' => $codeVerifier,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256'
        ];
    }

    /**
     * Build authorization URL with proper structure
     */
    public function buildAuthorizationUrl(string $state, string $redirectUri = null): array
    {
        $pkce = $this->generatePkceParameters();

        // Use provided redirect URI or fallback to stored one
        $redirectUri = $redirectUri ?? $this->redirect_uri;

        // Build authorization parameters
        $authParams = [
            'client_id' => $this->client_id,
            'response_type' => 'code',
            'scope' => 'openid profile email FirstName',
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'code_challenge' => $pkce['code_challenge'],
            'code_challenge_method' => $pkce['code_challenge_method']
        ];

        // Build the return URL (double-encoded)
        $returnUrl = '/connect/authorize/callback?' . http_build_query($authParams);

        // Build the final authorization URL
        $authorizationUrl = $this->getAuthorizationEndpoint() . '?ReturnUrl=' . urlencode($returnUrl);

        return [
            'authorization_url' => $authorizationUrl,
            'code_verifier' => $pkce['code_verifier']
        ];
    }
}
