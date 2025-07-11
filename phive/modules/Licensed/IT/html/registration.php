<?php
$user = cuRegistration();
if ($_POST['extract_tax_code']) {
    exit(json_encode(lic('extractTaxCode', [$_POST['tax_code']], $user)));
}

if ($_GET['get_municipality_by_province_list']) {
    exit(json_encode(lic("getMunicipalityByProvinceList", [$_GET['province']], $user)));
}

if ($_GET['get_all_municipality_by_province_list']) {
    exit(json_encode(lic("getAllMunicipalityByProvinceList", [$_GET['province']], $user)));
}

if ($_GET['get_issuing_authority_list']) {
    exit(json_encode(lic("getIssuingAuthorityList", [$_GET['doc_type']], $user)));
}

if ($_GET['load_step2_data']) {
    exit(json_encode(lic("getStep2Data", [$user], $user)));
}