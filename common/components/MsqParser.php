<?php
/**
 * Created by PhpStorm.
 * User: yury
 * Date: 30.07.18
 * Time: 9:52
 */

namespace app\common\components;


class MsqParser implements ScheduleParser
{
    const SCHEDULE_ARRIVAL_URL      = 'http://airport.by/timetable/online-arrival';
    const SCHEDULE_DEPARTURE_URL    = 'http://airport.by/timetable/online-departure';

    public function getSchedule()
    {
        $this->_parseSchedule();
    }

    protected function _parseSchedule()
    {
        $arrivalHtml = @file_get_contents(self::SCHEDULE_ARRIVAL_URL);
    }
}