<?php

namespace App;

use GuzzleHttp\Client;

class ScraperService
{
    public function scrape($matricNumber)
    {
        try {
            // Initialize browser
            $driver = DuskBrowser::createDriver();
            
            // Execute stealth script before navigation
            $driver->executeScript(StealthHelper::getFullStealthScript());
            
            // Navigate to page
            $driver->get('https://uitm-schedule.live/timetable');
            
            // Inject mouse movement
            $driver->executeScript(StealthHelper::getMouseMovementScript());
            
            // Wait for page load
            $initialWait = rand(4000, 8000);
            usleep($initialWait * 1000);
            
            // Natural scrolling
            $this->performNaturalScrolling($driver);
            usleep(5500000); // 5.5 seconds
            
            // Extract reCAPTCHA site key
            $siteKey = $this->extractSiteKey($driver);
            
            if (!$siteKey) {
                throw new \Exception('Could not extract reCAPTCHA site key');
            }
            
            // Type matric number
            $this->typeMatricNumber($driver, $matricNumber);
            
            // Wait for typing completion
            $typingTime = (strlen($matricNumber) * 150) + rand(500, 2000);
            usleep($typingTime * 1000);
            
            // Additional human interactions
            usleep(3000000); // 3 seconds
            $driver->executeScript("
                setTimeout(() => {
                    document.activeElement.blur();
                    document.body.click();
                }, 500);
                
                setTimeout(() => {
                    window.dispatchEvent(new Event('blur'));
                    setTimeout(() => {
                        window.dispatchEvent(new Event('focus'));
                    }, 1000 + Math.random() * 2000);
                }, 2000);
            ");
            
            usleep(2000000); // 2 seconds
            
            // Generate reCAPTCHA token with retry
            $token = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $token = $driver->executeScript("
                    return new Promise((resolve) => {
                        if (typeof grecaptcha === 'undefined') {
                            resolve('');
                            return;
                        }
                        
                        grecaptcha.ready(async function() {
                            try {
                                const actions = ['submit', 'timetable_view', 'schedule_check', 'lookup'];
                                const action = actions[Math.floor(Math.random() * actions.length)];
                                
                                const token = await grecaptcha.execute('" . $siteKey . "', {
                                    action: action
                                });
                                
                                resolve(token);
                            } catch (error) {
                                resolve('');
                            }
                        });
                    });
                ");
                
                if ($token && strlen($token) > 100) {
                    break;
                }
                
                usleep(2000000); // Wait before retry
            }
            
            if (!$token || strlen($token) < 100) {
                throw new \Exception('Failed to generate reCAPTCHA token');
            }
            
            // Close browser
            $driver->quit();
            
            // Make direct API call with token
            $result = $this->makeDirectApiCall($matricNumber, $token);
            
            return $result;
            
        } catch (\Exception $e) {
            if (isset($driver)) {
                $driver->quit();
            }
            throw $e;
        }
    }
    
    private function performNaturalScrolling($driver)
    {
        $driver->executeScript("
            function naturalScroll(targetY, duration = 2000) {
                const startY = window.pageYOffset;
                const distance = targetY - startY;
                let startTime = null;
                
                function animation(currentTime) {
                    if (!startTime) startTime = currentTime;
                    const timeElapsed = currentTime - startTime;
                    const progress = Math.min(timeElapsed / duration, 1);
                    
                    const easeProgress = progress < 0.5 
                        ? 2 * progress * progress 
                        : 1 - Math.pow(-2 * progress + 2, 2) / 2;
                    
                    window.scrollTo(0, startY + (distance * easeProgress));
                    
                    if (timeElapsed < duration) {
                        requestAnimationFrame(animation);
                    }
                }
                
                requestAnimationFrame(animation);
            }
            
            setTimeout(() => naturalScroll(400, 1500), 500);
            setTimeout(() => naturalScroll(100, 1200), 2500);
            setTimeout(() => naturalScroll(0, 1000), 4500);
        ");
    }
    
    private function extractSiteKey($driver)
    {
        return $driver->executeScript("
            function extractSiteKey() {
                const recaptchaScripts = Array.from(document.scripts).filter(s => 
                    s.src.includes('recaptcha') || s.src.includes('google.com/recaptcha')
                );
                
                for (let script of recaptchaScripts) {
                    const match = script.src.match(/render=([^&]+)/);
                    if (match) return match[1];
                }
                
                const elementsWithSitekey = document.querySelectorAll('[data-sitekey], [g-recaptcha-response]');
                for (let el of elementsWithSitekey) {
                    if (el.dataset.sitekey) return el.dataset.sitekey;
                }
                
                const inlineScripts = document.querySelectorAll('script:not([src])');
                for (let script of inlineScripts) {
                    const matches = script.textContent.match(/6L[0-9A-Za-z_-]{10,}/g);
                    if (matches && matches.length > 0) {
                        return matches[0];
                    }
                }
                
                return '';
            }
            
            return extractSiteKey();
        ");
    }
    
    private function typeMatricNumber($driver, $matricNumber)
    {
        $driver->executeScript("
            function simulateHumanTyping(element, text, callback) {
                let index = 0;
                const typingSpeed = [80, 120, 150, 200, 250];
                
                function typeCharacter() {
                    if (index < text.length) {
                        const char = text.charAt(index);
                        
                        if (Math.random() < 0.05 && index > 0) {
                            element.value = element.value.slice(0, -1);
                            element.dispatchEvent(new Event('input', { bubbles: true }));
                            setTimeout(() => {
                                element.value += char;
                                element.dispatchEvent(new Event('input', { bubbles: true }));
                                index++;
                                setTimeout(typeCharacter, typingSpeed[Math.floor(Math.random() * typingSpeed.length)]);
                            }, 100 + Math.random() * 100);
                        } else {
                            element.value += char;
                            
                            const events = ['keydown', 'keypress', 'keyup', 'input'];
                            events.forEach(eventType => {
                                element.dispatchEvent(new KeyboardEvent(eventType, {
                                    key: char,
                                    bubbles: true
                                }));
                            });
                            
                            index++;
                            const delay = typingSpeed[Math.floor(Math.random() * typingSpeed.length)] + Math.random() * 50;
                            setTimeout(typeCharacter, delay);
                        }
                    } else {
                        element.dispatchEvent(new Event('change', { bubbles: true }));
                        if (callback) callback();
                    }
                }
                
                setTimeout(() => {
                    element.focus();
                    element.click();
                    setTimeout(() => {
                        typeCharacter();
                    }, 200 + Math.random() * 300);
                }, 500);
            }
            
            const input = document.querySelector('input[name=\"matric_number\"]') || 
                         document.querySelector('#matric-input') || 
                         document.querySelector('input[type=\"text\"]');
            
            if (input) {
                input.value = ''; // Clear first
                simulateHumanTyping(input, '" . $matricNumber . "', function() {
                    console.log('Typing completed');
                });
            }
        ");
    }
    
    private function makeDirectApiCall($matricNumber, $token)
    {
        $client = new Client([
            'verify' => false,
            'timeout' => 30,
            'decode_content' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'application/json, text/plain, */*',
                'Accept-Language' => 'en-US,en;q=0.9,ms;q=0.8',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Referer' => 'https://uitm-schedule.live/timetable',
                'Origin' => 'https://uitm-schedule.live',
                'Sec-Fetch-Dest' => 'empty',
                'Sec-Fetch-Mode' => 'cors',
                'Sec-Fetch-Site' => 'same-origin',
            ]
        ]);
        
        usleep(rand(100000, 500000)); // Random delay
        
        $response = $client->post('https://uitm-schedule.live/api/fetch_schedule', [
            'form_params' => [
                'matric' => $matricNumber,
                'g-recaptcha-response' => $token
            ]
        ]);
        
        $data = json_decode($response->getBody(), true);
        
        if (!isset($data['success']) || $data['success'] !== true) {
            throw new \Exception('API returned error: ' . ($data['message'] ?? 'Unknown error'));
        }
        
        // Format response
        $classes = [];
        foreach ($data['data'] as $date => $dayData) {
            if ($dayData === null || !is_array($dayData)) {
                continue;
            }
            
            $dayName = $dayData['hari'] ?? 'Unknown';
            $jadual = $dayData['jadual'] ?? [];
            
            foreach ($jadual as $class) {
                $classes[] = [
                    'code' => $class['courseid'] ?? 'N/A',
                    'day' => $dayName,
                    'time' => $class['masa'] ?? 'N/A',
                    'course' => $class['course_desc'] ?? 'N/A',
                    'room' => $class['bilik'] ?? 'N/A',
                    'lecturer' => $class['lecturer'] ?? 'N/A',
                    'group' => $class['groups'] ?? 'N/A'
                ];
            }
        }
        
        return [
            'success' => true,
            'data' => $classes,
            'count' => count($classes)
        ];
    }
}
