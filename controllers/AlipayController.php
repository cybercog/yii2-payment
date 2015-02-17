<?php

namespace yii\payment\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\VerbFilter;

class AlipayController extends Controller{

	public $enableCsrfValidation = false;

	private $mode = 'alipay';

	public function behaviors(){
		return [
			'verbs' => [
				'class' => VerbFilter::className(),
				'actions' => [
					'async' => ['post'],
					'sync' => ['get'],
				],
			],
		];
	}

	public function actionAsync(){
		if(!isset($_POST['out_trade_no']) || !isset($_POST['trade_no']) || !isset($_POST['trade_status'])){
			return false;
		}

		$id = $_POST['out_trade_no'];
		$tid = $_POST['trade_no'];
		$status = $this->checkTradeStatus($_POST['trade_status']) ? 1 : 0;
		$manager = $this->module->manager;
		$verified = $manager->verifySign(true);
		$manager->saveNotify($this->mode, $id, $tid, $status, $verified, $_POST);

		if(!$verified){
			return false;
		}

		if($status && $manager->complete($id, $tid) && $this->module->asyncRoute){
			$this->run($this->module->asyncRoute, ['id' => $id]);
		}

		return 'success';
	}

	public function actionSync(){
		if(!$this->module->manager->verifySign()){
			return '验证失败';
		}

		$request = Yii::$app->request;
		if($this->checkTradeStatus($request->get('trade_status'))){
			return $this->module->syncRoute ? $this->redirect($this->module->syncRoute, ['id' => $request->get('out_trade_no')]) : '付款成功';
		}

		return '验证成功';
	}

	private function checkTradeStatus($trade_status){
		return $trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS';
	}

}
