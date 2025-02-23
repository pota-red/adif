<?php

namespace RFingAround\Adif;

class Spec {

    public static array $enum_field = ['address', 'address_intl', 'age', 'altitude', 'ant_az', 'ant_el', 'ant_path', 'arrl_sect', 'award_granted',
        'award_submitted', 'a_index', 'band', 'band_rx', 'call', 'check', 'class', 'clublog_qso_upload_date', 'clublog_qso_upload_status', 'cnty',
        'cnty_alt', 'comment', 'comment_intl', 'cont', 'contacted_op', 'contest_id', 'country', 'country_intl', 'cqz', 'credit_submitted',
        'credit_granted', 'darc_dok', 'dcl_qslrdate', 'dcl_qslsdate', 'dcl_qsl_rcvd', 'dcl_qsl_sent', 'distance', 'dxcc', 'email', 'eq_call',
        'eqsl_qslrdate', 'eqsl_qslsdate', 'eqsl_qsl_rcvd', 'eqsl_qsl_sent', 'fists', 'fists_cc', 'force_init', 'freq', 'freq_rx', 'gridsquare',
        'gridsquare_ext', 'guest_op', 'hamlogeu_qso_upload_date', 'hamlogeu_qso_upload_status', 'hamqth_qso_upload_date', 'hamqth_qso_upload_status',
        'hrdlog_qso_upload_date', 'hrdlog_qso_upload_status', 'iota', 'iota_island_id', 'ituz', 'k_index', 'lat', 'lon', 'lotw_qslrdate',
        'lotw_qslsdate', 'lotw_qsl_rcvd', 'lotw_qsl_sent', 'max_bursts', 'mode', 'morse_key_info', 'morse_key_type', 'ms_shower', 'my_altitude',
        'my_antenna', 'my_antenna_intl', 'my_arrl_sect', 'my_city', 'my_city_intl', 'my_cnty', 'my_cnty_alt', 'my_country', 'my_country_intl',
        'my_cq_zone', 'my_darc_dok', 'my_dxcc', 'my_fists', 'my_gridsquare', 'my_gridsquare_ext', 'my_iota', 'my_iota_island_id', 'my_itu_zone',
        'my_lat', 'my_lon', 'my_morse_key_info', 'my_morse_key_type', 'my_name', 'my_name_intl', 'my_postal_code', 'my_postal_code_intl', 'my_pota_ref',
        'my_rig', 'my_rig_intl', 'my_sig', 'my_sig_intl', 'my_sig_info', 'my_sig_info_intl', 'my_sota_ref', 'my_state', 'my_street', 'my_street_intl',
        'my_usaca_counties', 'my_vucc_grids', 'my_wwff_ref', 'name', 'name_intl', 'notes', 'notes_intl', 'nr_bursts', 'nr_pings', 'operator',
        'owner_callsign', 'pfx', 'pota_ref', 'precedence', 'prop_mode', 'public_key', 'qrzcom_qso_download_date', 'qrzcom_qso_download_status',
        'qrzcom_qso_upload_date', 'qrzcom_qso_upload_status', 'qslmsg', 'qslmsg_intl', 'qslmsg_rcvd', 'qslrdate', 'qslsdate', 'qsl_rcvd', 'qsl_rcvd_via',
        'qsl_sent', 'qsl_sent_via', 'qsl_via', 'qso_complete', 'qso_date', 'qso_date_off', 'qso_random', 'qth', 'qth_intl', 'region', 'rig', 'rig_intl',
        'rst_rcvd', 'rst_sent', 'rx_pwr', 'sat_mode', 'sat_name', 'sfi', 'sig', 'sig_intl', 'sig_info', 'sig_info_intl', 'silent_key', 'skcc', 'sota_ref',
        'srx', 'srx_string', 'state', 'station_callsign', 'stx', 'stx_string', 'submode', 'swl', 'ten_ten', 'time_off', 'time_on', 'tx_pwr', 'uksmg',
        'usaca_counties', 've_prov', 'vucc_grids', 'web', 'wwff_ref'];

