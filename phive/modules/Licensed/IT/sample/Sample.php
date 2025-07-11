<?php
//load_test.php is important script for security reason, checking is local or stage env,
//if yes to executed or die() otherwise
require_once __DIR__ . '/load_test.php';

//TYPES
require_once __DIR__ . '/../lib/Types/DateType.php';
require_once __DIR__ . '/../lib/Types/TimeType.php';
require_once __DIR__ . '/../lib/Types/DateTimeType.php';
require_once __DIR__ . '/../lib/Types/BirthDataType.php';
require_once __DIR__ . '/../lib/Types/ResidenceType.php';
require_once __DIR__ . '/../lib/Types/DocumentType.php';
require_once __DIR__ . '/../lib/Types/NaturalPersonType.php';
require_once __DIR__ . '/../lib/Types/LegalEntityType.php';
require_once __DIR__ . '/../lib/Types/BonusDetailType.php';
require_once __DIR__ . '/../lib/Types/TransactionHandlingDetailType.php';
require_once __DIR__ . '/../lib/Types/ServiceOperationsDetailType.php';
require_once __DIR__ . '/../lib/Types/NaturalPersonSimplifiedType.php';
require_once __DIR__ . '/../lib/Types/LimitType.php';



//TABLES
require_once __DIR__ . '/../lib/Tables/AccountHolderDocumentType.php';
require_once __DIR__ . '/../lib/Tables/GenderType.php';
require_once __DIR__ . '/../lib/Tables/GamingFamily.php';
require_once __DIR__ . '/../lib/Tables/GamingTypeLotto.php';
require_once __DIR__ . '/../lib/Tables/TransactionReasonCode.php';
require_once __DIR__ . '/../lib/Tables/ServiceOperationReasonCode.php';
require_once __DIR__ . '/../lib/Tables/GamblingAccountLimitType.php';



//DateType
$data_type = new DateType("29", "11", "2019");
echo "DateType\n{$data_type->toString()}\n\n";


//TimeType
$ora_type = new TimeType("10", "50", "40");
echo "TimeType\n{$ora_type->toString()}\n\n";


//DataTimeType
$data_ora_type = new DateTimeType($data_type, $ora_type);
echo "DataTimeType\n{$data_ora_type->toString()}\n\n";


//DateOfBirthType
$birth_date_type = new BirthDataType();
$birth_date_type->setDataType(new DateType("28", "11", "1991"));
$birth_date_type->setBirthplace("Catanzaro");
$birth_date_type->setBirthplaceProvinceAcronym("CZ");
echo "DateOfBirthType\n{$birth_date_type->toString()}\n\n";


//ResidenceType
$residence_type = new ResidenceType();
$residence_type->setResidentialAddress("Via Piana timpone, 21");
$residence_type->setMunicipalityOfResidence("Catanzaro");
$residence_type->setResidentialProvinceAcronym("CZ");
$residence_type->setResidentialPostCode("88100");
echo "ResidenceType\n{$residence_type->toString()}\n\n";


//DocumentType
$document_type = new DocumentType();
$account_holder_document_type = new AccountHolderDocumentType();
$document_type->setTypology(AccountHolderDocumentType::$driver_license);
$document_type->setDateOfIssue(new DateType("22", "08", "2019"));
$document_type->setDocumentNumber("CA91016EW");
$document_type->setIssuingAuthority("Comune");
$document_type->setWhereIssued("Catanzaro");
echo "DocumentType\n{$document_type->toString()}\n\n";


//NaturalPersonType
$natural_person = new NaturalPersonType();
$natural_person->setTaxCode("PSSFNC91S28C352E");
$natural_person->setSurname("Passanti");
$natural_person->setName("Name");
$natural_person->setGender(GenderType::$male);
$natural_person->setDateOfBirth($birth_date_type);
$natural_person->setResidence($residence_type);
$natural_person->setDocument($document_type);
$natural_person->setEmail("passantifrancesco@gmail.com");
$natural_person->setPseudonym("francesco.pasasnti");
echo "NaturalPersonType\n{$natural_person->toString()}\n\n";


//LegalEntityType
$legal_entity = new LegalEntityType();
$legal_entity->setVatNumber("IT123456789");
$legal_entity->setCompanyName("Video Slots");
$company_headquarter = new ResidenceType();
$company_headquarter->setResidentialAddress("Via delle betulle, 45");
$company_headquarter->setMunicipalityOfResidence("Milano");
$company_headquarter->setResidentialProvinceAcronym("MI");
$company_headquarter->setResidentialPostCode("12345");
$legal_entity->setCompanyHeadquarter($company_headquarter);
$legal_entity->setEmail("francesco.passanti@videoslots.com");
$legal_entity->setPseudonym("videoslots");
echo "LegalEntityType\n{$legal_entity->toString()}\n\n";

//BonusDetailType
$bouns_detail = new BonusDetailType(GamingFamily::$lotto, GamingTypeLotto::$milion_day, 100);
echo "BonusDetailType\n{$bouns_detail->toString()}\n\n";

//TransactionHandlingDetailType
$transaction_handling_detail_type = new TransactionHandlingDetailType(TransactionReasonCode::$withdrawal, 10, 2000);
echo "TransactionHandlingDetailType\n{$transaction_handling_detail_type->toString()}\n\n";

//ServiceOperationsDetailType
$service_operations_detail_type = new ServiceOperationsDetailType(ServiceOperationReasonCode::$dormant_account, 10);
echo "TransactionHandlingDetailType\n{$service_operations_detail_type->toString()}\n\n";

//NaturalPersonSimplifiedType
$natural_person_simp = new NaturalPersonSimplifiedType();
$natural_person_simp->setTaxCode("PSSFNC91S28C352E");
$natural_person_simp->setSurname("Passanti");
$natural_person_simp->setName("Name");
$natural_person_simp->setGender(GenderType::$male);
$natural_person_simp->setDateOfBirth($birth_date_type);
$natural_person_simp->setResidentialProvinceAcronym("CZ");
echo "NaturalPersonSimplifiedType\n{$natural_person_simp->toString()}\n\n";

//LimitType
$limit = new LimitType(GamblingAccountLimitType::$daily, 200);
echo "LimitType\n{$limit->toString()}\n\n";
