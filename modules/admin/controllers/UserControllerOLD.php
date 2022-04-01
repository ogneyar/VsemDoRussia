<?php

namespace app\modules\admin\controllers;

use Yii;
use yii\helpers\Url;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\helpers\ArrayHelper;
use yii\filters\VerbFilter;
use yii\base\Exception;
use PhpOffice\PhpWord\TemplateProcessor;
use app\models\User;
use app\models\Account;
use app\models\AccountLog;
use app\models\Template;
use app\models\Parameter;
use app\modules\admin\models\AccountForm;
use app\models\ProviderStock;
use app\helpers\Html;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends BaseController
{
    public function behaviors()
    {
        return ArrayHelper::merge(parent::behaviors(), [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ]);
    }

    public function actionDownloadRequest($id)
    {
        return $this->downloadDocumentFile($id);
    }

    public function actionDownloadOffer($id)
    {
        return $this->downloadDocumentFile($id);
    }

    public function actionDownloadBusiness($id)
    {
        return $this->downloadDocumentFile($id);
    }

    public function actionDownloadQuestionary($id)
    {
        return $this->downloadDocumentFile($id);
    }

    public function actionDownloadIncomingPayment($id)
    {
        $user = User::findOne($id);

        if (!$user) {
            throw new NotFoundHttpException('Участник не найден.');
        }

        $templateName = preg_replace('/^download-\w+-/', '', $this->action->id);
        $templateFile = Template::getFileByName('user', $templateName);
        if (!$templateFile) {
            throw new NotFoundHttpException('Шаблон не найден.');
        }
        $templateExtension = pathinfo($templateFile, PATHINFO_EXTENSION);
        $attachmentName = sprintf('%s-%d.%s', $templateName, (int) $user->number, $templateExtension);

        $objectReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objectExcel = $objectReader->load($templateFile);

        $parameters = Template::getUserParameters($user);
        $parameters['message'] = 'Основание: Взносы: Вступительные -90 руб. Минимальные паевые -10 руб.';
        $objectExcel->setActiveSheetIndex(0)
            ->setCellValue('A25', $parameters['message'])
            ->setCellValue('BQ16', $parameters['message'])
            ->setCellValue('K23', $parameters['fullName'])
            ->setCellValue('BQ14', $parameters['fullName'])
            ->setCellValue('BB15', $parameters['createdDate'])
            ->setCellValue('BQ12', 'от ' . $parameters['createdDate'] . ' г.')
            ->setCellValue('BQ29', $parameters['createdDate'] . ' г.');
        $objectWriter = \PHPExcel_IOFactory::createWriter($objectExcel, 'Excel5');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $attachmentName .'"');
        header('Cache-Control: max-age=0');

        $objectWriter->save('php://output');

        exit();
    }

    public function actionDownloadUserPaymentByMonths($id, $months)
    {
        $user = User::findOne($id);

        if (!$user) {
            throw new NotFoundHttpException('Участник не найден.');
        }

        $templateName = preg_replace('/^download-\w+-(\w+)-by-\w+$/', '$1', $this->action->id);
        $templateFile = Template::getFileByName('user', $templateName);
        if (!$templateFile) {
            throw new NotFoundHttpException('Шаблон не найден.');
        }
        $templateExtension = pathinfo($templateFile, PATHINFO_EXTENSION);
        $attachmentName = sprintf('%s-%d.%s', $templateName, (int) $user->number, $templateExtension);

        $objectReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objectExcel = $objectReader->load($templateFile);

        $subscriberPaymentAmount = (int) Parameter::getValueByName('subscriber-payment');
        $paymentTotal = $months * $subscriberPaymentAmount;

        $spelloutTotal = sprintf(
            '%s %02d копеек',
            Yii::t('app', '{value, spellout}', ['value' => floor($paymentTotal)], Yii::$app->language),
            round(100 * ($paymentTotal - floor($paymentTotal)))
        );

        $parameters = Template::getUserParameters($user);
        $parameters['paymentTotal'] = sprintf('%.2f', $paymentTotal);
        $parameters['message'] = sprintf('Основание: Членский взнос за %d мес. - %.2f руб.', $months, $parameters['paymentTotal']);
        $objectExcel->setActiveSheetIndex(0)
            ->setCellValue('A25', $parameters['message'])
            ->setCellValue('AM21', $parameters['paymentTotal'])
            ->setCellValue('BB15', $parameters['createdDate'])
            ->setCellValue('BQ12', 'от ' . $parameters['createdDate'] . ' г.')
            ->setCellValue('BQ14', $parameters['fullName'])
            ->setCellValue('BQ16', $parameters['message'])
            ->setCellValue('BQ23', $spelloutTotal)
            ->setCellValue('BQ29', $parameters['createdDate'] . ' г.')
            ->setCellValue('BV21', floor($paymentTotal))
            ->setCellValue('CM21', sprintf('%02d', round(100 * ($paymentTotal - floor($paymentTotal)))))
            ->setCellValue('F27', $spelloutTotal)
            ->setCellValue('K23', $parameters['fullName']);
        $objectWriter = \PHPExcel_IOFactory::createWriter($objectExcel, 'Excel5');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $attachmentName .'"');
        header('Cache-Control: max-age=0');

        $objectWriter->save('php://output');

        exit();
    }

    public function actionDownloadUserPaymentByQuarter($id)
    {
        $user = User::findOne($id);

        if (!$user) {
            throw new NotFoundHttpException('Участник не найден.');
        }

        $templateName = preg_replace('/^download-\w+-(\w+)-by-\w+$/', '$1', $this->action->id);
        $templateFile = Template::getFileByName('user', $templateName);
        if (!$templateFile) {
            throw new NotFoundHttpException('Шаблон не найден.');
        }
        $templateExtension = pathinfo($templateFile, PATHINFO_EXTENSION);
        $attachmentName = sprintf('%s-%d.%s', $templateName, (int) $user->number, $templateExtension);

        $objectReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objectExcel = $objectReader->load($templateFile);

        $subscriberPaymentAmount = (int) Parameter::getValueByName('subscriber-payment');
        $paymentTotal = User::SUBSCRIBER_MONTHS_INTERVAL * $subscriberPaymentAmount;

        $spelloutTotal = sprintf(
            '%s %02d копеек',
            Yii::t('app', '{value, spellout}', ['value' => floor($paymentTotal)], Yii::$app->language),
            round(100 * ($paymentTotal - floor($paymentTotal)))
        );

        $parameters = Template::getUserParameters($user);
        $parameters['paymentTotal'] = sprintf('%.2f', $paymentTotal);
        $parameters['message'] = sprintf('Основание: Ежеквартальный взнос - %.2f руб.', $parameters['paymentTotal']);
        $objectExcel->setActiveSheetIndex(0)
            ->setCellValue('A25', $parameters['message'])
            ->setCellValue('AM21', $parameters['paymentTotal'])
            ->setCellValue('BB15', $parameters['createdDate'])
            ->setCellValue('BQ12', 'от ' . $parameters['createdDate'] . ' г.')
            ->setCellValue('BQ14', $parameters['fullName'])
            ->setCellValue('BQ16', $parameters['message'])
            ->setCellValue('BQ23', $spelloutTotal)
            ->setCellValue('BQ29', $parameters['createdDate'] . ' г.')
            ->setCellValue('BV21', floor($paymentTotal))
            ->setCellValue('CM21', sprintf('%02d', round(100 * ($paymentTotal - floor($paymentTotal)))))
            ->setCellValue('F27', $spelloutTotal)
            ->setCellValue('K23', $parameters['fullName']);
        $objectWriter = \PHPExcel_IOFactory::createWriter($objectExcel, 'Excel5');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $attachmentName .'"');
        header('Cache-Control: max-age=0');

        $objectWriter->save('php://output');

        exit();
    }

    public function actionDownloadUserPaymentByCost($id, $cost)
    {
        $user = User::findOne($id);

        if (!$user) {
            throw new NotFoundHttpException('Участник не найден.');
        }

        $templateName = preg_replace('/^download-\w+-(\w+)-by-\w+$/', '$1', $this->action->id);
        $templateFile = Template::getFileByName('user', $templateName);
        if (!$templateFile) {
            throw new NotFoundHttpException('Шаблон не найден.');
        }
        $templateExtension = pathinfo($templateFile, PATHINFO_EXTENSION);
        $attachmentName = sprintf('%s-%d.%s', $templateName, (int) $user->number, $templateExtension);

        $objectReader = \PHPExcel_IOFactory::createReader('Excel5');
        $objectExcel = $objectReader->load($templateFile);

        $paymentTotal = sprintf('%.2f', (double) $cost);

        $spelloutTotal = sprintf(
            '%s %02d копеек',
            Yii::t('app', '{value, spellout}', ['value' => floor($paymentTotal)], Yii::$app->language),
            round(100 * ($paymentTotal - floor($paymentTotal)))
        );

        $parameters = Template::getUserParameters($user);
        $parameters['paymentTotal'] = sprintf('%.2f', $paymentTotal);
        $parameters['message'] = sprintf('Основание: Членский взнос - %.2f руб.', $parameters['paymentTotal']);
        $objectExcel->setActiveSheetIndex(0)
            ->setCellValue('A25', $parameters['message'])
            ->setCellValue('AM21', $parameters['paymentTotal'])
            ->setCellValue('BB15', $parameters['createdDate'])
            ->setCellValue('BQ12', 'от ' . $parameters['createdDate'] . ' г.')
            ->setCellValue('BQ14', $parameters['fullName'])
            ->setCellValue('BQ16', $parameters['message'])
            ->setCellValue('BQ23', $spelloutTotal)
            ->setCellValue('BQ29', $parameters['createdDate'] . ' г.')
            ->setCellValue('BV21', floor($paymentTotal))
            ->setCellValue('CM21', sprintf('%02d', round(100 * ($paymentTotal - floor($paymentTotal)))))
            ->setCellValue('F27', $spelloutTotal)
            ->setCellValue('K23', $parameters['fullName']);
        $objectWriter = \PHPExcel_IOFactory::createWriter($objectExcel, 'Excel5');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $attachmentName .'"');
        header('Cache-Control: max-age=0');

        $objectWriter->save('php://output');

        exit();
    }

    public function actionAccount($id)
    {
        $user = User::findOne($id);
        $model = new AccountForm(['user_id' => $user->id]);

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            Account::swap(null, $user->getAccount($model->account_type), $model->amount, $model->message);
            if (isset($user->provider) && $model->amount < 0) {
                ProviderStock::setStockSum($user->id, $model->amount);
            }

            return $this->redirect(['account', 'id' => $id, 'type' => $model->account_type]);
        }

        $accounts = [];
        $accountTypes = ArrayHelper::getColumn($user->accounts, 'type');
        foreach ($accountTypes as $accountType) {
            $account = $user->getAccount($accountType);
            if ($account) {
                $accounts[] = [
                    'type' => $account->type,
                    'name' => Html::makeTitle($account->typeName),
                    'account' => $account,
                    'dataProvider' => new ActiveDataProvider([
                        'id' => $account->type,
                        'query' => AccountLog::find()->where('account_id = :account_id', [':account_id' => $account->id]),
                        'sort' => ['defaultOrder' => ['created_at' => SORT_DESC]],
                        'pagination' => [
                            'params' => array_merge($_GET, [
                                'type' => $account->type,
                            ]),
                        ],
                    ]),
                ];
            }
        }

        $accountType = Yii::$app->getRequest()->getQueryParam('type');
        if (!$user->getAccount($accountType)) {
            $accountType = Account::TYPE_DEPOSIT;
        }

        return $this->render('account', [
            'user' => $user,
            'model' => $model,
            'accounts' => $accounts,
            'accountType' => $accountType,
        ]);
    }

    protected function downloadDocumentFile($id)
    {
        $user = User::findOne($id);

        if (!$user) {
            throw new NotFoundHttpException('Участник не найден.');
        }

        $templateName = preg_replace('/^download-/', '', $this->action->id);
        $templateFile = Template::getFileByName('user', $templateName);
        if (!$templateFile) {
            throw new NotFoundHttpException('Шаблон не найден.');
        }
        $templateExtension = pathinfo($templateFile, PATHINFO_EXTENSION);
        $attachmentName = sprintf('%s-%d.%s', $templateName, (int) $user->number, $templateExtension);

        $templateProcessor = new TemplateProcessor($templateFile);

        foreach (Template::getUserParameters($user) as $name => $value) {
            $templateProcessor->setValue($name, $value);
        }

        return Yii::$app->response->sendFile(
            $templateProcessor->save(),
            $attachmentName
        );
    }
}
