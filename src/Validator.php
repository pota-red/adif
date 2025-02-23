<?php

namespace Pota\Adif;

class Validator {

    public static function entry(array $fields) : bool|array {
        $errors = [];
        foreach ($fields as $k => $v) {
            $k = trim(strtolower($k));
            switch ($k) {
                case 'age':
                    if (!is_numeric($v) || $v < 0 || $v > 120) {
                        $errors[] = $k;
                    }
                    break;
                case 'ant_az':
                    if (!is_numeric($v) || $v < 0 || $v > 360) {
                        $errors[] = $k;
                    }
                    break;
                case 'ant_el':
                    if (!is_numeric($v) || $v < -90 || $v > 90) {
                        $errors[] = $k;
                    }
                    break;
                case 'ant_path':
                    if (!Spec::isAntPath($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'arrl_sect':
                case 'my_arrl_sect':
                    if (!Spec::isArrlSection($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'a_index':
                    if (!is_numeric($v) || $v < 0 || $v > 400) {
                        $errors[] = $k;
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
                    if (!is_numeric($v) || $v < 0) {
                        $errors[] = $k;
                    }
                    break;
                case 'altitude':
                case 'my_altitude':
                    if (!is_numeric($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'k_index':
                    if (!is_numeric($v) || $v < 0 || $v > 9) {
                        $errors[] = $k;
                    }
                    break;
                case 'my_cq_zone':
                    if (!is_numeric($v) || $v < 1 || $v > 40) {
                        $errors[] = $k;
                    }
                    break;
                case 'nr_bursts':
                case 'nr_pings':
                    if (!is_integer($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'my_itu_zone':
                    if (!is_numeric($v) || $v < 1 || $v > 90) {
                        $errors[] = $k;
                    }
                    break;
                case 'sfi':
                    if (!is_numeric($v) || $v < 0 || $v > 300) {
                        $errors[] = $k;
                    }
                    break;
                case 'freq':
                    if (!Spec::isFreq($v, (array_key_exists('band', $fields) ? $fields['band'] : null))) {
                        $errors[] = $k;
                    }
                    break;
                case 'freq_rx':
                    if (!Spec::isFreq($v, (array_key_exists('band_rx', $fields) ? $fields['band_rx'] : null))) {
                        $errors[] = $k;
                    }
                    break;
                case 'band':
                case 'band_rx':
                    if (!Spec::isBand($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'mode':
                    if (!Spec::isMode($v)) {
                        $errors[] = $k;
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
                    if (!Spec::isDate($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'time_off':
                case 'time_on':
                    if (!Spec::isTime($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'submode':
                    if (!Spec::isSubMode($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'gridsquare':
                case 'my_gridsquare':
                    if (!Spec::isMaidenhead($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'vucc_grids':
                case 'my_vucc_grids':
                    $vs = explode(',', $v);
                    foreach ($vs as $v1) {
                        if (!Spec::isMaidenhead(trim($v1))) {
                            $errors[] = $k;
                            break;
                        }
                    }
                    break;
                case 'iota':
                case 'my_iota':
                    if (!Spec::isIotaRef($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'rst_rcvd':
                case 'rst_sent':
                    if (!Spec::isRst($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'wwff_ref':
                case 'my_wwff_ref':
                    if (!Spec::isWwffRef($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'lat':
                case 'my_lat':
                    if (!Spec::isLat($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'lon':
                case 'my_lon':
                    if (!Spec::isLon($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'clublog_qso_upload_status':
                case 'hamlogeu_qso_upload_status':
                case 'hamqth_qso_upload_status':
                case 'hrdlog_qso_upload_status':
                case 'qrzcom_qso_upload_status':
                    if (!Spec::isQsoUpload($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'qrzcom_qso_download_status':
                    if (!Spec::isQsoDownload($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'dcl_qsl_rcvd':
                case 'eqsl_qsl_rcvd':
                case 'lotw_qsl_rcvd':
                case 'qsl_rcvd':
                    if (!Spec::isQsoRcvd($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'dcl_qsl_sent':
                case 'eqsl_qsl_sent':
                case 'lotw_qsl_sent':
                case 'qsl_sent':
                    if (!Spec::isQsoSent($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'qsl_rcvd_via':
                case 'qsl_sent_via':
                    if (!Spec::isQslVia($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'cont':
                    if (!Spec::isContinent($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'dxcc':
                case 'my_dxcc':
                    if (!Spec::isDxcc($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'prop_mode':
                    if (!Spec::isPropagation($v)) {
                        $errors[] = $k;
                    }
                    break;
                case 'pota_ref':
                case 'my_pota_ref':
                    if (!Spec::isPotaRef($v)) {
                        $errors[] = $k;
                    }
                    break;
            }
        }
        return empty($errors) ? true : $errors;
    }

}

