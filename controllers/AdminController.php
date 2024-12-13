<?php

namespace humhub\modules\auth\orcid\controllers;

use Yii;
use humhub\modules\admin\components\Controller;
use humhub\modules\auth\orcid\models\ConfigureForm;

class AdminController extends Controller
{
    public function actionIndex()
    {
        $model = ConfigureForm::getInstance();

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->saveSettings()) {
            $this->view->saved();
        }

        return $this->render('index', ['model' => $model]);
    }
}