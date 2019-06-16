<?php

namespace benhall14\phpCalendar;

use DateTime;
use stdClass;

/**
 * Simple PHP Calendar Class.
 *
 * @copyright  Copyright (c) Benjamin Hall
 * @license https://github.com/benhall14/php-calendar
 * @package protocols
 * @version 1.1
 * @author Benjamin Hall <https://linkedin.com/in/benhall14>
 */
class Calendar {

    /**
     * The events array.
     * @var array
     */
    private $events = [];

    /**
     * Add an event to the current calendar instantiation.
     * @param string $start The start date in Y-m-d format.
     * @param string $end The end date in Y-m-d format.
     * @param string $summary The summary string of the event.
     * @param boolean $mask The masking class.
     * @param array $classes (optional) A list of classes to use for the event.
     * @return $this
     */
    public function addEvent($start, $end, $summary = null, $mask = null, array $classes = []) {
        $event = new stdClass();

        $event->start = DateTime::createFromFormat('Y-m-d', $start);

        $event->end = DateTime::createFromFormat('Y-m-d', $end);

        $event->mask    = !!$mask;
        $event->classes = false;

        if (count($classes) > 0) {
            $event->classes = implode(' ', $classes);
        }

        $event->summary = $summary;

        $this->events[] = $event;

        return $this;
    }

    /**
     * Add an array of events using $this->addEvent();
     *
     * Each array element must have the following:
     *     'start'  =>   start date in Y-m-d format.
     *     'end'    =>   end date in Y-m-d format.
     *     (optional) 'mask' => a masking class name.
     *     (optional) 'classes' => custom classes to include.
     *
     * @param array $events The events array.
     * @return $this
     */
    public function addEvents($events) {
        if (is_array($events)) {
            foreach ($events as $event) {
                if (array_key_exists('start', $event) !== false && array_key_exists('end', $event) !== false) {
                    $classes = array_key_exists('classes', $event) !== false ? $event['classes'] : [];
                    $mask    = array_key_exists('mask', $event) !== false ? !!$event['mask'] : null;
                    $summary = array_key_exists('summary', $event) !== false ? $event['summary'] : null;
                    $this->addEvent($event['start'], $event['end'], $summary, $mask, $classes);
                }
            }
        }

        return $this;
    }

    /**
     * Remove all events tied to this calendar
     * @return $this
     */
    public function clearEvents() {
        $this->events = [];

        return $this;
    }

    /**
     * Find an event from the internal pool
     * @param DateTime $date The date to match an event for.
     * @return array Either an array of events or false.
     */
    private function findEvents(DateTime $date) {
        $found_events = [];

        if (isset($this->events)) {
            foreach ($this->events as $event) {
                if ($date->getTimestamp() >= $event->start->getTimestamp() && $date->getTimestamp() <= $event->end->getTimestamp()) {
                    $found_events[] = $event;
                }
            }
        }

        return $found_events;
    }

    /**
     * Draw the calendar and echo out.
     * @param string $date The date of this calendar.
     * @param string $color
     * @return string The calendar
     * @throws \Exception
     */
    public function draw($date = null, $color = null) {
        $calendar = '';

        if ($date !== null) {
            $date = DateTime::createFromFormat('Y-m-d', $date);
            $date->modify('first day of this month');
        } else {
            $date = new DateTime();
            $date->modify('first day of this month');
        }

        $today = new DateTime();

        $total_days_in_month = (int)$date->format('t');

        $color = strlen($color) > 0 ? $color : '';

        $calendar .= '<table class="calendar ' . $color . '">';

        $calendar .= '<thead>';

        $calendar .= '<tr class="calendar-title">';

        $calendar .= '<th colspan="7">';

        $calendar .= $date->format('F Y');

        $calendar .= '</th>';

        $calendar .= '</tr>';

        $calendar .= '<tr class="calendar-header">';

        $calendar .= '<th>';

        $calendar .= implode('</th><th>', ['S', 'M', 'T', 'W', 'T', 'F', 'S']);

        $calendar .= '</th>';

        $calendar .= '</tr>';

        $calendar .= '</thead>';

        $calendar .= '<tbody>';

        $calendar .= '<tr>';

        # padding before the month start date IE. if the month starts on Wednesday
        for ($x = 0; $x < $date->format('w'); $x++) {
            $calendar .= '<td class="pad"> </td>';
        }

        $running_day = clone $date;

        $running_day_count = 1;

        do {
            $events = $this->findEvents($running_day);

            $class = '';

            $event_summary = '';

            if ($events) {
                foreach ($events as $index => $event) {
                    # is the current day the start of the event
                    if ($event->start->format('Y-m-d') == $running_day->format('Y-m-d')) {
                        $class         .= $event->mask ? ' mask-start' : '';
                        $class         .= ($event->classes) ? ' ' . $event->classes : '';
                        $event_summary .= ($event->summary) ?: '';

                        # is the current day in between the start and end of the event
                    } elseif (
                        $running_day->getTimestamp() > $event->start->getTimestamp()
                        && $running_day->getTimestamp() < $event->end->getTimestamp()
                    ) {
                        $class .= $event->mask ? ' mask' : '';

                        # is the current day the start of the event
                    } elseif ($running_day->format('Y-m-d') == $event->end->format('Y-m-d')) {
                        $class .= $event->mask ? ' mask-end' : '';
                    }
                }
            }

            $today_class = ($running_day->format('Y-m-d') == $today->format('Y-m-d')) ? ' today' : '';

            $calendar .= '<td class="day' . $class . $today_class . '" title="' . htmlentities($event_summary) . '">';

            $calendar .= '<div>';

            $calendar .= $running_day->format('j');

            $calendar .= '</div>';

            $calendar .= '<div>';

            $calendar .= $event_summary;

            $calendar .= '</div>';

            $calendar .= '</td>';

            # check if this calendar-row is full and if so push to a new calendar row
            if ($running_day->format('w') == 6) {
                $calendar .= '</tr>';

                # start a new calendar row if there are still days left in the month
                if (($running_day_count + 1) <= $total_days_in_month) {
                    $calendar .= '<tr>';
                }

                # reset padding because its a new calendar row
                $day_padding_offset = 0;
            }

            $running_day->modify('+1 Day');

            $running_day_count++;
        } while ($running_day_count <= $total_days_in_month);

        $padding_at_end_of_month = 7 - $running_day->format('w');

        # padding at the end of the month
        if ($padding_at_end_of_month && $padding_at_end_of_month < 7) {
            for ($x = 1; $x <= $padding_at_end_of_month; $x++) {
                $calendar .= '<td class="pad"> </td>';
            }
        }

        $calendar .= '</tr>';

        $calendar .= '</tbody>';

        $calendar .= '</table>';

        return $calendar;
    }
}