    public static array $enum_band = [
        '1.25CM' => [24000, 24250], '1.25M' => [222, 225], '10M' => [28, 29.7], '12M' => [24.89, 24.99],
        '13CM' => [2300, 2450], '15M' => [21, 21.45], '160M' => [1.8, 2], '17M' => [18.068, 18.168], '1MM' => [241000, 250000],
        '2.5MM' => [119980, 123000], '20M' => [14, 14.35], '2190M' => [.1357, .1378], '23CM' => [1240, 1300], '2M' => [144, 148],
        '2MM' => [134000, 149000], '30M' => [10.1, 10.15], '33CM' => [902, 928], '3CM' => [10000, 10500], '40M' => [7, 7.3],
        '4M' => [70, 71], '4MM' => [75500, 81000], '560M' => [.501, .503], '60M' => [5.06, 5.45], '630M' => [.472, .479],
        '6CM' => [5650, 5925], '6M' => [50, 54], '6MM' => [47000, 47200], '70CM' => [420, 450], '80M' => [3.5, 4],
        '9CM' => [3300, 3500], 'SUBMM' => [300000, 7500000], '5MM' => [54.000001, 69.9], '8MM' => [40, 45]];

    public static array $enum_mode = [
        'AM' => [],
        'ARDOP' => [],
        'ATV' => [],
        'CHIP' => ['CHIP64', 'CHIP128'],
        'CLO' => [],
        'CONTESTI' => [],
        'CW' => ['PCW'],
        'DIGITALVOICE' => ['C4FM', 'DMR', 'DSTAR', 'FREEDV', 'M17'],
        'DOMINO' => ['DOM-M', 'DOM4', 'DOM5', 'DOM8', 'DOM11', 'DOM16', 'DOM22', 'DOM44', 'DOM88', 'DOMINOEX', 'DOMINOF'],
        'DYNAMIC' => ['VARA HF', 'VARA SATELLITE', 'VARA FM 1200', 'VARA FM 9600'],
        'FAX' => [],
        'FM' => [],
        'FSK441' => [],
        'FT8' => [],
        'HELL' => ['FMHELL', 'FSKHELL', 'HELL80', 'HELLX5', 'HELLX9', 'HFSK', 'PSKHELL', 'SLOWHELL'],
        'ISCAT' => ['ISCAT-A', 'ISCAT-B'],
        'JT4' => ['JT4A', 'JT4B', 'JT4C', 'JT4D', 'JT4E', 'JT4F', 'JT4G'],
        'JT6M' => [],
        'JT9' => ['JT9-1', 'JT9-2', 'JT9-5', 'JT9-10', 'JT9-30', 'JT9A', 'JT9B', 'JT9C', 'JT9D', 'JT9E', 'JT9E FAST', 'JT9F', 'JT9F FAST', 'JT9G', 'JT9G FAST', 'JT9H', 'JT9H FAST'],	 
        'JT44' => [],
        'JT65' => ['JT65A', 'JT65B', 'JT65B2', 'JT65C', 'JT65C2'],	 
        'MFSK' => ['FSQCALL', 'FST4', 'FST4W', 'FT4', 'JS8', 'JTMS', 'MFSK4', 'MFSK8', 'MFSK11', 'MFSK16', 'MFSK22', 'MFSK31', 'MFSK32', 'MFSK64', 'MFSK64L', 'MFSK128', 'MFSK128L', 'Q65'],	 
        'MSK144' => [],
        'MT63' => [],
        'OLIVIA' => ['OLIVIA 4/125', 'OLIVIA 4/250', 'OLIVIA 8/250', 'OLIVIA 8/500', 'OLIVIA 16/500', 'OLIVIA 16/1000', 'OLIVIA 32/1000'],
        'OPERA' => ['OPERA-BEACON', 'OPERA-QSO'],
        'PAC' => ['PAC2', 'PAC3', 'PAC4'],
        'PAX' => ['PAX2'],
        'PHONE' => [], // POTA specific, not ADIF compliant, used for legacy importer only
        'PKT' => [],
        'PSK' => ['8PSK125', '8PSK125F', '8PSK125FL', '8PSK250', '8PSK250F', '8PSK250FL', '8PSK500', '8PSK500F', '8PSK1000', '8PSK1000F', '8PSK1200F', 'FSK31', 'PSK10', 'PSK31', 'PSK63', 'PSK63F', 'PSK63RC4', 'PSK63RC5', 'PSK63RC10', 'PSK63RC20', 'PSK63RC32', 'PSK125', 'PSK125C12', 'PSK125R', 'PSK125RC10', 'PSK125RC12', 'PSK125RC16', 'PSK125RC4', 'PSK125RC5', 'PSK250', 'PSK250C6', 'PSK250R', 'PSK250RC2', 'PSK250RC3', 'PSK250RC5', 'PSK250RC6', 'PSK250RC7', 'PSK500', 'PSK500C2', 'PSK500C4', 'PSK500R', 'PSK500RC2', 'PSK500RC3', 'PSK500RC4', 'PSK800C2', 'PSK800RC2', 'PSK1000', 'PSK1000C2', 'PSK1000R', 'PSK1000RC2', 'PSKAM10', 'PSKAM31', 'PSKAM50', 'PSKFEC31', 'QPSK31', 'QPSK63', 'QPSK125','QPSK250', 'QPSK500', 'SIM31'],
        'PSK2K' => [],
        'Q15' => [],
        'QRA64' => ['QRA64A', 'QRA64B', 'QRA64C', 'QRA64D', 'QRA64E'],
        'ROS' => ['ROS-EME', 'ROS-HF', 'ROS-MF'],
        'RTTY' => ['ASCI'],	 
        'RTTYM' => [],
        'SSB' => ['LSB', 'USB'],
        'SSTV' => [],
        'T10' => [],
        'THOR' => ['THOR-M', 'THOR4', 'THOR5', 'THOR8', 'THOR11', 'THOR16', 'THOR22', 'THOR25X4', 'THOR50X1', 'THOR50X2', 'THOR100'],
        'THRB' => ['THRBX', 'THRBX1', 'THRBX2', 'THRBX4', 'THROB1', 'THROB2', 'THROB4'],
        'TOR' => ['AMTORFEC', 'GTOR', 'NAVTEX', 'SITORB'],
        'V4' => [],
        'VOI' => [],
        'WINMOR' => [],
        'WSPR' => [] 
    ];

