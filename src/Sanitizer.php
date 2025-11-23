<?php

namespace Pota\Adif;

class Sanitizer {

    public static function entry(array $fields) : array {
        if (array_key_exists('station_callsign', $fields) && !array_key_exists('operator', $fields)) {
            $fields['operator'] = $fields['station_callsign'];
        }
        if (array_key_exists('my_sig', $fields) && $fields['my_sig'] == 'pota' && array_key_exists('my_pota_ref', $fields) && array_key_exists('my_sig_info', $fields) && $fields['my_sig_info'] == $fields['my_pota_ref']) {
            unset($fields['my_sig'], $fields['my_sig_info']);
        }
        if (array_key_exists('my_sig', $fields) && array_key_exists('my_sig_info', $fields) && $fields['my_sig'] == 'pota' && !array_key_exists('my_pota_ref', $fields)) {
            $fields['my_pota_ref'] = $fields['my_sig_info'];
            unset($fields['my_sig'], $fields['my_sig_info']);
        }
        if (array_key_exists('sig', $fields) && array_key_exists('sig_info', $fields) && $fields['sig'] == 'pota' && !array_key_exists('pota_ref', $fields)) {
            $fields['pota_ref'] = $fields['sig_info'];
            unset($fields['sig'], $fields['sig_info']);
        }
        foreach ($fields as $k => $v) {
            $k = trim(strtolower($k));
            switch ($k) {
                case 'age':
                case 'ant_az':
                case 'tx_pwr':
                case 'ant_el':
                case 'a_index':
                case 'k_index':
                case 'max_bursts':
                case 'my_altitude':
                case 'rx_pwr':
                case 'sfi':
                case 'srx':
                case 'stx':
                case 'dxcc':
                case 'my_dxcc':
                    $v = (integer)$v;
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
                case 'band':
                case 'band_rx':
                case 'call':
                case 'pota_ref':
                case 'my_pota_ref':
                case 'sig_info':
                case 'my_sig_info':
                case 'operator':
                case 'station_callsign':
                case 'cnty':
                case 'submode':
                case 'state':
                case 'sig':
                case 'my_sig':
                case 'gridsquare':
                case 'my_gridsquare':
                case 'prop_mode':
                    $v = trim(strtoupper($v));
                    break;
                case 'mode':
                    $v = trim(strtoupper($v));
                    if (preg_match('/^(USB)|(LSB)$/', $v)) {
                        $fields['submode'] = $v;
                        $v = 'SSB';
                    }
                    $hash[$k] = $v;
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
                    $hash[$k] = $v;
                    break;
                case 'time_on':
                case 'time_off':
                    $v = str_pad(trim(preg_replace('/\D/', '', $v)), 6, '0');
                    break;
                case 'rst_rcvd':
                case 'rst_sent':
                    $v = trim(str_replace(['-','_',' '], '', $v));
                    break;
                    $v = substr(trim(preg_replace('/\D/', '', $v)), 0, 5);
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
                    $v = (boolean)$v;
                    break;
            }
            $fields[$k] = $v;
        }
        if (array_key_exists('band', $fields) && !Spec::isBand($fields['band']) && array_key_exists('freq', $fields)) {
            $fields['band'] = Spec::bandFromFreq($fields['freq']);
        }
        if (array_key_exists('band_rx', $fields) && !Spec::isBand($fields['band_rx']) && array_key_exists('freq_rx', $fields)) {
            $fields['band_rx'] = Spec::bandFromFreq($fields['freq_rx']);
        }
        return $fields;
    }

    public static function filter(array $fields, array $errors, array $optional) : array {
        foreach ($errors as $k => $v) {
            if (in_array($v, $optional)) {
                unset($fields[$v]);
                unset($errors[$k]);
            }
        }
        return [$fields, count($errors) == 0 ?? null];
    }

}

