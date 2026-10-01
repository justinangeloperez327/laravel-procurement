<?php

namespace App\Domain\Procurement\Enums;

enum ProcurementActivityType: string
{
    case PreProcurementConference = 'pre_procurement_conference';
    case AdvertisementPosting = 'advertisement_posting';
    case PreBidConference = 'pre_bid_conference';
    case Clarification = 'clarification';
    case SupplementalBulletin = 'supplemental_bulletin';
    case BidSubmissionDeadline = 'bid_submission_deadline';
    case BidOpening = 'bid_opening';
    case BidEvaluation = 'bid_evaluation';
    case PostQualification = 'post_qualification';
    case BacDeliberation = 'bac_deliberation';
    case BacResolution = 'bac_resolution';
    case HopeApproval = 'hope_approval';
    case Award = 'award';
    case ContractSigning = 'contract_signing';
    case NoticeToProceed = 'notice_to_proceed';
}
