<?php

namespace humhub\modules\auth\orcid\controllers;

use Yii;
use humhub\modules\user\models\User;
use humhub\modules\user\models\Password;
use humhub\modules\user\models\Profile;
use humhub\modules\user\authclient\AuthAction;
use humhub\components\Controller;
use yii\web\NotFoundHttpException;

class AuthController extends Controller
{
    public function actions()
    {
        return [
            'auth' => [
                'class' => AuthAction::class,
                'successCallback' => [$this, 'onAuthSuccess'],
            ],
        ];
    }

    public function onAuthSuccess($client)
    {
        $attributes = $client->getUserAttributes();

        $user = $this->createOrUpdateUser($client);

        if ($user) {
            Yii::$app->user->login($user, $client->authTimeout);
            $this->redirect(['/user/profile']);
        } else {
            Yii::$app->session->setFlash('error', 'Unable to authenticate with ORCID.');
            $this->redirect(['/user/auth/login']);
        }
    }

    protected function createOrUpdateUser($client)
    {
        $attributes = $client->getUserAttributes();
        $email = $client->getEmail();
        $user = User::findOne(['email' => $email]);

        if (!$user) {
            $user = new User();
            $user->scenario = 'registration';
            $user->status = User::STATUS_ENABLED;
            $user->email = $email;
            $user->username = $client->getUsername();

            if (!$user->save()) {
                Yii::error('Could not create user: ' . print_r($user->getErrors(), true));
                return null;
            }

            $profile = new Profile();
            $profile->user_id = $user->id;
            $profile->firstname = $attributes['name']['given-names']['value'] ?? '';
            $profile->lastname = $attributes['name']['family-name']['value'] ?? '';
            $profile->save();

            $password = new Password();
            $password->user_id = $user->id;
            $password->setPassword(Yii::$app->security->generateRandomString(16));
            $password->save();
        }

        $auth = $user->getAuth('orcid');
        if ($auth === null) {
            $auth = new Auth([
                'user_id' => $user->id,
                'source' => 'orcid',
                'source_id' => $client->getUserId(),
            ]);
            $auth->save();
        }

        return $user;
    }

    public function actionFetchWorks()
    {
        $client = Yii::$app->authClientCollection->getClient('orcid');
        try {
            $works = $client->fetchWorks();
            return $this->asJson($works);
        } catch (\Exception $e) {
            Yii::error('Error fetching works: ' . $e->getMessage());
            return $this->asJson(['error' => 'Unable to fetch works']);
        }
    }

    public function actionUpdateWork()
    {
        $client = Yii::$app->authClientCollection->getClient('orcid');
        $workData = Yii::$app->request->post();
        try {
            $result = $client->updateWork($workData);
            return $this->asJson($result);
        } catch (\Exception $e) {
            Yii::error('Error updating work: ' . $e->getMessage());
            return $this->asJson(['error' => 'Unable to update work']);
        }
    }
}