    public static array $enum_dxcc_entity = [0 => 'None', 1 => 'CANADA', 3 => 'AFGHANISTAN', 4 => 'AGALEGA & ST. BRANDON IS.', 5 => 'ALAND IS.', 6 => 'ALASKA',
        7 => 'ALBANIA', 9 => 'AMERICAN SAMOA', 10 => 'AMSTERDAM & ST. PAUL IS.', 11 => 'ANDAMAN & NICOBAR IS.', 12 => 'ANGUILLA', 13 => 'ANTARCTICA', 14 => 'ARMENIA',
        15 => 'ASIATIC RUSSIA', 16 => 'NEW ZEALAND SUBANTARCTIC ISLANDS', 17 => 'AVES I.', 18 => 'AZERBAIJAN', 20 => 'BAKER & HOWLAND IS.', 21 => 'BALEARIC IS.',
        22 => 'PALAU', 24 => 'BOUVET', 27 => 'BELARUS', 29 => 'CANARY IS.', 31 => 'C. KIRIBATI (BRITISH PHOENIX IS.)', 32 => 'CEUTA & MELILLA', 33 => 'CHAGOS IS.',
        34 => 'CHATHAM IS.', 35 => 'CHRISTMAS I.', 36 => 'CLIPPERTON I.', 37 => 'COCOS I.', 38 => 'COCOS (KEELING) IS.', 40 => 'CRETE', 41 => 'CROZET I.',
        43 => 'DESECHEO I.', 45 => 'DODECANESE', 46 => 'EAST MALAYSIA', 47 => 'EASTER I.', 48 => 'E. KIRIBATI (LINE IS.)', 49 => 'EQUATORIAL GUINEA', 50 => 'MEXICO',
        51 => 'ERITREA', 52 => 'ESTONIA', 53 => 'ETHIOPIA', 54 => 'EUROPEAN RUSSIA', 56 => 'FERNANDO DE NORONHA', 60 => 'BAHAMAS', 61 => 'FRANZ JOSEF LAND',
        62 => 'BARBADOS', 63 => 'FRENCH GUIANA', 64 => 'BERMUDA', 65 => 'BRITISH VIRGIN IS.', 66 => 'BELIZE', 69 => 'CAYMAN IS.', 70 => 'CUBA', 71 => 'GALAPAGOS IS.',
        72 => 'DOMINICAN REPUBLIC', 74 => 'EL SALVADOR', 75 => 'GEORGIA', 76 => 'GUATEMALA', 77 => 'GRENADA', 78 => 'HAITI', 79 => 'GUADELOUPE', 80 => 'HONDURAS',
        82 => 'JAMAICA', 84 => 'MARTINIQUE', 86 => 'NICARAGUA', 88 => 'PANAMA', 89 => 'TURKS & CAICOS IS.', 90 => 'TRINIDAD & TOBAGO', 91 => 'ARUBA',
        94 => 'ANTIGUA & BARBUDA', 95 => 'DOMINICA', 96 => 'MONTSERRAT', 97 => 'ST. LUCIA', 98 => 'ST. VINCENT', 99 => 'GLORIOSO IS.', 100 => 'ARGENTINA',
        103 => 'GUAM', 104 => 'BOLIVIA', 105 => 'GUANTANAMO BAY', 106 => 'GUERNSEY', 107 => 'GUINEA', 108 => 'BRAZIL', 109 => 'GUINEA-BISSAU', 110 => 'HAWAII',
        111 => 'HEARD I.', 112 => 'CHILE', 114 => 'ISLE OF MAN', 116 => 'COLOMBIA', 117 => 'ITU HQ', 118 => 'JAN MAYEN', 120 => 'ECUADOR', 122 => 'JERSEY',
        123 => 'JOHNSTON I.', 124 => 'JUAN DE NOVA, EUROPA', 125 => 'JUAN FERNANDEZ IS.', 126 => 'KALININGRAD', 129 => 'GUYANA', 130 => 'KAZAKHSTAN',
        131 => 'KERGUELEN IS.', 132 => 'PARAGUAY', 133 => 'KERMADEC IS.', 135 => 'KYRGYZSTAN', 136 => 'PERU', 137 => 'REPUBLIC OF KOREA', 138 => 'KURE I.',
        140 => 'SURINAME', 141 => 'FALKLAND IS.', 142 => 'LAKSHADWEEP IS.', 143 => 'LAOS', 144 => 'URUGUAY', 145 => 'LATVIA', 146 => 'LITHUANIA', 147 => 'LORD HOWE I.',
        148 => 'VENEZUELA', 149 => 'AZORES', 150 => 'AUSTRALIA', 152 => 'MACAO', 153 => 'MACQUARIE I.', 157 => 'NAURU', 158 => 'VANUATU', 159 => 'MALDIVES',
        160 => 'TONGA', 161 => 'MALPELO I.', 162 => 'NEW CALEDONIA', 163 => 'PAPUA NEW GUINEA', 165 => 'MAURITIUS', 166 => 'MARIANA IS.', 167 => 'MARKET REEF',
        168 => 'MARSHALL IS.', 169 => 'MAYOTTE', 170 => 'NEW ZEALAND', 171 => 'MELLISH REEF', 172 => 'PITCAIRN I.', 173 => 'MICRONESIA', 174 => 'MIDWAY I.',
        175 => 'FRENCH POLYNESIA', 176 => 'FIJI', 177 => 'MINAMI TORISHIMA', 179 => 'MOLDOVA', 180 => 'MOUNT ATHOS', 181 => 'MOZAMBIQUE', 182 => 'NAVASSA I.',
        185 => 'SOLOMON IS.', 187 => 'NIGER', 188 => 'NIUE', 189 => 'NORFOLK I.', 190 => 'SAMOA', 191 => 'NORTH COOK IS.', 192 => 'OGASAWARA', 195 => 'ANNOBON I.',
        197 => 'PALMYRA & JARVIS IS.', 199 => 'PETER 1 I.', 201 => 'PRINCE EDWARD & MARION IS.', 202 => 'PUERTO RICO', 203 => 'ANDORRA', 204 => 'REVILLAGIGEDO',
        205 => 'ASCENSION I.', 206 => 'AUSTRIA', 207 => 'RODRIGUES I.', 209 => 'BELGIUM', 211 => 'SABLE I.', 212 => 'BULGARIA', 213 => 'SAINT MARTIN', 214 => 'CORSICA',
        215 => 'CYPRUS', 216 => 'SAN ANDRES & PROVIDENCIA', 217 => 'SAN FELIX & SAN AMBROSIO', 219 => 'SAO TOME & PRINCIPE', 221 => 'DENMARK', 222 => 'FAROE IS.',
        223 => 'ENGLAND', 224 => 'FINLAND', 225 => 'SARDINIA', 227 => 'FRANCE', 230 => 'FEDERAL REPUBLIC OF GERMANY', 232 => 'SOMALIA', 233 => 'GIBRALTAR',
        234 => 'SOUTH COOK IS.', 235 => 'SOUTH GEORGIA I.', 236 => 'GREECE', 237 => 'GREENLAND', 238 => 'SOUTH ORKNEY IS.', 239 => 'HUNGARY', 240 => 'SOUTH SANDWICH IS.',
        241 => 'SOUTH SHETLAND IS.', 242 => 'ICELAND', 245 => 'IRELAND', 246 => 'SOVEREIGN MILITARY ORDER OF MALTA', 247 => 'SPRATLY IS.', 248 => 'ITALY',
        249 => 'ST. KITTS & NEVIS', 250 => 'ST. HELENA', 251 => 'LIECHTENSTEIN', 252 => 'ST. PAUL I.', 253 => 'ST. PETER & ST. PAUL ROCKS', 254 => 'LUXEMBOURG',
        256 => 'MADEIRA IS.', 257 => 'MALTA', 259 => 'SVALBARD', 260 => 'MONACO', 262 => 'TAJIKISTAN', 263 => 'NETHERLANDS', 265 => 'NORTHERN IRELAND', 266 => 'NORWAY',
        269 => 'POLAND', 270 => 'TOKELAU IS.', 272 => 'PORTUGAL', 273 => 'TRINDADE & MARTIM VAZ IS.', 274 => 'TRISTAN DA CUNHA & GOUGH I.', 275 => 'ROMANIA',
        276 => 'TROMELIN I.', 277 => 'ST. PIERRE & MIQUELON', 278 => 'SAN MARINO', 279 => 'SCOTLAND', 280 => 'TURKMENISTAN', 281 => 'SPAIN', 282 => 'TUVALU',
        283 => 'UK SOVEREIGN BASE AREAS ON CYPRUS', 284 => 'SWEDEN', 285 => 'VIRGIN IS.', 286 => 'UGANDA', 287 => 'SWITZERLAND', 288 => 'UKRAINE',
        289 => 'UNITED NATIONS HQ', 291 => 'UNITED STATES OF AMERICA', 292 => 'UZBEKISTAN', 293 => 'VIET NAM', 294 => 'WALES', 295 => 'VATICAN', 296 => 'SERBIA',
        297 => 'WAKE I.', 298 => 'WALLIS & FUTUNA IS.', 299 => 'WEST MALAYSIA', 301 => 'W. KIRIBATI (GILBERT IS. )', 302 => 'WESTERN SAHARA', 303 => 'WILLIS I.',
        304 => 'BAHRAIN', 305 => 'BANGLADESH', 306 => 'BHUTAN', 308 => 'COSTA RICA', 309 => 'MYANMAR', 312 => 'CAMBODIA', 315 => 'SRI LANKA', 318 => 'CHINA',
        321 => 'HONG KONG', 324 => 'INDIA', 327 => 'INDONESIA', 330 => 'IRAN', 333 => 'IRAQ', 336 => 'ISRAEL', 339 => 'JAPAN', 342 => 'JORDAN',
        344 => 'DEMOCRATIC PEOPLE\'S REP. OF KOREA', 345 => 'BRUNEI DARUSSALAM', 348 => 'KUWAIT', 354 => 'LEBANON', 363 => 'MONGOLIA', 369 => 'NEPAL', 370 => 'OMAN',
        372 => 'PAKISTAN', 375 => 'PHILIPPINES', 376 => 'QATAR', 378 => 'SAUDI ARABIA', 379 => 'SEYCHELLES', 381 => 'SINGAPORE', 382 => 'DJIBOUTI', 384 => 'SYRIA',
        386 => 'TAIWAN', 387 => 'THAILAND', 390 => 'TURKEY', 391 => 'UNITED ARAB EMIRATES', 400 => 'ALGERIA', 401 => 'ANGOLA', 402 => 'BOTSWANA', 404 => 'BURUNDI',
        406 => 'CAMEROON', 408 => 'CENTRAL AFRICA', 409 => 'CAPE VERDE', 410 => 'CHAD', 411 => 'COMOROS', 412 => 'REPUBLIC OF THE CONGO',
        414 => 'DEMOCRATIC REPUBLIC OF THE CONGO', 416 => 'BENIN', 420 => 'GABON', 422 => 'THE GAMBIA', 424 => 'GHANA', 428 => 'COTE D\'IVOIRE', 430 => 'KENYA',
        432 => 'LESOTHO', 434 => 'LIBERIA', 436 => 'LIBYA', 438 => 'MADAGASCAR', 440 => 'MALAWI', 442 => 'MALI', 444 => 'MAURITANIA', 446 => 'MOROCCO', 450 => 'NIGERIA',
        452 => 'ZIMBABWE', 453 => 'REUNION I.', 454 => 'RWANDA', 456 => 'SENEGAL', 458 => 'SIERRA LEONE', 460 => 'ROTUMA I.', 462 => 'REPUBLIC OF SOUTH AFRICA',
        464 => 'NAMIBIA', 466 => 'SUDAN', 468 => 'KINGDOM OF ESWATINI', 470 => 'TANZANIA', 474 => 'TUNISIA', 478 => 'EGYPT', 480 => 'BURKINA FASO', 482 => 'ZAMBIA',
        483 => 'TOGO', 489 => 'CONWAY REEF', 490 => 'BANABA I. (OCEAN I.)', 492 => 'YEMEN', 497 => 'CROATIA', 499 => 'SLOVENIA', 501 => 'BOSNIA-HERZEGOVINA',
        502 => 'NORTH MACEDONIA (REPUBLIC OF)', 503 => 'CZECH REPUBLIC', 504 => 'SLOVAK REPUBLIC', 505 => 'PRATAS I.', 506 => 'SCARBOROUGH REEF', 507 => 'TEMOTU PROVINCE',
        508 => 'AUSTRAL I.', 509 => 'MARQUESAS IS.', 510 => 'PALESTINE', 511 => 'TIMOR-LESTE', 512 => 'CHESTERFIELD IS.', 513 => 'DUCIE I.', 514 => 'MONTENEGRO',
        515 => 'SWAINS I.', 516 => 'SAINT BARTHELEMY', 517 => 'CURACAO', 518 => 'SINT MAARTEN', 519 => 'SABA & ST. EUSTATIUS', 520 => 'BONAIRE',
        521 => 'SOUTH SUDAN (REPUBLIC OF)', 522 => 'REPUBLIC OF KOSOVO'];

