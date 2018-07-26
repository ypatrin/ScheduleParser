<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        $parserList = [
            'Hrk', 'Kbp', 'Iev', 'Lwo', 'Ods'
        ];

        $schedule = Yii::$app->cache->get("schedule");

        if ($schedule === false) {
            $schedule = [];

            foreach ($parserList as $parser) {
                $objectName =  "app\\common\\components\\{$parser}Parser";
                $instance = new $objectName();
                $results = $instance->getSchedule();

                if (empty($schedule)) {
                    $schedule = $results;
                } else {
                    $schedule = array_merge($schedule, $results);
                }
            }

            usort($schedule, function ($a, $b) {
                return strtotime($a->schedule_time) - strtotime($b->schedule_time);
            });

            //var_dump($schedule); exit;

            Yii::$app->cache->set("schedule", $schedule, 60 * 10);
        }
        return $this->render('index', [
            'schedule' => $schedule,
            'parserList' => $parserList
        ]);
    }
}
