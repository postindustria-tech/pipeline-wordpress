<?php

require(__DIR__ . "/TestFlowElement.php");

use fiftyone\pipeline\core\PipelineBuilder;

// Test Pipeline builder for use with PHP unit tests
class TestPipeline
{
    public $pipeline;

    public $flowElement;

    public function __construct()
    {
        $this->flowElement = new TestFlowElement();
        $this->pipeline = (new PipelineBuilder())
            ->add($this->flowElement)
            ->build();
    }
}