    public static array $enum_continent = ['NA' => 'North America', 'SA' => 'South America', 'EU' => 'Europe',
        'AF' => 'Africa', 'OC' => 'Oceania', 'AS' => 'Asia', 'AN' => 'Antarctica'];

    public static array $enum_antenna_path = ['G' => 'grayline', 'O' => 'other', 'S' => 'short path', 'L' => 'long path'];

    public static array $enum_arrl_section = ['AL' => 'Alabama', 'AK' => 'Alaska', 'AB' => 'Alberta', 'AR' => 'Arkansas', 'AZ' => 'Arizona',
        'BC' => 'British Columbia', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'EB' => 'East Bay', 'EMA' => 'Eastern Massachusetts',
        'ENY' => 'Eastern New York', 'EPA' => 'Eastern Pennsylvania', 'EWA' => 'Eastern Washington', 'GA' => 'Georgia', 'GH' => 'Golden Horseshoe',
        'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LAX' => 'Los Angeles',
        'LA' => 'Louisiana', 'ME' => 'Maine', 'MB' => 'Manitoba', 'MDC' => 'Maryland-DC', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
        'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NB' => 'New Brunswick', 'NH' => 'New Hampshire', 'NM' => 'New Mexico',
        'NLI' => 'New York City-Long Island', 'NL' => 'Newfoundland/Labrador', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'NTX' => 'North Texas',
        'NFL' => 'Northern Florida', 'NNJ' => 'Northern New Jersey', 'NNY' => 'Northern New York', 'NS' => 'Nova Scotia', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
        'ONE' => 'Ontario East', 'ONN' => 'Ontario North', 'ONS' => 'Ontario South', 'ORG' => 'Orange', 'OR' => 'Oregon', 'PAC' => 'Pacific',
        'PE' => 'Prince Edward Island', 'PR' => 'Puerto Rico', 'QC' => 'Quebec', 'RI' => 'Rhode Island', 'SV' => 'Sacramento Valley', 'SDG' => 'San Diego',
        'SF' => 'San Francisco', 'SJV' => 'San Joaquin Valley', 'SB' => 'Santa Barbara', 'SCV' => 'Santa Clara Valley', 'SK' => 'Saskatchewan',
        'SC' => 'South Carolina', 'SD' => 'South Dakota', 'STX' => 'South Texas', 'SFL' => 'Southern Florida', 'SNJ' => 'Southern New Jersey',
        'TN' => 'Tennessee', 'TER' => 'Territories', 'VI' => 'US Virgin Islands', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia',
        'WCF' => 'West Central Florida', 'WTX' => 'West Texas', 'WV' => 'West Virginia', 'WMA' => 'Western Massachusetts', 'WNY' => 'Western New York',
        'WPA' => 'Western Pennsylvania', 'WWA' => 'Western Washington', 'WI' => 'Wisconsin', 'WY' => 'Wyoming'];

