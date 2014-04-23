<?php

/**
 * Displays calendar with vacations overview.
 */
class holidaysBackendOverviewAction extends holidaysViewAction
{
    public function execute()
    {
        $start_date = waRequest::request('start');
        $start_ts = @strtotime($start_date);
        $start_ts > 0 || $start_ts = time();

        $start_date = date('Y-m-01', $start_ts);
        $end_date = date('Y-m-t', mktime(0, 0, 0, date("m", $start_ts)+3, 1, date("Y", $start_ts)));

        // Fetch vacatons
        $m = new holidaysModel();
        $sql = "SELECT h.*, c.name AS contact_name, c.photo AS contact_photo
                FROM holidays AS h
                    JOIN wa_contact AS c
                        ON c.id=h.contact_id
                WHERE h.start <= ?
                    AND h.end >= ?
                ORDER BY h.start";
        $vacations = $m->query($sql, $end_date, $start_date)->fetchAll('id');
        foreach($vacations as &$v) {
            $v['start_ts'] = strtotime($v['start']);
            $v['end_ts'] = strtotime($v['end']);
        }
        unset($v);

        // Fetch birthdays
        $birthdays = array();
        foreach($m->getContacts() as $c) {
            if ($c['birthday'] && ( $ts = strtotime($c['birthday']))) {
                $key = date('m-d', $ts);
                isset($birthdays[$key]) || $birthdays[$key] = array();
                $birthdays[$key][] = $c;
            }
        }

        // Build calendar structure
        $months = self::buildMonths($start_date, $end_date, $vacations, $birthdays);

        // Day names, starting from first day of week depending on locale
        $day_names = array();
        $loc = waLocale::getInfo(waLocale::getLocale());
        $first_day_of_week = ifset($loc['first_day'], 1);
        for ($i = 0; $i < 7; $i++) {
            $day_names[] = _ws(date('D', strtotime('next Monday +' . (($first_day_of_week - 2 + $i) % 7 + 1) . ' days')));
        }

        $this->view->assign('day_names', $day_names);
        $this->view->assign('vacations', $vacations);
        $this->view->assign('months', $months);
        $this->view->assign('next_start', date('Y-m-d', mktime(0, 0, 0, date("m", $start_ts)+4, 1, date("Y", $start_ts))));
        $this->view->assign('prev_start', date('Y-m-d', mktime(0, 0, 0, date("m", $start_ts)-4, 1, date("Y", $start_ts))));

        $this->setLayout(new holidaysBackendLayout());
        $this->layout->assign('c_core_classes', 'overview');
    }

    public static function buildMonths($start_date, $end_date, $vacations, $birthdays)
    {
        $months = array();

        $ts = strtotime($start_date);
        $end_ts = strtotime($end_date);

        while($ts < $end_ts) {
            $month_start = date('Y-m-01', $ts);
            $month_end =  date('Y-m-t', $ts);
            $m = array(
                'id' => date('Y-m', $ts),
                'name' => _ws(date('F', $ts)),
                'year' => date('Y', $ts),
                'weeks' => self::buildWeeks($month_start, $month_end, $vacations, $birthdays),
            );
            $months[$m['id']] = $m;

            $ts = mktime(0, 0, 0, date("m", $ts)+1, 1, date("Y", $ts));
        };

        return $months;
    }

