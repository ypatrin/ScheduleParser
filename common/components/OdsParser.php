<?php
/**
 * Created by PhpStorm.
 * User: ypatrin
 * Date: 23.07.2018
 * Time: 12:47
 */

namespace app\common\components;


class OdsParser implements ScheduleParser
{
    const SCHEDULE_PAGE_URL = 'http://www.odessa.aero/ru/iboard';

    private $_schedule_page_content = false;
    private $_schedule_parse_result = false;

    public function getSchedule()
    {
        $this->_loadSchedulePage();
        $this->_parseSchedulePage();

        return $this->_schedule_parse_result;
    }

    protected function _loadSchedulePage()
    {
        $this->_schedule_page_content = @file_get_contents(self::SCHEDULE_PAGE_URL);
    }

    protected function _parseSchedulePage()
    {
        $flights = [];

        libxml_use_internal_errors(true);

        $dom = new \DOMDocument();
        $dom->loadHTML($this->_schedule_page_content);
        $finder = new \DOMXpath($dom);
        $classname = "iboardbodytab";

        $nodes = $finder->query("//div[@class='{$classname}']");
        $departure = $nodes[0];
        $arrival = $nodes[1];

        $this->_buildResults($departure, "departure");
        $this->_buildResults($arrival, "arrival");
    }

    protected function _buildResults($results, $direction)
    {
        $tr = $results->getElementsByTagName('tr');
        for ($i = 1; $i < $tr->length; $i++)
        {
            $tdList = $tr[$i]->getElementsByTagName('td');

            $flightObject = new FlightObject();

            $fNumberArr = explode('/', $tdList[0]->nodeValue);
            $fNumber = substr($fNumberArr[0], 0, 2).' '.substr($fNumberArr[0], 2, strlen($fNumberArr[0]));
            if (!empty($fNumberArr[1])) {
                $fNumber .= '/'. substr($fNumberArr[1], 0, 2).' '.substr($fNumberArr[1], 2, strlen($fNumberArr[1]));
            }

            $flightObject->flightNumber = $fNumber;
            $flightObject->airport = $tdList[1]->nodeValue;
            $flightObject->status = $this->_getStatus(strtoupper($tdList[4]->nodeValue));
            $flightObject->direction = $direction;

            if ($tdList[2]->getElementsByTagName('div')->length == 0)
            {
                $flightObject->schedule_time = date('Y-m-d') . ' ' . $tdList[2]->nodeValue;
                $flightObject->rel_date = "today";
            }
            else
            {
                $dateVal = $tdList[2]->getElementsByTagName('div')[0]->nodeValue;

                $time = str_replace($dateVal, '', $tdList[2]->nodeValue);
                $date = str_replace(['(', ')'], '', $dateVal);

                $flightObject->schedule_time = date('Y-m-d H:i', strtotime($date.' '.$time));
                $flightObject->rel_date = "tomorrow";
            }

            if (!empty(trim($tdList[3]->nodeValue))) {
                $flightObject->real_time = date('Y-m-d') . ' ' . $tdList[3]->nodeValue;
            }

            $flightObject->_source = 'ODS';

            $flights[] = $flightObject;
            unset($flightObject);
        }

        if (empty($this->_schedule_parse_result)) $this->_schedule_parse_result = $flights;
        else $this->_schedule_parse_result = array_merge($this->_schedule_parse_result, $flights);
    }

    protected function _getStatus($status)
    {
        $status = trim($status);
        $status = mb_convert_encoding($status, 'UTF-8', mb_detect_encoding($status));

        switch (strtoupper($status)) {
            case "Вылетел": return FlightObject::SCHEDULE_STATUS_DP; break;
            case "Прибыл": return FlightObject::SCHEDULE_STATUS_LN; break;
            case "Регистрация завершена": return FlightObject::SCHEDULE_STATUS_CK_COMPLETE; break;
            case "Летит": return FlightObject::SCHEDULE_STATUS_FR; break;
            case "Идет регистрация": return FlightObject::SCHEDULE_STATUS_CK; break;
            case "По расписанию": return FlightObject::SCHEDULE_STATUS_ON; break;
            default: return strtoupper($status); break;
        }

        return strtoupper($status);
    }
}