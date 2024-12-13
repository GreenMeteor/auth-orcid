<?php

namespace humhub\modules\auth\orcid\authclient;

use Yii;
use yii\helpers\Json;
use yii\authclient\OAuth2;
use yii\authclient\OAuthToken;

class OrcidClient extends OAuth2
{
    public $authUrl = 'https://orcid.org/oauth/authorize';
    public $tokenUrl = 'https://orcid.org/oauth/token';
    public $apiBaseUrl = 'https://api.orcid.org/v3.0';
    public $scope = '/authenticate';

    public function init()
    {
        parent::init();
        $this->initScope();
    }

    protected function initScope()
    {
        $scope = ['/authenticate'];
        if ($this->isFeatureEnabled('enableProfileSync')) {
            $scope[] = '/read-limited';
        }
        if ($this->isFeatureEnabled('enableWorksFetch') || $this->isFeatureEnabled('enableWorksUpdate')) {
            $scope[] = '/activities/update';
        }
        $this->scope = implode(' ', $scope);
    }

    protected function initUserAttributes()
    {
        if ($this->isFeatureEnabled('enableProfileSync')) {
            $person = $this->api($this->getOrcid() . '/person', 'GET');
            $education = $this->fetchEducation();
            $employment = $this->fetchEmployment();
            
            return [
                'orcid' => $this->getOrcid(),
                'name' => $this->extractName($person),
                'email' => $this->extractEmail($person),
                'biography' => $this->extractBiography($person),
                'country' => $this->extractCountry($person),
                'keywords' => $this->extractKeywords($person),
                'website' => $this->extractWebsite($person),
                'education' => $education,
                'employment' => $employment,
            ];
        }
        return [];
    }

    protected function extractName($person)
    {
        $name = $person['name'] ?? [];
        return [
            'given-names' => $name['given-names']['value'] ?? null,
            'family-name' => $name['family-name']['value'] ?? null,
        ];
    }

    protected function extractEmail($person)
    {
        $emails = $person['emails']['email'] ?? [];
        return array_map(function($email) {
            return $email['email'];
        }, $emails);
    }

    protected function extractBiography($person)
    {
        return $person['biography']['content'] ?? null;
    }

    protected function extractCountry($person)
    {
        return $person['addresses']['address'][0]['country']['value'] ?? null;
    }

    protected function extractKeywords($person)
    {
        $keywords = $person['keywords']['keyword'] ?? [];
        return array_map(function($keyword) {
            return $keyword['content'];
        }, $keywords);
    }

    protected function extractWebsite($person)
    {
        $urls = $person['researcher-urls']['researcher-url'] ?? [];
        return array_map(function($url) {
            return [
                'name' => $url['url-name'],
                'value' => $url['url']['value'],
            ];
        }, $urls);
    }

    protected function defaultName()
    {
        return 'orcid';
    }

    protected function defaultTitle()
    {
        return 'ORCID';
    }

    protected function defaultViewOptions()
    {
        return [
            'popupWidth' => 860,
            'popupHeight' => 480,
        ];
    }

    public function getOrcid()
    {
        return $this->getAccessToken()->getParam('orcid');
    }

    protected function apiInternal($accessToken, $url, $method, array $params, array $headers)
    {
        $headers['Accept'] = 'application/vnd.orcid+json';
        if ($method === 'POST' || $method === 'PUT') {
            $headers['Content-Type'] = 'application/vnd.orcid+json';
        }
        return parent::apiInternal($accessToken, $url, $method, $params, $headers);
    }

    public function fetchWorks()
    {
        if ($this->isFeatureEnabled('enableWorksFetch')) {
            return $this->api($this->getOrcid() . '/works', 'GET');
        }
        return null;
    }

    public function updateWork($workData)
    {
        if ($this->isFeatureEnabled('enableWorksUpdate')) {
            return $this->api($this->getOrcid() . '/work', 'POST', Json::encode($workData));
        }
        return null;
    }

    public function fetchEducation()
    {
        if ($this->isFeatureEnabled('enableProfileSync')) {
            $educations = $this->api($this->getOrcid() . '/educations', 'GET');
            return $this->formatAffiliations($educations['education-summary']);
        }
        return null;
    }

    public function fetchEmployment()
    {
        if ($this->isFeatureEnabled('enableProfileSync')) {
            $employments = $this->api($this->getOrcid() . '/employments', 'GET');
            return $this->formatAffiliations($employments['employment-summary']);
        }
        return null;
    }

    protected function formatAffiliations($affiliations)
    {
        return array_map(function($affiliation) {
            return [
                'organization' => $affiliation['organization']['name'],
                'department' => $affiliation['department-name'],
                'title' => $affiliation['role-title'],
                'start-date' => $this->formatDate($affiliation['start-date']),
                'end-date' => $this->formatDate($affiliation['end-date']),
            ];
        }, $affiliations);
    }

    protected function formatDate($date)
    {
        if (!$date) return null;
        return sprintf('%04d-%02d-%02d', $date['year']['value'], $date['month']['value'], $date['day']['value']);
    }

    protected function defaultReturnUrl()
    {
        return Yii::$app->getUrlManager()->createAbsoluteUrl(['/auth/orcid/auth/index']);
    }

    protected function isFeatureEnabled($feature)
    {
        return Yii::$app->settings->get("auth.orcid.{$feature}", true);
    }
}