    /** overviewAction() helper */
    public static function buildWeeks($start_date, $end_date, &$vacations, $birthdays)
    {
        $start_ts = strtotime($start_date);
        $end_ts = strtotime($end_date) + 24*3600 - 1;
        $cur_date = date('Y-m-d');

        static $colors = null;
        if (!$colors) {
            $colors = array(
                array('#F89B37', '#FFDD86', '#AF8649'),
                array('#5651D8', '#BEC6FF', '#2F2F8F'),
                array('#349FE5', '#A9D8FF', '#4E84A8'),
                array('#E04848', '#FFABAB', '#AC3737'),
                array('#33B448', '#82DD7A', '#3B863E'),
                array('#BA33C0', '#FFBEFF', '#802888'),
                array('#2BBBB5', '#73FFB1', '#2F8F7C'),
            );
        }

        // Prepare the first week by padding it with stubs
        $current_week = array('days' => array());
        $loc = waLocale::getInfo(waLocale::getLocale());
        $first_day_of_week = ifset($loc['first_day'], 1);
        $d = date('N', $start_ts);
        for ($i = 0; $i < (7 + $d - $first_day_of_week) % 7; $i++) {
            $current_week['days'][] = array(
                'stub' => true,
            );
        }

        // Each vacation has an index position of its color bar
        // so that each bar occupy its own line.
        // This stores currently occupied indexes.
        $vac_bars = array();
        foreach($vacations as $v) {
            if (isset($v['bar_index'])) {
                $vac_bars[$v['bar_index']] = $v['id'];
            }
        }

        // Loop over all days of the period collecting data into $weeks,
        // using $current_week as a buffer.
        // $ts is a timestamp of $date.
        // $weeks is the resulting array(): week id => week data
        $ts = $start_ts;
        $date = $start_date;
        $weeks = array();
        while ($ts < $end_ts) {

            $day_of_week = (int) date('N', $ts);
            $day_of_month = (int) substr($date, -2);

            $day = array(
                'ts' => $ts,
                'date' => $date,
                'day' => date('j', $ts),
                'weekday_name' => _ws(date('l', $ts)),
                'month_name' => _ws(date('F', $ts)), // !!! probably should get rid of it here
                'current' => $cur_date == $date,
                'first_of_month' => $day_of_month === 1,
                'last_of_month' => $day_of_month === (int) date('t', $ts),
                'weekend' => in_array(date('N', $ts), array('6', '7')),
                'vacations' => array(),
                'birthdays' => array(),
            );

            // Populate this day's vacations
            $prev_vac_bars = $vac_bars;
            $vac_bars = array();
            $max_index = -1;
            foreach($vacations as $i => &$v) {
                if ($ts < $v['start_ts'] || $v['end_ts'] < $ts) {
                    if (isset($v['bar_index'])) {
                        unset($vacations[$i]);
                        unset($prev_vac_bars[$v['bar_index']]);
                    }
                    continue;
                }

                if (!isset($v['bar_index'])) {
                    $v['bar_index'] = 0;
                    while (!empty($prev_vac_bars[$v['bar_index']]) || !empty($vac_bars[$v['bar_index']])) {
                        $v['bar_index']++;
                    }

                    $v['color'] = array_shift($colors);
                    $colors[] = $v['color'];
                    if (!is_array($v['color'])) {
                        $v['color'] = array($v['color'], $v['color'], $v['color']);
                    }

                    $v['css'] = array("background-color:{$v['color'][0]};");
                    !empty($v['color'][1]) && $v['css'][] = "border-top:1px solid {$v['color'][1]};";
                    !empty($v['color'][2]) && $v['css'][] = "border-bottom:1px solid {$v['color'][2]};";
                    $v['css'] = join('', $v['css']);

                    $v['week_started'] = date('Y-W', $ts);
                    $current_week['vacations_started'][$v['id']] = $v;
                }

                $vac_bars[$v['bar_index']] = $v['id'];
                $v['bar_index'] > $max_index && $max_index = $v['bar_index'];
            }
            unset($v);
            foreach(array_diff($prev_vac_bars, $vac_bars) as $vac_id) {
                unset($vacations[$vac_id]);
            }

            for ($i = 0; $i <= $max_index; $i++) {
                if (empty($vac_bars[$i])) {
                    $day['vacations'][] = array('stub' => true);
                } else {
                    $day['vacations'][] = $vacations[$vac_bars[$i]];
                }
            }

            // Populate this day's birthdays
            foreach(ifset($birthdays[date('m-d', $ts)], array()) as $c) {
                $day['birthdays'][] = array(
                    'contact_id' => $c['id'],
                    'contact_photo' => $c['photo'],
                    'contact_name' => $c['name'],
                    'date' => $date,
                    'age' => round(($ts - strtotime($c['birthday'])) / (3600*24*365)),
                );
            }
            if (!empty($day['birthdays'])) {
                $current_week['birthdays'] = array_merge(ifset($current_week['birthdays'], array()), $day['birthdays']);
            }

            $current_week['days'][] = $day;

            // Append $current_week to $weeks when full
            if (count($current_week['days']) >= 7) {
                $current_week['id'] = date('Y-W', $ts);

                // When $current_week starts a new month, mark the border between months
                $prev_week = $weeks ? end($weeks) : null;
                if (!$prev_week || $current_week['days'][6]['month_name'] !== $prev_week['days'][6]['month_name']) {
                    $prev_month_name = $prev_week ? $prev_week['days'][6]['month_name'] : null;
                    foreach($current_week['days'] as &$d) {
                        if (!empty($d['stub'])) {
                            $d['last_week_of_month'] = true;
                            continue;
                        }
                        if ($d['month_name'] === $prev_month_name) {
                            $d['last_week_of_month'] = true;
                        } else {
                            $d['first_week_of_month'] = true;
                        }
                    }
                    unset($d);

                    // Update last week of previous month
                    if ($prev_week && !empty($current_week['days'][0]['first_week_of_month'])) {
                        $pv =& $weeks[$prev_week['id']];
                        foreach($pv['days'] as &$d) {
                            $d['last_week_of_month'] = true;
                        }
                        unset($pv, $d);
                    }
                }

                // Determine first full week of a month
                if ((!$prev_week && empty($current_week['days'][0]['stub']))
                    || ifset($current_week['days'][0]['month_name']) !== ifset($prev_week['days'][0]['month_name'])
                ) {
                    $current_week['first_full_of_month'] = true;
                    $current_week['month_name'] = $current_week['days'][0]['month_name'];
                }

                $weeks[$current_week['id']] = $current_week;
                $current_week = array(
                    'days' => array(),
                );
            }

            // Prepare for next iteration
            $date = date('Y-m-d', mktime(0, 0, 0, date("m", $ts), date("d", $ts)+1, date("Y", $ts)));
            $ts = strtotime($date);
        }

        foreach($current_week['days'] as &$d) {
            $d['last_week_of_month'] = true;
        }
        unset($d);

        // Pad current week with stubs
        while(count($current_week['days']) < 7) {
            $current_week['days'][] = array(
                'stub' => true,
                'first_week_of_month' => true,
            );
        }

        $current_week['id'] = date('Y-W', $ts);
        $weeks[$current_week['id']] = $current_week;

        return $weeks;
    }
}

