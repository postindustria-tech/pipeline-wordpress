<?php

use fiftyone\pipeline\core\FlowElement;
use fiftyone\pipeline\core\AspectPropertyValue;
use fiftyone\pipeline\core\ElementDataDictionary;
use fiftyone\pipeline\core\BasicListEvidenceKeyFilter;

class TestFlowElement extends FlowElement
{
    public $dataKey;
    public $properties;

    public function __construct()
    {
        // List of Pipelines the FlowElement has been added to
        $this->pipelines = [];
        $this->dataKey = "testElement";
        $this->properties = array(
            "availableProperty" => array(
                "name" => "AvailableProperty",
                "type" => "string",
                "category" => "testCategory"
            ),
            "noValueProperty" => array(
                "name" => "NoValueProperty",
                "type" => "string",
                "category" => "testCategory"
            ),
            "testProperty" => array(
                "name" => "TestProperty",
                "type" => "int",
                "category" => "otherCategory"
            )
        );
    }

    public function processInternal($flowData)
    {
		
        $contents = [];

        $contents["javascript"] = "console.log('hello world')";
        $contents["normal"] = true;

        $contents["availableProperty"] = new AspectPropertyValue(null, "Value");
        $contents["noValueProperty"] = new AspectPropertyValue("Property is not available.", null);

        $data = new ElementDataDictionary($this, $contents);
		
        $flowData->setElementData($data);
    }
    
    public function getEvidenceKeyFilter()
    {
        return new BasicListEvidenceKeyFilter(["header.user-agent"]);
    }

    public function filterEvidenceKey($key)
    {
        return true;
    }
}
