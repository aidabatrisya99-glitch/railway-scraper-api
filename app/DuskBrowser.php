<?php

namespace App;

use Facebook\WebDriver\Chrome\ChromeOptions;
use Facebook\WebDriver\Remote\DesiredCapabilities;
use Facebook\WebDriver\Remote\RemoteWebDriver;

class DuskBrowser
{
    public static function createDriver()
    {
        $options = (new ChromeOptions)->addArguments([
            '--window-size=1920,1080',
            '--disable-search-engine-choice-screen',
            '--disable-blink-features=AutomationControlled',
            '--disable-dev-shm-usage',
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-infobars',
            '--disable-notifications',
            '--disable-popup-blocking',
            '--disable-save-password-bubble',
            '--disable-translate',
            '--disable-web-security',
            '--allow-running-insecure-content',
            '--disable-features=IsolateOrigins,site-per-process',
            '--disable-site-isolation-trials',
            '--disable-background-timer-throttling',
            '--disable-backgrounding-occluded-windows',
            '--disable-renderer-backgrounding',
            '--disable-component-update',
            '--disable-domain-reliability',
            '--disable-sync',
            '--user-agent=Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            '--disable-gpu',
            '--headless=new'
        ]);
        
        $options->setExperimentalOption('excludeSwitches', [
            'enable-automation',
            'enable-logging',
            'load-extension',
            'test-type'
        ]);
        
        $options->setExperimentalOption('useAutomationExtension', false);
        
        $options->setExperimentalOption('prefs', [
            'credentials_enable_service' => false,
            'profile.password_manager_enabled' => false,
            'profile.default_content_setting_values.notifications' => 2,
            'profile.default_content_setting_values.images' => 1,
            'profile.default_content_setting_values.cookies' => 1,
            'profile.default_content_setting_values.javascript' => 1,
            'profile.default_content_setting_values.plugins' => 1,
            'profile.default_content_setting_values.popups' => 2,
            'profile.default_content_setting_values.geolocation' => 2,
            'profile.default_content_setting_values.media_stream' => 2,
            'intl.accept_languages' => 'en-US,en;q=0.9',
            'webrtc.ip_handling_policy' => 'disable_non_proxied_udp',
            'webrtc.multiple_routes_enabled' => false,
            'webrtc.nonproxied_udp_enabled' => false,
            'enable_do_not_track' => true,
        ]);

        $capabilities = DesiredCapabilities::chrome();
        $capabilities->setCapability(ChromeOptions::CAPABILITY, $options);
        $capabilities->setCapability('pageLoadStrategy', 'normal');
        $capabilities->setCapability('acceptInsecureCerts', true);
        $capabilities->setCapability('unhandledPromptBehavior', 'ignore');
        $capabilities->setCapability('goog:loggingPrefs', [
            'performance' => 'ALL',
            'browser' => 'ALL',
        ]);

        return RemoteWebDriver::create(
            $_ENV['DUSK_DRIVER_URL'] ?? 'http://localhost:9515',
            $capabilities
        );
    }
}
