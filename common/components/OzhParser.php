<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 27.07.2018
 * Time: 10:54
 */

namespace app\common\components;


class OzhParser implements ScheduleParser
{
    const SCHEDULE_URL = 'https://ozh.aero/ru/';
    private $_schedule_parse_result = false;

    public function getSchedule()
    {
        $this->_parseSchedule();
        return $this->_schedule_parse_result;
    }

    protected function _parseSchedule()
    {
        $flights = [];

        $scheduleHtml = @file_get_contents(self::SCHEDULE_URL);
        if (!$scheduleHtml) return [];

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument();
        $dom->loadHTML($scheduleHtml);
        $finder = new \DOMXpath($dom);

        $nodes = $finder->query("//div[@class=\"schedule__table js-tab active animate departure\"]/div[@class=\"schedule__row\"]");

        foreach ($nodes as $node) {
            $flightNum = trim($node->childNodes[1]->nodeValue);
            $airline = trim($node->childNodes[3]->nodeValue);
            $destiny = trim($node->childNodes[5]->nodeValue);
            $time = trim($node->childNodes[7]->nodeValue);
            $status = trim($node->childNodes[9]->nodeValue);

            $key = 'departure'.$flightNum.$airline.$destiny.$time;

            $flightObject = new FlightObject();
            $flightObject->flightNumber = $flightNum;
            $flightObject->airport = str_replace('Zaporizhzhya', '', $destiny);
            $flightObject->airport = str_replace('―', '', $flightObject->airport);
            $flightObject->airport = trim($flightObject->airport);
            $flightObject->status = $status;
            $flightObject->schedule_time = date('Y-m-d') . ' ' . $time;
            $flightObject->carrier = $airline;

            $flightObject->rel_date = "today";
            $flightObject->direction = "departure";
            $flightObject->_source = 'OZH';

            $flights[$key] = $flightObject;
        }

        $nodes = $finder->query("//div[@class=\"schedule__table js-tab arrival\"]/div[@class=\"schedule__row\"]");

        foreach ($nodes as $node) {
            $flightNum = trim($node->childNodes[1]->nodeValue);
            $airline = trim($node->childNodes[3]->nodeValue);
            $destiny = trim($node->childNodes[5]->nodeValue);
            $time = trim($node->childNodes[7]->nodeValue);
            $status = trim($node->childNodes[9]->nodeValue);

            $key = 'arrival'.$flightNum.$airline.$destiny.$time;

            $flightObject = new FlightObject();
            $flightObject->flightNumber = $flightNum;
            $flightObject->airport = $destiny;
            $flightObject->airport = str_replace('Zaporizhzhya', '', $destiny);
            $flightObject->airport = str_replace('―', '', $flightObject->airport);
            $flightObject->airport = trim($flightObject->airport);

            $flightObject->status = $status;
            $flightObject->schedule_time = date('Y-m-d') . ' ' . $time;
            $flightObject->carrier = $airline;

            $flightObject->rel_date = "today";
            $flightObject->direction = "arrival";
            $flightObject->_source = 'OZH';

            $flights[$key] = $flightObject;
        }

        if (empty($this->_schedule_parse_result)) $this->_schedule_parse_result = $flights;
        else $this->_schedule_parse_result = array_merge($this->_schedule_parse_result, $flights);
    }
}