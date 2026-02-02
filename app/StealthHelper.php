<?php

namespace App\Helpers;

class StealthHelper
{
    public static function getFullStealthScript(): string
    {
        return "
            // ==================== WEBDRIVER OVERRIDES ====================
            Object.defineProperty(navigator, 'webdriver', {
                get: () => false,
                configurable: true
            });
            
            // Remove webdriver from navigator prototype
            delete navigator.__proto__.webdriver;
            
            // ==================== PLUGINS & MIME TYPES ====================
            const originalPlugins = navigator.plugins;
            Object.defineProperty(navigator, 'plugins', {
                get: () => {
                    const plugins = new Array(5).fill().map((_, i) => ({
                        description: 'Portable Document Format',
                        filename: 'internal-pdf-viewer',
                        length: 1,
                        name: 'PDF Viewer'
                    }));
                    
                    plugins.__proto__ = PluginArray.prototype;
                    return plugins;
                }
            });
            
            Object.defineProperty(navigator, 'mimeTypes', {
                get: () => {
                    const mimeTypes = new Array(5).fill().map((_, i) => ({
                        type: 'application/pdf',
                        suffixes: 'pdf',
                        description: 'Portable Document Format',
                        enabledPlugin: {
                            description: 'Portable Document Format',
                            filename: 'internal-pdf-viewer',
                            name: 'PDF Viewer',
                            length: 1
                        }
                    }));
                    
                    mimeTypes.__proto__ = MimeTypeArray.prototype;
                    return mimeTypes;
                }
            });
            
            // ==================== LANGUAGES & PLATFORM ====================
            Object.defineProperty(navigator, 'languages', {
                get: () => ['en-US', 'en', 'ms-MY', 'ms']
            });
            
            Object.defineProperty(navigator, 'language', {
                get: () => 'en-US'
            });
            
            const originalPlatform = navigator.platform;
            Object.defineProperty(navigator, 'platform', {
                get: () => 'Win32',
                configurable: true
            });
            
            // ==================== CHROME OVERRIDES ====================
            window.chrome = {
                runtime: {
                    connect: function() {},
                    sendMessage: function() {},
                    onConnect: {
                        addListener: function() {}
                    },
                    onMessage: {
                        addListener: function() {}
                    },
                    getManifest: function() {
                        return {};
                    }
                },
                loadTimes: function() {
                    return {
                        requestTime: 0,
                        startLoadTime: 0,
                        commitLoadTime: 0,
                        finishDocumentLoadTime: 0,
                        finishLoadTime: 0,
                        firstPaintTime: 0,
                        firstPaintAfterLoadTime: 0,
                        navigationType: 'Reload',
                        wasFetchedViaSpdy: false,
                        wasNpnNegotiated: true,
                        npnNegotiatedProtocol: 'h2',
                        wasAlternateProtocolAvailable: false,
                        connectionInfo: 'h2'
                    };
                },
                csi: function() {
                    return {
                        onloadT: Date.now(),
                        startE: Date.now() - 1000,
                        pageT: 1500,
                        tran: 15
                    };
                },
                app: {
                    isInstalled: false,
                    getDetails: function() { return null; }
                }
            };
            
            // ==================== PERMISSIONS OVERRIDE ====================
            const originalPermissionsQuery = navigator.permissions.query;
            navigator.permissions.query = function(parameters) {
                if (parameters.name === 'notifications') {
                    return Promise.resolve({ state: 'denied' });
                }
                if (parameters.name === 'geolocation') {
                    return Promise.resolve({ state: 'prompt' });
                }
                return originalPermissionsQuery.call(navigator, parameters);
            };
            
            // ==================== HARDWARE OVERRIDES ====================
            Object.defineProperty(navigator, 'hardwareConcurrency', {
                get: () => 8
            });
            
            Object.defineProperty(navigator, 'deviceMemory', {
                get: () => 8
            });
            
            // ==================== CANVAS FINGERPRINT SPOOF ====================
            const originalGetContext = HTMLCanvasElement.prototype.getContext;
            HTMLCanvasElement.prototype.getContext = function(contextType, contextAttributes) {
                const context = originalGetContext.call(this, contextType, contextAttributes);
                
                if (contextType === '2d') {
                    const originalFillText = context.fillText;
                    context.fillText = function(...args) {
                        if (typeof args[0] === 'string' && args[0].includes('Chrome')) {
                            args[0] = args[0].replace('Chrome', 'Chrome 120');
                        }
                        return originalFillText.apply(this, args);
                    };
                }
                
                if (contextType === 'webgl' || contextType === 'webgl2') {
                    const originalGetParameter = context.getParameter;
                    context.getParameter = function(parameter) {
                        // Override WebGL vendor/renderer
                        if (parameter === 37445) { // UNMASKED_VENDOR_WEBGL
                            return 'Intel Inc.';
                        }
                        if (parameter === 37446) { // UNMASKED_RENDERER_WEBGL
                            return 'Intel Iris OpenGL Engine';
                        }
                        return originalGetParameter.call(this, parameter);
                    };
                }
                
                return context;
            };
            
            // ==================== AUDIO CONTEXT SPOOF ====================
            if (typeof window.AudioContext !== 'undefined') {
                const originalCreateOscillator = window.AudioContext.prototype.createOscillator;
                window.AudioContext.prototype.createOscillator = function() {
                    const oscillator = originalCreateOscillator.call(this);
                    const originalStart = oscillator.start;
                    oscillator.start = function(...args) {
                        // Add slight variation to audio fingerprint
                        setTimeout(() => {
                            originalStart.apply(this, args);
                        }, Math.random() * 10);
                        return undefined;
                    };
                    return oscillator;
                };
            }
            
            // ==================== TIMEZONE & LOCALE ====================
            const originalTimezoneOffset = Date.prototype.getTimezoneOffset;
            Date.prototype.getTimezoneOffset = function() {
                return -480; // Malaysia timezone (UTC+8)
            };
            
            // ==================== WEBRTC BLOCKING ====================
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                const originalGetUserMedia = navigator.mediaDevices.getUserMedia;
                navigator.mediaDevices.getUserMedia = function(constraints) {
                    return Promise.reject(new Error('Permission denied'));
                };
            }
            
