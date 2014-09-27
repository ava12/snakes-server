<?php

class IndexController extends Controller {

//---------------------------------------------------------------------------
	public function actionIndex() {
		$this->render('index');
	}

//---------------------------------------------------------------------------
	public function actionLogin() {
		echo 'login';
	}

//---------------------------------------------------------------------------
	public function actionLogout() {
		Yii::app()->user->logout();
		$this->redirect('/');
	}

//---------------------------------------------------------------------------
	public function actionCaptcha() {
		Yii::app()->session->open();
		$captcha = new KCAPTCHA;
		$_SESSION['captcha'] = $captcha->getKeyString();
	}

//---------------------------------------------------------------------------
	public function actionRegister() {
		if (!$_POST) {
			$this->render('register');
			return;
		}

		
	}

//---------------------------------------------------------------------------
}