<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\common\components;

class SiteController extends Controller
{
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $schedule = Yii::$app->cache->get("schedule");

        if ($schedule === false) {
            $kbpSchedule = (new components\KbpParser())->getSchedule();
            $ievSchedule = (new components\IevParser())->getSchedule();

            $schedule = array_merge($kbpSchedule, $ievSchedule);
            usort($schedule, function ($a, $b) {
                return strtotime($a->schedule_time) - strtotime($b->schedule_time);
            });

            Yii::$app->cache->set("schedule", $schedule, 60 * 5);
        }
        return $this->render('index', ['schedule' => $schedule]);
    }
}
