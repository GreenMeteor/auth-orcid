<?php

namespace humhub\modules\auth\orcid\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

class ConfigureForm extends Model
{
    public $enabled;
    public $clientId;
    public $clientSecret;
    public $enableAuthentication;
    public $enableProfileSync;
    public $enableWorksFetch;
    public $enableWorksUpdate;
    public $enableEducationSync;
    public $enableEmploymentSync;
    public $scopes = '/authenticate';
    public $redirectUri;

    public function rules()
    {
        return [
            //[['clientId', 'clientSecret'], 'required'],
            [['clientId', 'clientSecret', 'scopes'], 'string'],
            [['enabled', 'enableAuthentication', 'enableProfileSync', 'enableWorksFetch', 'enableWorksUpdate', 'enableEducationSync', 'enableEmploymentSync'], 'boolean'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'enabled' => Yii::t('AuthOrcidModule.base', 'Enabled'),
            'clientId' => Yii::t('AuthOrcidModule.base', 'ORCID Client ID'),
            'clientSecret' => Yii::t('AuthOrcidModule.base', 'ORCID Client Secret'),
            'enableAuthentication' => Yii::t('AuthOrcidModule.base', 'Enable ORCID Authentication'),
            'enableProfileSync' => Yii::t('AuthOrcidModule.base', 'Enable Profile Synchronization'),
            'enableWorksFetch' => Yii::t('AuthOrcidModule.base', 'Enable Fetching Works'),
            'enableWorksUpdate' => Yii::t('AuthOrcidModule.base', 'Enable Updating Works'),
            'enableEducationSync' => Yii::t('AuthOrcidModule.base', 'Enable Education Synchronization'),
            'enableEmploymentSync' => Yii::t('AuthOrcidModule.base', 'Enable Employment Synchronization'),
            'scopes' => Yii::t('AuthOrcidModule.base', 'ORCID API Scopes'),
        ];
    }

    public function saveSettings()
    {
        $settings = Yii::$app->getModule('auth-orcid')->settings;

        $settings->set('enabled', (boolean)$this->enabled);
        $settings->set('clientId', $this->clientId);
        $settings->set('clientSecret', $this->clientSecret);
        $settings->set('enableAuthentication', (boolean)$this->enableAuthentication);
        $settings->set('enableProfileSync', (boolean)$this->enableProfileSync);
        $settings->set('enableWorksFetch', (boolean)$this->enableWorksFetch);
        $settings->set('enableWorksUpdate', (boolean)$this->enableWorksUpdate);
        $settings->set('enableEducationSync', (boolean)$this->enableEducationSync);
        $settings->set('enableEmploymentSync', (boolean)$this->enableEmploymentSync);
        $settings->set('scopes', $this->scopes);
    }

    public function loadSettings()
    {
        $settings = Yii::$app->getModule('auth-orcid')->settings;

        $this->enabled = (boolean)$settings->get('enabled');
        $this->clientId = $settings->get('clientId');
        $this->clientSecret = $settings->get('clientSecret');
        $this->enableAuthentication = (boolean)$settings->get('enableAuthentication');
        $this->enableProfileSync = (boolean)$settings->get('enableProfileSync');
        $this->enableWorksFetch = (boolean)$settings->get('enableWorksFetch');
        $this->enableWorksUpdate = (boolean)$settings->get('enableWorksUpdate');
        $this->enableEducationSync = (boolean)$settings->get('enableEducationSync');
        $this->enableEmploymentSync = (boolean)$settings->get('enableEmploymentSync');
        $this->scopes = $settings->get('scopes', '/authenticate');

        $this->redirectUri = Url::to(['/user/auth/external', 'authclient' => 'orcid'], true);
    }

    public function getEnabledScopes()
    {
        $scopes = ['/authenticate'];
        if ($this->enableProfileSync) {
            $scopes[] = '/read-limited';
        }
        if ($this->enableWorksFetch || $this->enableWorksUpdate) {
            $scopes[] = '/activities/update';
        }
        if ($this->enableEducationSync || $this->enableEmploymentSync) {
            $scopes[] = '/person/update';
        }
        return implode(' ', $scopes);
    }

    public static function getInstance()
    {
        $config = new static;
        $config->loadSettings();

        return $config;
    }
}