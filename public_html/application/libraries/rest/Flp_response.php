<?php
 
 /**
 * Sentir Development
 *
 * @category   Sentir Web Development
 * @package    CRM - GATEWAY
 * @copyright  Copyright 2014-2016 Sentir Development
 * @license    http://sentir.solutions/license/
 * @version    1.0.15.10
 * @author     Ahmet GOUDENOGLU <ahmet.gudenoglu@sentir-development.com>
 */


class Flp_Response{
    public $isCountryMatch = '';
    public $isHighRiskCountry = '';
    public $distanceInKm = 0;
    public $distanceInMile = 0;
    public $ipCountry = '';
    public $ipRegion = '';
    public $ipCity = '';
    public $ipContinent = '';
    public $ipLatitude = '';
    public $ipLongitude = '';
    public $ipTimezone = '';
    public $ipElevation = '';
    public $ipDomain = '';
    public $ipMobileMnc = '';
    public $ipMobileMcc = '';
    public $ipMobileBrand = '';
    public $ipNetspeed = '';
    public $ipIspName = '';
    public $ipUsageType = '';
    public $isFreeEmail = '';
    public $isNewDomainName = '';
    public $isProxyIpAddress = '';
    public $isBinFound = '';
    public $isBinCountryMatch = '';
    public $isBinNameMatch = '';
    public $isBinPrepaid = '';
    public $isAddressShipForward = '';
    public $isBillShipCityMatch = '';
    public $isBillShipStateMatch = '';
    public $isBillShipCountryMatch = '';
    public $isBillShipPostalMatch = '';
    public $isIpBlacklist = '';
    public $isEmailBlacklist = '';
    public $isCreditCardBlacklist = '';
    public $isDeviceBlacklist = '';
    public $userOrderId = '';
    public $userOrderMemo = '';
    public $fraudlabsproScore = '';
    public $fraudlabsproDistribution = '';
    public $fraudlabsproStatus = '';
    public $fraudlabsproId = '';
    public $fraudlabsproVersion = '';
    public $fraudlabsproErrorCode = '';
    public $fraudlabsproMessage = '';
    public $fraudlabsproCredits = '';

    //Reset the variables
    public function reset(){
        $this->isCountryMatch = '';
        $this->isHighRiskCountry = '';
        $this->distanceInKm = 0;
        $this->distanceInMile = 0;
        $this->ipCountry = '';
        $this->ipRegion = '';
        $this->ipCity = '';
        $this->ipContinent = '';
        $this->ipLatitude = '';
        $this->ipLongitude = '';
        $this->ipTimezone = '';
        $this->ipElevation = '';
        $this->ipDomain = '';
        $this->ipMobileMnc = '';
        $this->ipMobileMcc = '';
        $this->ipMobileBrand = '';
        $this->ipNetspeed = '';
        $this->ipIspName = '';
        $this->ipUsageType = '';
        $this->isFreeEmail = '';
        $this->isNewDomainName = '';
        $this->isProxyIpAddress = '';
        $this->isBinFound = '';
        $this->isBinCountryMatch = '';
        $this->isBinNameMatch = '';
        $this->isBinPrepaid = '';
        $this->isAddressShipForward = '';
        $this->isBillShipCityMatch = '';
        $this->isBillShipStateMatch = '';
        $this->isBillShipCountryMatch = '';
        $this->isBillShipPostalMatch = '';
        $this->isIpBlacklist = '';
        $this->isEmailBlacklist = '';
        $this->isCreditCardBlacklist = '';
        $this->isDeviceBlacklist = '';
        $this->userOrderId = '';
        $this->userOrderMemo = '';
        $this->fraudlabsproScore = '';
        $this->fraudlabsproDistribution = '';
        $this->fraudlabsproStatus = '';
        $this->fraudlabsproId = '';
        $this->fraudlabsproVersion = '';
        $this->fraudlabsproErrorCode = '';
        $this->fraudlabsproMessage = '';
        $this->fraudlabsproCredits = '';
    }

    //Decode the json result returns from FraudLabs Pro screen order
    public function decodeJsonResult($result){
        if(!is_null($json = json_decode($result))){
            $this->isCountryMatch = $json->is_country_match;
            $this->isHighRiskCountry = $json->is_high_risk_country;
            $this->distanceInKm = $json->distance_in_km;
            $this->distanceInMile = $json->distance_in_mile;
            $this->ipCountry = $json->ip_country;
            $this->ipRegion = $json->ip_region;
            $this->ipCity = $json->ip_city;
            $this->ipContinent = $json->ip_continent;
            $this->ipLatitude = $json->ip_latitude;
            $this->ipLongitude = $json->ip_longitude;
            $this->ipTimezone = $json->ip_timezone;
            $this->ipElevation = $json->ip_elevation;
            $this->ipDomain = $json->ip_domain;
            $this->ipMobileMnc = $json->ip_mobile_mnc;
            $this->ipMobileMcc = $json->ip_mobile_mcc;
            $this->ipMobileBrand = $json->ip_mobile_brand;
            $this->ipNetspeed = $json->ip_netspeed;
            $this->ipIspName = $json->ip_isp_name;
            $this->ipUsageType = $json->ip_usage_type;
            $this->isFreeEmail = $json->is_free_email;
            $this->isNewDomainName = $json->is_new_domain_name;
            $this->isProxyIpAddress = $json->is_proxy_ip_address;
            $this->isBinFound = $json->is_bin_found;
            $this->isBinCountryMatch = $json->is_bin_country_match;
            $this->isBinNameMatch = $json->is_bin_name_match;
            $this->isBinPrepaid = $json->is_bin_prepaid;
            $this->isAddressShipForward = $json->is_address_ship_forward;
            $this->isBillShipCityMatch = $json->is_bill_ship_city_match;
            $this->isBillShipStateMatch = $json->is_bill_ship_state_match;
            $this->isBillShipCountryMatch = $json->is_bill_ship_country_match;
            $this->isBillShipPostalMatch = $json->is_bill_ship_postal_match;
            $this->isIpBlacklist = $json->is_ip_blacklist;
            $this->isEmailBlacklist = $json->is_email_blacklist;
            $this->isCreditCardBlacklist = $json->is_credit_card_blacklist;
            $this->isDeviceBlacklist = $json->is_device_blacklist;
            $this->userOrderId = $json->user_order_id;
            $this->userOrderMemo = $json->user_order_memo;
            $this->fraudlabsproScore = $json->fraudlabspro_score;
            $this->fraudlabsproDistribution = $json->fraudlabspro_distribution;
            $this->fraudlabsproStatus = $json->fraudlabspro_status;
            $this->fraudlabsproId = $json->fraudlabspro_id;
            $this->fraudlabsproVersion = $json->fraudlabspro_version;
            $this->fraudlabsproErrorCode = $json->fraudlabspro_error_code;
            $this->fraudlabsproMessage = $json->fraudlabspro_message;
            $this->fraudlabsproCredits = $json->fraudlabspro_credits;
        }
    }
}


/* End of file Flp_response.php */ 