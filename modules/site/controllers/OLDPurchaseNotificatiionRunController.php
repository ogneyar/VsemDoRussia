<?php

namespace app\modules\site\controllers;

use Yii;

use yii\web\Response;
use app\models\Account;


class PurchaseNotificatiionRunController extends BaseController
{
   
    /**
     * 
     * @return mixed
     */
    public function actionIndex()
    {       
        return $this->render('index', []);
    }
    
    /**
     * 
     * @return array
     */
    public function actionUpdate()
    {       
        Yii::$app->response->format = Response::FORMAT_JSON;
        // return true;
        $value = "";
        if($_GET && $_GET["value"]) $value = $_GET["value"];
        if ($value) {
            $acc = Account::find()->where(['user_id' => Yii::$app->user->id,'type' => 'subscription'])->one();
            $acc->total = $value;
            $acc->save();
        }else {
            return  [
                'ok' => false,
                'message' => 'Значение не заменено.',
            ];
        }
        
        return  [
            'ok' => true,
            'message' => 'Значение заменено на ' . $value . '.',
            'value' => $value,
        ];
    }
    
    /**
     * 
     * @return array
     */
    public function actionSubscriber()
    {       
        Yii::$app->response->format = Response::FORMAT_JSON;
        return true;
    }

}