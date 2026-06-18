<?php

namespace App\Http\Controllers;

use App\Models\AiProviderSetting;
use App\Services\AiValidationService;
use App\Services\ToolProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class ApiSettingController extends Controller
{
    public function __construct(
        private AiValidationService $ai,
        private ToolProfileService $toolProfiles,
    ) {
    }

    public function index()
    {
        $providers = $this->ai->listProviderSettings();
        $toolProfiles = $this->toolProfiles->options();
        $useSimulation = config('ai.use_simulation');
        $defaultProvider = config('ai.default_provider');

        return view('settings.api', compact('providers', 'toolProfiles', 'useSimulation', 'defaultProvider'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'provider_key' => ['nullable', 'string', 'max:80', 'regex:/^[a-z0-9_\\-]+$/'],
            'provider_label' => ['required', 'string', 'max:120'],
            'tool_profile' => ['nullable', Rule::in($this->toolProfiles->keys())],
            'driver' => ['required', Rule::in(['openai_compatible', 'gemini', 'ollama'])],
            'api_key' => ['nullable', 'string', 'max:5000'],
            'api_url' => ['nullable', 'url', 'max:500'],
            'model' => ['nullable', 'string', 'max:255'],
            'use_live_api' => ['nullable', 'boolean'],
            'clear_api_key' => ['nullable', 'boolean'],
        ]);

        $providerKey = $data['provider_key'] ?: Str::slug($data['provider_label'], '_');
        if (!$providerKey) {
            return back()->withErrors(['provider_label' => 'Nama provider tidak valid.'])->withInput();
        }

        $setting = AiProviderSetting::firstOrNew(['provider_key' => $providerKey]);
        $setting->provider_label = $data['provider_label'];
        $setting->tool_profile = $data['tool_profile'] ?? null;
        $setting->driver = $data['driver'];
        $setting->api_url = $data['api_url'] ?? null;
        $setting->model = $data['model'] ?? null;
        $setting->use_live_api = (bool) ($data['use_live_api'] ?? false);

        if ($request->boolean('clear_api_key')) {
            $setting->api_key = null;
        } elseif ($request->filled('api_key')) {
            $setting->api_key = $data['api_key'];
        }

        $setting->save();

        return back()->with('success', 'Pengaturan API berhasil disimpan.');
    }

    public function destroy(string $providerKey)
    {
        $setting = AiProviderSetting::where('provider_key', $providerKey)->firstOrFail();
        $setting->delete();

        return back()->with('success', 'Provider AI berhasil dihapus.');
    }
}