    public static array $enum_qso_upload = ['Y' => 'uploaded/accepted', 'N' => 'not uploaded', 'M' => 'modified'];

    public static array $enum_qso_download = ['Y' => 'downloaded', 'N' => 'not downloaded', 'I' => 'ignore/invalid'];

    public static array $enum_qso_sent = ['Y' => 'yes', 'N' => 'no', 'R' => 'requested', 'Q' => 'queued', 'I' => 'ignore/invalid'];

    public static array $enum_qso_rcvd = ['Y' => 'yes', 'N' => 'no', 'R' => 'requested', 'v' => 'verified', 'I' => 'ignore/invalid'];

    public static array $enum_qsl_via = ['B' => 'bureau', 'd' => 'direct', 'e' => 'electronic', 'm' => 'manager'];

    public static array $enum_qsl_medium = ['CARD' => 'paper QSL card', 'EQSL' => 'eQSL.cc', 'LOTW' => 'ARRL Logbook of the World'];

    public static array $enum_propagation = ['AS' => 'Aircraft Scatter', 'AUE' => 'Aurora-E', 'AUR' => 'Aurora', 'BS' => 'Back scatter', 'ECH' => 'EchoLink',
        'EME' => 'Earth-Moon-Earth', 'ES' => 'Sporadic E', 'F2' => 'F2 Reflection', 'FAI' => 'Field Aligned Irregularities', 'GWAVE' => 'Ground Wave',
        'INTERNET' => 'Internet-assisted', 'ION' => 'Ionoscatter', 'IRL' => 'IRLP', 'LOS' => 'Line of Sight', 'MS' => 'Meteor scatter',
        'RPT' => 'Terrestrial or atmospheric repeater or transponder', 'RS' => 'Rain scatter', 'SAT' => 'Satellite', 'TEP' => 'Trans-equatorial',
        'TR' => 'Tropospheric ducting'];

