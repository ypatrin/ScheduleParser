<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 30.07.2018
 * Time: 14:33
 */

namespace app\common\components;


class B2Parser implements ScheduleParser
{
    const SCHEDULE_URL = 'https://belavia.by/plugins/ajax/flight-table.php';
    protected $_schedule_parse_result = false;

    public function getSchedule()
    {
        //today
        $url = $this->_getRequestUrl(date('d.m.Y'));
        $this->_parseSchedule($url, 'today', 'arrival');
        $url = $this->_getRequestUrl(date('d.m.Y'), 1);
        $this->_parseSchedule($url, 'today', 'departure');

        //yesterday
        $url = $this->_getRequestUrl(date('d.m.Y', strtotime('-1 day')));
        $this->_parseSchedule($url, 'yesterday', 'arrival');
        $url = $this->_getRequestUrl(date('d.m.Y', strtotime('-1 day')), 1);
        $this->_parseSchedule($url, 'yesterday', 'departure');

        //tomorrow
        $url = $this->_getRequestUrl(date('d.m.Y', strtotime('+1 day')));
        $this->_parseSchedule($url, 'tomorrow', 'arrival');
        $url = $this->_getRequestUrl(date('d.m.Y', strtotime('+1 day')), 1);
        $this->_parseSchedule($url, 'tomorrow', 'departure');

        return $this->_schedule_parse_result;
    }

    protected function _getRequestUrl($date, $departure = 0)
    {
        return self::SCHEDULE_URL . '?' . http_build_query([
            'siteid' => 1,
            'id' => 5,
            'airport' => 'MSQ',
            'date' => date('d.m.Y', strtotime($date)),
            'airport_Combobox' => 'Минск (MSQ)',
            'date_Datepicker' => date('d.m.Y', strtotime($date)),
            'departure' => $departure
        ]);
    }

    protected function _parseSchedule($url, $rel_day, $direction)
    {
        $flights = [];
        $scheduleHtml = @file_get_contents($url);

        $html = "<!DOCTYPE html>\n";
        $html .= "<html>\n";
        $html .= "<head><meta charset=\"utf-8\"></head>\n";
        $html .= "<body>{$scheduleHtml}</body>\n";
        $html .= "</html> ";

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($html);
        $finder = new \DOMXpath($dom);

        $nodes = $finder->query("//div");

        foreach ($nodes as $index => $node)
        {
            if ($node->getAttribute('class') == "headings visible-md")
                continue;
            if ($index == 1) continue;

            $divList = $node->getElementsByTagName('div');
            if ($divList->length < 5) continue;

            $flightObject = new FlightObject();
            $flightObject->flightNumber = trim($divList[0]->nodeValue);
            $flightObject->airport = trim($divList[1]->nodeValue);

            if (isset($divList[3]->getElementsByTagName('span')[0]))
                $removeFromTime = $divList[3]->getElementsByTagName('span')[0]->nodeValue;
            else
                $removeFromTime = '';

            if ($rel_day == "today")
                $flightObject->schedule_time = date('Y-m-d').' '.trim($divList[3]->nodeValue);
            if ($rel_day == "yesterday")
                $flightObject->schedule_time = date('Y-m-d', strtotime('-1 day')).' '.trim(str_replace("\n", "", $divList[3]->nodeValue));
            if ($rel_day == "tomorrow")
                $flightObject->schedule_time = date('Y-m-d', strtotime('+1 day')).' '.trim($divList[3]->nodeValue);
            $flightObject->schedule_time = trim(str_replace($removeFromTime, '', $flightObject->schedule_time));
            $flightObject->schedule_time = date('Y-m-d H:i', strtotime($flightObject->schedule_time));

            $flightObject->status = trim(str_replace('Комментарий:', '', $divList[4]->nodeValue));
            $flightObject->rel_date = $rel_day;
            $flightObject->carrier = 'Belavia';
            $flightObject->direction = $direction;
            $flightObject->_source = 'B2';

            $flights[] = $flightObject;
            unset($flightObject);
        }

        if (empty($this->_schedule_parse_result)) $this->_schedule_parse_result = $flights;
        else $this->_schedule_parse_result = array_merge($this->_schedule_parse_result, $flights);
    }
}