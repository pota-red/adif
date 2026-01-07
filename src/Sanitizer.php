<?php

namespace Pota\Adif;

class Sanitizer
{
    public static function entry(array $fields): array
    {

        if (isset($fields['station_callsign']) && !isset($fields['operator'])) {
            $fields['operator'] = $fields['station_callsign'];
        }

        // prefer my_pota_ref and pota_ref over my_sig_info and sig_info
        // mangle my_sig_info and sig_info to end up with only my_pota_ref and pota_ref if these are pota refs
        // ^^ both above are done in morph unroll pota refs now

        foreach ($fields as $k => $v) {
            $k = trim(strtolower($k));
            switch ($k) {
                case 'tx_pwr':
                case 'rx_pwr':
                    $v = substr($v, 0, 10);
                    break;
                case 'age':
                case 'ant_az':
                case 'ant_el':
                case 'a_index':
                case 'k_index':
                case 'max_bursts':
                case 'my_altitude':
                case 'sfi':
                case 'srx':
                case 'stx':
                case 'dxcc':
                case 'my_dxcc':
                    $v = (int)$v;
                    break;
                case 'altitude':
                case 'distance':
                    $v = (float)$v;
                    break;
                case 'ant_path':
                case 'clublog_qso_upload_status':
                case 'dcl_qsl_rcvd':
                case 'dcl_qsl_sent':
                case 'eqsl_qsl_rcvd':
                case 'eqsl_qsl_sent':
                case 'hamlogeu_qso_upload_status':
                case 'hamqth_qso_upload_status':
                case 'hrdlog_qso_upload_status':
                case 'lotw_qsl_rcvd':
                case 'lotw_qsl_sent':
                case 'qrzcom_qso_download_status':
                case 'qrzcom_qso_upload_status':
                case 'qsl_rcvd':
                case 'qsl_sent':
                    if (strlen($v) > 1) {
                        $v = substr($v, 0, 1);
                    }
                    break;
                case 'pota_ref':
                case 'my_pota_ref':
                case 'pota_my_park_ref':
                case 'pota_my_location':
                case 'pota_park_ref':
                case 'pota_location':
                case 'sig_info':
                case 'my_sig_info':
                case 'cnty':
                case 'submode':
                case 'state':
                case 'my_state':
                case 'sig':
                case 'my_sig':
                case 'gridsquare':
                case 'my_gridsquare':
                case 'prop_mode':
                    $v = trim(strtoupper($v));
                    break;
                case 'call':
                case 'operator':
                case 'station_callsign':
                    $v = trim(strtoupper($v));
                    if (strlen($v) > 30) {
                        $v = substr($v, 0, 30);
                    }
                    break;
                case 'mode':
                    $v = trim(strtoupper($v));
                    if (preg_match('/^(USB)|(LSB)$/', $v)) {
                        $fields['submode'] = $v;
                        $v = 'SSB';
                    }
                    $hash[$k] = $v;
                    break;
                case 'band':
                case 'band_rx':
                    $v = substr($v, 0, 6);
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
                    $v = trim(preg_replace('/\D/', '', $v));
                    if (strlen($v) > 8) {
                        $v = substr($v, 0, 8);
                    }
                    $hash[$k] = $v;
                    break;
                case 'time_on':
                case 'time_off':
                    $v = str_pad(trim(preg_replace('/\D/', '', $v)), 6, '0');
                    if (strlen($v) > 6) {
                        $v = substr($v, 0, 6);
                    }
                    break;
                case 'rst_rcvd':
                case 'rst_sent':
                    $v = substr($v, 0, 8);
                    break;
                case 'freq':
                case 'freq_rx':
                    $v = trim(number_format((float)$v, 6, '.', ''));
                    break;
                case 'cont':
                    if (strlen($v) > 2) {
                        $v = substr($v, 0, 2);
                    }
                    break;
                case 'silent_key':
                case 'qso_random':
                case 'force_init':
                    $v = (bool)$v;
                    break;
                case 'lat':
                case 'lon':
                case 'my_lat':
                case 'my_lon':
                    $v = substr($v, 0, 16);
                    break;
            }
            $fields[$k] = $v;
        }
        if (isset($fields['band']) && !Spec::isBand($fields['band']) && isset($fields['freq'])) {
            $fields['band'] = Spec::bandFromFreq($fields['freq']);
        }
        if (isset($fields['band_rx']) && !Spec::isBand($fields['band_rx']) && isset($fields['freq_rx'])) {
            $fields['band_rx'] = Spec::bandFromFreq($fields['freq_rx']);
        }
        if (isset($fields['operator']) && isset($fields['station_callsign']) && $fields['operator'] == $fields['station_callsign']) {
            unset($fields['station_callsign']);
        }

        return $fields;
    }

    public static function filter(array $fields, array $errors, array $optional): array
    {
        $optional_set = array_flip($optional);  // Convert to hash set for O(1) lookup
        foreach ($errors as $k => $v) {
            if (isset($optional_set[$v])) {
                unset($fields[$v]);
                unset($errors[$k]);
            }
        }

        return [$fields, count($errors) == 0 ?? null];
    }
}