    public static array $pota_fields = [
        'band', 'band_rx', 'call', 'cnty', 'freq', 'freq_rx', 'gridsquare', 'mode', 'my_antenna', 'my_gridsquare', 'my_lat', 'my_lon', 'my_pota_ref',
        'my_rig', 'my_sig', 'my_sig_info', 'my_state', 'operator', 'pota_ref', 'qso_date', 'rst_rcvd', 'rst_sent', 'rx_pwr', 'sat_mode', 'sat_name',
        'sig', 'sig_info', 'state', 'station_callsign', 'submode', 'time_on', 'tx_pwr', 'my_sig', 'my_sig_info'];

    public static array $base_fields = ['band', 'call', 'mode', 'operator', 'qso_date', 'time_on'];

    public static array $pota_unique = ['band', 'call', 'mode', 'my_pota_ref', 'my_sig_info', 'operator', 'pota_ref', 'qso_date', 'sig_info', 'submode'];

    public static function isField(string $text) : bool {
        return in_array(strtolower($text), self::$enum_field);
    }

    public static function isBand(string $text) : bool  {
        return array_key_exists(strtoupper($text), self::$enum_band);
    }

    public static function isMode(string $text) : bool {
        return array_key_exists(strtoupper($text), self::$enum_mode);
    }