            // Block RTCPeerConnection
            if (typeof window.RTCPeerConnection !== 'undefined') {
                const originalRTCPeerConnection = window.RTCPeerConnection;
                window.RTCPeerConnection = function() {
                    throw new Error('RTCPeerConnection is not allowed');
                };
                window.RTCPeerConnection.prototype = originalRTCPeerConnection.prototype;
            }
            
            // ==================== CONSOLE LOG OVERRIDE ====================
            const originalConsoleLog = console.log;
            console.log = function(...args) {
                // Don't log automation-related messages
                if (typeof args[0] === 'string' && (
                    args[0].includes('webdriver') || 
                    args[0].includes('automation') ||
                    args[0].includes('driver')
                )) {
                    return;
                }
                originalConsoleLog.apply(console, args);
            };
            
            // ==================== PROPERTY DESCRIPTOR ====================
            Object.getOwnPropertyDescriptor_original = Object.getOwnPropertyDescriptor;
            Object.getOwnPropertyDescriptor = function(object, property) {
                if (object === navigator && property === 'webdriver') {
                    return undefined;
                }
                return Object.getOwnPropertyDescriptor_original(object, property);
            };
            
            console.log('Stealth mode fully activated');
        ";
    }
    
    public static function getMouseMovementScript(): string
    {
        return "
            // Generate realistic mouse movements
            function generateMousePath(startX, startY, endX, endY, steps = 50) {
                const path = [];
                const controlX = startX + (endX - startX) * 0.5 + (Math.random() * 100 - 50);
                const controlY = startY + (endY - startY) * 0.5 + (Math.random() * 100 - 50);
                
                for (let i = 0; i <= steps; i++) {
                    const t = i / steps;
                    // Cubic bezier curve for natural mouse movement
                    const x = Math.pow(1 - t, 3) * startX + 
                              3 * Math.pow(1 - t, 2) * t * controlX + 
                              3 * (1 - t) * Math.pow(t, 2) * controlX + 
                              Math.pow(t, 3) * endX;
                    
                    const y = Math.pow(1 - t, 3) * startY + 
                              3 * Math.pow(1 - t, 2) * t * controlY + 
                              3 * (1 - t) * Math.pow(t, 2) * controlY + 
                              Math.pow(t, 3) * endY;
                    
                    path.push({x: Math.round(x), y: Math.round(y)});
                }
                return path;
            }
            
            // Move mouse along natural path
            function moveMouseNatural() {
                const startX = Math.random() * window.innerWidth;
                const startY = Math.random() * window.innerHeight;
                const endX = Math.random() * window.innerWidth;
                const endY = Math.random() * window.innerHeight;
                
                const path = generateMousePath(startX, startY, endX, endY);
                
                path.forEach((point, index) => {
                    setTimeout(() => {
                        const event = new MouseEvent('mousemove', {
                            clientX: point.x,
                            clientY: point.y,
                            bubbles: true
                        });
                        document.dispatchEvent(event);
                    }, index * 10 + Math.random() * 5);
                });
            }
            
            // Start random mouse movements
            setInterval(moveMouseNatural, 3000 + Math.random() * 7000);
            
            // Initial movement
            setTimeout(moveMouseNatural, 1000);
        ";
    }
}
