<?php

namespace fiftyone\pipeline\wordpress;

use fiftyone\pipeline\core\FlowElement;
use fiftyone\pipeline\core\ElementDataDictionary;
use fiftyone\pipeline\core\BasicListEvidenceKeyFilter;

class TestFlowElement extends FlowElement
{
    public $dataKey = "testElement";
    public function processInternal($flowData)
    {
		
        $contents = [];

        $contents["javascript"] = "console.log('hello world')";
        $contents["normal"] = true;

        $contents["testProperty"] = new AspectPropertyValue(null, "Value");

        $data = new ElementDataDictionary($this, $contents);
		
        $flowData->setElementData($data);
    }

    public $properties = array(
        "testProperty" => array(
            "type" => "string",
			"category" => "testCategory"
        )
    );
    
    public function getEvidenceKeyFilter()
    {
        return new BasicListEvidenceKeyFilter(["header.user-agent"]);
    }
}
