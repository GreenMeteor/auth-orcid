<?php

namespace humhub\modules\auth\orcid\authclient;

use Yii;
use yii\authclient\OAuth2;
use humhub\modules\user\models\User;
use humhub\modules\user\models\ProfileField;
use humhub\modules\auth\orcid\models\ConfigureForm;

/**
 * ORCID authentication client for HumHub
 *
 * @see https://info.orcid.org/documentation/integration-guide/
 */
class OrcidClient extends OAuth2
{
    /**
     * @inheritdoc
     */
    public $authUrl;

    /**
     * @inheritdoc
     */
    public $tokenUrl;

    /**
     * @inheritdoc
     */
    public $apiBaseUrl;

    /**
     * @inheritdoc
     */
    public $revokeUrl;

    /**
     * @inheritdoc
     */
    public $scope;


    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        $config = ConfigureForm::getInstance();

        if ($config->enabled) {
            $this->authUrl = 'https://orcid.org/oauth/authorize';
            $this->tokenUrl = 'https://orcid.org/oauth/token';
            $this->apiBaseUrl = 'https://pub.orcid.org/v3.0';
            $this->scope = $config->scopes;
            $this->clientId = $config->clientId;
            $this->clientSecret = $config->clientSecret;
            $this->orcidAttribute = $config->orcidAttribute;
            $this->autoRefreshAccessToken = true;
        }
    }

    /**
     * @inheritdoc
     */
    protected function defaultName()
    {
        return 'orcid';
    }

    /**
     * @inheritdoc
     */
    protected function defaultTitle()
    {
        return 'ORCID';
    }

    /**
     * @inheritdoc
     */
    protected function defaultViewOptions()
    {
        return [
            'popupWidth' => 800,
            'popupHeight' => 500,
            'cssIcon' => 'fa fa-id-card',
            'buttonBackgroundColor' => '#A6CE39',
        ];
    }

    /**
     * @inheritdoc
     */
    protected function initUserAttributes()
    {
        $accessToken = $this->getAccessToken();
        if (isset($accessToken->params['orcid'])) {
            $orcid = $accessToken->params['orcid'];

            $response = $this->api("/$orcid/record", 'GET');

            $attributes = [
                'id' => $orcid,
                'email' => null,
                'name' => null,
                'given_name' => null,
                'family_name' => null,
            ];

            if (isset($response['person'])) {
                $person = $response['person'];

                if (isset($person['emails']['email'])) {
                    foreach ($person['emails']['email'] as $email) {
                        if (isset($email['email']) && isset($email['primary']) && $email['primary'] === true) {
                            $attributes['email'] = $email['email'];
                            break;
                        }
                    }

                    if ($attributes['email'] === null && !empty($person['emails']['email'][0]['email'])) {
                        $attributes['email'] = $person['emails']['email'][0]['email'];
                    }
                }

                if (isset($person['name'])) {
                    $name = $person['name'];

                    if (isset($name['given-names']['value'])) {
                        $attributes['given_name'] = $name['given-names']['value'];
                    }

                    if (isset($name['family-name']['value'])) {
                        $attributes['family_name'] = $name['family-name']['value'];
                    }

                    $fullName = [];
                    if (!empty($attributes['given_name'])) {
                        $fullName[] = $attributes['given_name'];
                    }
                    if (!empty($attributes['family_name'])) {
                        $fullName[] = $attributes['family_name'];
                    }

                    if (!empty($fullName)) {
                        $attributes['name'] = implode(' ', $fullName);
                    }
                }
            }

            return $attributes;
        }

        return [];
    }

    /**
     * @inheritdoc
     */
    public function applyAccessTokenToRequest($request, $accessToken)
    {
        $request->getHeaders()->set('Authorization', 'Bearer ' . $accessToken->getToken());
    }

    /**
     * @inheritdoc
     */
    protected function defaultNormalizeUserAttributeMap()
    {
        return [
            'id' => 'id',
            'username' => 'id',
            'email' => 'email',
            'firstname' => 'given_name',
            'lastname' => 'family_name',
        ];
    }

    /**
     * Store ORCID ID in user profile
     *
     * @param User $user
     * @param array $attributes
     */
    public function afterSave($user, $attributes)
    {
        parent::afterSave($user, $attributes);

        if (!empty($attributes['id'])) {
            $fieldName = $this->orcidAttribute
            $orcidField = ProfileField::findOne(['internal_name' => $this->$fieldName]);

            if ($orcidField !== null) {
                $profile = $user->profile;

                if ($profile->hasAttribute($fieldName)) {
                    $profile->$fieldName = $attributes['id'];
                    $profile->save();
                }
            }

            if ($orcidField === null) {
            //This should never happen! (unless config was modified out of the system)
                Yii::$app->session->setFlash('info', Yii::t('AuthModule.base', 
                    'User logged in with ORCID {orcid} - consider creating an ORCID profile field.', 
                    ['orcid' => $attributes['id']]
                ));
            }
        }
    }

    /**
     * Get or create the profile field category ID for ORCID
     * 
     * @return int|null
     */
    private static function getProfileFieldCategoryId()
    {
        $category = \humhub\modules\user\models\ProfileFieldCategory::findOne(['title' => 'Academic']);

        if ($category === null) {
            $maxOrder = \humhub\modules\user\models\ProfileFieldCategory::find()->max('sort_order');
            $category = new \humhub\modules\user\models\ProfileFieldCategory();
            $category->title = 'Academic';
            $category->description = 'Academic information';
            $category->sort_order = $maxOrder + 1;
            $category->visibility = 1;
            $category->save();
        }

        return $category !== null ? $category->id : null;
    }
}
