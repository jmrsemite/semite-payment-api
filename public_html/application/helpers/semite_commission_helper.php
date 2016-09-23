<?php
/*
 * Formula for acquirer
 *
 * TRX Amount   : 100
 * BRCommission      : %5
 *
 * $commission = (TRX Amount * Commission) / 100 = 5
 */

function get_br_commission_amount($trx_amount,$commission){

    return $commission = ($trx_amount * $commission) / 100;
}

/*
 * Formula for processor
 *
 * TRX Amount   : 100
 * SRCommission : BRCommission - SRCommission
 *
 * $commission = (TRX Amount * (SRCommission) / 100 = 1
 */

function get_sr_commission_amount($trx_amount,$br_commssion,$commission){

    return $commission = ($trx_amount * ($commission - $br_commssion)) / 100;
}

/*
 * Formula for rollbackAmount
 *
 * TRX Amount   : 100
 * RBAmount : ((TRX Amount - (acquirerCommission + processorCommission)) * 10 ) / 100
 *
 * $commission = (TRX Amount * (SRCommission) / 100 = 1
 */

function get_rollback_amount($trx_amount,$sr_commission,$br_commssion,$rollback_percentage){

    $total_commission = get_br_commission_amount($trx_amount,$br_commssion) + get_sr_commission_amount($trx_amount,$br_commssion,$sr_commission);

    return $rollback_amount = (($trx_amount - $total_commission) * $rollback_percentage) / 100;
}