    public static function isSubMode(string $text) : bool {
        $text = strtoupper($text);
        foreach (self::$enum_mode as $m => $s) {
            if (in_array($text, $s)) {
                return true;
            }
        }
        return false;
    }

    public static function isContinent(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_continent);
    }

    public static function isAntPath(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_antenna_path);
    }

    public static function isArrlSection(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_arrl_section);
    }

    public static function isQsoDownload(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_qso_download);
    }

    public static function isQsoUpload(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_qso_upload);
    }

    public static function isQsoRcvd(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_qso_rcvd);
    }

    public static function isQsoSent(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_qso_sent);
    }

    public static function isQslVia(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_qsl_via);
    }

    public static function isQslMedium(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_qsl_medium);
    }

    public static function isDxcc(string $text) : bool {
        return array_key_exists((integer)$text, self::$enum_dxcc_entity);
    }

    public static function isPropagation(string $text) : bool {
        return array_key_exists(trim(strtoupper($text)), self::$enum_propagation);
    }

    public static function isLat(string $text) : bool {
        return preg_match('/^[-]?(([0-8]?[0-9])\.(\d+))|(90(\.0+)?)$/', $text);
    }

    public static function isLon(string $text) : bool {
        return preg_match('/^[-]?((((1[0-7][0-9])|([0-9]?[0-9]))\.(\d+))|180(\.0+)?)$/', $text);
    }

    public static function isMaidenhead(string $text) : bool {
        $len = strlen($text);
        if ($len <= 12 && $len % 2 == 0) {
            $rs = ['[A-R]{2}', '[0-9]{2}', '[A-X]{2}', '[0-9]{2}', '[A-X]{2}', '[0-9]{2}'];
            return preg_match('/^' . implode('', array_slice($rs, 0, intval($len / 2))) . '$/', strtoupper($text));
        }
        return false;
    }

    public static function isRst(string $text) : bool {
        $len = strlen($text);
        if ($len >= 2 && $len <= 4) {
            $rs = ['[1-5][1-9nx]', '[1-9nx]', '[ackmsx]'];
            return preg_match('/^' . implode('', array_slice($rs, 0, $len - 1)) . '$/', strtolower($text));
        }
        return false;
    }

    public static function isDate(string $text) : bool {
        $data = preg_replace('/\D/', '', $text);
        if (strlen($data) == 8) {
            $y = intval(substr($data, 0, 4));
            $m = intval(substr($data, 4, 2));
            $d = intval(substr($data, 6, 2));
            return ($y >= 1930 && $y <= ((integer)date('Y') + 1) && $m >= 1 && $m <= 12 && $d >= 1 && $d <= 31);
        }
        return false;
    }

    public static function isTime(string $text) : bool {
        $data = preg_replace('/\D/', '', $text);
        if (strlen($text) == 4 || strlen($text) == 6) {
            $h = intval(substr($data, 0, 2));
            $m = intval(substr($data, 2, 2));
            $s = strlen($data) == 6 ? intval(substr($data, 4, 2)) : 0;
            return ($h >= 0 && $h <= 23 && $m >= 0 && $m <= 59 && $s >= 0 && $s <= 59);
        }
        return false;
    }

    public static function isFreq(string $text, string $band = null) : bool {
        $text = (float)$text;
        if (!empty($band) && array_key_exists($band, self::$enum_band)) {
            return $text >= self::$enum_band[$band][0] && $text <= self::$enum_band[$band][1];
        }
        foreach (self::$enum_band as $range) {
            if ($text >= $range[0] && $text <= $range[1]) {
                return true;
            }
        }
        return false;
    }

    public static function isPotaRef(string $text) : bool {
        return preg_match('/([A-z0-9]+-[0-9]{4,})(@([A-z0-9]+-[A-Z0-9]+))?/', $text);
    }

    public static function isIotaRef(string $text) : bool {
        $text = explode('-', trim($text), 2);
        return self::isContinent($text[0]) && is_numeric($text[1]);
    }

    public static function isWwffRef(string $text) : bool {
        return preg_match('/^([A-z0-9]{1,4}FF-[0-9]{4,})$/', $text);
    }

    public static function isSotaRef(string $text) : bool {
        return preg_match('/^([A-z0-9]+\/[A-Z0-9]+-[0-9]{1,3})$/', $text);
    }

}

