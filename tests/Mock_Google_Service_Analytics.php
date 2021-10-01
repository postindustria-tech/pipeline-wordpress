<?php
/*
    This Original Work is copyright of 51 Degrees Mobile Experts Limited.
    Copyright 2019 51 Degrees Mobile Experts Limited, 5 Charlotte Close,
    Caversham, Reading, Berkshire, United Kingdom RG4 7BY.

    This Original Work is licensed under the European Union Public Licence (EUPL) 
    v.1.2 and is subject to its terms as set out below.

    If a copy of the EUPL was not distributed with this file, You can obtain
    one at https://opensource.org/licenses/EUPL-1.2.

    The 'Compatible Licences' set out in the Appendix to the EUPL (as may be
    amended by the European Commission) shall be deemed incompatible for
    the purposes of the Work and the provisions of the compatibility
    clause in Article 5 of the EUPL shall not apply.
*/

require(__DIR__ . "/../lib/vendor/autoload.php");

// Mock Google Service Analytics for use with PHP unit tests
class Mock_Google_Service_Analytics {

    public $ga_service;

    public function __construct() {
        $this->ga_service = Mockery::mock('Google_Service_Analytics');     
    }
        
    public function mock_management_accountSummaries(){

        $managementAccountSummeries = Mockery::mock('ManagementAccountSummaries');                       
        $accounts = Mockery::mock('Accounts');
        $accountSummary1 = Mockery::mock('AccountSummary');  
        $webPropertySummary1 = Mockery::mock('WebPropertySummary');
        $accountSummary2 = Mockery::mock('AccountSummary'); 
        $webPropertySummary2 = Mockery::mock('WebPropertySummary');

        $this->ga_service->management_accountSummaries = $managementAccountSummeries;
        $managementAccountSummeries->shouldReceive('listManagementAccountSummaries')->andReturn($accounts);
        $accounts->shouldReceive('getItems')->andReturn(array($accountSummary1, $accountSummary2));        
        $accountSummary1->shouldReceive('getId')->andReturn("123456789");
        $accountSummary1->shouldReceive('getWebProperties')->andReturn(array($webPropertySummary1, $webPropertySummary1));
        $webPropertySummary1->shouldReceive('getId')->andReturnValues(["test-123456789-1", "test-123456789-2"]);
        $accountSummary2->shouldReceive('getId')->andReturn("333333333");
        $accountSummary1->shouldReceive('getWebProperties')->andReturn(array($webPropertySummary2));
        $webPropertySummary2->shouldReceive('getId')->andReturn("test-333333333-1");  
    }
    

    public function mock_management_customDimensions(){

        $managementCustomDimensions = Mockery::mock('ManagementCustomDimensions');                       
        $customDimensions = Mockery::mock('CustomDimensions');
        $customDimension1 = Mockery::mock('CustomDimension');
        $customDimension2 = Mockery::mock('CustomDimension');
        
        $this->ga_service->management_customDimensions = $managementCustomDimensions;
        $managementCustomDimensions->shouldReceive('insert')->andReturn(1);
        $managementCustomDimensions->shouldReceive('listManagementCustomDimensions')->andReturn($customDimensions);
        $customDimensions->shouldReceive('getItems')->andReturn(array($customDimension1, $customDimension2));        
        $customDimension1->shouldReceive('getName')->andReturn("51D.testelement.testproperty1");
        $customDimension1->shouldReceive('getIndex')->andReturn(1);
        $customDimension2->shouldReceive('getName')->andReturn("51D.testelement.testproperty2");
        $customDimension2->shouldReceive('getIndex')->andReturn(2); 
    }    
}
