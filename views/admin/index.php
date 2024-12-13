<?php

use yii\helpers\Url;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model humhub\modules\auth\orcid\models\ConfigureForm */

$this->title = Yii::t('AuthOrcidModule.base', 'ORCID Authentication Settings');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="panel panel-default">
    <div class="panel-heading"><?= Html::encode($this->title) ?></div>
    <div class="panel-body">
        <?php $form = ActiveForm::begin(['id' => 'configure-form']); ?>
        <br\>
        <?= $form->field($model, 'enabled')->checkbox() ?>
        <br \>
        <h4><?= Yii::t('AuthOrcidModule.base', 'ORCID API Credentials') ?></h4>
        <?= $form->field($model, 'clientId')->textInput(['autocomplete' => 'off']) ?>
        <?= $form->field($model, 'clientSecret')->passwordInput(['autocomplete' => 'off']) ?>

        <h4><?= Yii::t('AuthOrcidModule.base', 'Feature Settings') ?></h4>
        <?= $form->field($model, 'enableAuthentication')->checkbox() ?>
        <?= $form->field($model, 'enableProfileSync')->checkbox() ?>
        <?= $form->field($model, 'enableWorksFetch')->checkbox() ?>
        <?= $form->field($model, 'enableWorksUpdate')->checkbox() ?>
        <?= $form->field($model, 'enableEducationSync')->checkbox() ?>
        <?= $form->field($model, 'enableEmploymentSync')->checkbox() ?>

        <div class="form-group">
            <?= Html::submitButton(Yii::t('AuthOrcidModule.base', 'Save'), ['class' => 'btn btn-primary', 'name' => 'save-button']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <?= Yii::t('AuthOrcidModule.base', 'ORCID Integration Instructions') ?>
    </div>
    <div class="panel-body">
        <ol>
            <li><?= Yii::t('AuthOrcidModule.base', 'Register your application at {orcidUrl}', ['orcidUrl' => Html::a('ORCID Developer Tools', 'https://orcid.org/developer-tools', ['target' => '_blank'])]) ?></li>
            <li><?= Yii::t('AuthOrcidModule.base', 'Set the Redirect URI to: {redirectUri}', ['redirectUri' => Html::tag('code', Url::to(['/user/auth/external', 'authclient' => 'orcid'], true))]) ?></li>
            <li><?= Yii::t('AuthOrcidModule.base', 'Copy the provided Client ID and Client Secret to the fields above.') ?></li>
            <li><?= Yii::t('AuthOrcidModule.base', 'Configure the desired features using the checkboxes above.') ?></li>
            <li><?= Yii::t('AuthOrcidModule.base', 'Save the settings.') ?></li>
        </ol>
        <p><?= Yii::t('AuthOrcidModule.base', 'For more information on ORCID integration, please refer to the {docLink}.', ['docLink' => Html::a('ORCID API documentation', 'https://info.orcid.org/documentation/', ['target' => '_blank'])]) ?></p>
    </div>
</div>