<?php

namespace Pota\Adif;

class Validator
{
    public static function entry(array $fields): bool|array
    {
        $errors = [];
        $required = Spec::$base_fields;
        $required_set = array_flip($required);  // Convert to hash set for O(1) lookup
        $found_required = [];
        $self = [];
        foreach ($fields as $k => $v) {
            if (is_null($v)) {
                unset($fields[$k]);
            }
        }
        foreach ($fields as $k => $v) {
            $k = trim(strtolower($k));
            if (isset($required_set[$k])) {
                $found_required[$k] = true;  // Use hash set instead of array
            }
            $error = null;
            switch ($k) {
                case 'call':
                case 'operator':
                    $self[] = $v;
                    if (!preg_match('|^(?=.*[A-Za-z])(?=.*[0-9]).+$|', $v) || (preg_match('/^(.)\1*$/', $v) === 1)) {
                        $error = $k;
                    }
                    break;
                case 'station_callsign':
                    if (!preg_match('|^(?=.*[A-Za-z])(?=.*[0-9]).+$|', $v) || (preg_match('/^(.)\1*$/', $v) === 1)) {
                        $error = $k;
                    }
                    break;
                case 'age':
                    if (! is_numeric($v) || $v < 0 || $v > 120) {
                        $error = $k;
                    }
                    break;
                case 'ant_az':
                    if (! is_numeric($v) || $v < 0 || $v > 360) {
                        $error = $k;
                    }
                    break;
                case 'ant_el':
                    if (! is_numeric($v) || $v < -90 || $v > 90) {
                        $error = $k;
                    }
                    break;
                case 'ant_path':
                    if (! Spec::isAntPath($v)) {
                        $error = $k;
                    }
                    break;
                case 'arrl_sect':
                case 'my_arrl_sect':
                    if (! Spec::isArrlSection($v)) {
                        $error = $k;
                    }
                    break;
                case 'a_index':
                    if (! is_numeric($v) || $v < 0 || $v > 400) {
                        $error = $k;
                    }
                    break;
                case 'distance':
                case 'max_bursts':
                case 'rx_pwr':
                case 'tx_pwr':
                case 'srx':
                case 'stx':
                case 'my_fists':
                case 'my_iota_island_id':
                case 'ten_ten':
                case 'uksmg':
                    if (! is_numeric($v) || $v < 0) {
                        $error = $k;
                    }
                    break;
                case 'altitude':
                case 'my_altitude':
                    if (! is_numeric($v)) {
                        $error = $k;
                    }
                    break;
                case 'k_index':
                    if (! is_numeric($v) || $v < 0 || $v > 9) {
                        $error = $k;
                    }
                    break;
                case 'my_cq_zone':
                    if (! is_numeric($v) || $v < 1 || $v > 40) {
                        $error = $k;
                    }
                    break;
                case 'nr_bursts':
                case 'nr_pings':
                    if (! is_int($v)) {
                        $error = $k;
                    }
                    break;
                case 'my_itu_zone':
                    if (! is_numeric($v) || $v < 1 || $v > 90) {
                        $error = $k;
                    }
                    break;
                case 'sfi':
                    if (! is_numeric($v) || $v < 0 || $v > 300) {
                        $error = $k;
                    }
                    break;
                case 'freq':
                    if (! Spec::isFreq($v, (array_key_exists('band', $fields) ? $fields['band'] : null))) {
                        $error = $k;
                    }
                    break;
                case 'freq_rx':
                    if (! Spec::isFreq($v, (array_key_exists('band_rx', $fields) ? $fields['band_rx'] : null))) {
                        $error = $k;
                    }
                    break;
                case 'band':
                case 'band_rx':
                    if (! Spec::isBand($v)) {
                        $error = $k;
                    }
                    break;
                case 'mode':
                    if (! Spec::isMode($v)) {
                        $error = $k;
                    }
                    break;
                case 'dcl_qslrdate':
                case 'dcl_qslsdate':
                case 'eqsl_qslrdate':
                case 'eqsl_qslsdate':
                case 'hamlogeu_qso_upload_date':
                case 'hamqth_qso_upload_date':
                case 'hrdlog_qso_upload_date':
                case 'lotw_qslrdate':
                case 'lotw_qslsdate':
                case 'qrzcom_qso_download_date':
                case 'qrzcom_qso_upload_date':
                case 'qslrdate':
                case 'qslsdate':
                case 'qso_date':
                case 'qso_date_off':
                    if (! Spec::isDate($v)) {
                        $error = $k;
                    }
                    break;
                case 'time_off':
                case 'time_on':
                    if (! Spec::isTime($v)) {
                        $error = $k;
                    }
                    break;
                case 'submode':
                    if (! Spec::isSubMode($v)) {
                        $error = $k;
                    }
                    break;
                case 'gridsquare':
                case 'my_gridsquare':
                    if (! Spec::isMaidenhead($v)) {
                        $error = $k;
                    }
                    break;
                case 'vucc_grids':
                case 'my_vucc_grids':
                    $vs = explode(',', $v);
                    foreach ($vs as $v1) {
                        if (! Spec::isMaidenhead(trim($v1))) {
                            $error = $k;
                            break;
                        }
                    }
                    break;
                case 'my_lat':
                case 'lat':
                case 'my_lon':
                case 'lon':
                case 'rst_rcvd':
                case 'rst_sent':
                case 'pota_ref':
                case 'my_pota_ref':
                    break;
                case 'clublog_qso_upload_status':
                case 'hamlogeu_qso_upload_status':
                case 'hamqth_qso_upload_status':
                case 'hrdlog_qso_upload_status':
                case 'qrzcom_qso_upload_status':
                    if (! Spec::isQsoUpload($v)) {
                        $error = $k;
                    }
                    break;
                case 'qrzcom_qso_download_status':
                    if (! Spec::isQsoDownload($v)) {
                        $error = $k;
                    }
                    break;
                case 'dcl_qsl_rcvd':
                case 'eqsl_qsl_rcvd':
                case 'lotw_qsl_rcvd':
                case 'qsl_rcvd':
                    if (! Spec::isQsoRcvd($v)) {
                        $error = $k;
                    }
                    break;
                case 'dcl_qsl_sent':
                case 'eqsl_qsl_sent':
                case 'lotw_qsl_sent':
                case 'qsl_sent':
                    if (! Spec::isQsoSent($v)) {
                        $error = $k;
                    }
                    break;
                case 'qsl_rcvd_via':
                case 'qsl_sent_via':
                    if (! Spec::isQslVia($v)) {
                        $error = $k;
                    }
                    break;
                case 'cont':
                    if (! Spec::isContinent($v)) {
                        $error = $k;
                    }
                    break;
                case 'dxcc':
                case 'my_dxcc':
                    if (! Spec::isDxcc($v)) {
                        $error = $k;
                    }
                    break;
                case 'prop_mode':
                    if (! Spec::isPropagation($v)) {
                        $error = $k;
                    }
                    break;
                case 'pota_park_ref':
                case 'pota_my_park_ref':
                    if (! Spec::isPotaRef($v)) {
                        $error = $k;
                    }
                    break;
                case 'sota_ref':
                case 'my_sota_ref':
                    if (! Spec::isSotaRef($v)) {
                        $error = $k;
                    }
                    break;
                case 'iota':
                case 'my_iota':
                    if (! Spec::isIotaRef($v)) {
                        $error = $k;
                    }
                    break;
                case 'wwff_ref':
                case 'my_wwff_ref':
                    if (! Spec::isWwffRef($v)) {
                        $error = $k;
                    }
                    break;
            }
            if (! empty($error)) {
                $errors[] = $error;
            }
        }
        if (count(array_unique($self)) != 2) {
            $errors[] = '@self';
        }
        if (count($found_required) != count($required)) {
            foreach ($required as $k) {
                if (! isset($found_required[$k])) {
                    $errors[] = $k;
                }
            }
        }

        return empty($errors) ? true : $errors;
    }

    public static function duration(array $entries): bool
    {
        $first = time();
        $last = 0;
        foreach ($entries as $entry) {
            if (! isset($entry['qso_date']) || ! isset($entry['time_on'])) {
                continue;
            }
            $stamp = strtotime($entry['qso_date'] . ' ' . $entry['time_on']);
            if ($stamp < $first) {
                $first = $stamp;
            } elseif ($stamp > $last) {
                $last = $stamp;
            }
        }
        $diff = ($last - $first);

        return $diff > 0 && (count($entries) / $diff) < 5;
    }
}
