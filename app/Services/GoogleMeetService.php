<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class GoogleMeetService
{
    private $client;
    private $calendar;

    public function __construct()
    {
        $this->initializeClient();
    }

    /**
     * Initialize Google Client
     */
    private function initializeClient()
    {
        try {
            $credentialsPath = config('services.google.credentials_path');
            
            // Check if credentials file exists
            if (!file_exists($credentialsPath)) {
                throw new Exception('Google credentials file not found at: ' . $credentialsPath);
            }

            $this->client = new Client();
            $this->client->setApplicationName(config('services.google.application_name', 'PaperTrail MS'));
            $this->client->setScopes([Calendar::CALENDAR]);
            
            // Validate credentials file format
            $credentialsContent = json_decode(file_get_contents($credentialsPath), true);
            if (!$credentialsContent) {
                throw new Exception('Invalid Google credentials file format. Please ensure it\'s valid JSON.');
            }
            
            // Check if it's a web application credentials file
            if (!isset($credentialsContent['web']) && !isset($credentialsContent['installed'])) {
                throw new Exception('Google credentials file must be for a web application or installed application. Please download the correct OAuth 2.0 Client ID credentials from Google Cloud Console.');
            }
            
            $this->client->setAuthConfig($credentialsPath);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('select_account consent');
            
            // Set redirect URI for OAuth flow
            $redirectUri = config('services.google.redirect_uri');
            if ($redirectUri) {
                $this->client->setRedirectUri($redirectUri);
            }

            // Load stored access token
            $this->loadToken();

            $this->calendar = new Calendar($this->client);
        } catch (Exception $e) {
            Log::error('Failed to initialize Google Client: ' . $e->getMessage());
            throw new Exception('Google Meet integration is not properly configured: ' . $e->getMessage());
        }
    }

    /**
     * Create a Google Calendar event with Meet link
     */
    public function createMeetingEvent($title, $description, $startTime, $endTime, $attendees = [])
    {
        try {
            $event = new Event([
                'summary' => $title,
                'description' => $description,
                'start' => new EventDateTime([
                    'dateTime' => Carbon::parse($startTime)->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'UTC'),
                ]),
                'end' => new EventDateTime([
                    'dateTime' => Carbon::parse($endTime)->toRfc3339String(),
                    'timeZone' => config('app.timezone', 'UTC'),
                ]),
                'conferenceData' => new ConferenceData([
                    'createRequest' => new CreateConferenceRequest([
                        'requestId' => uniqid(),
                        'conferenceSolutionKey' => new ConferenceSolutionKey([
                            'type' => 'hangoutsMeet'
                        ])
                    ])
                ]),
                'attendees' => $this->formatAttendees($attendees),
            ]);

            $calendarId = config('services.google.calendar_id', 'primary');
            $createdEvent = $this->calendar->events->insert($calendarId, $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all'
            ]);

            return [
                'event_id' => $createdEvent->getId(),
                'meet_link' => $createdEvent->getConferenceData()->getEntryPoints()[0]->getUri(),
                'calendar_link' => $createdEvent->getHtmlLink(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to create Google Meet event: ' . $e->getMessage());
            throw new Exception('Failed to create Google Meet event: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing Google Calendar event
     */
    public function updateMeetingEvent($eventId, $title, $description, $startTime, $endTime, $attendees = [])
    {
        try {
            $calendarId = config('services.google.calendar_id', 'primary');
            $event = $this->calendar->events->get($calendarId, $eventId);

            $event->setSummary($title);
            $event->setDescription($description);
            $event->setStart(new EventDateTime([
                'dateTime' => Carbon::parse($startTime)->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ]));
            $event->setEnd(new EventDateTime([
                'dateTime' => Carbon::parse($endTime)->toRfc3339String(),
                'timeZone' => config('app.timezone', 'UTC'),
            ]));
            $event->setAttendees($this->formatAttendees($attendees));

            $updatedEvent = $this->calendar->events->update($calendarId, $eventId, $event, [
                'sendUpdates' => 'all'
            ]);

            return [
                'event_id' => $updatedEvent->getId(),
                'meet_link' => $updatedEvent->getConferenceData()->getEntryPoints()[0]->getUri(),
                'calendar_link' => $updatedEvent->getHtmlLink(),
            ];

        } catch (Exception $e) {
            Log::error('Failed to update Google Meet event: ' . $e->getMessage());
            throw new Exception('Failed to update Google Meet event: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Google Calendar event
     */
    public function deleteMeetingEvent($eventId)
    {
        try {
            $calendarId = config('services.google.calendar_id', 'primary');
            $this->calendar->events->delete($calendarId, $eventId, [
                'sendUpdates' => 'all'
            ]);
            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete Google Meet event: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Format attendees for Google Calendar
     */
    private function formatAttendees($attendees)
    {
        $formattedAttendees = [];
        foreach ($attendees as $attendee) {
            if (is_string($attendee)) {
                $formattedAttendees[] = ['email' => $attendee];
            } elseif (is_array($attendee) && isset($attendee['email'])) {
                $formattedAttendees[] = $attendee;
            }
        }
        return $formattedAttendees;
    }

    /**
     * Check if Google Meet integration is properly configured
     */
    public function isConfigured()
    {
        return config('services.google.credentials_path') && 
               file_exists(config('services.google.credentials_path'));
    }

    /**
     * Get authorization URL for OAuth setup
     */
    public function getAuthUrl()
    {
        // Ensure redirect URI is set
        $redirectUri = config('services.google.redirect_uri');
        if (!$redirectUri) {
            throw new Exception('Google redirect URI is not configured. Please set GOOGLE_REDIRECT_URI in your .env file.');
        }
        
        // Force set the redirect URI to ensure it's correct
        $this->client->setRedirectUri($redirectUri);
        
        // Verify what redirect URI the client is actually using
        Log::info('Google OAuth redirect URI from config: ' . $redirectUri);
        Log::info('Google Client redirect URI: ' . $this->client->getRedirectUri());
        
        $authUrl = $this->client->createAuthUrl();
        Log::info('Google OAuth auth URL: ' . $authUrl);
        
        return $authUrl;
    }

    /**
     * Handle OAuth callback and store tokens
     */
    public function handleCallback($code)
    {
        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($token['error'])) {
                throw new Exception('Error fetching access token: ' . $token['error']);
            }

            // Store the token (you might want to store this in database or cache)
            $tokenPath = storage_path('app/google_token.json');
            file_put_contents($tokenPath, json_encode($token));

            return true;
        } catch (Exception $e) {
            Log::error('Failed to handle Google OAuth callback: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Load stored access token
     */
    private function loadToken()
    {
        $tokenPath = storage_path('app/google_token.json');
        if (file_exists($tokenPath)) {
            $token = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($token);

            // Refresh token if expired
            if ($this->client->isAccessTokenExpired()) {
                if ($this->client->getRefreshToken()) {
                    $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                    file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
                }
            }
        } else {
            // Token file doesn't exist - OAuth setup required
            Log::warning('Google OAuth token not found. Please complete the OAuth setup first.');
        }
    }

    /**
     * Check if OAuth token exists and is valid
     */
    public function hasValidToken()
    {
        $tokenPath = storage_path('app/google_token.json');
        if (!file_exists($tokenPath)) {
            return false;
        }

        try {
            $token = json_decode(file_get_contents($tokenPath), true);
            $this->client->setAccessToken($token);
            
            // Check if token is expired and can't be refreshed
            if ($this->client->isAccessTokenExpired() && !$this->client->getRefreshToken()) {
                return false;
            }
            
            return true;
        } catch (Exception $e) {
            Log::error('Invalid Google OAuth token: ' . $e->getMessage());
            return false;
        }
    }
}
