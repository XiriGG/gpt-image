<?php
function getApiConfig($provider = null) {
    $configFile = __DIR__ . '/api_config.json';
    if (!file_exists($configFile)) {
        return null;
    }
    $config = json_decode(file_get_contents($configFile), true);
    if ($provider === null) {
        return $config;
    }
    return isset($config[$provider]) ? $config[$provider] : null;
}

function getEnabledApiConfig() {
    $config = getApiConfig();
    if (!$config) {
        return null;
    }
    foreach ($config as $provider => $settings) {
        if (isset($settings['enabled']) && $settings['enabled'] && !empty($settings['api_key'])) {
            return array(
                'provider' => $provider,
                'config' => $settings
            );
        }
    }
    return null;
}

function saveApiConfig($config) {
    $configFile = __DIR__ . '/api_config.json';
    return file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
?>