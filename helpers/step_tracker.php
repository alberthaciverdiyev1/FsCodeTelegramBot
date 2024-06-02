<?php
include './configs/urls.php';
class Step
{

    public function currentStep()
    {
        return file_get_contents(STEP_PATH);
    }
    public function updateStep($step): void
    {
        file_put_contents(STEP_PATH, $step);
    }
}